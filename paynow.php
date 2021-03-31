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
include_once(dirname(__FILE__) . '/classes/PaynowLogger.php');

class Paynow extends PaymentModule
{
    protected $html = '';
    protected $postErrors = [];
    protected $callToActionText = '';

    /**
     * @var \Paynow\Client
     */
    public $api_client;

    public function __construct()
    {
        $this->name = 'paynow';
        $this->tab = 'payments_gateways';
        $this->version = '1.3.2';
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
        $this->callToActionText = $this->l('Pay by paynow.pl');

        if (!$this->isConfigured()) {
            $this->warning = $this->l('API Keys must be configured before using this module.');
        } else {
            $this->initializeApiClient();
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
            `created_at` datetime,
            `modified_at` datetime,
            UNIQUE (`id_payment`, `status`)
        )');
    }

    private function registerHooks()
    {
        $registerStatus = $this->registerHook('header') &&
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('actionOrderSlipAdd') &&
            $this->registerHook('displayAdminOrderTop') &&
            $this->registerHook('displayAdminAfterHeader');

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
            $this->unregisterHook('actionOrderSlipAdd') &&
            $this->unregisterHook('displayAdminOrderTop') &&
            $this->unregisterHook('displayAdminAfterHeader');

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
            Configuration::updateValue('PAYNOW_REFUNDS_ENABLED', 1) &&
            Configuration::updateValue('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED', 0) &&
            Configuration::updateValue('PAYNOW_REFUNDS_ON_STATUS', Configuration::get('PS_OS_REFUND')) &&
            Configuration::updateValue('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS', 0) &&
            Configuration::updateValue('PAYNOW_SEPARATE_PAYMENT_METHODS', 0) &&
            Configuration::updateValue('PAYNOW_PROD_API_KEY', '') &&
            Configuration::updateValue('PAYNOW_PROD_API_SIGNATURE_KEY', '') &&
            Configuration::updateValue('PAYNOW_SANDBOX_ENABLED', 0) &&
            Configuration::updateValue('PAYNOW_SANDBOX_API_KEY', '') &&
            Configuration::updateValue('PAYNOW_SANDBOX_API_SIGNATURE_KEY', '') &&
            Configuration::updateValue('PAYNOW_ORDER_INITIAL_STATE', $this->createOrderInitialState()) &&
            Configuration::updateValue('PAYNOW_ORDER_CONFIRMED_STATE', 2) &&
            Configuration::updateValue('PAYNOW_ORDER_REJECTED_STATE', 6) &&
            Configuration::updateValue('PAYNOW_ORDER_ERROR_STATE', 8);
    }

    private function deleteModuleSettings()
    {
        return Configuration::deleteByName('PAYNOW_DEBUG_LOGS_ENABLED') &&
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
            Configuration::deleteByName('PAYNOW_ORDER_INITIAL_STATE') &&
            Configuration::deleteByName('PAYNOW_ORDER_CONFIRMED_STATE') &&
            Configuration::deleteByName('PAYNOW_ORDER_REJECTED_STATE') &&
            Configuration::deleteByName('PAYNOW_ORDER_ERROR_STATE');
    }

    public function createOrderInitialState()
    {
        $state_name = 'PAYNOW_ORDER_INITIAL_STATE';
        if (Configuration::get($state_name) ||
            Validate::isLoadedObject(new OrderState(Configuration::get($state_name)))) {
            return null;
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

    private function initializeApiClient()
    {
        $this->api_client = new \Paynow\Client(
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

    public function isSandboxEnabled()
    {
        return (int)Configuration::get('PAYNOW_SANDBOX_ENABLED') === 1;
    }

    private function isActive()
    {
        if (!$this->active || !$this->isConfigured()) {
            return;
        }

        return true;
    }

    private function isConfigured()
    {
        return $this->getApiKey() && $this->getSignatureKey();
    }

    public function checkCurrency($cart)
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
        $this->context->controller->addCSS(($this->_path) . 'views/css/front.css', 'all');
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $this->context->controller->addJs(($this->_path) . 'views/js/front.js', 'all');
        }
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->isActive() || !$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [];
        if (Configuration::get('PAYNOW_SEPARATE_PAYMENT_METHODS')) {
            $payment_methods = $this->getPaymentMethods();
            if (!empty($payment_methods)) {
                $list = [];
                foreach ($payment_methods->getAll() as $payment_method) {
                    if (!isset($list[$payment_method->getType()])) {
                        if (Paynow\Model\PaymentMethods\Type::PBL == $payment_method->getType()) {
                            $this->context->smarty->assign([
                                'paynowPbls' => $payment_methods->getOnlyPbls(),
                                'action' => $this->context->link->getModuleLink($this->name, 'payment', [], true)
                            ]);
                            array_push($payment_options, $this->paymentOption(
                                $this->getPaymentMethodTitle($payment_method->getType()),
                                $this->getLogo(),
                                $this->context->link->getModuleLink($this->name, 'payment', [], true)
                            )->setForm($this->context->smarty->fetch('module:paynow/views/templates/front/1.7/payment_form.tpl')));
                        } else {
                            array_push($payment_options, $this->paymentOption(
                                $this->getPaymentMethodTitle($payment_method->getType()),
                                $payment_method->getImage(),
                                $this->context->link->getModuleLink($this->name, 'payment', ['paymentMethodId' => $payment_method->getId()], true)
                            ));
                        }
                        $list[$payment_method->getType()] = $payment_method->getId();
                    }
                }
            }
        } else {
            array_push($payment_options, $this->paymentOption(
                $this->callToActionText,
                $this->getLogo(),
                $this->context->link->getModuleLink($this->name, 'payment', [], true)
            ));
        }

        return $payment_options;
    }

    private function getPaymentMethodTitle($payment_method_type)
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

    private function getPaymentMethods()
    {
        $total = number_format($this->context->cart->getOrderTotal(true, Cart::BOTH) * 100, 0, '', '');
        $payment_client = new Paynow\Service\Payment($this->api_client);
        $currency = new Currency($this->context->cart->id_currency);
        try {
            return $payment_client->getPaymentMethods($currency->iso_code, $total);
        } catch (\Paynow\Exception\PaynowException $exception) {
            PaynowLogger::error($exception->getMessage());
        }

        return null;
    }

    private function paymentOption($title, $logo, $action, $additional = null)
    {
        $payment_option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $payment_option->setModuleName($this->name)
            ->setCallToActionText($title)
            ->setLogo($logo)
            ->setAdditionalInformation($additional)
            ->setAction($action);
        return $payment_option;
    }

    public function hookPayment($params)
    {
        if (!$this->isActive() || !$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->context->smarty->assign([
            'cta_text' => $this->callToActionText,
            'logo' => $this->getLogo(),
            'paynow_url' => $this->context->link->getModuleLink('paynow', 'payment')
        ]);

        $payment_options = [];
        if (Configuration::get('PAYNOW_SEPARATE_PAYMENT_METHODS')) {
            $payment_methods = $this->getPaymentMethods();
            if (!empty($payment_methods)) {
                $list = [];
                foreach ($payment_methods->getAll() as $payment_method) {
                    if (!isset($list[$payment_method->getType()])) {
                        if (Paynow\Model\PaymentMethods\Type::PBL == $payment_method->getType()) {
                            array_push($payment_options, [
                                'name' => $this->getPaymentMethodTitle($payment_method->getType()),
                                'image' => $this->getLogo(),
                                'pbls' => $payment_methods->getOnlyPbls()
                            ]);
                        } else {
                            array_push($payment_options, [
                                'name' => $this->getPaymentMethodTitle($payment_method->getType()),
                                'image' => $payment_method->getImage(),
                                'id' => $payment_method->getId(),
                                'status' => "DISABLED"
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

    public function hookDisplayPaymentEU()
    {
        return [
            'cta_text' => $this->callToActionText,
            'logo' => $this->getLogo(),
            'action' => $this->context->link->getModuleLink('paynow', 'payment')
        ];
    }

    public function hookDisplayOrderDetail($params)
    {
        if (!$this->isActive()) {
            return;
        }

        $id_order = (int)$params['order']->id;

        if (!$this->canOrderPaymentBeRetried($id_order)) {
            return;
        }

        $this->context->smarty->assign([
            'paynow_url' => $this->context->link->getModuleLink('paynow', 'payment', [
                'id_order' => $id_order,
                'order_reference' => $params['order']->reference
            ])
        ]);
        return $this->display(__FILE__, '/views/templates/hook/order_details.tpl');
    }

    /**
     * Handle status change to make a refund
     *
     * @param $params
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (Configuration::get('PAYNOW_REFUNDS_ENABLED') && Configuration::get('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED') && $this->context->controller instanceof AdminController) {
            $order = new Order($params['id_order']);
            $newOrderStatus = $params['newOrderStatus'];

            if ((int)Configuration::get('PAYNOW_REFUNDS_ON_STATUS') === $newOrderStatus->id) {
                $amount_to_refund = $order->total_paid;
                if (!Configuration::get('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS')) {
                    $amount_to_refund -= $order->total_shipping_tax_incl;
                }
                $payments = $order->getOrderPaymentCollection()->getResults();
                $this->processRefund($order->reference, $payments, $amount_to_refund);
            }
        }
    }

    /**
     * Handle add order slip to make a refund
     *
     * @param $params
     */
    public function hookActionOrderSlipAdd($params)
    {
        if (Configuration::get('PAYNOW_REFUNDS_ENABLED') && Tools::isSubmit('makeRefundViaPaynow')) {
            $order = $params['order'];
            PaynowLogger::info('Processing refund request {orderReference={}}', [$order->reference]);
            if ($this->name = $order->module) {
                $orderSlip = $order->getOrderSlipsCollection()
                    ->orderBy('date_upd', 'desc')
                    ->getFirst();
                $payments = $order->getOrderPaymentCollection()->getResults();
                $amount_from_slip = $orderSlip->amount + $orderSlip->shipping_cost_amount;
                $this->processRefund($order->reference, $payments, $amount_from_slip);
            }
        }
    }

    private function processRefund($order_reference, array $payments, $amount)
    {
        if (!empty($payments)) {
            foreach ($payments as $payment) {
                if ($this->displayName != $payment->payment_method || $payment->amount < $amount) {
                    continue;
                }

                $refund_amount = number_format($amount * 100, 0, '', '');
                try {
                    PaynowLogger::info(
                        'Found transaction to make a refund {orderReference={}, paymentId={}, amount={}}',
                        [
                            $order_reference,
                            $payment->transaction_id,
                            $refund_amount
                        ]
                    );
                    $refund_client = new Paynow\Service\Refund($this->api_client);
                    $refund = $refund_client->create(
                        $payment->transaction_id,
                        uniqid($payment->order_reference, true),
                        $refund_amount
                    );
                    PaynowLogger::info(
                        'Refund has been created successfully {orderReference={}, refundId={}}',
                        [
                            $payment->order_reference,
                            $refund->getRefundId()
                        ]
                    );
                } catch (Paynow\Exception\PaynowException $exception) {
                    foreach ($exception->getErrors() as $error) {
                        PaynowLogger::error(
                            $exception->getMessage() . ' {orderReference={}, paymentId={}, type={}, message={}}',
                            [
                                $payment->order_reference,
                                $payment->transaction_id,
                                $error->getType(),
                                $error->getMessage()
                            ]
                        );
                    }
                }
            }
        }
    }

    public function hookDisplayAdminOrderTop($params)
    {
        if (!Configuration::get('PAYNOW_REFUNDS_ENABLED')) {
            return;
        }

        $this->context->smarty->assign('makePaynowRefundCheckboxLabel', $this->l('Make a refund via paynow.pl'));
        return $this->fetchTemplate('/views/templates/hook/admin_order_top.tpl');
    }

    public function hookDisplayAdminAfterHeader()
    {
        try {
            $client = new \Github\Client();
            $release = $client->api('repo')->releases()->latest('pay-now', 'paynow-prestashop');

            if ($release && version_compare($this->version, $release['tag_name'], '<')) {
                $this->context->smarty->assign([
                    'download_url' => $release['assets'][0]['browser_download_url'],
                    'version_name' => $release['name'],
                    'changelog_url' => $release['html_url']
                ]);
                return $this->fetchTemplate('/views/templates/admin/_partials/upgrade.tpl');
            }
        } catch (Exception $exception) {
            PaynowLogger::error($exception->getMessage());
        }

        return null;
    }

    public function canOrderPaymentBeRetried($id_order)
    {
        $last_payment_status = $this->getLastPaymentStatusByOrderId($id_order);
        $order = new Order($id_order);
        return $last_payment_status['status'] !== \Paynow\Model\Payment\Status::STATUS_CONFIRMED &&
            in_array(
                (int)$order->current_state,
                [
                    (int)Configuration::get('PAYNOW_ORDER_ERROR_STATE'),
                    (int)Configuration::get('PAYNOW_ORDER_REJECTED_STATE')
                ]
            );
    }

    public function getLogo()
    {
        return Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo-paynow.png');
    }

    private function postValidation()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            if ((int)Tools::getValue('PAYNOW_SANDBOX_ENABLED') == 1 &&
                (!Tools::getValue('PAYNOW_SANDBOX_API_KEY') ||
                    !Tools::getValue('PAYNOW_SANDBOX_API_SIGNATURE_KEY'))) {
                $this->postErrors[] = $this->l('Integration keys must be set');
            }

            if ((int)Tools::getValue('PAYNOW_SANDBOX_ENABLED') == 0 &&
                (!Tools::getValue('PAYNOW_PROD_API_KEY') ||
                    !Tools::getValue('PAYNOW_PROD_API_SIGNATURE_KEY'))) {
                $this->postErrors[] = $this->l('Integration keys must be set');
            }
        }
    }

    private function postProcess()
    {
        Configuration::updateValue(
            'PAYNOW_DEBUG_LOGS_ENABLED',
            Tools::getValue('PAYNOW_DEBUG_LOGS_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_REFUNDS_ENABLED',
            Tools::getValue('PAYNOW_REFUNDS_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED',
            Tools::getValue('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_REFUNDS_ON_STATUS',
            Tools::getValue('PAYNOW_REFUNDS_ON_STATUS')
        );
        Configuration::updateValue(
            'PAYNOW_REFUNDS_WITH_SHIPPING_COSTS',
            Tools::getValue('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS')
        );
        Configuration::updateValue(
            'PAYNOW_SEPARATE_PAYMENT_METHODS',
            Tools::getValue('PAYNOW_SEPARATE_PAYMENT_METHODS')
        );
        Configuration::updateValue(
            'PAYNOW_PROD_API_KEY',
            Tools::getValue('PAYNOW_PROD_API_KEY')
        );
        Configuration::updateValue(
            'PAYNOW_PROD_API_SIGNATURE_KEY',
            Tools::getValue('PAYNOW_PROD_API_SIGNATURE_KEY')
        );
        Configuration::updateValue(
            'PAYNOW_SANDBOX_ENABLED',
            Tools::getValue('PAYNOW_SANDBOX_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_SANDBOX_API_KEY',
            Tools::getValue('PAYNOW_SANDBOX_API_KEY')
        );
        Configuration::updateValue(
            'PAYNOW_SANDBOX_API_SIGNATURE_KEY',
            Tools::getValue('PAYNOW_SANDBOX_API_SIGNATURE_KEY')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_INITIAL_STATE',
            Tools::getValue('PAYNOW_ORDER_INITIAL_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_CONFIRMED_STATE',
            Tools::getValue('PAYNOW_ORDER_CONFIRMED_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_REJECTED_STATE',
            Tools::getValue('PAYNOW_ORDER_REJECTED_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_ERROR_STATE',
            Tools::getValue('PAYNOW_ORDER_ERROR_STATE')
        );

        if ($this->isConfigured()) {
            $this->html .= $this->displayConfirmation($this->l('Configuration updated'));
            $this->sendShopUrlsConfiguration();
        }
    }

    private function sendShopUrlsConfiguration()
    {
        $this->initializeApiClient();
        $shop_configuration = new Paynow\Service\ShopConfiguration($this->api_client);
        try {
            $shop_configuration->changeUrls(
                $this->context->link->getModuleLink('paynow', 'return'),
                $this->context->link->getModuleLink('paynow', 'notifications')
            );
            return true;
        } catch (Paynow\Exception\PaynowException $exception) {
            PaynowLogger::error('Could not properly configure shop urls {message={}}', [$exception->getMessage()]);
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
            return false;
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
        $this->renderForm();

        return $this->html;
    }

    private function displayBackOfficeAccountInformation()
    {
        $this->html .= $this->fetchTemplate('/views/templates/admin/_partials/account.tpl');
    }

    private function renderForm()
    {
        $form = [];
        $form['pos_sandbox'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Sandbox configuration'),
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Test mode (Sandbox)'),
                        'desc' => $this->l('Enable if you are using test shop environment'),
                        'name' => 'PAYNOW_SANDBOX_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Key'),
                        'name' => 'PAYNOW_SANDBOX_API_KEY'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Signature Key'),
                        'name' => 'PAYNOW_SANDBOX_API_SIGNATURE_KEY'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ]
            ]
        ];

        $form['pos_prod'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Production configuration'),
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('API Key'),
                        'name' => 'PAYNOW_PROD_API_KEY'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Signature Key'),
                        'name' => 'PAYNOW_PROD_API_SIGNATURE_KEY'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ]
            ]
        ];

        $order_states = OrderState::getOrderStates(ContextCore::getContext()->language->id);
        $form['payment_statuses'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Payment status mapping'),
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Awaiting payment confirmation'),
                        'name' => 'PAYNOW_ORDER_INITIAL_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Payment has been authorized by the buyer'),
                        'name' => 'PAYNOW_ORDER_CONFIRMED_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Payment has not been authorized by the buyer'),
                        'name' => 'PAYNOW_ORDER_REJECTED_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Error occurred during the payment process and the payment could not be completed'),
                        'name' => 'PAYNOW_ORDER_ERROR_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ]
            ]
        ];

        $form['refunds'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Refunds'),
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'name' => '',
                        'html_content' => $this->displayInfoMessage($this->l('The module allows you to make an automatic refund from the balance for payments made by paynow.pl. To use this option, you must select the daily payout frequency in the paynow panel. To do this, go to Settings -> Payout schedule and then select the setting for the appropriate store.'))
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable refunds'),
                        'name' => 'PAYNOW_REFUNDS_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('After status change'),
                        'name' => 'PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('On status'),
                        'name' => 'PAYNOW_REFUNDS_ON_STATUS',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Include shipping costs'),
                        'name' => 'PAYNOW_REFUNDS_WITH_SHIPPING_COSTS',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ]
            ]
        ];

        $logs_path = _PS_MODULE_DIR_ . $this->name . '/logs';
        $form['additional_options'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Additional options'),
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show separated payment methods'),
                        'name' => 'PAYNOW_SEPARATE_PAYMENT_METHODS',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable logs'),
                        'desc' => $this->l('Logs are available in ') . ' ' . $logs_path,
                        'name' => 'PAYNOW_DEBUG_LOGS_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->name_controller = $this->name;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' .
            $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        $this->html .= $helper->generateForm($form);
    }

    public function displayInfoMessage($message)
    {
        $this->context->smarty->assign([
            'message' => $message
        ]);
        return $this->fetchTemplate('/views/templates/admin/_partials/info.tpl');
    }

    private function fetchTemplate($view)
    {
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . $view);
    }

    private function getConfigFieldsValues()
    {
        return [
            'PAYNOW_REFUNDS_ENABLED' => Configuration::get('PAYNOW_REFUNDS_ENABLED'),
            'PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED' => Configuration::get('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED'),
            'PAYNOW_REFUNDS_ON_STATUS' => Configuration::get('PAYNOW_REFUNDS_ON_STATUS'),
            'PAYNOW_REFUNDS_WITH_SHIPPING_COSTS' => Configuration::get('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS'),
            'PAYNOW_DEBUG_LOGS_ENABLED' => Configuration::get('PAYNOW_DEBUG_LOGS_ENABLED'),
            'PAYNOW_SEPARATE_PAYMENT_METHODS' => Configuration::get('PAYNOW_SEPARATE_PAYMENT_METHODS'),
            'PAYNOW_PROD_API_KEY' => Configuration::get('PAYNOW_PROD_API_KEY'),
            'PAYNOW_PROD_API_SIGNATURE_KEY' => Configuration::get('PAYNOW_PROD_API_SIGNATURE_KEY'),
            'PAYNOW_SANDBOX_ENABLED' => Configuration::get('PAYNOW_SANDBOX_ENABLED'),
            'PAYNOW_SANDBOX_API_KEY' => Configuration::get('PAYNOW_SANDBOX_API_KEY'),
            'PAYNOW_SANDBOX_API_SIGNATURE_KEY' => Configuration::get('PAYNOW_SANDBOX_API_SIGNATURE_KEY'),
            'PAYNOW_ORDER_INITIAL_STATE' => Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
            'PAYNOW_ORDER_CONFIRMED_STATE' => Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE'),
            'PAYNOW_ORDER_REJECTED_STATE' => Configuration::get('PAYNOW_ORDER_REJECTED_STATE'),
            'PAYNOW_ORDER_ERROR_STATE' => Configuration::get('PAYNOW_ORDER_ERROR_STATE')
        ];
    }

    public function storePaymentState(
        $id_payment,
        $status,
        $id_order,
        $id_cart,
        $order_reference,
        $external_id,
        $modified_at = null
    ) {
        $modified_at = !$modified_at ? 'NOW()' : '"' . $modified_at . '"';

        try {
            $sql = '
                INSERT INTO ' . _DB_PREFIX_ . 'paynow_payments 
                    (id_order, id_cart, id_payment, order_reference, external_id, status, created_at, modified_at) 
                VALUES (
                    ' . (int)$id_order . ', 
                    ' . (int)$id_cart . ', 
                    "' . pSQL($id_payment) . '", 
                    "' . pSQL($order_reference) . '", 
                    "' . pSQL($external_id) . '", 
                    "' . pSQL($status) . '", 
                    NOW(), 
                    ' . $modified_at . '
                ) 
                ON DUPLICATE KEY 
                UPDATE modified_at=' . $modified_at;
            if (Db::getInstance()->execute($sql)) {
                return (int)Db::getInstance()->Insert_ID();
            }
        } catch (PrestaShopDatabaseException $exception) {
            PaynowLogger::error($exception->getMessage() . '{orderReference={}}', [$order_reference]);
        }

        return false;
    }

    public function getLastPaymentStatus($id_payment)
    {
        return Db::getInstance()->getRow('
            SELECT id_order, id_cart, order_reference, status, id_payment, external_id 
            FROM  ' . _DB_PREFIX_ . 'paynow_payments 
            WHERE id_payment="' . pSQL($id_payment) . '" ORDER BY created_at DESC');
    }

    public function getLastPaymentStatusByOrderId($id_order)
    {
        return Db::getInstance()->getRow('
            SELECT status 
            FROM  ' . _DB_PREFIX_ . 'paynow_payments 
            WHERE id_order="' . (int)$id_order . '" ORDER BY created_at DESC');
    }

    public function getLastPaymentDataByOrderReference($order_reference)
    {
        return Db::getInstance()->getRow('
            SELECT id_order, id_cart, order_reference, status, id_payment, external_id 
            FROM  ' . _DB_PREFIX_ . 'paynow_payments 
            WHERE order_reference="' . pSQL($order_reference) . '" ORDER BY created_at DESC');
    }

    public function getOrderUrl($order)
    {
        if (Cart::isGuestCartByCartId($order->id_cart)) {
            $customer = new Customer((int)$order->id_customer);
            return $this->context->link->getPageLink(
                'guest-tracking',
                null,
                $this->context->language->id,
                [
                    'order_reference' => $order->reference,
                    'email' => $customer->email
                ]
            );
        }

        return $this->context->link->getPageLink(
            'order-detail',
            null,
            $this->context->language->id,
            [
                'id_order' => $order->id
            ]
        );
    }
}
