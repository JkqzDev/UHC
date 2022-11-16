<?php

declare(strict_types=1);

namespace juqn\twitter\method;

use juqn\twitter\data\Consumer;
use juqn\twitter\data\Token;
use juqn\twitter\request\Request;
use juqn\twitter\Util;

abstract class SignatureMethod_RSA_SHA1 extends SignatureMethod {
    
	public function get_name(): string {
		return 'RSA-SHA1';
	}

	/**
	 * Up to the SP to implement this lookup of keys. Possible ideas are:
	 * (1) do a lookup in a table of trusted certs keyed off of consumer
	 * (2) fetch via http using a url provided by the requester
	 * (3) some sort of specific discovery code based on request
	 *
	 * Either way should return a string representation of the certificate
	 */
	abstract protected function fetch_public_cert(&$request);

	/**
	 * Up to the SP to implement this lookup of keys. Possible ideas are:
	 * (1) do a lookup in a table of trusted certs keyed off of consumer
	 *
	 * Either way should return a string representation of the certificate
	 */
	abstract protected function fetch_private_cert(&$request);

	public function build_signature(Request $request, Consumer $consumer, ?Token $token): string {
		$base_string = $request->get_signature_base_string();
		$request->base_string = $base_string;

		// Fetch the private key cert based on the request
		$cert = $this->fetch_private_cert($request);

		// Pull the private key ID from the certificate
		$privatekeyid = openssl_get_privatekey($cert);

		// Sign using the key
		$ok = openssl_sign($base_string, $signature, $privatekeyid);

		// Release the key resource
		openssl_free_key($privatekeyid);

		return base64_encode($signature);
	}


	public function check_signature(Request $request, Consumer $consumer, Token $token, string $signature): bool {
		$decoded_sig = base64_decode($signature, true);

		$base_string = $request->get_signature_base_string();

		// Fetch the public key cert based on the request
		$cert = $this->fetch_public_cert($request);

		// Pull the public key ID from the certificate
		$publickeyid = openssl_get_publickey($cert);

		// Check the computed signature against the one passed in the query
		$ok = openssl_verify($base_string, $decoded_sig, $publickeyid);

		// Release the key resource
		openssl_free_key($publickeyid);

		return $ok == 1;
	}
}