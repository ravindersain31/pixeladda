<?php

namespace App\Payment\Paypal;

class Order extends Base
{
    private array $name = [
        'first_name' => '',
        'last_name' => '',
    ];

    private array $shippingAddress = [
        'address_line_1' => '',
        'address_line_2' => '',
        'admin_area_2' => '',
        'admin_area_1' => '',
        'postal_code' => '',
        'country_code' => '',
    ];

    private float $shippingAmount = 0;

    private float $taxAmount = 0;

    private float $discountAmount = 0;

    private float $handlingAmount = 0;

    private float $insuranceAmount = 0;

    private float $shippingDiscountAmount = 0;

    public function createOrder(array $items, bool $isExpress = false): array
    {
        if (count($this->redirectUrls) < 2 && !$isExpress) {
            $this->throwError('Redirect URLs are required');
        }
        $this->getToken();
        $response = $this->client->request('POST', '/v2/checkout/orders', [
            'base_uri' => $this->apiUrl[$this->env],
            'auth_bearer' => $this->accessToken,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($this->buildOrder($items, $isExpress)),
        ]);
        return $this->handleOrderCreateResponse($response);
    }

    public function setShippingAddress(array $address): void
    {
        $this->shippingAddress = [
            ...$this->shippingAddress,
            ...$address,
        ];
    }

    public function setCustomerName(array $name): void
    {
        $this->name = $name;
    }


    public function setShippingAmount(float $shippingAmount): void
    {
        $this->shippingAmount = $shippingAmount;
    }

    public function setTaxAmount(float $taxAmount): void
    {
        $this->taxAmount = $taxAmount;
    }

    public function setDiscountAmount(float $discountAmount): void
    {
        $this->discountAmount = $discountAmount;
    }

    public function setHandlingAmount(float $handlingAmount): void
    {
        $this->handlingAmount = $handlingAmount;
    }

    public function setInsuranceAmount(float $insuranceAmount): void
    {
        $this->insuranceAmount = $insuranceAmount;
    }

    public function setShippingDiscountAmount(float $shippingDiscountAmount): void
    {
        $this->shippingDiscountAmount = $shippingDiscountAmount;
    }

    public function buildOrder(array $items, bool $isExpress = false): array
    {
        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [$this->makePurchaseUnit($items)],
        ];
        if (!$isExpress) {
            $orderData['payment_source'] = [
                ...$this->paymentSource('paypal')
            ];
        }
        return $orderData;
    }

    private function handleOrderCreateResponse($response): array
    {
        $response = json_decode($response->getContent(false), true);
        if (isset($response['status']) && $response['status'] === 'PAYER_ACTION_REQUIRED') {
            $action = current(array_filter($response['links'], function ($link) {
                return $link['rel'] === 'payer-action';
            }));
            return [
                'success' => true,
                'action' => 'redirect',
                'redirectUrl' => $action['href'],
                'transaction' => [
                    'gatewayId' => $response['id'],
                ],
            ];
        } else if (isset($response['status']) && $response['status'] === 'CREATED') {
            return [
                'success' => true,
                'action' => 'capture',
                'transaction' => [
                    'gatewayId' => $response['id'],
                    'links' => $response['links'],
                ],
            ];
        }
        return [
            'success' => false,
            'action' => $response['status'] ?? 'FAILED',
            'message' => $response['message'] ?? 'Unknown error',
        ];
    }

    private function makePurchaseUnit($items): array
    {
        $unit = [
            'items' => [],
        ];

        $itemTotalAmount = 0;
        foreach ($items as $item) {
            $unit['items'][] = [
                'name' => $item['name'],
                'description' => $item['description'],
                'unit_amount' => [
                    'currency_code' => $this->currencyCode,
                    'value' => number_format($item['price'], 2, '.', '')
                ],
                'quantity' => $item['quantity'],
            ];
            $itemTotalAmount += ($item['price'] * $item['quantity']);
        }

        $breakdown = [
            'item_total' => [
                'currency_code' => $this->currencyCode,
                'value' => number_format($itemTotalAmount, 2, '.', ''),
            ],
            'shipping' => [
                'currency_code' => $this->currencyCode,
                'value' => number_format($this->shippingAmount, 2, '.', ''),
            ],
            'tax_total' => [
                'currency_code' => $this->currencyCode,
                'value' => number_format($this->taxAmount, 2, '.', ''),
            ],
            'discount' => [
                'currency_code' => $this->currencyCode,
                'value' => number_format($this->discountAmount, 2, '.', ''),
            ],
            'handling' => [
                'currency_code' => $this->currencyCode,
                'value' => number_format($this->handlingAmount, 2, '.', ''),
            ],
            'insurance' => [
                'currency_code' => $this->currencyCode,
                'value' => number_format($this->insuranceAmount, 2, '.', ''),
            ],
            'shipping_discount' => [
                'currency_code' => $this->currencyCode,
                'value' => number_format($this->shippingDiscountAmount, 2, '.', ''),
            ],
        ];

        $unit['amount'] = [
            'currency_code' => $this->currencyCode,
            'value' => $this->calculateAmount($itemTotalAmount),
            'breakdown' => $breakdown,
        ];

        if (!empty($this->name['first_name'])) {
            $unit['shipping'] = [
                'type' => 'SHIPPING',
                'name' => [
                    'full_name' => $this->name['first_name'] . ' ' . $this->name['last_name'],
                ],
                'address' => $this->shippingAddress,
            ];
        }

        return $unit;
    }

    private function calculateAmount($itemTotalAmount): string
    {
        $valueBreakdown = [
            'item_total' => [
                'value' => number_format($itemTotalAmount, 2, '.', ''),
            ],
            'shipping' => [
                'value' => number_format($this->shippingAmount, 2, '.', ''),
            ],
            'tax_total' => [
                'value' => number_format($this->taxAmount, 2, '.', ''),
            ],
            'handling' => [
                'value' => number_format($this->handlingAmount, 2, '.', ''),
            ],
            'insurance' => [
                'value' => number_format($this->insuranceAmount, 2, '.', ''),
            ],
        ];

        $totalAmount = array_reduce($valueBreakdown, function ($carry, $item) {
            return $carry + floatval($item['value']);
        }, 0);

        $totalAmount -= $this->discountAmount;

        return number_format($totalAmount, 2, '.', '');
    }

}