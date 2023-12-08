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
	/**
	 * @var array
	 */
	private $translations;

    public function initContent()
    {
		$this->translations = $this->module->getTranslationsArray();
        parent::initContent();

        $this->removeSavedInstrument();
    }

    private function removeSavedInstrument()
    {
        $response = [
            'success' => false
        ];

        if ($this->isTokenValid()) {
            try {
                $savedInstrumentToken = Tools::getValue('savedInstrumentToken');

                (new PaynowSavedInstrumentHelper($this->context, $this->module))->remove($savedInstrumentToken);

                $response = [
                    'success' => true,
                ];
            } catch (Exception $e) {
                $response['error'] = $this->translations['An error occurred while deleting the saved card.'];
                PaynowLogger::error(
                    'An error occurred during saved instrument removal {code={}, message={}}',
                    [
                        $e->getCode(),
                        $e->getMessage()
                    ]
                );
            }
        }

        $this->ajaxRender(json_encode($response));
        exit;
    }
}
