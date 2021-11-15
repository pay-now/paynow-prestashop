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
     * @return Notices|null
     */
    public function getNotices(?string $locale)
    {
        try {
            PaynowLogger::info("Retrieving GDPR notices");
            $gdpr_client = new Paynow\Service\DataProcessing($this->client);
            return $gdpr_client->getNotices($locale);
        } catch (\Paynow\Exception\PaynowException $exception) {
            PaynowLogger::error($exception->getMessage());
        }

        return null;
    }
}