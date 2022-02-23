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

use Paynow\Client;

class PaynowRefundProcessor
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
     * @param $module_display_name
     */
    public function __construct(Client $client, $module_display_name)
    {
        $this->client = $client;
        $this->module_display_name = $module_display_name;
    }

    /**
     * @param Order $order
     */
    public function processFromOrderSlip(Order $order)
    {
        $orderSlip = $order->getOrderSlipsCollection()
                           ->orderBy('date_upd', 'desc')
                           ->getFirst();
        $amount = $orderSlip->amount + $orderSlip->shipping_cost_amount;
        $filteredPayments = $this->filterPayments(
            $order->getOrderPaymentCollection()->getResults(),
            $amount
        );
        $this->process($order, $filteredPayments, $amount);
    }

    /**
     * @param Order $order
     */
    public function processFromOrderStatusChange(Order $order)
    {
        $amount_to_refund = $order->total_paid;
        if (!Configuration::get('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS')) {
            $amount_to_refund -= $order->total_shipping_tax_incl;
        }
        $filteredPayments = $this->filterPayments(
            $order->getOrderPaymentCollection()->getResults(),
            $amount_to_refund
        );
        $this->process($order, $filteredPayments, $amount_to_refund);
    }

    private function process($order, $payments, $amount)
    {
        if (! empty($payments)) {
            PaynowLogger::info(
                'Processing refund request {orderId={}, orderReference={}}',
                [
                    $order->id,
                    $order->reference
                ]
            );
            $payment = $payments[0];
            $refund_amount = number_format($amount * 100, 0, '', '');
            try {
                PaynowLogger::info(
                    'Found transaction to make a refund {amount={}, orderId={}, orderReference={}, paymentId={}}',
                    [
                        $refund_amount,
                        $order->id,
                        $order->reference,
                        $payment->transaction_id
                    ]
                );
                $response = (new Paynow\Service\Refund($this->client))->create(
                    $payment->transaction_id,
                    uniqid($payment->order_reference, true),
                    $refund_amount
                );
                PaynowLogger::info(
                    'Refund has been created successfully {orderId={}, orderReference={}, refundId={}}',
                    [
                        $payment->id_order,
                        $payment->order_reference,
                        $response->getRefundId()
                    ]
                );
            } catch (Paynow\Exception\PaynowException $exception) {
                foreach ($exception->getErrors() as $error) {
                    PaynowLogger::error(
                        'An error occurred during refund request process {code={}, orderId={}, orderReference={}, paymentId={}, type={}, message={}}',
                        [
                            $exception->getCode(),
                            $payment->id_order,
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

    private function filterPayments($payments, $amount): array
    {
        return array_filter($payments, function ($payment) use ($amount) {
            return $this->module_display_name === $payment->payment_method &&
                   $amount <= $payment->amount;
        });
    }
}
