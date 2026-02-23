<?php

namespace App\Helper;

class ShippingTrackingLinksHelper
{
    const shippingCarriers = [
        'UPS' => [
            'url' => 'https://www.ups.com/track?tracknum=###TRACKING_NUMBER###',
        ],
        'UPSDAP' => [
            'url' => 'https://www.ups.com/track?tracknum=###TRACKING_NUMBER###',
        ],
        'DHL' => [
            'url' => 'https://www.dhl.com/tracking.html?tracking-id=###TRACKING_NUMBER###',
        ],
        'FedEx' => [
            'url' => 'https://www.fedex.com/fedextrack?trknbr=###TRACKING_NUMBER###',
        ],
        'USPS' => [
            'url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=###TRACKING_NUMBER###',
        ],
    ];

    public function generateTrackingLink(string $carrier, string|array $tracking): string
    {
        $config = self::shippingCarriers[$carrier] ?? null;
        if ($config) {
            if ($tracking && is_array($tracking) && isset($tracking['trk'])) {
                $tracking = $tracking['trk'];
            }
            return str_replace('###TRACKING_NUMBER###', $tracking, $config['url']);
        }
        $httpQuery = http_build_query($tracking);
        return 'https://yardsignplus.com/shipment/track/?' . $httpQuery;
    }
}