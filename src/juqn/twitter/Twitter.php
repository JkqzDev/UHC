<?php

declare(strict_types=1);

namespace juqn\twitter;

use CURLFile;
use Exception;
use InvalidArgumentException;
use juqn\twitter\data\Consumer;
use juqn\twitter\data\Token;
use juqn\twitter\method\SignatureMethod_HMAC_SHA1;
use juqn\twitter\request\Request;

class Twitter {

    public const ME = 1;
    public const ME_AND_FRIENDS = 2;
    public const REPLIES = 3;
    public const RETWEETS = 128;

    private const API_URL = 'https://api.twitter.com/1.1/';

    private static string $cacheExpire = '30 minutes';
    public static string $cacheDir;

    public array $httpOptions = [
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_USERAGENT => 'Twitter for PHP',
    ];

    private Consumer $consumer;
    private Token $token;

    /**
     * @throws Exception
     */
    public function __construct(
        string $consumerKey,
        string $consumerSecret,
        string $accessToken = null,
        string $accessTokenSecret = null
    ) {
        if (!extension_loaded('curl')) {
            throw new Exception('PHP extension CURL is not loaded.');
        }
        $this->consumer = new Consumer($consumerKey, $consumerSecret);

        if ($accessToken && $accessTokenSecret) {
            $this->token = new Token($accessToken, $accessTokenSecret);
        }
    }

    /**
     * @throws Exception
     */
    public function send(string $message, array|string $mediaPath = null, array $options = []): mixed {
        $mediaIds = [];

        foreach ((array) $mediaPath as $item) {
            $res = $this->request(
                'https://upload.twitter.com/1.1/media/upload.json',
                'POST',
                [],
                ['media' => $item]
            );
            $mediaIds[] = $res->media_id_string;
        }
        return $this->request(
            'statuses/update',
            'POST',
            $options + ['status' => $message, 'media_ids' => implode(',', $mediaIds) ?: null]
        );
    }

    /**
     * @throws Exception
     */
    public function request(string $resource, string $method, array $data = [], array $files = []): mixed {
        if (!strpos($resource, '://')) {
            if (!strpos($resource, '.')) {
                $resource .= '.json';
            }
            $resource = self::API_URL . $resource;
        }

        foreach ($data as $key => $val) {
            if ($val === null) {
                unset($data[$key]);
            }
        }

        foreach ($files as $key => $file) {
            if (!is_file($file)) {
                throw new Exception("Cannot read the file $file. Check if file exists on disk and check its permissions.");
            }
            $data[$key] = new CURLFile($file);
        }

        $headers = ['Expect:'];

        if ($method === 'JSONPOST') {
            $method = 'POST';
            $data = json_encode($data);
            $headers[] = 'Content-Type: application/json';

        } elseif (($method === 'GET' || $method === 'DELETE') && $data) {
            $resource .= '?' . http_build_query($data, '', '&');
        }

        $request = Request::from_consumer_and_token($this->consumer, $this->token, $method, $resource);
        $request->sign_request(new SignatureMethod_HMAC_SHA1, $this->consumer, $this->token);
        $headers[] = $request->to_header();

        $options = [
                CURLOPT_URL => $resource,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
            ] + $this->httpOptions;

        if ($method === 'POST') {
            $options += [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_SAFE_UPLOAD => true,
            ];
        } elseif ($method === 'DELETE') {
            $options += [
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            ];
        }
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception('Server error: ' . curl_error($curl));
        }

        if (str_contains(curl_getinfo($curl, CURLINFO_CONTENT_TYPE), 'application/json')) {
            $payload = @json_decode($result, false, 128, JSON_BIGINT_AS_STRING);
            if ($payload === false) {
                throw new Exception('Invalid server response');
            }
        }
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($code >= 400) {
            throw new Exception(
                $payload->errors[0]->message ?? "Server error #$code with answer $result",
                $code
            );
        } elseif ($code === 204) {
            $payload = true;
        }

        return $payload;
    }

    /**
     * @throws Exception
     */
    public function load(int $flags = self::ME, int $count = 20, array $data = null): array {
        static $timelines = [
            self::ME => 'user_timeline',
            self::ME_AND_FRIENDS => 'home_timeline',
            self::REPLIES => 'mentions_timeline',
        ];

        if (!isset($timelines[$flags & 3])) {
            throw new InvalidArgumentException;
        }

        return $this->cachedRequest('statuses/' . $timelines[$flags & 3], (array) $data + [
                'count' => $count,
                'include_rts' => $flags & self::RETWEETS ? 1 : 0,
            ]);
    }

    /**
     * @throws Exception
     */
    public function cachedRequest(string $resource, array $data = [], $cacheExpire = null): mixed {
        if (!self::$cacheDir) {
            return $this->request($resource, 'GET', $data);
        }

        if ($cacheExpire === null) {
            $cacheExpire = self::$cacheExpire;
        }
        $cacheFile = self::$cacheDir
            . '/twitter.'
            . md5($resource . json_encode($data) . serialize([$this->consumer, $this->token]))
            . '.json';

        $cache = @json_decode((string) @file_get_contents($cacheFile));
        $expiration = is_string($cacheExpire)
            ? strtotime($cacheExpire) - time()
            : $cacheExpire;
        if ($cache && @filemtime($cacheFile) + $expiration > time()) {
            return $cache;
        }

        try {
            $payload = $this->request($resource, 'GET', $data);
            file_put_contents($cacheFile, json_encode($payload));
            return $payload;

        } catch (Exception $e) {
            if ($cache) {
                return $cache;
            }
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function destroy(int|string $id): bool {
        $res = $this->request("statuses/destroy/$id", 'POST', ['id' => $id]);
        return $res->id ?: false;
    }

    /**
     * @throws Exception
     */
    public function get(int|string $id) {
        return $this->request("statuses/show/$id", 'GET');
    }
}