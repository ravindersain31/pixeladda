<?php

namespace App\Constant;

class NotFoundRouteMapping
{
    const URL_MAPPING = [
        '/read-estate' => '/real-estate',
        '/category/14' => '/',
        '/customer/login' => '/login',
        '/page/contact-us' => '/contact-us',
        '/page/customer-photos?page=1' => '/customer-photos',
        '/page/customer-photos?page=2' => '/customer-photos',
        '/page/customer-photos?page=3' => '/customer-photos',
        '/page/customer-photos?page=4' => '/customer-photos',
        '/page/gallery?page=2' => '/customer-photos',
        '/page/gallery?page=3' => '/customer-photos',
        '/shopnow' => '/shop-now',
        '/shop/order-sample' => '/order-sample',
        '/foreclosure/shop/yard-sign/order-sample' => '/order-sample',
        '/graduation/shop/yard-sign/order-sample' => '/order-sample',
        '/for-sale/shop/yard-sign/order-sample' => '/order-sample',
        '/protest/shop/yard-sign/order-sample' => '/order-sample',
        '/community/shop/yard-sign/order-sample' => '/order-sample',
        '/health-safety/shop/yard-sign/order-sample' => '/order-sample',
        '/sign-riders/shop/yard-sign/order-sample' => '/order-sample',
        '/church/shop/yard-sign/order-sample' => '/order-sample',
        '/political/shop/yard-sign/order-sample' => '/order-sample',
        '/holidays/shop/yard-sign/order-sample' => '/order-sample',
        '/business-ads/shop/yard-sign/order-sample' => '/order-sample',
        '/contractor/shop/yard-sign/order-sample' => '/order-sample',
        '/custom-mockup/shop/yard-sign/order-sample' => '/order-sample',
        '/restaurant/shop/yard-sign/order-sample' => '/order-sample',
        '/birthday/shop/yard-sign/order-sample' => '/order-sample',
        '/real-estate/shop/yard-sign/order-sample' => '/order-sample',
        '/custom-mockup' => '/custom-signs',
        '/basement-epoxy-flooring' => '/epoxy-flooring',
        '/conservative' => '/political',
        '/custom' => '/custom-signs',
        '/democratic' => '/political',
        '/diabetic' => '/diabetes',
        '/liberal' => '/political',
        '/maga' => '/political',
        '/presidential' => '/political',
        '/pre-packed' => '/yard-letters',
    ];

    const CATEGORY_MAPPING = [
      'newborn' => 'baby-shower'
    ];
}