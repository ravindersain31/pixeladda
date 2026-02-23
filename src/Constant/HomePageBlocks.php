<?php

namespace App\Constant;

class HomePageBlocks
{
    const BLOCKS = [
        'contractor' => [
            'title' => 'Contractor Yard Signs',
            'isCustom' => false,
            'products' => ['PT00021', 'PT00059', 'PT00079'],
            'linkToCategory' => 'contractor'
        ],
        'political' => [
            'title' => 'Political Yard Signs',
            'isCustom' => false,
            'products' => ['PO0075', 'PO00076', 'PO00086'],
            'linkToCategory' => 'political'
        ],
        'real-estate' => [
            'title' => 'Real Estate Yard Signs',
            'isCustom' => false,
            'products' => ['RE00167', 'RE00177', 'RE00187'],
            'linkToCategory' => 'real-estate'
        ],
        'business-ads' => [
            'title' => 'Business Ads Yard Signs',
            'isCustom' => false,
            'products' => ['BA0110', 'BA00111', 'BA00123'],
            'linkToCategory' => 'business-ads'
        ],
        'for-sale' => [
            'title' => 'For Sale Yard Signs',
            'isCustom' => false,
            'products' => ['FS00230', 'FS00041', 'FS00047'],
            'linkToCategory' => 'for-sale'
        ],
        'hand-fans' => [
            'title' => 'Hand Fans Yard Signs',
            'isCustom' => false,
            'products' => ['HF0041', 'HF0042', 'HF0047', 'HF0048', 'HF0049'],
            'linkToCategory' => 'hand-fans'
        ],
        // 'sign-riders' => [
        //     'title' => 'Sign Riders Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['SR00455', 'SR00469', 'SR00137'],
        //     'linkToCategory' => 'sign-riders'
        // ],
        // 'foreclosure' => [
        //     'title' => 'Foreclosure Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['FC00104', 'FC00115', 'FC00124'],
        //     'linkToCategory' => 'foreclosure'
        // ],
        // 'restaurant' => [
        //     'title' => 'Restaurant Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['RS00321', 'RS00091', 'RS00101'],
        //     'linkToCategory' => 'restaurant'
        // ],
        // 'birthday' => [
        //     'title' => 'Birthday Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['BD00154', 'BD00134', 'BD00144'],
        //     'linkToCategory' => 'birthday'
        // ],
        // 'graduation' => [
        //     'title' => 'Graduation Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['GR00093', 'GR00080', 'GR00083'],
        //     'linkToCategory' => 'graduation'
        // ],
        // 'church' => [
        //     'title' => 'Church Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['CC00266', 'CC00277', 'CC00007'],
        //     'linkToCategory' => 'church'
        // ],
        // 'community' => [
        //     'title' => 'Community Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['CU00011', 'CU00021', 'CU00052'],
        //     'linkToCategory' => 'community'
        // ],
        // 'health-safety' => [
        //     'title' => 'Health & Safety Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['HS0001', 'HS00702', 'HS00016'],
        //     'linkToCategory' => 'health-safety'
        // ],
        // 'protest' => [
        //     'title' => 'Protest Yard Signs',
        //     'isCustom' => false,
        //     'products' => ['PR00014', 'PR0001', 'PR00004'],
        //     'linkToCategory' => 'protest'
        // ],
    ];

    static public function getAllSkus(): array
    {
        $skus = [];
        foreach (self::BLOCKS as $block) {
            $skus = array_merge($skus, $block['products']);
        }
        return $skus;
    }
}