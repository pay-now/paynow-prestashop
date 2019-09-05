<?php

namespace Paynow\HttpClient;

/**
 * Interface HttpClientInterface
 *
 * @package Paynow\HttpClient
 */
interface HttpClientInterface
{
    public function post($url, array $data);

    public function patch($url, array $data);

    public function get($url);
}
