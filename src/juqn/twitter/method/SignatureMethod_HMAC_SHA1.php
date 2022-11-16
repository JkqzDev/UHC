<?php

declare(strict_types=1);

namespace juqn\twitter\method;

use juqn\twitter\data\Consumer;
use juqn\twitter\data\Token;
use juqn\twitter\request\Request;
use juqn\twitter\Util;

class SignatureMethod_HMAC_SHA1 extends SignatureMethod {
    
    public function get_name(): string {
        return 'HMAC-SHA1';
	}
	
	public function build_signature(Request $request, Consumer $consumer, ?Token $token): string {
		$base_string = $request->get_signature_base_string();
		$request->base_string = $base_string;

		$key_parts = [
			$consumer->secret,
			$token ? $token->secret : '',
		];

		$key_parts = Util::urlencode_rfc3986($key_parts);
		$key = implode('&', $key_parts);

		return base64_encode(hash_hmac('sha1', $base_string, $key, true));
	}
}