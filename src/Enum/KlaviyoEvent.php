<?php

namespace App\Enum;

enum KlaviyoEvent: string
{
    public const ADDED_TO_CART     = 'Added To Cart';
    public const STARTED_CHECKOUT  = 'Started Checkout';
    public const VIEWED_PRODUCT    = 'Viewed Product';
    public const PLACED_ORDER      = 'Placed Order';
    public const ACTIVE_ON_SITE    = 'Active On Site';
    public const CANCELLED_ORDER   = 'Cancelled Order';
    public const FULFILLED_ORDER   = 'Fulfilled Order';
    public const SAVE_YOUR_DESIGN   = 'Save Your Design';
    public const SAVE_CART   = 'Save Cart';
    public const SAVE_EMAIL_QUOTE   = 'Save Email Quote';
}
