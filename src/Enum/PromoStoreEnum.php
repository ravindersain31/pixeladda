<?php

namespace App\Enum;

enum PromoStoreEnum: string
{
    case YARD_SIGN_PROMO = 'yardsignpromo';
    const SALES_EMAIL = 'sales@yardsignplus.com';
    const SCHOLARSHIPS_EMAIL = 'scholarships@yardssignplus.com';
    const PROMO_SCHOLARSHIPS_EMAIL = 'scholarships@yardsignpromo.com';
    const PROMO_SALES_EMAIL = 'sales@yardsignpromo.com';
    const SUPPORT_EMAIL = 'support@yardsignplus.com';
    const PROMO_SUPPORT_EMAIL = 'support@yardsignpromo.com';
    const ADMIN_EMAIL = 'sales@yardsignplus.com';
    const CONTACT_US_EMAIL = 'contactus@yardsignplus.com';
    const RECEIPT_EMAIL = 'receipt@yardsignplus.com';
    const ARTWORK_EMAIL = 'artwork@yardsignplus.com';
    const NOREPLY_EMAIL = 'noreply@yardsignplus.com';
    const ACTIVE_STORAGE_HOST = 'https://static.yardsignplus.com/';
    const STORE_NAME = 'YardSignPlus';
    const YSP_LOGO_TEXT = 'YSP Logo';
    const PROMO_LOGO_TEXT = 'Promo Logo';
    const YSP_DISCOUNT_TEXT = 'YSP Logo';
    const PROMO_DISCOUNT_TEXT = 'Promo Logo';
    const STORE_URL = 'Yardsignplus.com';
    const PROMO_STORE_NAME = 'YardSignPromo';
    const PROMO_STORE_URL = 'Yardsignpromo.com';
    const YARD_SIGN_LOGO = 'https://static.yardsignplus.com/logo.png';
    const PROMO_LOGO = 'https://static.yardsignplus.com/assets/logo-white-6941171f4b68f898242867.png';
    const YARD_SIGN_LOGO_OTHER = 'https://static.yardsignplus.com/fit-in/300x300/assets/ysp-logo.png';

    public static function isPromoHost(string $host): bool
    {
        return str_contains($host, self::YARD_SIGN_PROMO->value);
    }
}
