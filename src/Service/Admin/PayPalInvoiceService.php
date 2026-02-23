<?php

namespace App\Service\Admin;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PayPalInvoiceService
{
    private const PAYPAL_API_TOKEN_PATH = '/v1/oauth2/token';
    private const PAYPAL_INVOICES_PATH = '/v2/invoicing/invoices';
    private const PAYPAL_PAYMENTS_CAPTURE_PATH = '/v2/payments/captures';
    private const PAYPAL_SEND_INVOICE_PATH = '/send';

    private const HTTP_STATUS_OK = 200;
    private const HTTP_STATUS_CREATED = 201;
    private const HTTP_STATUS_ACCEPTED = 202;

    private string $paypalApiUrl;

    public function __construct(
        private readonly ParameterBagInterface $parameter,
        private readonly HttpClientInterface $httpClient
    ) {
        $this->paypalApiUrl = $parameter->get('PAYPAL_API_URL');
    }

    private function generateAccessToken(): array
    {
        $clientId = $this->parameter->get('PAYPAL_CLIENT_ID');
        $secret = $this->parameter->get('PAYPAL_CLIENT_SECRET');

        try {
            $response = $this->httpClient->request('POST', $this->paypalApiUrl . self::PAYPAL_API_TOKEN_PATH, [
                'auth_basic' => [$clientId, $secret],
                'body' => ['grant_type' => 'client_credentials'],
            ]);

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            $this->handleRequestException($e);
        }

        return [];
    }

    private function handleRequestException(TransportExceptionInterface $exception): void
    {
        throw new \RuntimeException('Failed to generate access token: ' . $exception->getMessage(), $exception->getCode(), $exception);
    }

    private function sendRequest(string $method, string $url, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $url, $options);

            if (
                $response->getStatusCode() === self::HTTP_STATUS_OK
                || $response->getStatusCode() === self::HTTP_STATUS_CREATED
                || $response->getStatusCode() === self::HTTP_STATUS_ACCEPTED
            ) {
                return $response->toArray();
            }

            throw new \RuntimeException('Request failed with status code ' . $response->getStatusCode());
        } catch (TransportExceptionInterface $e) {
            $this->handleRequestException($e);
        }

        return [];
    }

    private function getAuthorizationHeader(array $accessToken): array
    {
        return [
            'Authorization' => 'Bearer ' . $accessToken['access_token'],
            'Content-Type' => 'application/json',
        ];
    }

    public function getPayPalInvoices(): array
    {
        $paypalAccessToken = $this->generateAccessToken();

        if (!isset($paypalAccessToken['access_token'])) {
            return $paypalAccessToken;
        }

        $url = $this->paypalApiUrl . self::PAYPAL_INVOICES_PATH . '?total_required=true&page_size=50';

        return $this->sendRequest('GET', $url, ['headers' => $this->getAuthorizationHeader($paypalAccessToken)]);
    }

    public function generateInvoice(array $dataArray = []): array
    {
        $paypalAccessToken = $this->generateAccessToken();

        if (!isset($paypalAccessToken['access_token'])) {
            return $paypalAccessToken;
        }

        $url = $this->paypalApiUrl . self::PAYPAL_INVOICES_PATH;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $this->getAuthorizationHeader($paypalAccessToken),
                'json' => $dataArray,
            ]);

            if ($response->getStatusCode() === self::HTTP_STATUS_OK || $response->getStatusCode() === self::HTTP_STATUS_CREATED || $response->getStatusCode() === self::HTTP_STATUS_ACCEPTED) {
                if ($this->paypalApiUrl != 'https://api.sandbox.paypal.com') {
                    $sendInvoiceResponse = $this->sendGeneratedInvoice($response->toArray());
                    $sendInvoiceResponse['status'] = $response->getStatusCode();
                    return $sendInvoiceResponse;
                } else {
                    return ['status' => self::HTTP_STATUS_OK, 'message' => 'Invoice has been generated successfully'];
                }
            } else {
                return ['status' => $response->getStatusCode(), 'message' => 'Please fill out all fields.'];
            }
        } catch (TransportExceptionInterface $e) {
            $this->handleRequestException($e);
        }

        return [];
    }


    private function sendGeneratedInvoice(array $generateInvoiceResponse): array
    {
        $paypalAccessToken = $this->generateAccessToken();

        if (!isset($paypalAccessToken['access_token'])) {
            return $paypalAccessToken;
        }

        $url = $generateInvoiceResponse['href'] . self::PAYPAL_SEND_INVOICE_PATH;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $this->getAuthorizationHeader($paypalAccessToken),
                'json' => ['send_to_invoicer' => true],
            ]);

            $resultArray = $response->toArray();
            $resultArray['status'] = $response->getStatusCode();

            if ($response->getStatusCode() !== self::HTTP_STATUS_OK && $response->getStatusCode() !== self::HTTP_STATUS_CREATED && $response->getStatusCode() !== self::HTTP_STATUS_ACCEPTED) {
                return ['status' => $response->getStatusCode(), 'error' => 'Please fill out all fields.'];
            }

            return $resultArray;
        } catch (TransportExceptionInterface $e) {
            $this->handleRequestException($e);
        }

        return [];
    }

    public function updateInvoiceById(string $invoiceId, array $invoiceData): array
    {

        $paypalAccessToken = $this->generateAccessToken();

        if (!isset($paypalAccessToken['access_token'])) {
            return $paypalAccessToken;
        }

        $url = sprintf('%s%s/%s', $this->paypalApiUrl, self::PAYPAL_INVOICES_PATH, $invoiceId);

        return $this->sendRequest('PUT', $url, [
            'headers' => $this->getAuthorizationHeader($paypalAccessToken),
            'json' => $invoiceData,
        ]);
    }


    public function getInvoiceById(string $invoiceId): array
    {
        $paypalAccessToken = $this->generateAccessToken();

        if (!isset($paypalAccessToken['access_token'])) {
            return $paypalAccessToken;
        }

        $url = sprintf('%s%s/%s', $this->paypalApiUrl, self::PAYPAL_INVOICES_PATH, $invoiceId);

        return $this->sendRequest('GET', $url, ['headers' => $this->getAuthorizationHeader($paypalAccessToken)]);
    }

    public function createDraftInvoiceData($data): array
    {
        $formDataArray = [];

        $invoiceItems = $data['items'];

        $invoiceDate = new \DateTimeImmutable();

        $formDataArray['detail'] = [
            'invoice_number' => $data['invoiceNumber'],
            'currency_code' => 'USD',
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'payment_term' => [
                'term_type' => 'NO_DUE_DATE',
            ],
        ];

        $formDataArray['invoicer'] = [
            'name' => [
                'given_name' => 'Yard Sign Plus',
            ],
            'address' => [
                'address_line_1' => '10511 Kipp Way',
                'address_line_2' => 'St #430',
                'admin_area_2' => 'Houston',
                'admin_area_1' => 'TX',
                'postal_code' => '77099',
                'country_code' => 'US',
            ],
            'email_address' => 'sales@yardsignplus.com',
            'website' => 'https://www.yardsignplus.com/',
            'logo_url' => 'https://static.yardsignplus.com/cu-20231128-m7FNPO0EdD.png',
        ];

        extract([
            'fname' => $data['firstName'],
            'lname' => $data['lastName'],
            'email' => $data['email'],
        ]);

        $formDataArray['primary_recipients'][] = [
            'billing_info' => [
                'name' => [
                    'given_name' => $fname,
                    'surname' => $lname,
                ],
                'email_address' => $email,
            ],
        ];

        $apiInvoiceItems = [];

        foreach ($invoiceItems as $invoiceItem) {
            $apiInvoiceItems[] = [
                'name' => $invoiceItem['itemName'],
                'description' => $invoiceItem['itemDescription'],
                'quantity' => $invoiceItem['itemQuantity'],
                'unit_amount' => [
                    'currency_code' => 'USD',
                    'value' => $invoiceItem['itemPrice'],
                ],
                'unit_of_measure' => 'QUANTITY',
            ];
        }

        $formDataArray['items'] = $apiInvoiceItems;

        return $formDataArray;
    }

    public function checkIfInvoiceIsExist($invoiceNumber): bool
    {
        $paypalInvoices = $this->getPayPalInvoices();
        $paypalInvoicesItems = $paypalInvoices['items'] ?? [];
        
        foreach ($paypalInvoicesItems as $invoice) {
            if ($invoice['detail']['invoice_number'] == $invoiceNumber) {
                return true;
            }
        }
        return false;
    }

    public function refundInvoice(string $invoiceId, ?float $amount = null): array
    {
        $paypalAccessToken = $this->generateAccessToken();

        if (!isset($paypalAccessToken['access_token'])) {
            return [
                'success' => false,
                'message' => 'Unable to generate PayPal access token.'
            ];
        }

        // Get invoice details
        $invoiceUrl = sprintf('%s%s/%s', $this->paypalApiUrl, self::PAYPAL_INVOICES_PATH, $invoiceId);
        $invoiceResponse = $this->httpClient->request('GET', $invoiceUrl, [
            'headers' => $this->getAuthorizationHeader($paypalAccessToken),
        ]);

        $invoice = $invoiceResponse->toArray(false);
        $transactions = $invoice['payments']['transactions'] ?? [];
        $paymentTransaction = null;

        foreach ($transactions as $transaction) {
            if (isset($transaction['transaction_type']) && $transaction['transaction_type'] === 'SALE') {
                $paymentTransaction = $transaction;
                break;
            }
        }

        if (!$paymentTransaction || empty($paymentTransaction['payment_id'])) {
            return [
                'success' => false,
                'message' => 'No valid PayPal payment transaction found for this invoice.'
            ];
        }

        $transactionId = $paymentTransaction['payment_id'];

        $refundUrl = sprintf('%s%s/%s/refund', $this->paypalApiUrl, self::PAYPAL_PAYMENTS_CAPTURE_PATH, $transactionId);

        $refundAmount = $amount ?? $paymentTransaction['amount']['value'];
        $currency = $paymentTransaction['amount']['currency_code'] ?? 'USD';

        $payload = [
            'amount' => [
                'value' => number_format($refundAmount, 2, '.', ''),
                'currency_code' => $currency ?? "USD",
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $refundUrl, [
                'headers' => $this->getAuthorizationHeader($paypalAccessToken),
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getContent(false), true);

            return [
                'success' => in_array($statusCode, [200, 201, 202], true),
                'status' => $statusCode,
                'data' => $result,
                'message' => 'Refund processed successfully.',
            ];
        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'message' => 'Transport error: ' . $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Refund error: ' . $e->getMessage(),
            ];
        }
    }


    public function sendInvoiceById(string $invoiceId): array
    {
        $paypalAccessToken = $this->generateAccessToken();

        if (!isset($paypalAccessToken['access_token'])) {
            return [
                'status' => 401,
                'message' => 'Unable to generate PayPal access token.'
            ];
        }

        $url = sprintf('%s%s/%s%s', $this->paypalApiUrl, self::PAYPAL_INVOICES_PATH, $invoiceId, self::PAYPAL_SEND_INVOICE_PATH);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $this->getAuthorizationHeader($paypalAccessToken),
                'json' => [
                    'send_to_invoicer' => true, 
                ],
            ]);

            return [
                'status' => $response->getStatusCode(),
                'data' => $response->toArray(false),
            ];
        } catch (TransportExceptionInterface $e) {
            return [
                'status' => 500,
                'message' => 'Transport error while sending invoice: ' . $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 500,
                'message' => 'Error sending invoice: ' . $e->getMessage(),
            ];
        }
    }
}
