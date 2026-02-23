<?php


namespace App\SlackSchema;

use App\Entity\Reports\DailyCogsReport;

class DailySalesReportSchema
{

    public static function get(\DateTimeImmutable|\DateTime $date, DailyCogsReport $dailyCog): bool|string
    {
        $blocks = [];

        $symbol = '$';

        SlackSchemaBuilder::markdown($blocks, "*SALES REPORT*");
        SlackSchemaBuilder::markdown($blocks, "*Date:* " . $date->format('M d, Y'));

        $totalOrders = $dailyCog->getTotalOrders();
        $ordersContent = "*Total Orders:* " . $totalOrders;

        $totalSales = $dailyCog->getTotalSales();
        $totalPaidSales = $dailyCog->getTotalPaidSales();

        $ordersContent .= "\n*Total Sales:* " . $symbol . number_format($totalSales, 2);
        $ordersContent .= "\n*Total Paid Sales:* " . $symbol . number_format($totalPaidSales, 2);
        $ordersContent .= "\n*Total Pay Later Sales:* " . $symbol . number_format($dailyCog->getTotalPayLaterSales(), 2);
        $ordersContent .= "\n*Total Check/PO Sales:* " . $symbol . number_format($dailyCog->getTotalCheckSales(), 2);
        $ordersContent .= "\n*Total Refund:* " . $symbol . number_format($dailyCog->getTotalRefundedAmount(), 2);
        $ordersContent .= "\n*Total GoogleAds Cost:* $" . number_format($dailyCog->getGoogleAdsSpent(), 2);

        $avgOrderPrice = $totalPaidSales > 0 ? number_format($totalPaidSales / $dailyCog->getTotalPaidOrders(), 2) : 0;
        $ordersContent .= "\n*Avg. Order Price:* " . $symbol . $avgOrderPrice;

        $totalAdsCost = $dailyCog->getTotalAdsCost();
        $ratio = $totalAdsCost > 0 ? ($totalPaidSales / $totalAdsCost) : 0;
        $ordersContent .= "\n*Ratio:* " . number_format($ratio, 2);
        SlackSchemaBuilder::markdown($blocks, $ordersContent);

        return json_encode([
            'blocks' => $blocks,
        ]);
    }

}
