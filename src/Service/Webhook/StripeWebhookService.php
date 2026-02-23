<?php

namespace App\Service\Webhook;

use App\Service\Admin\StripeInvoiceService;
use Stripe\Invoice;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeWebhookService
{
    public function __construct(
        private readonly StripeInvoiceService $stripeInvoiceService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function handle(Request $request): void
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $invoiceWebhookSecret = $this->stripeInvoiceService->getInvoiceWebhookSecret();

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $invoiceWebhookSecret);
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid Stripe webhook signature.');
        }

        match ($event->type) {
            'invoice.payment_succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'invoice.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null, 
        };
    }

    private function handlePaymentSucceeded(Invoice $invoice): void
    {
        $lineItems = $this->getLineItems($invoice);

        $invoiceData = $this->buildInvoiceData($invoice, $lineItems);

    }

    private function handlePaymentFailed(Invoice $invoice): void
    {
        $invoiceData = $this->buildInvoiceData($invoice);

    }

    private function getLineItems(Invoice $invoice): array
    {
        if (empty($invoice->lines->data)) {
            return [];
        }

        return array_map(
            fn($item, $i) => sprintf(
                '%d. %s - Qty %d',
                $i + 1,
                $item->description ?? 'N/A',
                $item->quantity ?? 1
            ),
            iterator_to_array($invoice->lines->data),
            array_keys(iterator_to_array($invoice->lines->data))
        );
    }

    private function buildInvoiceData(Invoice $invoice, array $lineItems = []): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'customer_email' => $invoice->customer_email ?? null,
            'customer_name' => $invoice->customer_name ?? null,
            'amount_due' => $invoice->amount_due,
            'amount_paid' => $invoice->amount_paid,
            'status' => $invoice->status,
            'created' => new \DateTime('@' . $invoice->created),
            'hosted_invoice_url' => $invoice->hosted_invoice_url,
            'refund_amount' => 0,
            'refund_status' => null,
            'items' => $lineItems,
        ];
    }
}
