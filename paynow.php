<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @author mElements S.A.
 * @copyright mElements S.A.
 * @license   MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__) . '/vendor/autoload.php');
include_once(dirname(__FILE__) . '/classes/PaynowFrontController.php');
include_once(dirname(__FILE__) . '/classes/PaynowLogger.php');
include_once(dirname(__FILE__) . '/classes/PaynowHelper.php');
include_once(dirname(__FILE__) . '/classes/PaynowNotificationRetryProcessing.php');
include_once(dirname(__FILE__) . '/classes/PaynowNotificationStopProcessing.php');
include_once(dirname(__FILE__) . '/classes/PaynowConfigurationHelper.php');
include_once(dirname(__FILE__) . '/classes/PaynowPaymentMethodsHelper.php');
include_once(dirname(__FILE__) . '/classes/PaynowPaymentAuthorizeException.php');
include_once(dirname(__FILE__) . '/classes/PaynowPaymentOptions.php');
include_once(dirname(__FILE__) . '/classes/PaynowRefundProcessor.php');
include_once(dirname(__FILE__) . '/classes/PaynowGDPRHelper.php');
include_once(dirname(__FILE__) . '/classes/PaynowLinkHelper.php');
include_once(dirname(__FILE__) . '/classes/PaynowAdminFormHelper.php');
include_once(dirname(__FILE__) . '/classes/PaynowOrderCreateProcessor.php');
include_once(dirname(__FILE__) . '/classes/PaynowOrderStateProcessor.php');
include_once(dirname(__FILE__) . '/models/PaynowPaymentData.php');
include_once(dirname(__FILE__) . '/classes/PaynowFrontController.php');
include_once(dirname(__FILE__) . '/classes/PaynowPaymentProcessor.php');
include_once(dirname(__FILE__) . '/classes/PaynowPaymentDataBuilder.php');
include_once(dirname(__FILE__) . '/classes/PaynowGithubClient.php');

class Paynow extends PaymentModule
{
    protected $html = '';
    protected $postErrors = [];
    private $call_to_action_text = '';

    public function __construct()
    {
        $this->name = 'paynow';
        $this->tab = 'payments_gateways';
        $this->version = '1.6.21';
        $this->ps_versions_compliancy = ['min' => '1.6.0', 'max' => _PS_VERSION_];
        $this->author = 'mElements S.A.';
        $this->is_eu_compatible = 1;
        $this->controllers = ['payment', 'return'];
        $this->bootstrap = true;
        $this->module_key = '86f0413df24b36cc82b831f755669dc7';

        $this->currencies = true;

        parent::__construct();

        $this->displayName = $this->l('Pay by paynow.pl');
        $this->description = $this->l('Accepts payments by paynow.pl');
        $this->confirm_uninstall = $this->l('Are you sure you want to uninstall? You will lose all your settings!');
        $this->call_to_action_text = $this->l('BLIK, bank transfers and card payments');

        if (!$this->isConfigured()) {
            $this->warning = $this->l('API Keys must be configured before using this module.');
        } else {
            $this->getPaynowClient();
        }

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->createDbTables() ||
            !$this->createModuleSettings() ||
            !$this->registerHooks()) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        if (!$this->unregisterHooks() || !$this->deleteModuleSettings() || !parent::uninstall()) {
            return false;
        }
        return true;
    }

    private function createDbTables()
    {
        return Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'paynow_payments` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `id_order` INT(10) UNSIGNED NOT NULL,
            `id_cart` INT(10) UNSIGNED NOT NULL,
            `id_payment` varchar(30) NOT NULL,
            `order_reference` varchar(9)  NOT NULL,
            `external_id` varchar(50)  NOT NULL,
            `status` varchar(64) NOT NULL,
            `total` DECIMAL(20,6) NOT NULL DEFAULT \'0.000000\',
            `locked` TINYINT(1) NOT NULL DEFAULT 0,
            `active` tinyint(1) NOT NULL DEFAULT 0,
            `counter` tinyint(1) NOT NULL DEFAULT 0,
            `sent_at` datetime DEFAULT NULL,
            `created_at` datetime,
            `modified_at` datetime,
            UNIQUE (`id_payment`, `status`),
            INDEX `index_order_cart_payment_reference` (`id_order`, `id_cart`, `id_payment`, `order_reference`)
        )');
    }

    private function registerHooks()
    {
        $registerStatus = $this->registerHook('header') &&
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('actionOrderSlipAdd') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayAdminAfterHeader') &&
            $this->registerHook('actionAdminControllerSetMedia');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $registerStatus &= $this->registerHook('payment') &&
                $this->registerHook('displayPaymentEU');
            $this->updatePosition(Hook::getIdByName('displayPayment'), false, 1);
            $this->updatePosition(Hook::getIdByName('displayPaymentEU'), false, 1);
        } else {
            $registerStatus &= $this->registerHook('paymentOptions');
            $this->updatePosition(Hook::getIdByName('paymentOptions'), false, 1);
        }

        return $registerStatus;
    }

    private function unregisterHooks()
    {
        $registerStatus = $this->unregisterHook('header') &&
            $this->unregisterHook('displayOrderDetail') &&
            $this->unregisterHook('actionOrderStatusPostUpdate') &&
            $this->unregisterHook('actionOrderSlipAdd') &&
            $this->unregisterHook('displayAdminOrder') &&
            $this->unregisterHook('displayAdminAfterHeader') &&
            $this->unregisterHook('actionAdminControllerSetMedia');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $registerStatus &= $this->unregisterHook('displayPaymentEU') &&
                $this->unregisterHook('payment');
        } else {
            $registerStatus &= $this->unregisterHook('paymentOptions');
        }

        return $registerStatus;
    }

    private function createModuleSettings()
    {
        return Configuration::updateValue('PAYNOW_DEBUG_LOGS_ENABLED', 0) &&
            Configuration::updateValue('PAYNOW_USE_CLASSIC_RETURN_URL', 0) &&
            Configuration::updateValue('PAYNOW_REFUNDS_ENABLED', 1) &&
            Configuration::updateValue('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED', 0) &&
            Configuration::updateValue('PAYNOW_REFUNDS_ON_STATUS', Configuration::get('PS_OS_REFUND')) &&
            Configuration::updateValue('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS', 0) &&
            Configuration::updateValue('PAYNOW_SEPARATE_PAYMENT_METHODS', 1) &&
            Configuration::updateValue('PAYNOW_PROD_API_KEY', '') &&
            Configuration::updateValue('PAYNOW_PROD_API_SIGNATURE_KEY', '') &&
            Configuration::updateValue('PAYNOW_SANDBOX_ENABLED', 0) &&
            Configuration::updateValue('PAYNOW_SANDBOX_API_KEY', '') &&
            Configuration::updateValue('PAYNOW_SANDBOX_API_SIGNATURE_KEY', '') &&
            Configuration::updateValue('PAYNOW_ORDER_INITIAL_STATE', $this->createOrderInitialState()) &&
            Configuration::updateValue('PAYNOW_ORDER_CONFIRMED_STATE', 2) &&
            Configuration::updateValue('PAYNOW_ORDER_REJECTED_STATE', Configuration::get('PAYNOW_ORDER_INITIAL_STATE')) &&
            Configuration::updateValue('PAYNOW_ORDER_ERROR_STATE', 8) &&
            Configuration::updateValue('PAYNOW_SEND_ORDER_ITEMS', 0) &&
            Configuration::updateValue('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED', 0) &&
            Configuration::updateValue('PAYNOW_PAYMENT_VALIDITY_TIME', 86400) &&
            Configuration::updateValue('PAYNOW_ORDER_ABANDONED_STATE', Configuration::get('PAYNOW_ORDER_INITIAL_STATE')) &&
            Configuration::updateValue('PAYNOW_ORDER_EXPIRED_STATE', Configuration::get('PAYNOW_ORDER_INITIAL_STATE')) &&
            Configuration::updateValue('PAYNOW_CREATE_ORDER_STATE', 1) &&
            Configuration::updateValue('PAYNOW_RETRY_PAYMENT_BUTTON_ENABLED', 1) &&
            Configuration::updateValue('PAYNOW_RETRY_BUTTON_ORDER_STATE', join(',', [8,20,12])) &&
            Configuration::updateValue('PAYNOW_BLIK_AUTOFOCUS_ENABLED', 1);
    }

    private function deleteModuleSettings()
    {
        return Configuration::deleteByName('PAYNOW_DEBUG_LOGS_ENABLED') &&
            Configuration::deleteByName('PAYNOW_USE_CLASSIC_RETURN_URL') &&
            Configuration::deleteByName('PAYNOW_REFUNDS_ENABLED') &&
            Configuration::deleteByName('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED') &&
            Configuration::deleteByName('PAYNOW_REFUNDS_ON_STATUS') &&
            Configuration::deleteByName('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS') &&
            Configuration::deleteByName('PAYNOW_SEPARATE_PAYMENT_METHODS') &&
            Configuration::deleteByName('PAYNOW_PROD_API_KEY') &&
            Configuration::deleteByName('PAYNOW_PROD_API_SIGNATURE_KEY') &&
            Configuration::deleteByName('PAYNOW_SANDBOX_ENABLED') &&
            Configuration::deleteByName('PAYNOW_SANDBOX_API_KEY') &&
            Configuration::deleteByName('PAYNOW_SANDBOX_API_SIGNATURE_KEY') &&
            Configuration::deleteByName('PAYNOW_ORDER_CONFIRMED_STATE') &&
            Configuration::deleteByName('PAYNOW_ORDER_REJECTED_STATE') &&
            Configuration::deleteByName('PAYNOW_ORDER_ERROR_STATE') &&
            Configuration::deleteByName('PAYNOW_SEND_ORDER_ITEMS') &&
            Configuration::deleteByName('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED') &&
            Configuration::deleteByName('PAYNOW_PAYMENT_VALIDITY_TIME') &&
            Configuration::deleteByName('PAYNOW_ORDER_ABANDONED_STATE') &&
            Configuration::deleteByName('PAYNOW_ORDER_EXPIRED_STATE') &&
            Configuration::deleteByName('PAYNOW_CREATE_ORDER_STATE') &&
            Configuration::deleteByName('PAYNOW_RETRY_PAYMENT_BUTTON_ENABLED') &&
            Configuration::deleteByName('PAYNOW_RETRY_BUTTON_ORDER_STATE') &&
            Configuration::deleteByName('PAYNOW_BLIK_AUTOFOCUS_ENABLED');
    }

    public function createOrderInitialState()
    {
        $state_name = 'PAYNOW_ORDER_INITIAL_STATE';
        if (Configuration::get($state_name) ||
            Validate::isLoadedObject(new OrderState(Configuration::get($state_name)))) {
            return (int)Configuration::get($state_name);
        }

        $order_state = new OrderState();
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            if (Tools::strtolower($language['iso_code']) == 'pl') {
                $order_state->name[$language['id_lang']] = 'Oczekuje na płatność';
            } else {
                $order_state->name[$language['id_lang']] = "Awaiting for payment";
            }
        }

        $order_state->send_email = false;
        $order_state->invoice = false;
        $order_state->unremovable = true;
        $order_state->hidden = false;
        $order_state->delivery = false;
        $order_state->logable = false;
        $order_state->color = '#f39200';
        $order_state->module_name = $this->name;

        if ($order_state->add()) {
            $source = _PS_MODULE_DIR_ . $this->name . '/views/img/os-logo.gif';
            $destination = _PS_ROOT_DIR_ . '/img/os/' . $order_state->id . '.gif';
            copy($source, $destination);
        }

        return $order_state->id;
    }

    public function getCallToActionText(): string
    {
        return $this->call_to_action_text;
    }

    /**
     * @return \Paynow\Client
     */
    public function getPaynowClient()
    {
        return new \Paynow\Client(
            $this->getApiKey(),
            $this->getSignatureKey(),
            $this->isSandboxEnabled() ? \Paynow\Environment::SANDBOX : \Paynow\Environment::PRODUCTION,
            'Prestashop-' . _PS_VERSION_ . '/Plugin-' . $this->version
        );
    }

    public function getApiKey()
    {
        return $this->isSandboxEnabled() ?
            Configuration::get('PAYNOW_SANDBOX_API_KEY') :
            Configuration::get('PAYNOW_PROD_API_KEY');
    }

    public function getSignatureKey()
    {
        return $this->isSandboxEnabled() ?
            Configuration::get('PAYNOW_SANDBOX_API_SIGNATURE_KEY') :
            Configuration::get('PAYNOW_PROD_API_SIGNATURE_KEY');
    }

    public function isSandboxEnabled(): bool
    {
        return (int)Configuration::get('PAYNOW_SANDBOX_ENABLED') === 1;
    }

    private function isActive(): bool
    {
        return $this->active && $this->isConfigured();
    }

    private function isConfigured(): bool
    {
        return !empty($this->getApiKey()) && !empty($this->getSignatureKey());
    }

    private function showRetryButton(array $params): bool
    {
        if ((int)Configuration::get('PAYNOW_RETRY_PAYMENT_BUTTON_ENABLED') === 0) {
            return false;
        }

        /** @var \PrestaShop\PrestaShop\Adapter\Entity\Order $order */
        $order = $params['order'] ?? null;
        if ($order === null) {
            return false;
        }
        $paynowPaymentData = PaynowPaymentData::findLastByOrderId($order->id);
        if ($paynowPaymentData === false) {
            return false;
        }

        $retryBtnOrderStates = explode(',', Configuration::get('PAYNOW_RETRY_BUTTON_ORDER_STATE'));

        return in_array($order->getCurrentState(), $retryBtnOrderStates);
    }

    public function checkCurrency($cart): bool
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return $currency_order->id == $currencies_module->id;
    }

    public function hookHeader()
    {
        ContextCore::getContext()->controller->addCSS(($this->_path) . 'views/css/front.css', 'all');
        ContextCore::getContext()->controller->addJs(($this->_path) . 'views/js/front.js', 'all');
    }

    public function getPaymentMethodTitle($payment_method_type): string
    {
        switch ($payment_method_type) {
            default:
                return '';
            case \Paynow\Model\PaymentMethods\Type::BLIK:
                return $this->l('Pay by Blik');
            case \Paynow\Model\PaymentMethods\Type::CARD:
                return $this->l('Pay by card');
            case \Paynow\Model\PaymentMethods\Type::PBL:
                return $this->l('Pay by online transfer');
            case \Paynow\Model\PaymentMethods\Type::GOOGLE_PAY:
                return $this->l('Pay by Google Pay');
        }
    }

    /**
     * @return \Paynow\Response\PaymentMethods\PaymentMethods|null
     * @throws Exception
     */
    private function getPaymentMethods()
    {
        $total = number_format($this->context->cart->getOrderTotal() * 100, 0, '', '');
        $currency = new Currency($this->context->cart->id_currency);
        return (new PaynowPaymentMethodsHelper($this->getPaynowClient()))->getAvailable($currency->iso_code, $total);
    }

    private function getGDPRNotices(): array
    {
        $locale  = $this->context->language->locale ?? $this->context->language->language_code;
        return (new PaynowGDPRHelper($this->getPaynowClient()))->getNotices($locale);
    }

    /** Returns is possible to show payment option
     *
     * @param $params
     *
     * @return bool
     */
    private function arePaymentOptionsEnabled($params): bool
    {
        return $this->isActive() && $this->checkCurrency($params['cart']) && $this->context->cart->getOrderTotal() >= 1.00;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->arePaymentOptionsEnabled($params)) {
            return;
        }

        $payment_options = new PaynowPaymentOptions(
            $this->context,
            $this,
            $this->getPaymentMethods(),
            $this->getGDPRNotices()
        );

        return $payment_options->generate();
    }

    public function hookPayment($params)
    {
        if (!$this->arePaymentOptionsEnabled($params)) {
            return [];
        }

        $gdpr_notices = $this->getGDPRNotices();
        $this->context->smarty->assign([
            'cta_text' => $this->getCallToActionText(),
            'logo' => $this->getLogo(),
            'paynow_url' => PaynowLinkHelper::getPaymentUrl(),
            'data_processing_notices' => $gdpr_notices ?? null
        ]);

        $payment_options = [];
        if ((int)Configuration::get('PAYNOW_SEPARATE_PAYMENT_METHODS') === 1) {
            $payment_methods = $this->getPaymentMethods();
            if (!empty($payment_methods)) {
                $list = [];
                foreach ($payment_methods->getAll() as $payment_method) {
                    if (!isset($list[$payment_method->getType()])) {
                        if (Paynow\Model\PaymentMethods\Type::PBL == $payment_method->getType()) {
                            array_push($payment_options, [
                                'name' => $this->getPaymentMethodTitle($payment_method->getType()),
                                'image' => $this->getLogo(),
                                'type' => $payment_method->getType(),
                                'authorization' => $payment_method->getAuthorizationType(),
                                'pbls' => $payment_methods->getOnlyPbls()
                            ]);
                        } else {
                            if (Paynow\Model\PaymentMethods\Type::BLIK == $payment_method->getType()) {
                                $this->context->smarty->assign([
                                    'action_blik' => Context::getContext()->link->getModuleLink(
                                        'paynow',
                                        'chargeBlik',
                                        [
                                            'paymentMethodId' => $payment_method->getId()
                                        ]
                                    ),
                                    'action_token' => Tools::encrypt($this->context->customer->secure_key),
                                    'error_message' => $this->getTranslationsArray()['An error occurred during the payment process'],
                                    'terms_message' => $this->getTranslationsArray()['You have to accept terms and conditions'],
                                    'blik_autofocus' => Configuration::get('PAYNOW_BLIK_AUTOFOCUS_ENABLED') === '0' ? '0' : '1',
                                ]);
                            }
                            array_push($payment_options, [
                                'name' => $this->getPaymentMethodTitle($payment_method->getType()),
                                'image' => $payment_method->getImage(),
                                'id' => $payment_method->getId(),
                                'enabled' => $payment_method->isEnabled(),
                                'type' => $payment_method->getType(),
                                'authorization' => $payment_method->getAuthorizationType(),
                            ]);
                        }
                        $list[$payment_method->getType()] = $payment_method->getId();
                    }
                }
                $this->context->smarty->assign([
                    'payment_options' => $payment_options
                ]);
            }
        }

        return $this->display(__FILE__, '/views/templates/hook/payment.tpl');
    }

    public function hookDisplayPaymentEU($params)
    {
        if (!$this->arePaymentOptionsEnabled($params)) {
            return [];
        }

        return [
            'cta_text' => $this->getCallToActionText(),
            'logo' => $this->getLogo(),
            'action' => PaynowLinkHelper::getPaymentUrl()
        ];
    }

    public function hookDisplayOrderDetail($params)
    {
        if (!$this->isActive() || !$this->showRetryButton($params)) {
            return;
        }

        $id_order = (int)$params['order']->id;

        $order = new Order($id_order);

        if (!$this->canOrderPaymentBeRetried($order)) {
            return;
        }

        $this->context->smarty->assign([
            'paynow_url' => PaynowLinkHelper::getPaymentUrl([
                'id_order' => $id_order,
                'order_reference' => $params['order']->reference
            ])
        ]);
        return $this->display(__FILE__, '/views/templates/hook/order_details.tpl');
    }

    /**
     * Handle add order slip to make a refund
     *
     * @param $params
     */
    public function hookActionOrderSlipAdd($params)
    {
        if ((int)Configuration::get('PAYNOW_REFUNDS_ENABLED') === 1 && Tools::isSubmit('makeRefundViaPaynow') &&
            $this->name == $params['order']->module) {
            (new PaynowRefundProcessor($this->getPaynowClient(), $this->displayName))
                ->processFromOrderSlip($params['order']);
        }
    }

    /**
     * Handle status change to make a refund
     *
     * @param $params
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if ((int)Configuration::get('PAYNOW_REFUNDS_ENABLED') === 1 &&
            (int)Configuration::get('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED') === 1 &&
            $this->context->controller instanceof AdminController) {
            $order = new Order($params['id_order']);
            $newOrderStatus = $params['newOrderStatus'];

            if ((int)Configuration::get('PAYNOW_REFUNDS_ON_STATUS') === $newOrderStatus->id) {
                (new PaynowRefundProcessor($this->getPaynowClient(), $this->displayName))
                    ->processFromOrderStatusChange($order);
            }
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        if (!(int)Configuration::get('PAYNOW_REFUNDS_ENABLED') === 1) {
            return;
        }

        $order = new Order($params['id_order']);
        if ($order->module !== $this->name) {
            return;
        }

        $this->context->smarty->assign('makePaynowRefundCheckboxLabel', $this->l('Make a refund via paynow.pl'));
        return $this->fetchTemplate('/views/templates/hook/admin_order_top.tpl');
    }

    public function hookDisplayAdminAfterHeader()
    {
        $file_name = 'paynow-' . date('Y-m-d');
        $file_path = dirname(__FILE__) . '/log/' . $file_name . '-' . Tools::encrypt($file_name) . '.log';

        if (Tools::getValue('show_paynow_logs')) {
            echo '<pre>';
            echo file_get_contents($file_path);
            echo '</pre>';
        }

        $client = new PaynowGithubClient();
        $latest_release = $client->latest('pay-now', 'paynow-prestashop');
        if ($latest_release && isset($latest_release->tag_name) &&
            version_compare($this->version, $latest_release->tag_name, '<')) {
            $this->context->smarty->assign([
                'download_url' => $latest_release->assets[0]->browser_download_url,
                'version_name' => $latest_release->name,
                'changelog_url' => $latest_release->html_url
            ]);
            return $this->fetchTemplate('/views/templates/admin/_partials/upgrade.tpl');
        }

        return null;
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        if (Tools::getValue("configure") && Tools::getValue("configure") == "paynow") {
            ContextCore::getContext()->controller->addJquery();
            ContextCore::getContext()->controller->addJS(($this->_path) . '/views/js/admin.js', 'all');
        }
    }

    public function canOrderPaymentBeRetried($order): bool
    {
        try {
            $last_payment_data = PaynowPaymentData::findLastByOrderId($order->id);

            return empty($last_payment_data) ||
                $last_payment_data->status !== \Paynow\Model\Payment\Status::STATUS_CONFIRMED ||
                in_array(
                    (int)$order->current_state,
                    [
                        (int)Configuration::get('PAYNOW_ORDER_ERROR_STATE'),
                        (int)Configuration::get('PAYNOW_ORDER_REJECTED_STATE')
                    ]
                );
        } catch (PrestaShopException $exception) {
            PaynowLogger::error($exception->getMessage());
        }

        return false;
    }

    public function getLogo()
    {
        return Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo-paynow.png');
    }

    private function postValidation()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            if ((int)Tools::getValue('PAYNOW_SANDBOX_ENABLED') === 1 &&
                (!Tools::getValue('PAYNOW_SANDBOX_API_KEY') ||
                    !Tools::getValue('PAYNOW_SANDBOX_API_SIGNATURE_KEY'))) {
                $this->postErrors[] = $this->l('Integration keys must be set');
            }

            if ((int)Tools::getValue('PAYNOW_SANDBOX_ENABLED') == 0 &&
                (!Tools::getValue('PAYNOW_PROD_API_KEY') ||
                    !Tools::getValue('PAYNOW_PROD_API_SIGNATURE_KEY'))) {
                $this->postErrors[] = $this->l('Integration keys must be set');
            }

            if ($this->validateValidityTime()) {
                $this->postErrors[] = $this->l('Payment validity time must be greater than 60 and less than 86400 seconds');
            }

            if ((int)Tools::getValue('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED') === 1 &&
                !Validate::isInt(Tools::getValue('PAYNOW_PAYMENT_VALIDITY_TIME'))) {
                $this->postErrors[] = $this->l('Payment validity time must be integer');
            }
        }
    }

    private function validateValidityTime(): bool
    {
        if ((int)Tools::getValue('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED') == 0) {
            return false;
        }

        return (int)Tools::getValue('PAYNOW_PAYMENT_VALIDITY_TIME') > 86400 &&
            (int)Tools::getValue('PAYNOW_PAYMENT_VALIDITY_TIME') < 60;
    }

    private function postProcess()
    {
        PaynowConfigurationHelper::update();
        if ($this->isConfigured()) {
            $this->html .= $this->displayConfirmation($this->l('Configuration updated'));
            $this->sendShopUrlsConfiguration();
        }
    }

    private function sendShopUrlsConfiguration()
    {
        $shop_configuration = new Paynow\Service\ShopConfiguration($this->getPaynowClient());
        try {
            $shop_configuration->changeUrls(
                Context::getContext()->link->getModuleLink($this->name, 'return'),
                PaynowLinkHelper::getNotificationUrl()
            );
        } catch (Paynow\Exception\PaynowException $exception) {
            PaynowLogger::error(
                'An error occurred during shop urls configuration {code={}, message={}}',
                [
                    $exception->getCode(),
                    $exception->getPrevious()->getMessage()
                ]
            );
            if ($exception->getCode() == 401) {
                $this->html .= $this->displayError($this->l('Wrong configuration for API credentials'));
            } else {
                if (sizeof($exception->getErrors()) > 1) {
                    $errors = [];
                    foreach ($exception->getErrors() as $error) {
                        $errors[] = $this->l('API Response: ') . $error->getType() . ' - ' . $error->getMessage();
                    }
                    $this->html .= $this->displayWarning($errors);
                } else {
                    foreach ($exception->getErrors() as $error) {
                        $this->html .= $this->displayWarning(
                            $this->l('API Response: ') . $error->getType() . ' - ' . $error->getMessage()
                        );
                    }
                }
            }
        }
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            $this->postValidation();
            if (!count($this->postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->postErrors as $error) {
                    $this->html .= $this->displayError($error);
                }
            }
        } else {
            $this->html .= '<br />';
        }

        $this->displayBackOfficeAccountInformation();
        $this->html .= (new PaynowAdminFormHelper($this, $this->context, $this->getTranslationsArray()))->generate();

        return $this->html;
    }

    private function displayBackOfficeAccountInformation()
    {
        $this->html .= $this->fetchTemplate('/views/templates/admin/_partials/account.tpl');
    }

    public function fetchTemplate($view)
    {
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . $view);
    }

    public function getTranslationsArray(): array
    {
        return [
            'Sandbox configuration'                                                                                                                                                             => $this->l('Sandbox configuration'),
            'Test mode (Sandbox)'                                                                                                                                                               => $this->l('Test mode (Sandbox)'),
            'Enable to use test environment'                                                                                                                                                    => $this->l('Enable to use test environment'),
            'Yes'                                                                                                                                                                               => $this->l('Yes'),
            'No'                                                                                                                                                                                => $this->l('No'),
            'API Key'                                                                                                                                                                           => $this->l('API Key'),
            'API Signature Key'                                                                                                                                                                 => $this->l('API Signature Key'),
            'Save'                                                                                                                                                                              => $this->l('Save'),
            'Production configuration'                                                                                                                                                          => $this->l('Production configuration'),
            'Payment status mapping'                                                                                                                                                            => $this->l('Payment status mapping'),
            'Awaiting payment confirmation'                                                                                                                                                     => $this->l('Awaiting payment confirmation'),
            'Payment has been authorized by the buyer'                                                                                                                                          => $this->l('Payment has been authorized by the buyer'),
            'Payment has not been authorized by the buyer'                                                                                                                                      => $this->l('Payment has not been authorized by the buyer'),
            'An error occurred during the payment process and the payment could not be completed'                                                                                               => $this->l('An error occurred during the payment process and the payment could not be completed'),
            'An error occurred during the payment process'                                                                                                                                      => $this->l('An error occurred during the payment process.'),
            'Payment has been abandoned by the buyer'                                                                                                                                           => $this->l('Payment has been abandoned by the buyer'),
            'Payment has been expired'                                                                                                                                                          => $this->l('Payment has been expired'),
            'Refunds'                                                                                                                                                                           => $this->l('Refunds'),
            'Enable refunds'                                                                                                                                                                    => $this->l('Enable refunds'),
            'After status change'                                                                                                                                                               => $this->l('After status change'),
            'On status'                                                                                                                                                                         => $this->l('On status'),
            'Include shipping costs'                                                                                                                                                            => $this->l('Include shipping costs'),
            'Additional options'                                                                                                                                                                => $this->l('Additional options'),
            'Show separated payment methods'                                                                                                                                                    => $this->l('Show separated payment methods'),
            'Use order-confirmation page as shop\'s return URL'                                                                                                                                 => $this->l('Use order-confirmation page as shop\'s return URL'),
            'Buyer will be redirected to order-confirmation page after payment.'                                                                                                                => $this->l('Buyer will be redirected to order-confirmation page after payment.'),
            'Send order items'                                                                                                                                                                  => $this->l('Send order items'),
            'Enable sending ordered products information: name, categories, quantity and unit price.'                                                                                           => $this->l('Enable sending ordered products information: name, categories, quantity and unit price.'),
            'Enable logs'                                                                                                                                                                       => $this->l('Enable logs'),
            'Logs are available in '                                                                                                                                                            => $this->l('Logs are available in '),
            'Use payment validity time'                                                                                                                                                         => $this->l('Use payment validity time'),
            'Enable to limit the validity of the payment.'                                                                                                                                      => $this->l('Enable to limit the validity of the payment.'),
            'Payment validity time'                                                                                                                                                             => $this->l('Payment validity time'),
            'Determines how long it will be possible to pay for the order from the moment the payment link is generated. The value expressed in seconds. Must be between 60 and 86400 seconds.' => $this->l('Determines how long it will be possible to pay for the order from the moment the payment link is generated. Value expressed in seconds. The value must be between 60 and 86400 seconds.'),
            'Order No: '                                                                                                                                                                        => $this->l('Order No: '),
            'Order to cart: '                                                                                                                                                                   => $this->l('Order to cart: '),
            'Confirm the payment using the app on your phone.'                                                                                                                                  => $this->l('Confirm the payment using the app on your phone.'),
            'Wrong BLIK code'                                                                                                                                                                   => $this->l('Wrong BLIK code'),
            'BLIK code has expired'                                                                                                                                                             => $this->l('BLIK code has expired'),
            'BLIK code already used'                                                                                                                                                            => $this->l('BLIK code already used'),
            'You have to accept terms and conditions'                                                                                                                                           => $this->l('You have to accept terms and conditions'),
            'Moment of creating order'                                                                                                                                                          => $this->l('Moment of creating order'),
            'On clicking the Place order'                                                                                                                                                       => $this->l('On clicking the Place order'),
            'After the successful Paynow payment'                                                                                                                                               => $this->l('After the successful Paynow payment'),
            'Show retry payment button'                                                                                                                                                         => $this->l('Show retry payment button'),
            'The button appears on the order details screen.'                                                                                                                                   => $this->l('The button appears on the order details screen.'),
            'Show retry payment button on selected statuses'                                                                                                                                    => $this->l('Show retry payment button on selected statuses'),
            'BLIK field autofocus'                                                                                                                                                              => $this->l('BLIK field autofocus'),
            'Autofocus on checkout form field: BLIK code. Enabled by default. Disabling may be helpful when checkout page is visualy long (e.g. single-page checkout).'                         => $this->l('Autofocus on checkout form field: BLIK code. Enabled by default. Disabling may be helpful when checkout page is visualy long (e.g. single-page checkout).'),
        ];
    }
}
