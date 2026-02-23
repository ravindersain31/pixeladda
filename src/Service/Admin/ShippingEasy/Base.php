<?php

namespace App\Service\Admin\ShippingEasy;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Base
{
    public string $apiBase = 'https://api.shippingeasy.com';

    public string $version = '0.4.3';

    protected string $env;

    protected string $storeKey;

    protected string $apiKey;

    protected string $apiSecret;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly Signature             $signature,
    )
    {
        $this->env = $this->parameterBag->get('SHIPPING_EASY_ENV');
        $this->storeKey = $this->parameterBag->get('SHIPPING_EASY_STORE_KEY');
        $this->apiKey = $this->parameterBag->get('SHIPPING_EASY_API_KEY');
        $this->apiSecret = $this->parameterBag->get('SHIPPING_EASY_API_SECRET');

        $this->signature->setApiSecret($this->apiSecret);
    }

    public function request(string $endpoint, ?array $body = null, string $method = 'POST'): array
    {
        $params = [
            'api_key' => $this->apiKey,
            'api_timestamp' => time(),
        ];
        $payload = null;
        if ($body && $method === 'POST') {
            $body = ['order' => $body];
            $payload = $body;
            $this->signature->setBody($body);
        } elseif ($body && $method === 'GET') {
            $params = array_merge($params, $body);
            $this->signature->setBody(null);
        } else {
            $this->signature->setBody(null);
        }
        $this->signature->setHttpMethod($method);
        $this->signature->setPath($endpoint);
        $this->signature->setParams($params);


        $params['api_signature'] = $this->signature->encrypted();

        $requestUrl = $this->apiBase . $endpoint . '?' . http_build_query($params);

        return $this->call($requestUrl, $payload, $method);
    }

    private function call(string $url, ?array $payload = null, string $method = 'POST'): array
    {
        $curl = curl_init();
        $opts = [];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        if ($payload) {
            $payload = json_encode($payload);
            $headers[] = 'Content-Length: ' . strlen($payload);
        }

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $payload;
        } elseif ($method === 'GET') {
            $opts[CURLOPT_HTTPGET] = 1;
        }

        $opts[CURLOPT_URL] = $url;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_CONNECTTIMEOUT] = 30;
        $opts[CURLOPT_TIMEOUT] = 80;
        $opts[CURLOPT_FOLLOWLOCATION] = true;
        $opts[CURLOPT_MAXREDIRS] = 4;
        $opts[CURLOPT_POSTREDIR] = 1 | 2 | 4; // Maintain method across redirect for all 3XX redirect types
        $opts[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($curl, $opts);
        $rbody = curl_exec($curl);

        if ($rbody === false) {
            $errno = curl_errno($curl);
            $message = curl_error($curl);
            curl_close($curl);
            $message = $this->handleCurlError($errno, $message);
            return [
                'success' => false,
                'message' => $message,
            ];
        }

        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $rdata = json_decode($rbody, true);

        $message = match ($rcode) {
            201 => 'Order has been created successfully',
            200 => 'Your ShippingEasy request was successful',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Unknown Error',
        };
        $errors = [];
        if (isset($rdata['errors']) && is_array($rdata['errors'])) {
            $errors = $rdata['errors'];
        } elseif (isset($rdata['errors']) && !is_array($rdata['errors'])) {
            $errors = json_decode($rdata['errors'], true);
        }
        return [
            'success' => in_array($rcode, [200, 201]),
            'message' => $message,
            'data' => !in_array($rcode, [200, 201]) ? $errors : $rdata,
            'code' => $rcode,
        ];
    }

    public function handleCurlError($errno, $message): string
    {
        $apiBase = $this->apiBase;
        $msg = match ($errno) {
            CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST, CURLE_OPERATION_TIMEOUTED => "Could not connect to ShippingEasy ($apiBase).  Please check your internet connection and try again.  If this problem persists, let us know at support@shippingeasy.com.",
            CURLE_SSL_CACERT, CURLE_SSL_PEER_CERTIFICATE => "Could not verify ShippingEasy's SSL certificate.  Please make sure that your network is not intercepting certificates.  (Try going to $apiBase in your browser.)  If this problem persists, let us know at support@shippingeasy.com.",
            default => "Unexpected error communicating with ShippingEasy.  If this problem persists, let us know at support@shippingeasy.com.",
        };

        $msg .= "\n\n(Network error [errno $errno]: $message)";
        return $msg;
    }
}