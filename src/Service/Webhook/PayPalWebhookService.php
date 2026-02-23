<?php

namespace App\Service\Webhook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PayPalWebhookService
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function handle(Request $request): void
    {
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['event_type'])) {
            throw new \RuntimeException('Invalid PayPal webhook payload.');
        }

        match ($payload['event_type']) {
            'INVOICING.INVOICE.PAID' => $this->invoicePaid($payload['resource']['invoice'] ?? []),
            default => null,
        };
    }

    private function invoicePaid(array $invoice): void
    {
        $lineItems = $this->getLineItems($invoice);

        $invoiceData = $this->buildInvoiceData($invoice, $lineItems);

    }

    private function getLineItems(array $invoice): array
    {
        if (empty($invoice['items'])) {
            return [];
        }

        return array_map(
            fn($item, $i) => sprintf(
                '%d. %s - Qty %d',
                $i + 1,
                $item['name'] ?? 'N/A',
                $item['quantity'] ?? 1
            ),
            $invoice['items'],
            array_keys($invoice['items'])
        );
    }

    private function buildInvoiceData(array $invoice, array $lineItems = []): array
    {
        $primaryRecipient = null;

        if (!empty($invoice['primary_recipients']) && is_array($invoice['primary_recipients'])) {
            $primaryRecipient = reset($invoice['primary_recipients'])['billing_info'] ?? null;
        }

        $customerEmail = $primaryRecipient['email_address'] ?? null;
        $customerName  = $primaryRecipient['name']['full_name'] ?? null;

        return [
            'id' => $invoice['id'] ?? null,

            'invoice_number' => $invoice['id'] ??  $invoice['detail']['invoice_number'] ?? null,

            'customer_email' => $customerEmail,
            'customer_name'  => $customerName,

            'amount_due'  => $invoice['amount']['value'] ?? null,
            'amount_paid' => $invoice['payments']['paid_amount']['value'] ?? null,

            'status' => strtoupper($invoice['status'] ?? 'UNKNOWN'),

            'created' => !empty($invoice['detail']['metadata']['create_time'])
                ? new \DateTime($invoice['detail']['metadata']['create_time'])
                : null,

            'hosted_invoice_url' => $invoice['detail']['metadata']['recipient_view_url'] ?? null,

            'refund_amount' => $invoice['refunds']['refund_amount']['value'] ?? 0,
            'refund_status' => $invoice['refunds']['status'] ?? null,

            'items' => $lineItems,
        ];
    }

}
