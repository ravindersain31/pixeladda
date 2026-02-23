<?php

namespace App\Helper;

use App\Entity\StoreDomain;
use App\Enum\PromoStoreEnum;

class PromoStoreHelper
{
    public function isPromoStore(?StoreDomain $storeDomain = null)
    {
        return $storeDomain && str_contains($storeDomain->getDomain(), PromoStoreEnum::YARD_SIGN_PROMO->value);
    }

    public function storeBasedUrl(string $url, ?StoreDomain $storeDomain = null): string
    {
        if (!$this->isPromoStore($storeDomain) ) {
            return $url; 
        }

        return preg_replace('/\/\/[^\/]+/', '//' . $storeDomain->getDomain(), $url, 1);
    }

    public function isPromoStoreByUrl(?string $url = null): bool
    {
        if (!$url) {
            return false;
        }

        return str_contains($url, PromoStoreEnum::YARD_SIGN_PROMO->value);
    }


    public function storeInfo(?StoreDomain $storeDomain = null): array
    {
        $storeName                = PromoStoreEnum::STORE_NAME;
        $storeEmail               = PromoStoreEnum::SALES_EMAIL;
        $storeSupportEmail        = PromoStoreEnum::SUPPORT_EMAIL;
        $storeScholarshipsEmail   = PromoStoreEnum::SCHOLARSHIPS_EMAIL;
        $storeYardLogo            = PromoStoreEnum::YARD_SIGN_LOGO;
        $storeYardLogoOther       = PromoStoreEnum::YARD_SIGN_LOGO_OTHER;
        $logoTitle                = PromoStoreEnum::YSP_LOGO_TEXT;
        $logoDiscountText         = PromoStoreEnum::YSP_DISCOUNT_TEXT;

        if ($this->isPromoStore($storeDomain)) {
            $storeName              = PromoStoreEnum::PROMO_STORE_NAME;
            $storeEmail             = PromoStoreEnum::PROMO_SALES_EMAIL;
            $storeSupportEmail      = PromoStoreEnum::PROMO_SUPPORT_EMAIL;
            $storeScholarshipsEmail = PromoStoreEnum::PROMO_SCHOLARSHIPS_EMAIL;
            $storeYardLogo          = PromoStoreEnum::PROMO_LOGO;
            $storeYardLogoOther     = PromoStoreEnum::PROMO_LOGO;
            $logoTitle              = PromoStoreEnum::PROMO_LOGO_TEXT;
            $logoDiscountText       = PromoStoreEnum::PROMO_DISCOUNT_TEXT;
        }

        return [
            'storeTitle'              => $storeName,
            'logoTitle'               => $logoTitle,
            'logoDiscountText'        => $logoDiscountText,
            'storeEmail'              => $storeEmail,
            'storeSupportEmail'       => $storeSupportEmail,
            'storeScholarshipsEmail'  => $storeScholarshipsEmail,
            'storeYardLogo'           => $storeYardLogo,
            'storeYardLogoOther'      => $storeYardLogoOther,
        ];
    }

}