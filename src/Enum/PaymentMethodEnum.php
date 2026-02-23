<?php

namespace App\Enum;

enum PaymentMethodEnum
{
    const NO_PAYMENT = 'NO_PAYMENT';

    const CREDIT_CARD = 'CREDIT_CARD';

    const PAYPAL = 'PAYPAL';

    const PAYPAL_EXPRESS = 'PAYPAL_EXPRESS';

    const GOOGLE_PAY = 'GOOGLE_PAY';

    const CHECK = 'CHECK';

    const SEE_DESIGN_PAY_LATER = 'SEE_DESIGN_PAY_LATER';

    const STRIPE = 'STRIPE';
    
    const AMAZON_PAY = 'AMAZON_PAY';

    const AFFIRM = 'AFFIRM';

    const APPLE_PAY = 'APPLE_PAY';

    const HELP_MESSAGES = [
        self::CREDIT_CARD => 'Please wait while we authenticate you.',
        self::STRIPE => 'You will be redirected on Stripe to complete the payment.',
        self::PAYPAL => 'You will be redirected on PayPal to complete the payment.',
        self::GOOGLE_PAY => 'You will be redirected on Google Pay to complete the payment.',
        self::AMAZON_PAY => 'Amazon Pay',
        self::APPLE_PAY => 'You will be redirected on Apple Pay to complete the payment.',
        self::AFFIRM => 'You will be redirected on Affirm to complete the payment. Affirm: for personal use. Terms apply.',
        self::CHECK => 'Your products will not be shipped until an official check or purchase order is received.',
        self::SEE_DESIGN_PAY_LATER => 'At Checkout you may choose to pay after receiving and approving of a design sent from us. Please note that your order will not go into production until payment is received. The final delivery date will be determined after payment is received and the design is approved. Kindly keep a check on your email for the digital proof we will be sending you shortly.',
    ];

    const LABELS = [
        self::CREDIT_CARD => 'Credit Card',
        self::STRIPE => 'Stripe',
        self::PAYPAL => 'Paypal',
        self::GOOGLE_PAY => 'Google Pay',
        self::AMAZON_PAY => 'Amazon Pay',
        self::APPLE_PAY => 'Apple Pay',
        self::AFFIRM => 'Affirm',
        self::CHECK => 'Check/PO',
        self::SEE_DESIGN_PAY_LATER => 'See Design Pay Later',
    ];

    public static function getLabel(string $status): string
    {
        if (isset(self::LABELS[$status])) {
            return self::LABELS[$status];
        }
        return $status;
    }
}