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
        $total = null
    ) {
        $now                    = (new DateTime())->format('Y-m-d H:i:s');
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
        $model->created_at  = $now;
        $model->modified_at = $now;
        if ($model->add(false)) {
            PaynowLogger::debug(
                'Created paynow data entry {cartId={}, externalId={}}',
                [
                    $id_cart,
                    $external_id
                ]
            );
        } else {
            PaynowLogger::warning(
                'Can\'t create paynow data entry {cartId={}, externalId={}}',
                [
                    $id_cart,
                    $external_id
                ]
            );
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
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('external_id', '=', $external_id)
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
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('order_reference', '=', $order_reference)
            ->orderBy('created_at', 'desc')
            ->getFirst();
    }

    /**
     * @param $id_payment
     *
     * @return false|ObjectModel
     * @throws PrestaShopException
     */
    public static function findLastByPaymentId($id_payment)
    {
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('id_payment', '=', $id_payment)
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
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('id_order', '=', $id_order)
            ->orderBy('created_at', 'desc')
            ->getFirst();
    }

    public static function updateStatus($id_payment, $status)
    {
        $data = PaynowPaymentData::findByPaymentId($id_payment);
        if ($data) {
            $data->status      = $status;
            $data->modified_at = (new DateTime())->format('Y-m-d H:i:s');
            if ($data->update()) {
                PaynowLogger::debug(
                    'Successfully updated payment data {paymentId={}, status={}}',
                    [
                        $id_payment,
                        $status
                    ]
                );
            } else {
                PaynowLogger::warning(
                    'Can\'t update payment data due update error {paymentId={}, status={}}',
                    [
                        $id_payment,
                        $status
                    ]
                );
            }
        } else {
            PaynowLogger::warning(
                'Can\'t update payment data due empty data {paymentId={}, status={}}',
                [
                    $id_payment,
                    $status
                ]
            );
        }
    }

    public static function updateOrderIdAndOrderReferenceByPaymentId(
        $id_order,
        $order_reference,
        $id_payment
    ) {
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
