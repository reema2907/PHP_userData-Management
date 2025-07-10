<?php

namespace App\Service;

use League\OAuth1\Client\Server\Twitter;

class TwitterOAuthService
{
    private Twitter $server;

    public function __construct(string $clientId, string $clientSecret, string $callbackUrl)
    {
        $this->server = new Twitter([
            'identifier' => $clientId,
            'secret' => $clientSecret,
            'callback_uri' => $callbackUrl,
        ]);
    }

    public function getServer(): Twitter
    {
        return $this->server;
    }
}
