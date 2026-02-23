<?php

namespace App\Twig;

use App\Helper\PromoStoreHelper;
use App\Service\StoreInfoService;
use App\Service\ThemeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StoreInfoExtension extends AbstractExtension
{
    public function __construct(private readonly StoreInfoService $storeInfoService, private readonly ThemeService $themeService, private readonly PromoStoreHelper $promoStoreHelper)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isPromoStore', [$this->storeInfoService, 'isPromoStore']),
            new TwigFunction('storeBasedUrl', [$this->storeInfoService, 'storeBasedUrl']),
            new TwigFunction('storeInfo', [$this->storeInfoService, 'storeInfo']),
            new TwigFunction('isPromoByUrl', [$this->promoStoreHelper, 'isPromoStoreByUrl']),
            new TwigFunction('themeColors', [$this->themeService, 'getThemeColors']),
        ];
    }
}