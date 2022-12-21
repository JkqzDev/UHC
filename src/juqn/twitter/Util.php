<?php

declare(strict_types=1);

namespace juqn\twitter;

final class Util {

    public static function parse_parameters(string $input): array {
        if (!isset($input) || !$input) {
            return [];
        }

        $pairs = explode('&', $input);

        $parsed_parameters = [];
        foreach ($pairs as $pair) {
            $split = explode('=', $pair, 2);
            $parameter = self::decode_rfc3986($split[0]);
            $value = isset($split[1]) ? self::decode_rfc3986($split[1]) : '';

            if (isset($parsed_parameters[$parameter])) {
                if (is_scalar($parsed_parameters[$parameter])) {
                    $parsed_parameters[$parameter] = [$parsed_parameters[$parameter]];
                }
                $parsed_parameters[$parameter][] = $value;
            } else {
                $parsed_parameters[$parameter] = $value;
            }
        }
        return $parsed_parameters;
    }

    public static function decode_rfc3986(string $string): string {
        return urldecode($string);
    }

    public static function build_http_query(array $params): string {
        if (!$params) {
            return '';
        }

        $keys = self::urlencoded_rfc3986(array_keys($params));
        $values = self::urlencoded_rfc3986(array_values($params));
        $params = array_combine($keys, $values);

        uksort($params, 'strcmp');

        $pairs = [];
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                sort($value, SORT_STRING);
                foreach ($value as $duplicate_value) {
                    $pairs[] = $parameter . '=' . $duplicate_value;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }
        return implode('&', $pairs);
    }

    public static function urlencoded_rfc3986($input): array|string {
        if (is_array($input)) {
            return array_map([self::class, 'urlencoded_rfc3986'], $input);
        } elseif (is_scalar($input)) {
            return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode((string) $input)));
        } else {
            return '';
        }
    }
}