<?php

use Paynow\Model\PaymentMethods\PaymentMethod;
use Paynow\Response\DataProcessing\Notices;
use Paynow\Response\PaymentMethods\PaymentMethods;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (! defined('_PS_VERSION_')) {
    exit;
}

class PaymentOptions
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

    public function execute()
    {
        $payment_options = [];

        if (Configuration::get('PAYNOW_SEPARATE_PAYMENT_METHODS')) {
            if (! empty($this->payment_methods)) {
                $list = [];
                $this->context->smarty->assign([
                    'action' => $this->context->link->getModuleLink($this->module->name, 'payment', [], true),
                    'data_processing_notices' => $this->data_processing_notices ? $this->data_processing_notices->getAll() : null
                ]);

                /** @var PaymentMethod $payment_method */
                foreach ($this->payment_methods->getAll() as $payment_method) {
                    if (! isset($list[$payment_method->getType()])) {
                        if (Paynow\Model\PaymentMethods\Type::PBL == $payment_method->getType()) {
                            $this->context->smarty->assign([
                                'paynowPbls' => $this->payment_methods->getOnlyPbls(),
                            ]);
                            array_push($payment_options, $this->getPaymentOption(
                                $this->module->getPaymentMethodTitle($payment_method->getType()),
                                $this->module->getLogo(),
                                $this->context->link->getModuleLink($this->module->name, 'payment', [], true),
                                'module:paynow/views/templates/front/1.7/payment_form.tpl'
                            ));
                        } else {
                            if (Paynow\Model\PaymentMethods\Type::BLIK == $payment_method->getType()) {
                                $this->context->smarty->assign([
                                    'action_blik' => $this->context->link->getModuleLink(
                                        $this->module->name,
                                        'payment',
                                        ['paymentMethodId' => $payment_method->getId()],
                                        true
                                    )
                                ]);
                            }

                            array_push($payment_options, $this->getPaymentOption(
                                $this->module->getPaymentMethodTitle($payment_method->getType()),
                                $payment_method->getImage(),
                                $this->context->link->getModuleLink(
                                    $this->module->name,
                                    'payment',
                                    ['paymentMethodId' => $payment_method->getId()],
                                    true
                                ),
                                $this->isWhiteLabelEnabled($payment_method->getType(), $payment_method) ? 'module:paynow/views/templates/front/1.7/blik_payment_form.tpl' : null
                            ));
                        }
                        $list[$payment_method->getType()] = $payment_method->getId();
                    }
                }
            }
        } else {
            array_push($payment_options, $this->getPaymentOption(
                $this->module->getCallToActionText(),
                $this->module->getLogo(),
                $this->context->link->getModuleLink($this->module->name, 'payment', [], true)
            ));
        }

        return $payment_options;
    }

    /**
     * @param string $payment_method_type
     * @param PaymentMethod $payment_method
     *
     * @return bool
     */
    private function isWhiteLabelEnabled(string $payment_method_type, PaymentMethod $payment_method): bool
    {
        return $payment_method_type == $payment_method->getType() && Paynow\Model\PaymentMethods\AuthorizationType::CODE == $payment_method->getAuthorizationType();
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
}
