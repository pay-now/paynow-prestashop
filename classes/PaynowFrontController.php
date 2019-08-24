<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @copyright mBank S.A.
 * @license   MIT License
 */

class PaynowFrontController extends ModuleFrontController
{
    /**
     * @var PaynowApiClient
     */
    protected $apiClient;

    public function __construct()
    {
        parent::__construct();

        $user_agent = 'Prestashop-'._PS_VERSION_.'/Plugin-'.$this->module->version;
        $this->apiClient = new PaynowApiClient($this->module->getApiUrl(), $this->module->getApiKey(), $this->module->getSignatureKey(), $user_agent);
    }

    public function renderTemplate($template_name)
    {
        if (version_compare(_PS_VERSION_, '1.7', 'gt')) {
            $template_name = 'module:paynow/views/templates/front/1.7/' . $template_name;
        }

        $this->setTemplate($template_name);
    }
}
