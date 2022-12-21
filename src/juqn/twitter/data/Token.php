<?php

declare(strict_types=1);

namespace juqn\twitter\data;

use juqn\twitter\Util;

class Token {


    public function __construct(private string $key, private string $secret) {}

    public function getKey(): string {
        return $this->key;
    }

    public function getSecret(): string {
        return $this->secret;
    }

    public function __toString(): string {
        return $this->to_string();
    }

    public function to_string(): string {
        return 'oauth_token=' .
            Util::urlencoded_rfc3986($this->key) .
            '&oauth_token_secret=' .
            Util::urlencoded_rfc3986($this->secret);
    }
}