<?php

namespace App\Enum;

enum WholeSellerEnum: string
{
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case LOGIN_ROUTE = 'login';
    case WHOLE_SELLER_LOGIN_ROUTE = 'whole_seller_login';
    case WHOLE_SELLER_LOGIN_PATH = '/whole-seller-login';
    case LOGIN_ROUTE_PATH = '/login';
    case WHOLE_SELLER_CREATE_ACCOUNT_PATH = '/whole-seller-create-account';
    case WHOLE_SELLER_CREATE_ACCOUNT_ROUTE = 'whole_seller_create_account';
    case CREATE_ACCOUNT_ROUTE = 'create_account';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
        };
    }
}