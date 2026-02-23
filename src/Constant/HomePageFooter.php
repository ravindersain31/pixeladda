<?php

namespace App\Constant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HomePageFooter extends AbstractController
{
    const REVIEW_TITLE = 'OUR SATISFIED CUSTOMERS!';
    const TOTAL_REVIEWS = 144;
    const REVIEWS = [
        1 => [
            'name' => 'Miguel T.',
            'image' => 'https://static.yardsignplus.com/storage/images/fit-in/200x200/review-person/Miguel T.png',
            'stars' => 5,
            'comment' => 'Love the order process! So much fun and easy to customize online.'
        ],
        2 => [
            'name' => 'Marium G.',
            'image' => 'https://static.yardsignplus.com/storage/images/fit-in/200x200/review-person/Marium G.png',
            'stars' => 5,
            'comment' => "Love their prices. You won't find lower prices anywhere else for custom yard signs. Quality is awesome..."
        ],
        3 => [
            'name' => 'David J.',
            'image' => 'https://static.yardsignplus.com/storage/images/fit-in/200x200/review-person/David J.png',
            'stars' => 5,
            'comment' => "I ordered yard signs for our annual campaign through them and their quality is flawless. Their chat support is incredible. Will be ordering again"
        ],
        4 => [
            'name' => 'Sara W.',
            'image' => 'https://static.yardsignplus.com/storage/images/fit-in/200x200/review-person/Sara W.png',
            'stars' => 5,
            'comment' => "I was very impressed with your products and turnaround time. Yes, I will recommend your business."
        ],
        5 => [
            'name' => 'Jayden S.',
            'image' => 'https://static.yardsignplus.com/storage/images/fit-in/200x200/review-person/jayden.webp',
            'stars' => 5,
            'comment' => "I was fascinated with how easy it is to customize my yard signs and the prices are the most competitive. I will soon repeat my order for future events!."
        ]
    ];

    const BANNERS = [
        [
            'desktop' => 'https://static.yardsignplus.com/storage/images/YSP_Home_Discount_desktop_2025.webp',
            'mobile' => 'https://static.yardsignplus.com/storage/images/YSP_Home_discount_mobile_2025.webp',
            'route_name' => 'custom_yard_sign_editor',
        ],
        [
            'desktop' => 'https://static.yardsignplus.com/storage/banners/desktop-discount-above-1500.webp',
            'mobile' => 'https://static.yardsignplus.com/storage/banners/mobile-discount-above-1500.webp',
            'route_name' => 'custom_yard_sign_editor',

        ],
        [
            'desktop' => 'https://static.yardsignplus.com/storage/banners/home-banner-desktop-69426373e57f2584700880.webp',
            'mobile' => 'https://static.yardsignplus.com/storage/banners/home-banner-mobile-694263771f8ba403165693.webp',
            'route_name' => 'custom_yard_sign_editor',

        ],
        [
            'desktop' => 'https://static.yardsignplus.com/storage/banners/Home-Banner-Desktop-2025.webp',
            'mobile' => 'https://static.yardsignplus.com/storage/banners/Home-Banner-Mobile-2025.webp',
            'route_name' => 'custom_yard_sign_editor',

        ],
        [
            'desktop' => 'https://static.yardsignplus.com/storage/banners/how-to-order-banner-desktop-694264db71e44947647751.webp',
            'mobile' => 'https://static.yardsignplus.com/storage/banners/how-to-order-banner-mobile-694264dd9486d571421927.webp',
            'route_name' => 'how_to_order',
        ],
    ];

    const PROMO_BANNERS = [
        [
            'desktop' => 'https://static.yardsignplus.com/storage/banners/banner-wholesalers-bulk-discounts-desktop-6948e21e576af584661355.webp',
            'mobile' => 'https://static.yardsignplus.com/storage/banners/banner-wholesalers-bulk-discounts-mobile-2-6948e22472334157674042.webp',
            'route_name' => 'whole_seller_create_account',
        ],
        [
            'desktop' => 'https://static.yardsignplus.com/storage/banners/ysp-discount-01-banner-desktop-1-6943e564d354a415532414.webp',
            'mobile' => 'https://static.yardsignplus.com/storage/banners/ysp-discount-01-banner-mobile-1-6943e8a4b3f68763092734.webp',
            'route_name' => 'custom_yard_sign_editor',
        ],
        [
            'desktop' => 'https://static.yardsignplus.com/storage/promo-store/Lowest-Price-Banner-Ship-01.webp',
            'mobile' => 'https://static.yardsignplus.com/storage/promo-store/Mobile_Home.webp',
            'route_name' => 'custom_yard_sign_editor',
        ]
    ];

    const INFO_TITLE = 'CALL US NOW!';
    const INFO = [
        'Expert Help Always Available',
        'Bulk Discounts on Large Quantities',
        'FREE Design Previews',
        'FREE Shipping on Qualifying Orders',
        'No Tax',
        'No Minimum Order Quantities',
        'FREE EXPERT Design Customization Assistance',
        'FREE Custom Artwork Creation',
        'No Setup Fees',
        'No Custom Design Fees',
        'See Design - Pay Later, Risk FREE',
    ];
    const INFO_TELEPHONE = '+1-877-958-1499';
    const INFO_EMAIL = 'sales@yardsignplus.com';
    const PROMO_INFO_EMAIL = 'sales@yardsignpromo.com';


    public function getHomeIcons(): array
    {
        $icons = [
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-02.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Sign Riders Sign',
                'title' => 'Sign Riders',
                'url' => $this->generateUrl('category', ['slug' => 'sign-riders'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Sign Riders Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-03.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Restaurant Sign',
                'title' => 'Restaurant',
                'url' => $this->generateUrl('category', ['slug' => 'restaurant'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Restaurant Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-04.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Foreclosure Sign',
                'title' => 'Foreclosure',
                'url' => $this->generateUrl('category', ['slug' => 'foreclosure'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Foreclosure Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-05.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Birthday Sign',
                'title' => 'Birthday',
                'url' => $this->generateUrl('category', ['slug' => 'birthday'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Birthday Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-06.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Graduation Sign',
                'title' => 'Graduation',
                'url' => $this->generateUrl('category', ['slug' => 'graduation'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Graduation Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-07.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Church Sign',
                'title' => 'Church',
                'url' => $this->generateUrl('category', ['slug' => 'church'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Church Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-08.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Community Sign',
                'title' => 'Community',
                'url' => $this->generateUrl('category', ['slug' => 'community'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Community Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-16.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Health & Safety Sign',
                'title' => 'Health & Safety',
                'url' => $this->generateUrl('category', ['slug' => 'health-safety'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Health & Safety Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-17.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Protest Sign',
                'title' => 'Protest',
                'url' => $this->generateUrl('category', ['slug' => 'protest'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Protest Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-18.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Holidays Sign',
                'title' => 'Holidays',
                'url' => $this->generateUrl('category', ['slug' => 'holidays'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Holidays Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-19.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes No Soliciting Sign',
                'title' => 'No Soliciting',
                'url' => $this->generateUrl('category', ['slug' => 'no-soliciting'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes No Soliciting Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-20.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Events Sign',
                'title' => 'Events',
                'url' => $this->generateUrl('category', ['slug' => 'events'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Events Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-09.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Religion Sign',
                'title' => 'Religion',
                'url' => $this->generateUrl('category', ['slug' => 'religion'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Religion Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-10.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Traffic Sign',
                'title' => 'Traffic',
                'url' => $this->generateUrl('category', ['slug' => 'traffic'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Traffic Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-11.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Warehouse Sign',
                'title' => 'Warehouse',
                'url' => $this->generateUrl('category', ['slug' => 'warehouse'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Warehouse Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-12.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Sports Sign',
                'title' => 'Sports',
                'url' => $this->generateUrl('category', ['slug' => 'sports'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Sports Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-13.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Pride Sign',
                'title' => 'Pride',
                'url' => $this->generateUrl('category', ['slug' => 'pride'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Pride Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-14.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Wedding Sign',
                'title' => 'Weddings',
                'url' => $this->generateUrl('category', ['slug' => 'wedding'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Wedding Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-15.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Baby Shower Sign',
                'title' => 'Baby Shower',
                'url' => $this->generateUrl('category', ['slug' => 'baby-shower'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Baby Shower Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-21.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Military Sign',
                'title' => 'Military',
                'url' => $this->generateUrl('category', ['slug' => 'military'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Military Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-22.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Government Sign',
                'title' => 'Government',
                'url' => $this->generateUrl('category', ['slug' => 'government'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Government Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-23.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Animals Sign',
                'title' => 'Animals',
                'url' => $this->generateUrl('category', ['slug' => 'animals'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Animals Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-24.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Home Sign',
                'title' => 'Home',
                'url' => $this->generateUrl('category', ['slug' => 'home'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Home Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/YSP-25.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Pool Sign',
                'title' => 'Pool',
                'url' => $this->generateUrl('category', ['slug' => 'pool'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Pool Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/Business.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Business Ads Sign',
                'title' => 'Business Ads',
                'url' => $this->generateUrl('category', ['slug' => 'business-ads'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Business Ads Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/Contractor.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Contractor Sign',
                'title' => 'Contractor',
                'url' => $this->generateUrl('category', ['slug' => 'contractor'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Contractor Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/Flags.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Flags Sign',
                'title' => 'Flags',
                'url' => $this->generateUrl('category', ['slug' => 'flags'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Flags Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/For-Sale.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes For Sale Sign',
                'title' => 'For Sale',
                'url' => $this->generateUrl('category', ['slug' => 'for-sale'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes For Sale Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/Political.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Political Sign',
                'title' => 'Political',
                'url' => $this->generateUrl('category', ['slug' => 'political'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Political Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/Prepacked.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Pre Packed Sign',
                'title' => 'Pre Packed',
                'url' => $this->generateUrl('category', ['slug' => 'yard-letters'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Pre Packed Sign'
            ],
            [
                'src' => 'https://static.yardsignplus.com/fit-in/500x500/storage/icons/Real-Estate.webp',
                'alt' => 'Custom Outdoor Yard Signs Multiple Sizes Real Estate Sign',
                'title' => 'Real Estate',
                'url' => $this->generateUrl('category', ['slug' => 'real-estate'], UrlGeneratorInterface::ABSOLUTE_URL),
                'description' => 'Custom Outdoor Yard Signs Multiple Sizes Real Estate Sign'
            ],
        ];

        return $icons;
    }


    public static function getConstants()
    {
        return [
            'REVIEW_TITLE' => self::REVIEW_TITLE,
            'REVIEWS' => self::REVIEWS,
            'TOTAL_REVIEWS' => self::TOTAL_REVIEWS,
            'INFO' => self::INFO,
            'INFO_EMAIL' => self::INFO_EMAIL,
            'PROMO_INFO_EMAIL' => self::PROMO_INFO_EMAIL,
            'INFO_TITLE' => self::INFO_TITLE,
            'INFO_TELEPHONE' => self::INFO_TELEPHONE,
            'BANNERS' => self::BANNERS,
            'PROMO_BANNERS' => self::PROMO_BANNERS,
        ];
    }

    public function getActiveBanners(): array
    {
        $banners = self::BANNERS;
        $today = new \DateTime();
        return array_filter($banners, function ($banner) use ($today) {
            $fromDate = isset($banner['from']) ? new \DateTime($banner['from']) : null;
            $endDate = isset($banner['end']) ? new \DateTime($banner['end']) : null;

            return (!$fromDate || $fromDate <= $today) && (!$endDate || $endDate >= $today);
        });
    }
}