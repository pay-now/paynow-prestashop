<?php

namespace Paynow\HttpClient;

interface HttpClientInterface
{
    public function post(string $url, array $data, ?string $idempotencyKey = null): ApiResponse;

    public function patch(string $url, array $data): ApiResponse;

    public function get(string $url, ?string $query = null): ApiResponse;
}
