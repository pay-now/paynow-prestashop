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

class PaynowRemoveSavedInstrumentModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->removeSavedInstrument();
    }

    private function removeSavedInstrument()
    {
        $response = [
            'success' => false
        ];

        if ($this->isTokenValid()) {
            $savedInstrumentToken = Tools::getValue('savedInstrumentToken');

            $response = [
                'success' => true,
                'token' => $savedInstrumentToken,
            ];
        }

        $this->ajaxRender(json_encode($response));
        exit;
    }
}
