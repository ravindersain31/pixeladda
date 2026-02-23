<?php

namespace App\Service;

use App\Helper\PromoStoreHelper;
use Symfony\Component\HttpFoundation\RequestStack;

class ThemeService
{
    public function __construct(private readonly RequestStack $requestStack, private readonly PromoStoreHelper $promoStoreHelper) {}

    public function getThemeColors(): array
    {
        $host = $this->requestStack->getCurrentRequest()?->getHost();
        $isPromo =  $this->promoStoreHelper->isPromoStoreByUrl($host);
        if ($isPromo) {
            return [
                'primary' => '#25549b',
                'secondary' => '#FFC107',
                'light' => '#e8eefc',
                'background'=> '#f9f2ff',
                'background2'=> '#bbd2f5;',
                'background1' => '#869ec2ff',
                'primaryColorLight'=> '#25549bc4',
                'border' => '#8fafe0',
            ];
        }
        return [
            'primary' => '#6f4c9e',
            'secondary' => '#2ac473',
            'light' => '#7651a91f',
            'background' => '#f9f2ff',
            'background1' => '#ebdcff',
            'primaryColorLight' => '#a17fc1',
            'border' => '#6f4c9e',
            'background2'=> '#f8f0ff;',
        ];
    }
}
