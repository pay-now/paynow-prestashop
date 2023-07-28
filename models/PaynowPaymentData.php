<?php

use Paynow\Model\Payment\Status;

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

class PaynowPaymentData extends ObjectModel
{
    const TABLE = 'paynow_payments';

    const PRIMARY_KEY = 'id';

    public $id;

    public $id_order;

    public $id_cart;

    public $id_payment;

    public $order_reference;

    public $external_id;

    public $status;

    public $total;

    public $locked;

    public $active;

    public $counter;

    public $sent_at;

    public $created_at;

    public $modified_at;

    public static $definition = [
        'table'   => self::TABLE,
        'primary' => self::PRIMARY_KEY,
        'fields'  => [
            self::PRIMARY_KEY => ['type' => self::TYPE_INT],
            'id_order'        => ['type' => self::TYPE_INT, 'required' => false],
            'id_cart'         => ['type' => self::TYPE_INT, 'required' => true],
            'id_payment'      => ['type' => self::TYPE_STRING, 'required' => true],
            'order_reference' => ['type' => self::TYPE_STRING, 'required' => false],
            'external_id'     => ['type' => self::TYPE_STRING, 'required' => true],
            'status'          => ['type' => self::TYPE_STRING, 'required' => true],
            'total'           => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => false],
            'locked'          => ['type' => self::TYPE_INT, 'required' => false],
            'active'          => ['type' => self::TYPE_INT, 'required' => false],
            'counter'         => ['type' => self::TYPE_INT, 'required' => false],
            'sent_at'         => ['type' => self::TYPE_DATE, 'required' => true],
            'created_at'      => ['type' => self::TYPE_DATE, 'required' => true],
            'modified_at'     => ['type' => self::TYPE_DATE, 'required' => true]
        ]
    ];

    public static function create(
        $id_payment,
        $status,
        $id_order,
        $id_cart,
        $order_reference,
        $external_id,
        $total = null,
        $sent_at = null
    ) {
        $now                    = (new DateTime('now', new DateTimeZone(Configuration::get('PS_TIMEZONE'))))->format('Y-m-d H:i:s');
        if (!$sent_at) {
            $sent_at = $now;
        }
        $model                  = new PaynowPaymentData();
        $model->id_order        = $id_order;
        $model->id_cart         = $id_cart;
        $model->id_payment      = $id_payment;
        $model->order_reference = $order_reference;
        $model->external_id     = $external_id;
        $model->status          = $status;
        if ($total) {
            $model->total = $total;
        }
        $model->locked      = 0;
        $model->active      = 1;
        $model->counter     = 0;
        $model->created_at  = $now;
        $model->modified_at = $now;
        $model->sent_at     = $sent_at;


        try {
            $result = $model->save(false, false);
            if (!$result) {
                throw new Exception('Model-save() returned false.');
            }
            if ($status == Status::STATUS_NEW) {
                PaynowLogger::debug('Deactivating all payments.', ['external_id' => $external_id]);
                Db::getInstance()->update(
                    self::TABLE,
                    ['active' => 0],
                    'external_id = "' . pSQL($external_id) . '" AND id != "' . $model->id . '"'
                );
            }
            PaynowLogger::debug(
                'Created new paynow data entry',
                [
                    'id_order'        => $model->id_order,
                    'id_cart'         => $model->id_cart,
                    'id_payment'      => $model->id_payment,
                    'order_reference' => $model->order_reference,
                    'external_id'     => $model->external_id,
                    'status'          => $model->status,
                    'sent_at'         => $model->sent_at,
                ]
            );
        } catch (Exception $e) {
            PaynowLogger::debug(
                'Can\'t create paynow data entry',
                [
                    'model' => (array)$model,
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getline(),
                ]
            );
            throw $e;
        }

    }

    /**
     * @param $id_payment
     *
     * @return false|PaynowPaymentData
     * @throws PrestaShopException
     */
    public static function findByPaymentId($id_payment)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('id_payment', '=', $id_payment)
            ->setPageSize(1)
            ->getFirst();
    }

    /**
     * @param $external_id
     *
     * @return PrestaShopCollection
     * @throws PrestaShopException
     */
    public static function findAllByExternalId($external_id)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('external_id', '=', $external_id)
            ->orderBy('created_at', 'desc')
            ->getAll();
    }

    /**
     * @param $external_id
     * @param $payment_id
     *
     * @return PrestaShopCollection
     * @throws PrestaShopException
     */
    public static function findAllByExternalIdAndPaymentId($external_id, $payment_id)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('external_id', '=', $external_id)
            ->where('id_payment', '=', $payment_id)
            ->orderBy('created_at', 'desc')
            ->getAll();
    }

    /**
     * @param $external_id
     *
     * @return false|ObjectModel
     * @throws PrestaShopException
     */
    public static function findLastByExternalId($external_id)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('external_id', '=', $external_id)
            ->orderBy('created_at', 'desc')
            ->getFirst();
    }

    /**
     * @param $order_reference
     *
     * @return false|ObjectModel
     * @throws PrestaShopException
     */
    public static function findLastByOrderReference($order_reference)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('order_reference', '=', $order_reference)
            ->orderBy('created_at', 'desc')
            ->getFirst();
    }

    /**
     * @param $id_cart
     *
     * @return false|ObjectModel
     * @throws PrestaShopException
     */
    public static function findLastByCartId($id_cart)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('id_cart', '=', $id_cart)
            ->orderBy('created_at', 'desc')
            ->getFirst();
    }

    /**
     * @param $id_order
     *
     * @return false|ObjectModel
     * @throws PrestaShopException
     */
    public static function findLastByOrderId($id_order)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('id_order', '=', $id_order)
            ->orderBy('created_at', 'desc')
            ->getFirst();
    }

    public static function updateOrderIdAndOrderReferenceByPaymentId(
        $id_order,
        $order_reference,
        $id_payment
    ) {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $data = PaynowPaymentData::findByPaymentId($id_payment);
        if ($data) {
            $data->id_order        = $id_order;
            $data->order_reference = $order_reference;
            $data->modified_at     = (new DateTime())->format('Y-m-d H:i:s');
            if ($data->update()) {
                PaynowLogger::debug(
                    'Successfully updated payment data {orderId={}, orderReference={}, paymentId={}}',
                    [
                        $id_order,
                        $order_reference,
                        $id_payment
                    ]
                );
            } else {
                PaynowLogger::warning(
                    'Can\'t update payment data due update error {orderId={}, orderReference={}, paymentId={}}',
                    [
                        $id_order,
                        $order_reference,
                        $id_payment
                    ]
                );
            }
        } else {
            PaynowLogger::warning(
                'Can\'t update payment data due empty data {orderId={}, orderReference={}, paymentId={}}',
                [
                    $id_order,
                    $order_reference,
                    $id_payment
                ]
            );
        }
    }

    /**
     * @throws PrestaShopException
     */
    public static function updateByExternalId(
        $external_id,
        $data_to_update
    )
    {
        $context = array_merge(['external_id' => $external_id], $data_to_update);
        $payment = self::getActiveByExternalId($external_id);
        if (!$payment) {
            PaynowLogger::warning('Can\'t update payment data - payment not found', $context);
            return;
        }
        foreach ($data_to_update as $key => $value) {
            $payment->{$key} = $value;
        }
        $payment->modified_at     = (new DateTime())->format('Y-m-d H:i:s');
        if ($payment->update()) {
            PaynowLogger::debug('Successfully updated payment data', $context);
        } else {
            PaynowLogger::warning('Can\'t update payment data due update error', $context);
        }
    }

    /**
     * @throws PrestaShopException
     */
    public static function updatePaymentIdByExternalId(
        $external_id,
        $payment_id
    )
    {
        self::updateByExternalId($external_id, ['id_payment' => $payment_id]);
    }

    /**
     * @throws PrestaShopException
     */
    public static function getActiveByExternalId($external_id, $createNew = false, $payment_id = null) {
        $payments = self::findAllByExternalId($external_id)->getResults();
        foreach($payments as $payment) {
            if ($payment->active == '1') {
                return $payment;
            }
        }
        $payment = reset($payments);

        // when creating a new one, it is necessary to check whether there is an order/cart matching the $external_id
        // if one exists, based on its info, we create a payment object
        if ($createNew && !$payment) {
            // order check
            $order = Order::getByReference($external_id);
            if (Validate::isLoadedObject($order)) {
                PaynowPaymentData::create(
                    $payment_id,
                    Paynow\Model\Payment\Status::STATUS_NEW,
                    $order->id,
                    $order->id_cart,
                    $order->reference,
                    $order->reference,
                    $order->total_paid
                );
                return self::getActiveByExternalId($external_id);
            }

            // cart check
            [$cart_id, $random] = explode('_', $external_id);
            $cart = new Cart($cart_id);
            if (Validate::isLoadedObject($cart)) {
                PaynowPaymentData::create(
                    $payment_id,
                    Paynow\Model\Payment\Status::STATUS_NEW,
                    null,
                    $cart->id,
                    null,
                    $external_id,
                    $cart->getOrderTotal()
                );
                return self::getActiveByExternalId($external_id);
            }
        }
        return $payment;
    }

    public static function setOptimisticLockByExternalId($external_id)
    {
        $data = PaynowPaymentData::findLastByExternalId($external_id);
        if ($data) {
            $data->locked      = 1;
            $data->modified_at = (new DateTime())->format('Y-m-d H:i:s');
            if ($data->update()) {
                PaynowLogger::debug(
                    'Successfully set up optimistic lock on paynow data {externalId={}}',
                    [
                        $external_id
                    ]
                );
            } else {
                PaynowLogger::warning(
                    'Can\'t set optimistic lock due update error {externalId={}}',
                    [
                        $external_id
                    ]
                );
            }
        } else {
            PaynowLogger::warning(
                'Can\'t set optimistic lock due empty payment data {externalId={}}',
                [
                    $external_id
                ]
            );
        }
    }

    public static function setOptimisticLockByCartId($cart_id)
    {
        $data = PaynowPaymentData::findLastByCartId($cart_id);
        if ($data) {
            $data->locked      = 1;
            $data->modified_at = (new DateTime())->format('Y-m-d H:i:s');
            if ($data->update()) {
                PaynowLogger::debug(
                    'Successfully set up optimistic lock on paynow data {cartId={}}',
                    [
                        $cart_id
                    ]
                );
            } else {
                PaynowLogger::warning(
                    'Can\'t set optimistic lock due update error {cartId={}}',
                    [
                        $cart_id
                    ]
                );
            }
        } else {
            PaynowLogger::warning(
                'Can\'t set optimistic lock due empty payment data {cartId={}}',
                [
                    $cart_id
                ]
            );
        }
    }

    public static function unsetOptimisticLockByExternalId($external_id)
    {
        $data = PaynowPaymentData::findLastByExternalId($external_id);
        if ($data) {
            $data->locked      = 0;
            $data->modified_at = (new DateTime())->format('Y-m-d H:i:s');
            if ($data->update()) {
                PaynowLogger::debug(
                    'Successfully unset optimistic lock on paynow data {externalId={}}',
                    [
                        $external_id
                    ]
                );
            } else {
                PaynowLogger::warning(
                    'Can\'t unset optimistic lock due update error {externalId={}}',
                    [
                        $external_id
                    ]
                );
            }
        } else {
            PaynowLogger::warning(
                'Can\'t unset optimistic lock due empty payment data {externalId={}}',
                [
                    $external_id
                ]
            );
        }
    }

    public static function unsetOptimisticLockByCartId($cart_id)
    {
        $data = PaynowPaymentData::findLastByCartId($cart_id);
        if ($data) {
            $data->locked      = 0;
            $data->modified_at = (new DateTime())->format('Y-m-d H:i:s');
            if ($data->update()) {
                PaynowLogger::debug(
                    'Successfully unset optimistic lock on paynow data {cartId={}}',
                    [
                        $cart_id
                    ]
                );
            } else {
                PaynowLogger::warning(
                    'Can\'t unset optimistic lock due update error {cartId={}}',
                    [
                        $cart_id
                    ]
                );
            }
        } else {
            PaynowLogger::warning(
                'Can\'t unset optimistic lock due empty payment data {cartId={}}',
                [
                    $cart_id
                ]
            );
        }
    }
}
