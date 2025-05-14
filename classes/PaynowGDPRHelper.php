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

use Paynow\Client;
use Paynow\Exception\PaynowException;

/**
 * GdprHelper
 */
class PaynowGDPRHelper
{
	// in seconds
	private const OPTION_VALIDITY_TIME = 86400;

    private $cart;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     * @param $cart
     */
    public function __construct(Client $client, $cart)
    {
        $this->client = $client;
        $this->cart = $cart;
    }

    /**
     * @param string|null $locale
     *
     * @return array
     */
    public function getNotices(?string $locale)
    {
        $configurationId = 'PAYNOW_' . ( $this->isSandbox() ? 'SANDBOX_' : '') .'GDPR_' . $this->cleanLocale($locale);
        $configurationOption = Configuration::get($configurationId);

        if ( !$configurationOption || !$this->isValid( $locale ) ) {
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
                Configuration::updateValue($this->getOptionValidityKey($locale), time() + self::OPTION_VALIDITY_TIME);
                $configurationOption = Configuration::get($configurationId);
            }
        }

        $notices      = [];
        $unserialized = unserialize($configurationOption);
        if ($unserialized) {
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

    private function retrieve($locale): ?array
    {
        try {
            PaynowLogger::info("Retrieving GDPR notices");
            $idempotencyKey = PaynowKeysGenerator::generateIdempotencyKey(PaynowKeysGenerator::generateExternalIdByCart($this->cart));
            return (new Paynow\Service\DataProcessing($this->client))->getNotices($locale, $idempotencyKey)->getAll();
        } catch (PaynowException $exception) {
            PaynowLogger::error(
                'An error occurred during GDPR notices retrieve {code={}, message={}}',
                [
                    $exception->getCode(),
                    $exception->getPrevious()->getMessage()
                ]
            );
        }

        return null;
    }

    private function isSandbox()
    {
        return (int)Configuration::get('PAYNOW_SANDBOX_ENABLED') === 1;
    }

    private function cleanLocale($locale)
    {
        return Tools::strtoupper(str_replace('-', '_', $locale));
    }

	private function isValid( ?string $locale ): bool
	{
		$optionValidity = Configuration::get($this->getOptionValidityKey($locale));

		if ( empty( $optionValidity ) ) {
			return false;
		}

		return time() < (int) $optionValidity;
	}

	private function getOptionValidityKey( ?string $locale ): string
	{
		return 'PAYNOW_'.( $this->isSandbox() ? 'SANDBOX_' : '' ).'GDPR_VALIDITY_' . $this->cleanLocale($locale);
	}
}
