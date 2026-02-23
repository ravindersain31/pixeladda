import store from '@react/editor/redux/store.ts';
import {getPriceFromPriceChart} from "@react/editor/helper/pricing.ts";
import {DeliveryMethod, ItemProps, SHIPPING_MAX_DISCOUNT_AMOUNT, SHIPPING_MAX_DISCOUNT_AMOUNT_10} from '../redux/reducer/editor/interface';
import {isBiggerSize} from "@react/editor/helper/template.ts";
import AppState from '../redux/interface';
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";

dayjs.extend(utc);
dayjs.extend(timezone);

export const getShippingRateByDayNumber = (dayNumber: number, productShipping: any, state: any = null) => {
    const {config, editor} = state ?? store.getState();
    const shippingDatesByQuantities = getShippingFromShippingChart(state);
    const shipping = shippingDatesByQuantities[`day_${dayNumber}`];

    if (!shipping) return 0;
    if (shipping.free) return 0;
    const pricing = shipping.pricing;
    const {currencyCode = 'USD'} = config.store;

    const quantity = editor.totalQuantity + (config.cart.totalQuantity - config.cart.currentItemQuantity);

    let price = getPriceFromPriceChart(pricing, quantity, currencyCode);
    // if (isItemsHasBiggerSize(editor.items) || config.cart.hasBiggerSizes) {
    //     // make price 2x
    //     price *= 2;
    // }

    if (editor.deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP) {
        return price - (price * editor.deliveryMethod.discount) / 100;
    }

    return price;
}

export const isItemsHasBiggerSize = (items: {
    [key: string]: ItemProps
}) => {
    let hasBiggerSize = false;
    for (const key in items) {
        const item = items[key];
        if (isBiggerSize(`${item.customSize.templateSize.width}x${item.customSize.templateSize.height}`) && item.quantity > 0) {
            hasBiggerSize = true;
            break;
        }
    }
    return hasBiggerSize;
}

export const hasSaturdayDelivery = (shippingDates : any) => {
    return Object.values(shippingDates).some((day : any) => day.isSaturday === true);
};

export const getDiscountedPrice = (originalPrice: number, discountPercentage: number): number => {
    const maxCap = getMaxDiscountCap(discountPercentage);
    return Math.min((discountPercentage / 100) * originalPrice, maxCap);
}

export const getMaxDiscountCap = (discountPercentage: number): number =>
    discountPercentage <= 5 ? SHIPPING_MAX_DISCOUNT_AMOUNT : SHIPPING_MAX_DISCOUNT_AMOUNT_10;
  
export const getShippingFromShippingChart = (state: AppState): any => {
    const { config, editor } = state;
    const shipping: any = config.product.shipping;
    const quantity = editor.totalQuantity + (config.cart.totalQuantity - config.cart.currentItemQuantity);

    for (const [key, value] of Object.entries(shipping)) {
        const tier: any = value;
        const from: number = tier.from;
        const to: number = tier.to;

        if (quantity >= from && (to === null || quantity < to)) {
            return tier.shippingDates;
        }
    }

    return shipping['qty_1'].shippingDates;
}

export const checkSaturdayDeliveryEligibility = () => {
    const saturdayCutoffHour = "16:00:00";
    const currentDay = dayjs().day();
    const currentTime = dayjs().format('HH:mm:ss');

    return (
        (currentDay === 4 && currentTime >= saturdayCutoffHour) || // Thursday after 4 PM
        (currentDay === 5 && currentTime < saturdayCutoffHour)     // Friday before 4 PM
    );
};