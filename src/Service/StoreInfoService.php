<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Store;
use App\Entity\StoreDomain;
use App\Enum\PromoStoreEnum;
use App\Repository\StoreDomainRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Enum\StoreConfigEnum;
use App\Helper\PromoStoreHelper;

class StoreInfoService
{
    public function __construct(private readonly PromoStoreHelper $promoStoreHelper,private readonly UrlGeneratorInterface $urlGenerator, private readonly RequestStack $requestStack, private readonly StoreDomainRepository $storeDomainRepository) 
    {
    }

    public function isPromoStore(?StoreDomain $storeDomain = null): bool
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

    public function storeInfo(?Order $order = null): array
    {
        $storeUrl = $this->urlGenerator->generate('homepage');
        $storeName = StoreConfigEnum::STORE_NAME;
        $storeEmail = StoreConfigEnum::SALES_EMAIL;
        $storeSupportEmail = StoreConfigEnum::SUPPORT_EMAIL;
        $storeScholarshipsEmail = StoreConfigEnum::SCHOLARSHIPS_EMAIL;
        $storeYardLogo = StoreConfigEnum::YARD_SIGN_LOGO;
        $storeYardLogoOther = StoreConfigEnum::YARD_SIGN_LOGO_OTHER;
        $logoTitle = StoreConfigEnum::YSP_LOGO_TEXT;
        $logoDiscountText = StoreConfigEnum::YSP_DISCOUNT_TEXT;
        $request = $this->requestStack->getCurrentRequest();
        // $domain = $request?->getHost(); 
        $domain = 'local.yardsignplus.com';
        $storeDomain = $domain ? $this->storeDomainRepository->findOneBy(['domain' => $domain]) : null;

        if ($order && $order->getStoreDomain()) {
            $storeDomain = $order->getStoreDomain();
        }

        if ($storeDomain && $this->isPromoStore($storeDomain)) {
            $storeUrl = $this->storeBasedUrl($storeUrl, $storeDomain);
            $storeName = StoreConfigEnum::PROMO_STORE_NAME;
            $storeEmail = StoreConfigEnum::PROMO_SALES_EMAIL;
            $storeSupportEmail = StoreConfigEnum::PROMO_SUPPORT_EMAIL;
            $storeScholarshipsEmail = StoreConfigEnum::PROMO_SCHOLARSHIPS_EMAIL;
            $storeYardLogo = StoreConfigEnum::PROMO_LOGO;
            $storeYardLogoOther = StoreConfigEnum::PROMO_LOGO;
            $logoTitle = StoreConfigEnum::PROMO_LOGO_TEXT;
            $logoDiscountText = StoreConfigEnum::PROMO_DISCOUNT_TEXT;
        }

        return [
            'storeUrl' => $storeUrl,
            'storeTitle' => $storeName,
            'logoTitle' => $logoTitle,
            'logoDiscountText' => $logoDiscountText,
            'storeName' => $this->formatStoreName($storeName),
            'storeDomain' => $this->getBaseDomain($storeUrl),
            'storeHost' => $storeDomain?->getDomain() ?? $domain,
            'storeEmail'  => $storeEmail,
            'storeSupportEmail'  => $storeSupportEmail,
            'storeScholarshipsEmail'  => $storeScholarshipsEmail,
            'storeYardLogo' => $storeYardLogo,
            'storeYardLogoOther' => $storeYardLogoOther,
            'isPromoStore' => $this->isPromoStoreFlag($storeDomain),
        ];
    }

    private function formatStoreName(string $storeName): string
    {
        return trim(preg_replace('/([a-z])([A-Z])/', '$1 $2', $storeName));
    }

    private function getBaseDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? $url;
        return preg_replace('/^(?:\w+\.)?(\w+\.\w+)$/', '$1', $host) ?: $host;
    }

    public function getStoreName(?StoreDomain $storeDomain = null): string
    {
        return $this->isPromoStoreFlag($storeDomain)
            ? StoreConfigEnum::PROMO_STORE_NAME
            : StoreConfigEnum::STORE_NAME;
    }

    public function getSalesEmail(?StoreDomain $storeDomain = null): string
    {
        return $this->isPromoStoreFlag($storeDomain)
            ? StoreConfigEnum::PROMO_SALES_EMAIL
            : StoreConfigEnum::SALES_EMAIL;
    }

    private function isPromoStoreFlag(?StoreDomain $storeDomain): bool
    {
        if ($storeDomain) {
            return $this->isPromoStore($storeDomain);
        }

        $host = $this->requestStack->getCurrentRequest()->getHost();
        return $this->promoStoreHelper->isPromoStoreByUrl($host);
    }



    public function getStore(): ?Store
    {
        $domain = $this->requestStack->getCurrentRequest()?->getHost();
        $storeDomain = $domain ? $this->storeDomainRepository->findOneBy(['domain' => $domain]) : null;
        return $storeDomain?->getStore();
    }
}