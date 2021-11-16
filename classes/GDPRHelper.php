<?php

use Paynow\Client;
use Paynow\Response\DataProcessing\Notices;

/**
 * GdprHelper
 */
class GDPRHelper
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string|null $locale
     *
     * @return array
     */
    public function getNotices(?string $locale)
    {
        $configurationId = 'PAYNOW_'.(int)Configuration::get('PAYNOW_SANDBOX_ENABLED') == 1 ? 'SANDBOX_' : ''.'GDPR_' . Tools::strtoupper(str_replace('-', '_', $locale));
        $configurationOption = Configuration::get($configurationId);

        if ( ! $configurationOption) {
            $gdpr_notices = $this->retrieve($locale);

            if ($gdpr_notices) {
                $notices      = [];
                foreach ($gdpr_notices as $notice) {
                    array_push($notices, [
                        'title'   => base64_encode($notice->getTitle()),
                        'content' => base64_encode($notice->getContent()),
                        'locale'  => $notice->getLocale()
                    ]);
                }
                Configuration::updateValue($configurationId, serialize($notices));
                $configurationOption = Configuration::get($configurationId);
            }
        }

        $notices      = [];
        $unserialized = unserialize($configurationOption);
        if ($unserialized){
            foreach ($unserialized as $notice) {
                array_push($notices, [
                    'title' => base64_decode($notice['title']),
                    'content' => base64_decode($notice['content']),
                    'locale' => $notice['locale']
                ]);
            }
        }

        return $notices;
    }

    private function retrieve($locale) {
        try {
            PaynowLogger::info("Retrieving GDPR notices");
            $gdpr_client = new Paynow\Service\DataProcessing($this->client);
            return $gdpr_client->getNotices($locale)->getAll();
        } catch (\Paynow\Exception\PaynowException $exception) {
            PaynowLogger::error($exception->getMessage());
        }

        return null;
    }
}