<?php

namespace App\Service\Admin;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Stripe\StripeClient;

class StripeInvoiceService
{
    private StripeClient $stripe;
    private string $stripeEnv;
    private string $stripePubKey;
    private string $stripeWebhookKey;
    private string $stripeInvoiceWebhookSecret;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {
        $this->stripeEnv = $parameterBag->get('STRIPE_ENV');
        $this->stripePubKey = $parameterBag->get('STRIPE_PUB_KEY');
        $this->stripeWebhookKey = $parameterBag->get('STRIPE_WEBHOOK_KEY');
        $this->stripeInvoiceWebhookSecret = $parameterBag->get('STRIPE_INVOICE_WEBHOOK_SECRET');

        $this->stripe = new StripeClient($parameterBag->get('STRIPE_SECRET_KEY'));
    }

    public function getAllInvoices(): array
    {
        $invoices = [];
        $params = ['limit' => 100]; // Stripe max limit is 100 per request
        try {
            do {
                $stripeInvoices = $this->stripe->invoices->all($params);

                foreach ($stripeInvoices->data as $invoice) {
                    $refundAmount = 0;
                    $refundStatus = null;

                    if (!empty($invoice->charge) && is_string($invoice->charge)) {
                        $refunds = $this->stripe->refunds->all(['charge' => $invoice->charge]);

                        foreach ($refunds->data as $refund) {
                            $refundAmount += $refund->amount;
                            $refundStatus = $refund->status;
                        }
                    } elseif (!empty($invoice->payment_intent) && is_string($invoice->payment_intent)) {
                        $charges = $this->stripe->charges->all(['payment_intent' => $invoice->payment_intent]);
                        foreach ($charges->data as $charge) {
                            $refunds = $this->stripe->refunds->all(['charge' => $charge->id]);
                            foreach ($refunds->data as $refund) {
                                $refundAmount += $refund->amount;
                                $refundStatus = $refund->status;
                            }
                        }
                    }

                    $invoices[] = [
                        'id'                => $invoice->id,
                        'invoice_number'    => $invoice->number,
                        'customer_email'    => $invoice->customer_email ?? null,
                        'customer_name'     => $invoice->customer_name ?? null,
                        'amount_due'        => $invoice->amount_due,
                        'amount_paid'       => $invoice->amount_paid,
                        'status'            => $invoice->status,
                        'created'           => \DateTime::createFromFormat('U', $invoice->created),
                        'hosted_invoice_url'=> $invoice->hosted_invoice_url,
                        'refund_amount'     => $refundAmount,
                        'refund_status'     => $refundStatus,
                    ];
                }

                $params['starting_after'] = $stripeInvoices->has_more
                    ? end($stripeInvoices->data)->id
                    : null;

            } while ($params['starting_after']);

            return $invoices;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getInvoiceById(string $invoiceId): array
    {
        try {
            $invoice = $this->stripe->invoices->retrieve($invoiceId);

            $items = [];
            foreach ($invoice->lines->data as $line) {
                $items[] = [
                    'itemDescription' => $line->description ?? '',
                    'itemQuantity' => $line->quantity ?? 1,
                    'itemPrice' => ($line->amount ?? 0) / 100, 
                ];
            }

            return [
                'invoiceItemsObj' => [
                    'invoiceNumber' => $invoice->number ?? '',
                    'email' => $invoice->customer_email ?? '',
                    'firstName' => $invoice->metadata['first_name'] ?? '',
                    'lastName' => $invoice->metadata['last_name'] ?? '',
                    'items' => $items,
                ],
                'invoiceProducts' => $items,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'code' => 404,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function createInvoice(array $data): object
    {
        try {
            $customer = $this->stripe->customers->create([
                'email' => $data['email'],
                'name' => $data['firstName'] . ' ' . $data['lastName'],
            ]);

            $invoice = $this->stripe->invoices->create([
                'customer' => $customer->id,
                'auto_advance' => false,        // we will finalize manually
                'collection_method' => 'send_invoice',
                'days_until_due' => 7,
                'description' => "Invoice for {$data['firstName']} {$data['lastName']}",
                'metadata' => [
                    'invoice_number' => $data['invoiceNumber'] ?? null,
                    'first_name' => $data['firstName'],
                    'last_name' => $data['lastName'],
                ],
            ]);

            foreach ($data['items'] as $item) {
                $price = isset($item['itemPrice']) && is_numeric($item['itemPrice']) ? $item['itemPrice'] : 0;
                $quantity = isset($item['itemQuantity']) ? $item['itemQuantity'] : 1;

                if ($price <= 0) continue; 

                $this->stripe->invoiceItems->create([
                    'customer' => $customer->id,
                    'invoice' => $invoice->id,   
                    'description' => $item['itemDescription'] ?? 'Item',
                    'quantity' => $quantity,
                    'unit_amount' => intval($price * 100), 
                    'currency' => 'usd',
                ]);
            }

            $finalizedInvoice = $this->stripe->invoices->finalizeInvoice($invoice->id);

            $this->stripe->invoices->sendInvoice($invoice->id);

            return (object)[
                'status' => 'success',
                'invoiceId' => $finalizedInvoice->id,
                'invoiceUrl' => $finalizedInvoice->hosted_invoice_url,
                'amountDue' => ($finalizedInvoice->amount_due ?? 0) / 100,
                'invoiceStatus' => $finalizedInvoice->status, 
            ];

        } catch (\Throwable $e) {
            return (object)[
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function updateInvoice(string $invoiceId, array $payload): object
    {
        try {
            return $this->stripe->invoices->update($invoiceId, $payload);
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'error',
                'code' => 400,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function refundInvoice(string $invoiceId, ?float $amount = null)
    {
        try {
            $invoice = $this->stripe->invoices->retrieve($invoiceId);
            $paymentIntentId = $invoice->payment_intent;

            if (!$paymentIntentId) {
                return (object)[
                    'status' => 'error',
                    'error' => 'No payment found for this invoice.'
                ];
            }

            $refundParams = ['payment_intent' => $paymentIntentId];

            if ($amount !== null) {
                $refundParams['amount'] = (int)round($amount * 100);
            }

            $refund = $this->stripe->refunds->create($refundParams);

            return (object)[
                'status' => 'success',
                'refundId' => $refund->id,
                'amount' => $refund->amount / 100
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return (object)[
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPublicKey(): string
    {
        return $this->stripePubKey;
    }

    public function getWebhookKey(): string
    {
        return $this->stripeWebhookKey;
    }

    public function getInvoiceWebhookSecret(): string
    {
        return $this->stripeInvoiceWebhookSecret;
    }

    public function getEnv(): string
    {
        return $this->stripeEnv;
    }
}
