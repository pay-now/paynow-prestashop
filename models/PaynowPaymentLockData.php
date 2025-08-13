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

class PaynowPaymentLockData extends ObjectModel
{
    const TABLE = 'paynow_payment_locks';

    const PRIMARY_KEY = 'id';

	const COUNTER_LIMIT = 6;

    public $id;

    public $id_order;

    public $id_cart;

    public $counter;

    public $created_at;

    public $modified_at;

    public static $definition = [
        'table'   => self::TABLE,
        'primary' => self::PRIMARY_KEY,
        'fields'  => [
            self::PRIMARY_KEY => ['type' => self::TYPE_INT],
            'id_order'        => ['type' => self::TYPE_INT, 'required' => false],
            'id_cart'         => ['type' => self::TYPE_INT, 'required' => true],
            'counter'         => ['type' => self::TYPE_INT, 'required' => false],
            'created_at'      => ['type' => self::TYPE_DATE, 'required' => true],
            'modified_at'     => ['type' => self::TYPE_DATE, 'required' => true]
        ]
    ];

    public static function create(
        $id_order,
        $id_cart
    ) {
        $now                    = (new DateTime('now', new DateTimeZone(Configuration::get('PS_TIMEZONE'))))->format('Y-m-d H:i:s');
        $model                  = new PaynowPaymentLockData();
        $model->id_order        = $id_order;
        $model->id_cart         = $id_cart;
		$model->counter     	= 1;
        $model->created_at  	= $now;
        $model->modified_at 	= $now;

        try {
            $result = $model->save(false, false);
            if (!$result) {
                throw new Exception('Locks Model-save() returned false.');
            }
        } catch (Exception $e) {
            PaynowLogger::debug(
                'Can\'t create paynow lock entry',
                [
                    'model' => (array)$model,
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getline(),
                    'DB error' => Db::getInstance()->getMsgError(),
                ]
            );
            throw $e;
        }
    }

    /**
     * @param $order_id
     *
     * @return PrestaShopCollection
     * @throws PrestaShopException
     */
    public static function findByOrderId($order_id)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('id_order', '=', $order_id)
            ->setPageSize(1)
            ->getFirst();
    }

    /**
     * @param $cart_id
     *
     * @return PrestaShopCollection
     * @throws PrestaShopException
     */
    public static function findByCartId($cart_id)
    {
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $queryBuilder = new PrestaShopCollection(self::class);

        return $queryBuilder
            ->where('id_cart', '=', $cart_id)
            ->setPageSize(1)
			->getFirst();
    }

    public static function checkIsCartLocked($id_cart, $id_order = 0): bool
	{
        Db::getInstance(_PS_USE_SQL_SLAVE_)->disableCache();
        $data = PaynowPaymentLockData::findByCartId($id_cart);

        if ($data) {
			if ($data->counter >= self::COUNTER_LIMIT) {
				return false;
			}

			$data->counter++;
			$data->update();

			return true;
        } else {
			self::create($id_order, $id_cart);

            return true;
        }
    }
}
