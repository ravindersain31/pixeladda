<?php

namespace App\SlackSchema;

use App\Entity\Reports\DailyCogsReport;

class FacebookAdsReportSchema
{

    public static function get(\DateTimeImmutable|\DateTime $date, DailyCogsReport $dailyCog): bool|string
    {
        $blocks = [];

        $symbol = '$';

        $spend = isset($dailyCog->getFacebookAdsData()['Spend']) ? $dailyCog->getFacebookAdsData()['Spend'] : 0;
        $conversions = isset($dailyCog->getFacebookAdsData()['Conversions']) ? $dailyCog->getFacebookAdsData()['Conversions'] : 0;
        $revenue = isset($dailyCog->getFacebookAdsData()['Revenue']) ? $dailyCog->getFacebookAdsData()['Revenue'] : 0;
        $roas = isset($dailyCog->getFacebookAdsData()['ReturnOnAdSpend']) ? $dailyCog->getFacebookAdsData()['ReturnOnAdSpend'] : 0;

        SlackSchemaBuilder::markdown($blocks, "*Facebook Ads Cost USD*");
        SlackSchemaBuilder::markdown($blocks, "*Date:* " . $date->format('M d, Y'));
        SlackSchemaBuilder::markdown($blocks, "*FB Ad Cost:* " . $symbol . number_format($spend, 2));
        SlackSchemaBuilder::markdown($blocks, "*FB Ads Purchase:* " . $conversions);
        SlackSchemaBuilder::markdown($blocks, "*FB Ads Purchase Value:* " . $symbol . number_format($revenue, 2));
        SlackSchemaBuilder::markdown($blocks, "*FB Ads ROAS:* " . number_format($roas, 2));

        return json_encode([
            'blocks' => $blocks,
        ]);
    }

}
