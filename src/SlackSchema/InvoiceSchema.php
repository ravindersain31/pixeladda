<?php

namespace App\SlackSchema;

use App\Service\HostBasedRouterService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use DateTime;

class InvoiceSchema
{
    public static function get(array $invoiceData, array $options = [], ?ParameterBagInterface $params = null): bool|string
    {
        $blocks = [];

        SlackSchemaBuilder::markdown($blocks, self::createHeader($invoiceData, $options));
        SlackSchemaBuilder::divider($blocks);

        SlackSchemaBuilder::markdown($blocks, self::createInvoiceDetails($invoiceData));

        SlackSchemaBuilder::markdown($blocks, self::createCustomerDetails($invoiceData));

        SlackSchemaBuilder::markdown($blocks, self::createAmountSummary($invoiceData));

        if (!empty($invoiceData['created']) && $invoiceData['created'] instanceof DateTime) {
            SlackSchemaBuilder::markdown($blocks, self::createDateSection($invoiceData));
        }

        SlackSchemaBuilder::divider($blocks);

        if (!empty($invoiceData['items']) && is_array($invoiceData['items'])) {
            SlackSchemaBuilder::markdown($blocks, self::createInvoiceItems($invoiceData['items']));
        }

        SlackSchemaBuilder::divider($blocks);

        $buttons = self::createButtons($invoiceData, $options, $params);
        if (!empty($buttons)) {
            SlackSchemaBuilder::button($blocks, $buttons);
        }

        return json_encode(['blocks' => $blocks]);
    }

    private static function createHeader(array $invoiceData, array $options): string
    {
        $platform = strtoupper($options['platform'] ?? 'PAYPAL');
        $status = strtolower($invoiceData['status'] ?? '');

        return match ($status) {
            'paid', 'succeeded' => "*{$platform} INVOICE PAYMENT RECEIVED*",
            'failed' => "*{$platform} INVOICE PAYMENT FAILED*",
            'partially_refunded' => "*{$platform} INVOICE PARTIALLY REFUNDED*",
            'refunded' => "*{$platform} INVOICE FULLY REFUNDED*",
            default => "*{$platform} INVOICE STATUS UPDATE*",
        };
    }

    private static function createInvoiceDetails(array $invoiceData): string
    {
        return sprintf(
            "*Invoice Number:* %s\n*Status:* %s",
            $invoiceData['invoice_number'] ?? 'N/A',
            strtoupper($invoiceData['status'] ?? 'UNKNOWN')
        );
    }

    private static function createCustomerDetails(array $invoiceData): string
    {
        return sprintf(
            "*Customer Name:* %s\n*Customer Email:* %s",
            $invoiceData['customer_name'] ?? 'N/A',
            $invoiceData['customer_email'] ?? 'N/A'
        );
    }

    private static function createAmountSummary(array $invoiceData, array $options = []): string
    {
        $platform = strtolower($options['platform'] ?? 'paypal'); 

        $rawAmount = $invoiceData['amount_paid'] ?? 0;

        $amountPaid = $platform === 'paypal'
            ? number_format($rawAmount, 2)          // example: 26.00
            : number_format($rawAmount / 100, 2);   // example: 2600 → 26.00

        return sprintf("*Amount Paid:* $%s", $amountPaid);
    }

    private static function createDateSection(array $invoiceData): string
    {
        return "*Date:* " . $invoiceData['created']->format('M d, Y');
    }

    private static function createInvoiceItems(array $items): string
    {
        $itemsText = "*Items*\n---------------------------------------------------\n";
        $itemsText .= implode("\n", $items);
        $itemsText .= "\n---------------------------------------------------";

        return $itemsText;
    }

    private static function createButtons(array $invoiceData, array $options, ?ParameterBagInterface $params): array
    {
        $buttons = [];

        if (!empty($invoiceData['hosted_invoice_url'])) {
            $buttons[] = [
                'label' => 'View Invoice',
                'link' => $invoiceData['hosted_invoice_url'],
            ];
        }

        if (!empty($options['adminInvoiceList'])) {
            $buttons[] = [
                'label' => 'View in Admin',
                'link' => HostBasedRouterService::replaceWithAdminHost($options['adminInvoiceList'], $params),
            ];
        }

        if (!empty($options['refundLink'])) {
            $buttons[] = [
                'label' => 'Issue Refund',
                'link' => $options['refundLink'],
            ];
        }

        return $buttons;
    }
}
