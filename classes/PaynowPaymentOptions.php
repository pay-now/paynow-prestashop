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

use Paynow\Model\PaymentMethods\PaymentMethod;
use Paynow\Response\DataProcessing\Notices;
use Paynow\Response\PaymentMethods\PaymentMethods;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class PaynowPaymentOptions
{
    /**
     * @var Context
     */
    private $context;

    private $module;

    private $payment_methods;

    /**
     * @var Notices
     */
    private $data_processing_notices;

    /**
     * @param $context
     * @param $module
     * @param PaymentMethods|null
     * @param $data_processing_notices
     */
    public function __construct($context, $module, $payment_methods, $data_processing_notices)
    {
        $this->context                 = $context;
        $this->module                  = $module;
        $this->payment_methods         = $payment_methods;
        $this->data_processing_notices = $data_processing_notices;
    }

    public function generate(): array
    {
        if (!Configuration::get('PAYNOW_SEPARATE_PAYMENT_METHODS') || empty($this->payment_methods)) {
            return [
                $this->getPaymentOption(
                    $this->module->getCallToActionText(),
                    $this->module->getLogo(),
                    PaynowLinkHelper::getPaymentUrl()
                )
            ];
        }

        $digital_wallets = [
            Paynow\Model\PaymentMethods\Type::CLICK_TO_PAY => null,
            Paynow\Model\PaymentMethods\Type::GOOGLE_PAY => null,
            Paynow\Model\PaymentMethods\Type::APPLE_PAY => null,
        ];

        $this->context->smarty->assign([
            'action' => PaynowLinkHelper::getPaymentUrl(),
            'data_processing_notices' => $this->data_processing_notices,
			'data_paynow_plugin_version' => $this->module->version,
        ]);

        $isAnyPblEnabled = false;
        /** @var PaymentMethod $pbl_payment_method */
        foreach ($this->payment_methods->getOnlyPbls() as $pbl_payment_method) {
            if ($pbl_payment_method->isEnabled()) {
                $isAnyPblEnabled = true;
                break;
            }
        }

        $hiddenPaymentTypes = explode(',', Configuration::get('PAYNOW_HIDE_PAYMENT_TYPES'));
        $digitalWalletsHidden = in_array('DIGITAL_WALLETS', $hiddenPaymentTypes);

        $list = [];
        $payment_options = [];
        /** @var PaymentMethod $payment_method */
        foreach ($this->payment_methods->getAll() as $payment_method) {
            if (isset($list[$payment_method->getType()])) {
                continue;
            }

            if (in_array($payment_method->getType(), $hiddenPaymentTypes)) {
                continue;
            }

            if (Paynow\Model\PaymentMethods\Type::PBL == $payment_method->getType()) {
                if (!$isAnyPblEnabled) {
                    continue;
                }

                $this->context->smarty->assign([
                    'paynowPbls' => $this->payment_methods->getOnlyPbls(),
                ]);
                $payment_options[] = $this->getPaymentOption(
                    $this->module->getPaymentMethodTitle($payment_method->getType()),
                    $this->module->getLogo(),
                    PaynowLinkHelper::getPaymentUrl(),
                    'module:paynow/views/templates/front/1.7/payment_form.tpl'
                );
            } elseif (array_key_exists($payment_method->getType(), $digital_wallets)) {
                if (!$payment_method->isEnabled() || $digitalWalletsHidden) {
                    continue;
                }

                $digital_wallets[$payment_method->getType()] = $payment_method;
            } else {
                if (!$payment_method->isEnabled()) {
                    continue;
                }

                $this->setUpAdditionalTemplateVariables($payment_method);
                $payment_options[] = $this->getPaymentOption(
                    $this->module->getPaymentMethodTitle($payment_method->getType()),
                    $payment_method->getImage(),
                    PaynowLinkHelper::getPaymentUrl([
                        'paymentMethodId' => $payment_method->getId(),
                    ]),
                    $this->getForm($payment_method)
                );
            }

            $list[$payment_method->getType()] = $payment_method->getId();
        }

        $digital_wallets = array_values(array_filter($digital_wallets));
        if (!empty($digital_wallets)) {
            $this->context->smarty->assign([
                'paynowDigitalWalletsPayments' => $digital_wallets,
            ]);

            $payment_options[] = $this->getPaymentOption(
                $this->module->getPaymentMethodTitle('DIGITAL_WALLETS'),
                $this->module->getDigitalWalletsLogo($digital_wallets),
                PaynowLinkHelper::getPaymentUrl(),
                'module:paynow/views/templates/front/1.7/payment_method_digital_wallets_form.tpl'
            );
        }

        return $payment_options;
    }

    private function setUpAdditionalTemplateVariables($payment_method)
    {
        if (Paynow\Model\PaymentMethods\Type::BLIK == $payment_method->getType()) {
            $this->context->smarty->assign([
                'action_blik' => Context::getContext()->link->getModuleLink(
                    'paynow',
                    'chargeBlik',
                    [
                        'paymentMethodId' => $payment_method->getId()
                    ]
                ),
                'action_token' => Tools::encrypt($this->context->customer->secure_key ?? ''),
                'action_token_refresh' => Context::getContext()->link->getModuleLink('paynow', 'customerToken'),
                'error_message' => $this->getMessage('An error occurred during the payment process'),
                'terms_message' => $this->getMessage('First accept the terms of service, then click pay.'),
                'blik_autofocus' => Configuration::get('PAYNOW_BLIK_AUTOFOCUS_ENABLED') === '0' ? '0' : '1',
            ]);
        } elseif (Paynow\Model\PaymentMethods\Type::CARD == $payment_method->getType()) {
            $this->context->smarty->assign([
                'action_card' => PaynowLinkHelper::getPaymentUrl([
                    'paymentMethodId' => $payment_method->getId()
                ]),
                'action_remove_saved_instrument' => Context::getContext()->link->getModuleLink(
                    'paynow',
                    'removeSavedInstrument'
                ),
                'action_remove_saved_instrument_token' => Tools::encrypt($this->context->customer->secure_key ?? ''),
                'default_card_image' => Media::getMediaPath(_PS_MODULE_DIR_ . $this->module->name . '/views/img/card-default.svg'),
                'paynow_card_instruments' => $payment_method->getSavedInstruments(),
            ]);
        } elseif (Paynow\Model\PaymentMethods\Type::PAYPO == $payment_method->getType()) {
			$this->context->smarty->assign([
				'action_paypo' => PaynowLinkHelper::getPaymentUrl([
					'paymentMethodId' => $payment_method->getId()
				]),
			]);
		}
    }

    private function getForm($payment_method): ?string
    {
        if ($this->isWhiteLabelEnabled(Paynow\Model\PaymentMethods\Type::BLIK, $payment_method)) {
            return 'module:paynow/views/templates/front/1.7/payment_method_blik_form.tpl';
        }

        if (Paynow\Model\PaymentMethods\Type::CARD === $payment_method->getType()) {
            return 'module:paynow/views/templates/front/1.7/payment_method_card_form.tpl';
        }

		if (Paynow\Model\PaymentMethods\Type::PAYPO === $payment_method->getType()) {
			return 'module:paynow/views/templates/front/1.7/payment_method_paypo_form.tpl';
		}

        return null;
    }

    /**
     * @param string $payment_method_type
     * @param PaymentMethod $payment_method
     *
     * @return bool
     */
    private function isWhiteLabelEnabled(string $payment_method_type, PaymentMethod $payment_method): bool
    {
        return $payment_method_type == $payment_method->getType()
               && Paynow\Model\PaymentMethods\AuthorizationType::CODE == $payment_method->getAuthorizationType();
    }

    /**
     * @param $title
     * @param $logo
     * @param $action
     * @param null $form
     * @param null $additional
     *
     * @return PaymentOption
     * @throws SmartyException
     */
    private function getPaymentOption($title, $logo, $action, $form = null, $additional = null): PaymentOption
    {
        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();

        $paymentOption->setModuleName($this->module->name)
                              ->setCallToActionText($title)
                              ->setLogo($logo)
                              ->setAdditionalInformation($additional)
                              ->setAction($action);

        if ($form) {
            $paymentOption->setForm($this->context->smarty->fetch($form));
        }

        return $paymentOption;
    }

    private function getMessage($key)
    {
        return $this->module->getTranslationsArray()[$key];
    }
}
