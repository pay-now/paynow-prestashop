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

    /**
     * @var \Paynow\Client
     */
    public $api_client;

    public function __construct()
    {
        $this->name = 'paynow';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.6';
        $this->ps_versions_compliancy = ['min' => '1.6.0', 'max' => _PS_VERSION_];
        $this->author = 'mElements S.A.';
        $this->is_eu_compatible = 1;
        $this->controllers = ['payment', 'return'];
        $this->bootstrap = true;
        $this->module_key = '86f0413df24b36cc82b831f755669dc7';

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->displayName = 'Paynow';
        $this->description = $this->l('Accepts payments by Paynow');
        $this->confirm_uninstall = $this->l('Are you sure you want to uninstall? You will lose all your settings!');

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
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayOrderDetail');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $registerStatus &= $this->registerHook('payment') && $this->registerHook('displayPaymentEU');
        } else {
            $registerStatus &= $this->registerHook('paymentOptions');
        }

        return $registerStatus;
    }

    private function unregisterHooks()
    {
        $registerStatus = $this->unregisterHook('header') &&
            $this->unregisterHook('paymentReturn') &&
            $this->unregisterHook('displayOrderDetail');

        if (version_compare(_PS_VERSION_, '1.7', 'lt')) {
            $registerStatus &= $this->unregisterHook('displayPaymentEU') && $this->unregisterHook('payment');
        } else {
            $registerStatus &= $this->unregisterHook('paymentOptions');
        }

        return $registerStatus;
    }

    private function createModuleSettings()
    {
        return Configuration::updateValue('PAYNOW_DEBUG_LOGS_ENABLED', 0) &&
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

    public function hookHeader()
    {
        $this->context->controller->addCSS(($this->_path) . 'views/css/front.css', 'all');
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

    private function isActive($params)
    {
        if (!$this->active || !$this->checkCurrency($params['cart']) || !$this->isConfigured()) {
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

    public function hookPaymentOptions($params)
    {
        if (!$this->isActive($params)) {
            return;
        }

        $payment_option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $payment_option->setModuleName($this->name)
            ->setCallToActionText($this->l('Pay by online transfer or BLIK', 'paynow'))
            ->setLogo($this->getLogo())
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true));

        return [$payment_option];
    }

    public function hookPayment($params)
    {
        if (!$this->isActive($params)) {
            return;
        }

        $this->context->smarty->assign([
            'logo' => $this->getLogo(),
            'paynow_url' => $this->context->link->getModuleLink('paynow', 'payment')
        ]);

        return $this->display(__FILE__, '/views/templates/hook/payment.tpl');
    }

    public function hookDisplayPaymentEU()
    {
        return [
            'cta_text' => $this->l('Pay by online transfer or BLIK', 'paynow'),
            'logo' => $this->getLogo(),
            'action' => $this->context->link->getModuleLink('paynow', 'payment')
        ];
    }

    public function hookDisplayOrderDetail($params)
    {
        if (!$this->isActive($params)) {
            return;
        }

        $id_order = (int)$params['order']->id;

        if ($this->canOrderPaymentBeRetried($id_order)) {
            $this->context->smarty->assign([
                'paynow_url' => $this->context->link->getModuleLink('paynow', 'payment', [
                    'id_order' => $id_order,
                    'order_reference' => $params['order']->reference
                ])
            ]);
            return $this->display(__FILE__, '/views/templates/hook/order_details.tpl');
        }
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

    private function getLogo()
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
            $this->sendShopUrlsConfiguration();
        }

        $this->html .= $this->displayConfirmation($this->l('Configuration updated'));
    }

    private function sendShopUrlsConfiguration()
    {
        $this->initializeApiClient();
        $shop_configuration = new \Paynow\Service\ShopConfiguration($this->api_client);
        try {
            $shop_configuration->changeUrls(
                $this->context->link->getModuleLink('paynow', 'return'),
                $this->context->link->getModuleLink('paynow', 'notifications')
            );
        } catch (Paynow\Exception\PaynowException $exception) {
            PaynowLogger::log('Could not send shop urls configuration to Paynow');
        }
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            $this->postValidation();
            if (!count($this->postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->postErrors as $err) {
                    $this->html .= $this->displayError($err);
                }
            }
        } else {
            $this->html .= '<br />';
        }

        $this->html .= $this->displayBackOfficeInformation();
        $this->html .= $this->renderForm();

        return $this->html;
    }

    private function displayBackOfficeInformation()
    {
        return $this->display(__FILE__, '/views/templates/admin/information.tpl');
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

        $form['debug_logs'] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Debug'),
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable logs'),
                        'desc' => $this->l('This option enables debug logs for this module. Logs are available in ') . ' ' . _PS_MODULE_DIR_ . $this->name . '/logs',
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
                    ]
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

        return $helper->generateForm($form);
    }

    private function getConfigFieldsValues()
    {
        return [
            'PAYNOW_DEBUG_LOGS_ENABLED' => Configuration::get('PAYNOW_DEBUG_LOGS_ENABLED'),
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
    )
    {
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
        } catch (PrestaShopDatabaseException $e) {
            PaynowLogger::log($e->getMessage(), null, $order_reference);
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
