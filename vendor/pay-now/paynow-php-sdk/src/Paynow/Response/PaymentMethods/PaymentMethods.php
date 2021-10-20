<?php

namespace Paynow\Response\PaymentMethods;

use Paynow\Model\PaymentMethods\PaymentMethod;
use Paynow\Model\PaymentMethods\Type;

class PaymentMethods
{
    /**
     * @var PaymentMethod[]
     */
    private $list;

    public function __construct($body)
    {
        if (! empty($body)) {
            foreach ($body as $group) {
                if (! empty($group->paymentMethods)) {
                    foreach ($group->paymentMethods as $item) {
                        $this->list[] = new PaymentMethod(
                            $item->id,
                            $group->type,
                            $item->name,
                            $item->description,
                            $item->image,
                            $item->status
                        );
                    }
                }
            }
        }
    }

    /**
     * Retrieve all available payment methods
     *
     * @return PaymentMethod[]
     */
    public function getAll()
    {
        return $this->list;
    }

    /**
     * Retrieve only Blik payment methods
     *
     * @return PaymentMethod[]
     */
    public function getOnlyBlik()
    {
        $blikPaymentMethods = [];
        foreach ($this->list as $item) {
            if (Type::BLIK === $item->getType()) {
                $blikPaymentMethods[] = $item;
            }
        }

        return $blikPaymentMethods;
    }

    /**
     * Retrieve only Card payment methods
     *
     * @return PaymentMethod[]
     */
    public function getOnlyCards()
    {
        $cardPaymentMethods = [];
        foreach ($this->list as $item) {
            if (Type::CARD === $item->getType()) {
                $cardPaymentMethods[] = $item;
            }
        }

        return $cardPaymentMethods;
    }

    /**
     * Retrieve only GooglePay payment method
     *
     * @return PaymentMethod[]
     */
    public function getOnlyGooglePay()
    {
        $cardPaymentMethods = [];
        foreach ($this->list as $item) {
            if (Type::GOOGLE_PAY === $item->getType()) {
                $cardPaymentMethods[] = $item;
            }
        }

        return $cardPaymentMethods;
    }

    /**
     * Retrieve only Pbl payment methods
     *
     * @return PaymentMethod[]
     */
    public function getOnlyPbls()
    {
        $pblPaymentMethods = [];
        foreach ($this->list as $item) {
            if (Type::PBL === $item->getType()) {
                $pblPaymentMethods[] = $item;
            }
        }

        return $pblPaymentMethods;
    }
}
