export enum WarehouseShippingServiceEnum {
    UPS_NEXT_DAY_AIR_SAVER = 'UPS_NEXT_DAY_AIR_SAVER',
    UPS_2ND_DAY_AIR = 'UPS_2ND_DAY_AIR',
    UPS_3_DAY_SELECT = 'UPS_3_DAY_SELECT',
    UPS_GROUND = 'UPS_GROUND',
    USPS_PRIORITY_MAIL = 'USPS_PRIORITY_MAIL',
    FEDEX_INTERNATIONAL_ECONOMY = 'FEDEX_INTERNATIONAL_ECONOMY',
    FEDEX_INTERNATIONAL_PRIORITY = 'FEDEX_INTERNATIONAL_PRIORITY',
    FEDEX_GROUND = 'FEDEX_GROUND',
    FEDEX_HOME = 'FEDEX_HOME',
    FEDEX_STANDARD_OVERNIGHT = 'FEDEX_STANDARD_OVERNIGHT',
    FEDEX_2_DAY = 'FEDEX_2_DAY',
    // FEDEX_EXPRESS_SAVER = 'FEDEX_EXPRESS_SAVER',
}

export const SHIPPING_SERVICE_ORDER = {
    [WarehouseShippingServiceEnum.UPS_NEXT_DAY_AIR_SAVER]: 1,
    [WarehouseShippingServiceEnum.FEDEX_STANDARD_OVERNIGHT]: 2,
    [WarehouseShippingServiceEnum.UPS_2ND_DAY_AIR]: 3,
    [WarehouseShippingServiceEnum.FEDEX_2_DAY]: 4,
    [WarehouseShippingServiceEnum.UPS_3_DAY_SELECT]: 5,
    [WarehouseShippingServiceEnum.UPS_GROUND]: 6,
    [WarehouseShippingServiceEnum.FEDEX_GROUND]: 7,
    [WarehouseShippingServiceEnum.FEDEX_HOME]: 8,
    [WarehouseShippingServiceEnum.USPS_PRIORITY_MAIL]: 9,
    [WarehouseShippingServiceEnum.FEDEX_INTERNATIONAL_ECONOMY]: 10,
    [WarehouseShippingServiceEnum.FEDEX_INTERNATIONAL_PRIORITY]: 11,
    // [WarehouseShippingServiceEnum.FEDEX_EXPRESS_SAVER]: 12,
};

export const SHIPPING_SERVICE = {
    [WarehouseShippingServiceEnum.UPS_NEXT_DAY_AIR_SAVER]: {
        label: 'UPS Next Day Air Saver',
    },
    [WarehouseShippingServiceEnum.UPS_2ND_DAY_AIR]: {
        label: 'UPS 2nd Day Air',
    },
    [WarehouseShippingServiceEnum.UPS_3_DAY_SELECT]: {
        label: 'UPS 3 Day Select',
    },
    [WarehouseShippingServiceEnum.UPS_GROUND]: {
        label: 'UPS Ground',
    },
    [WarehouseShippingServiceEnum.USPS_PRIORITY_MAIL]: {
        label: 'USPS Priority Mail',
    },
    [WarehouseShippingServiceEnum.FEDEX_INTERNATIONAL_ECONOMY]: {
        label: 'FedEx International Economy',
    },
    [WarehouseShippingServiceEnum.FEDEX_INTERNATIONAL_PRIORITY]: {
        label: 'FedEx International Priority',
    },
    [WarehouseShippingServiceEnum.FEDEX_GROUND]: {
        label: 'FedEx Ground',
    },
    [WarehouseShippingServiceEnum.FEDEX_HOME]: {
        label: 'FedEx Home Delivery',
    },
    [WarehouseShippingServiceEnum.FEDEX_STANDARD_OVERNIGHT]: {
        label: 'FedEx Standard Overnight',
    },
    [WarehouseShippingServiceEnum.FEDEX_2_DAY]: {
        label: 'FedEx 2Day',
    },
    // [WarehouseShippingServiceEnum.FEDEX_EXPRESS_SAVER]: {
    //     label: 'FedEx Express Saver (3 Day)',
    // },
};

export const FEDEX_SHIPPING_SERVICE = {
    [WarehouseShippingServiceEnum.FEDEX_STANDARD_OVERNIGHT]: SHIPPING_SERVICE[WarehouseShippingServiceEnum.FEDEX_STANDARD_OVERNIGHT],
    [WarehouseShippingServiceEnum.FEDEX_2_DAY]: SHIPPING_SERVICE[WarehouseShippingServiceEnum.FEDEX_2_DAY],
    // [WarehouseShippingServiceEnum.FEDEX_EXPRESS_SAVER]: SHIPPING_SERVICE[WarehouseShippingServiceEnum.FEDEX_EXPRESS_SAVER],
    [WarehouseShippingServiceEnum.FEDEX_HOME]: SHIPPING_SERVICE[WarehouseShippingServiceEnum.FEDEX_HOME],
}

export const makeFormChoices = (): { label: string; value: WarehouseShippingServiceEnum }[] => {
    return Object.entries(FEDEX_SHIPPING_SERVICE).map(([key, service]) => ({
        label: service.label,
        value: key as WarehouseShippingServiceEnum,
    }));
};

export const getShippingServiceLabel = (service: WarehouseShippingServiceEnum): string => {
    return SHIPPING_SERVICE[service]?.label || service;
};