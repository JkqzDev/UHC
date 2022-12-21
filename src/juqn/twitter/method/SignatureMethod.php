<?php

declare(strict_types=1);

namespace juqn\twitter\method;

use juqn\twitter\data\Consumer;
use juqn\twitter\data\Token;
use juqn\twitter\request\Request;

abstract class SignatureMethod {

    abstract public function get_name(): string;

    abstract public function build_signature(Request $request, Consumer $consumer, ?Token $token): string;
}