<?php

namespace App\Twig;

use App\Enum\Admin\WarehouseOrderStatusEnum;
use App\Enum\Admin\WarehouseShippingServiceEnum;
use App\Enum\OrderChannelEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\ShippingEnum;
use App\Enum\WarehousePrinterEnum;

class EnumProvider
{
    public function getPaymentStatusLabel(string $status): string
    {
        return PaymentStatusEnum::getLabel($status);
    }

    public function getOrderStatusLabel(string $status): string
    {
        return OrderStatusEnum::LABELS[$status] ?? $status;
    }

    public function getShippingLabel(string $shipping): string
    {
        return ShippingEnum::LABELS[$shipping] ?? $shipping;
    }

    public function getWarehousePrinterName(string $printer, bool $coloredText = false, bool $badge = false): string
    {
        return WarehousePrinterEnum::getLabel($printer, $coloredText, $badge);
    }
    public function getWarehouseOrderStatus(string $status, bool $coloredText = false, bool $badge = false): string
    {
        return WarehouseOrderStatusEnum::getLabel($status, $coloredText, $badge);
    }

    public function getWarehouseShippingService(?string $shippingService): string
    {
        if(!$shippingService) return '';
        return WarehouseShippingServiceEnum::getLabel($shippingService);
    }

}