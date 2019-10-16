<?php

namespace Paynow;

interface ConfigurationInterface
{
    public function getApiKey();

    public function getSignatureKey();

    public function getEnvironment();

    public function getUrl();
}
