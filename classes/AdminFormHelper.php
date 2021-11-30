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

class AdminFormHelper
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var Context
     */
    private $context;

    private $translations;

    public function __construct($module, $context, $translations)
    {
        $this->module = $module;
        $this->context = $context;
        $this->translations = $translations;
    }

    public function generate(): string
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->name_controller = $this->module->name;
        $helper->title = $this->module->displayName;
        $helper->submit_action = 'submit' . $this->module->name;
        $helper->default_form_language = (new Language((int)Configuration::get('PS_LANG_DEFAULT')))->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&' . http_build_query([
                'configure'   => $this->module->name,
                'tab_module'  => $this->module->tab,
                'module_name' => $this->module->name
            ]);
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm($this->getForm());
    }
    
    private function getForm(): array
    {
        $form = [];
        $form['pos_sandbox'] = [
            'form' => [
                'legend' => [
                    'title' => $this->translations['Sandbox configuration'],
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->translations['Test mode (Sandbox)'],
                        'desc' => $this->translations['Enable to use test environment'],
                        'name' => 'PAYNOW_SANDBOX_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->translations['API Key'],
                        'name' => 'PAYNOW_SANDBOX_API_KEY'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->translations['API Signature Key'],
                        'name' => 'PAYNOW_SANDBOX_API_SIGNATURE_KEY'
                    ]
                ],
                'submit' => [
                    'title' => $this->translations['Save']
                ]
            ]
        ];

        $form['pos_prod'] = [
            'form' => [
                'legend' => [
                    'title' => $this->translations['Production configuration'],
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->translations['API Key'],
                        'name' => 'PAYNOW_PROD_API_KEY'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->translations['API Signature Key'],
                        'name' => 'PAYNOW_PROD_API_SIGNATURE_KEY'
                    ]
                ],
                'submit' => [
                    'title' => $this->translations['Save']
                ]
            ]
        ];

        $order_states = OrderState::getOrderStates($this->context->language->id);
        $form['payment_statuses'] = [
            'form' => [
                'legend' => [
                    'title' => $this->translations['Payment status mapping'],
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->translations['Awaiting payment confirmation'],
                        'name' => 'PAYNOW_ORDER_INITIAL_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->translations['Payment has been authorized by the buyer'],
                        'name' => 'PAYNOW_ORDER_CONFIRMED_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->translations['Payment has not been authorized by the buyer'],
                        'name' => 'PAYNOW_ORDER_REJECTED_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->translations['An error occurred during the payment process and the payment could not be completed'],
                        'name' => 'PAYNOW_ORDER_ERROR_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->translations['Payment has been abandoned by the buyer'],
                        'name' => 'PAYNOW_ORDER_ABANDONED_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->translations['Payment has been expired'],
                        'name' => 'PAYNOW_ORDER_EXPIRED_STATE',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->translations['Save']
                ]
            ]
        ];

        $form['refunds'] = [
            'form' => [
                'legend' => [
                    'title' => $this->translations['Refunds'],
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'name' => '',
                        'html_content' => $this->module->fetchTemplate('/views/templates/admin/_partials/info.tpl')
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->translations['Enable refunds'],
                        'name' => 'PAYNOW_REFUNDS_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->translations['After status change'],
                        'name' => 'PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->translations['On status'],
                        'name' => 'PAYNOW_REFUNDS_ON_STATUS',
                        'options' => [
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->translations['Include shipping costs'],
                        'name' => 'PAYNOW_REFUNDS_WITH_SHIPPING_COSTS',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->translations['Save']
                ]
            ]
        ];

        $logs_path = _PS_MODULE_DIR_ . $this->module->name . '/logs';
        $form['additional_options'] = [
            'form' => [
                'legend' => [
                    'title' => $this->translations['Additional options'],
                    'icon' => 'icon-cog'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->translations['Show separated payment methods'],
                        'name' => 'PAYNOW_SEPARATE_PAYMENT_METHODS',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->translations['Use order-confirmation page as shop\'s return URL'],
                        'desc' => $this->translations['Buyer will be redirected to order-confirmation page after payment.'],
                        'name' => 'PAYNOW_USE_CLASSIC_RETURN_URL',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->translations['Send order items'],
                        'desc' => $this->translations['Enable sending ordered products information: name, categories, quantity and unit price.'],
                        'name' => 'PAYNOW_SEND_ORDER_ITEMS',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->translations['Enable logs'],
                        'desc' => $this->translations['Logs are available in '] . ' ' . $logs_path,
                        'name' => 'PAYNOW_DEBUG_LOGS_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->translations['Use payment validity time'],
                        'desc' => $this->translations['Enable to limit the validity of the payment.'],
                        'name' => 'PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->translations['Yes']
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->translations['No']
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->translations['Payment validity time'],
                        'desc' => $this->translations['Determines how long it will be possible to pay for the order from the moment the payment link is generated. The value expressed in seconds. Must be between 60 and 86400 seconds.'],
                        'name' => 'PAYNOW_PAYMENT_VALIDITY_TIME'
                    ],
                ],
                'submit' => [
                    'title' => $this->translations['Save']
                ]
            ]
        ];
        
        return $form;
    }
    
    private function getConfigFieldsValues(): array
    {
        return [
            'PAYNOW_REFUNDS_ENABLED' => Configuration::get('PAYNOW_REFUNDS_ENABLED'),
            'PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED' => Configuration::get('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED'),
            'PAYNOW_REFUNDS_ON_STATUS' => Configuration::get('PAYNOW_REFUNDS_ON_STATUS'),
            'PAYNOW_REFUNDS_WITH_SHIPPING_COSTS' => Configuration::get('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS'),
            'PAYNOW_DEBUG_LOGS_ENABLED' => Configuration::get('PAYNOW_DEBUG_LOGS_ENABLED'),
            'PAYNOW_SEPARATE_PAYMENT_METHODS' => Configuration::get('PAYNOW_SEPARATE_PAYMENT_METHODS'),
            'PAYNOW_USE_CLASSIC_RETURN_URL' => Configuration::get('PAYNOW_USE_CLASSIC_RETURN_URL'),
            'PAYNOW_PROD_API_KEY' => Configuration::get('PAYNOW_PROD_API_KEY'),
            'PAYNOW_PROD_API_SIGNATURE_KEY' => Configuration::get('PAYNOW_PROD_API_SIGNATURE_KEY'),
            'PAYNOW_SANDBOX_ENABLED' => Configuration::get('PAYNOW_SANDBOX_ENABLED'),
            'PAYNOW_SANDBOX_API_KEY' => Configuration::get('PAYNOW_SANDBOX_API_KEY'),
            'PAYNOW_SANDBOX_API_SIGNATURE_KEY' => Configuration::get('PAYNOW_SANDBOX_API_SIGNATURE_KEY'),
            'PAYNOW_ORDER_INITIAL_STATE' => Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
            'PAYNOW_ORDER_CONFIRMED_STATE' => Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE'),
            'PAYNOW_ORDER_REJECTED_STATE' => Configuration::get('PAYNOW_ORDER_REJECTED_STATE'),
            'PAYNOW_ORDER_ERROR_STATE' => Configuration::get('PAYNOW_ORDER_ERROR_STATE'),
            'PAYNOW_SEND_ORDER_ITEMS' => Configuration::get('PAYNOW_SEND_ORDER_ITEMS'),
            'PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED' => Configuration::get('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED'),
            'PAYNOW_PAYMENT_VALIDITY_TIME' => Configuration::get('PAYNOW_PAYMENT_VALIDITY_TIME'),
            'PAYNOW_ORDER_ABANDONED_STATE' => Configuration::get('PAYNOW_ORDER_ABANDONED_STATE'),
            'PAYNOW_ORDER_EXPIRED_STATE' => Configuration::get('PAYNOW_ORDER_EXPIRED_STATE')
        ];
    }
}

