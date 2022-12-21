<?php

declare(strict_types=1);

namespace juqn\twitter\request;

use Exception;
use juqn\twitter\data\Consumer;
use juqn\twitter\data\Token;
use juqn\twitter\method\SignatureMethod;
use juqn\twitter\Util;

final class Request {

    public static string $version = '1.0';
    public string $base_string;

    public function __construct(private string $http_method, private string $http_url, private ?array $parameters = null) {
        $parameters = $parameters ?: [];
        $parameters = array_merge(Util::parse_parameters((string) parse_url($http_url, PHP_URL_QUERY)), $parameters);
        $this->parameters = $parameters;
    }

    public static function from_consumer_and_token(
        Consumer $consumer,
        ?Token   $token,
        string   $http_method,
        string   $http_url,
        array    $parameters = null
    ): self {
        $parameters = $parameters ?: [];
        $defaults = [
            'oauth_version' => self::$version,
            'oauth_nonce' => self::generate_nonce(),
            'oauth_timestamp' => self::generate_timestamp(),
            'oauth_consumer_key' => $consumer->getKey(),
        ];
        if ($token) {
            $defaults['oauth_token'] = $token->getKey();
        }
        $parameters = array_merge($defaults, $parameters);
        return new self($http_method, $http_url, $parameters);
    }

    private static function generate_nonce(): string {
        $mt = microtime();
        $rand = mt_rand();

        return md5($mt . $rand);
    }

    private static function generate_timestamp(): int {
        return time();
    }

    public function get_signature_base_string(): string {
        $parts = [
            $this->get_normalized_http_method(),
            $this->get_normalized_http_url(),
            $this->get_singable_parameters(),
        ];
        $parts = Util::urlencoded_rfc3986($parts);
        return implode('&', $parts);
    }

    public function get_normalized_http_method(): string {
        return strtoupper($this->http_method);
    }

    public function get_normalized_http_url(): string {
        $parts = parse_url($this->http_url);

        $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
        $port = (isset($parts['port']))
            ? $parts['port']
            : (($scheme == 'https') ? '443' : '80');
        $host = (isset($parts['host'])) ? $parts['host'] : '';
        $path = (isset($parts['path'])) ? $parts['path'] : '';

        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }
        return "$scheme://$host$path";
    }

    public function get_singable_parameters(): string {
        $params = $this->parameters;

        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return Util::build_http_query($params);
    }

    /**
     * @throws Exception
     */
    public function to_header(string $realm = null): string {
        $first = true;
        if ($realm) {
            $out = 'Authorization: OAuth realm="' . Util::urlencoded_rfc3986($realm) . '"';
            $first = false;
        } else {
            $out = 'Authorization: OAuth';
        }

        foreach ($this->parameters as $k => $v) {
            if (!str_starts_with($k, 'oauth')) {
                continue;
            }
            if (is_array($v)) {
                throw new Exception('Arrays not supported in headers');
            }
            $out .= $first ? ' ' : ',';
            $out .= Util::urlencoded_rfc3986($k) . '="' . Util::urlencoded_rfc3986($v) . '"';
            $first = false;
        }
        return $out;
    }

    public function __toString(): string {
        return $this->to_url();
    }

    public function to_url(): string {
        $post_data = $this->to_postdate();
        $out = $this->get_normalized_http_url();
        if ($post_data) {
            $out .= '?' . $post_data;
        }
        return $out;
    }

    public function to_postdate(): string {
        return Util::build_http_query($this->parameters);
    }

    public function sign_request(SignatureMethod $signature_method, Consumer $consumer, ?Token $token): void {
        $this->set_parameter(
            'oauth_signature_method',
            $signature_method->get_name(),
            false
        );
        $signature = $this->build_signature($signature_method, $consumer, $token);
        $this->set_parameter('oauth_signature', $signature, false);
    }

    public function set_parameter(string $name, $value, bool $allow_duplicates = true): void {
        if ($allow_duplicates && isset($this->parameters[$name])) {
            if (is_scalar($this->parameters[$name])) {
                $this->parameters[$name] = [$this->parameters[$name]];
            }

            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    public function build_signature(SignatureMethod $signature_method, Consumer $consumer, ?Token $token): string {
        return $signature_method->build_signature($this, $consumer, $token);
    }
}