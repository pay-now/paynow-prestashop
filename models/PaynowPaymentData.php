<?php

if (! defined('_PS_VERSION_')) {
    exit;
}

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

    public $created_at;

    public $modified_at;

    public static $definition = [
        'table'   => self::TABLE,
        'primary' => self::PRIMARY_KEY,
        'fields'  => [
            self::PRIMARY_KEY => ['type' => self::TYPE_INT],
            'id_order'        => ['type' => self::TYPE_INT, 'required' => true],
            'id_cart'         => ['type' => self::TYPE_INT, 'required' => true],
            'id_payment'      => ['type' => self::TYPE_STRING, 'required' => true],
            'order_reference' => ['type' => self::TYPE_STRING, 'required' => true],
            'external_id'     => ['type' => self::TYPE_STRING, 'required' => true],
            'status'          => ['type' => self::TYPE_STRING, 'required' => true],
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
        $external_id
    ) {
        $now      = (new DateTime())->format('Y-m-d H:i:s');

        $model                  = new PaynowPaymentData();
        $model->id_order        = $id_order;
        $model->id_cart         = $id_cart;
        $model->id_payment      = $id_payment;
        $model->order_reference = $order_reference;
        $model->external_id     = $external_id;
        $model->status          = $status;
        $model->created_at      = $now;
        $model->modified_at     = $now;
        $model->add(false);
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
            ->where('order_reference', '=', $external_id)
            ->orderBy('created_at', 'desc')
            ->getAll();
    }

    /**
     * @param $id_order
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
        $data->status = $status;
        $data->modified_at = (new DateTime())->format('Y-m-d H:i:s');
        $data->update();
    }
}
