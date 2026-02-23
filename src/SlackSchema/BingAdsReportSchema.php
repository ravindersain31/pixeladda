<?php

namespace App\SlackSchema;

use App\Entity\Reports\DailyCogsReport;

class BingAdsReportSchema
{

    public static function get(\DateTimeImmutable|\DateTime $date, DailyCogsReport $dailyCog): bool|string
    {
        $blocks = [];

        $symbol = '$';

        $spend = isset($dailyCog->getBingAdsData()['Spend']) ? $dailyCog->getBingAdsData()['Spend'] : 0;
        $revenue = isset($dailyCog->getBingAdsData()['Revenue']) ? $dailyCog->getBingAdsData()['Revenue'] : 0;
        $conversions = isset($dailyCog->getBingAdsData()['Conversions']) ? $dailyCog->getBingAdsData()['Conversions'] : 0;
        $roas = isset($dailyCog->getBingAdsData()['ReturnOnAdSpend']) ? $dailyCog->getBingAdsData()['ReturnOnAdSpend'] : 0;

        SlackSchemaBuilder::markdown($blocks, "*Bing Ads Cost USD*");
        SlackSchemaBuilder::markdown($blocks, "*Date:* " . $date->format('M d, Y'));
        SlackSchemaBuilder::markdown($blocks, "*Bing Total Spend:* " . $symbol . number_format($spend, 2));
        SlackSchemaBuilder::markdown($blocks, "*Bing Total Revenue:* " . $symbol .number_format(floatval($revenue), 2));
        SlackSchemaBuilder::markdown($blocks, "*Bing Total Conversions:* " . $conversions);
        SlackSchemaBuilder::markdown($blocks, "*Bing ROAS:* " . number_format($roas, 2));

        return json_encode([
            'blocks' => $blocks,
        ]);
    }

}
