<?php

use Paynow\Client;

class RefundProcessor
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $module_display_name;

    /**
     * @param Client $client
     */
    public function __construct(Client $client, $module_display_name)
    {
        $this->client = $client;
        $this->module_display_name = $module_display_name;
    }

    public function process($order_reference, array $payments, $amount)
    {
        if (!empty($payments)) {
            foreach ($payments as $payment) {
                if ($this->module_display_name != $payment->payment_method || $payment->amount < $amount) {
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
                    $refund = new Paynow\Service\Refund($this->client);
                    $response = $refund->create(
                        $payment->transaction_id,
                        uniqid($payment->order_reference, true),
                        $refund_amount
                    );
                    PaynowLogger::info(
                        'Refund has been created successfully {orderReference={}, refundId={}}',
                        [
                            $payment->order_reference,
                            $response->getRefundId()
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
}