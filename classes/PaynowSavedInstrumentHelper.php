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

use Paynow\Exception\ConfigurationException;
use Paynow\Exception\PaynowException;
use Paynow\Service\Payment;

/**
 * Class PaynowSavedInstrumentHelper
 */
class PaynowSavedInstrumentHelper
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Paynow
     */
    private $module;

    /**
     * @var Payment
     */
    private $payment_client;

    /**
     * @param Context $context
     * @param $module
     * @throws ConfigurationException
     */
    public function __construct(Context $context, $module)
    {
        $this->context = $context;
        $this->module = $module;
        $this->payment_client = new Paynow\Service\Payment($module->getPaynowClient());
    }

    /**
     * @param $token
     *
     * @return void
     */
    public function remove($token): void
    {
        try {
            $idempotencyKey = PaynowKeysGenerator::generateIdempotencyKey(PaynowKeysGenerator::generateExternalIdByCart($this->context->cart));
            $buyerExternalId = PaynowKeysGenerator::generateBuyerExternalId($this->context->cart->id_customer, $this->module);

            $this->payment_client->removeSavedInstrument($buyerExternalId, $token, $idempotencyKey);
        } catch (PaynowException $exception) {
            PaynowLogger::error(
                'An error occurred during saved instrument removal {code={}, message={}, errors={}, m={}}',
                [
                    $exception->getCode(),
                    $exception->getPrevious()->getMessage(),
                    $exception->getErrors(),
                    $exception->getMessage()
                ]
            );
        }
    }
}
