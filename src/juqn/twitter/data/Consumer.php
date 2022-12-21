<?php

declare(strict_types=1);

namespace juqn\twitter\data;

class Consumer {

    public function __construct(private string $key, private string $secret) {}

    public function getKey(): string {
        return $this->key;
    }

    public function getSecret(): string {
        return $this->secret;
    }

    public function __toString(): string {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}