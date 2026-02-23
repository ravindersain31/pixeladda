import dayjs from "dayjs";

// internal imports
import AppState from "@orderSample/redux/interface";
import store from "@orderSample/redux/store";
import { DeliveryMethod, SHIPPING_MAX_DISCOUNT_AMOUNT } from "@orderSample/redux/reducer/cart/interface";
import { getPriceFromPriceChart } from "@orderSample/utils/helper";

export const getShippingFromShippingChart = (state: AppState): any => {
    const { config, cartStage } = state;
    const shipping: any = config.product.shipping;
    const quantity = cartStage.totalQuantity + (config.cart.totalQuantity - config.cart.currentItemQuantity);

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
    return (currentDay === 3 && currentTime >= saturdayCutoffHour) ||  // Wednesday after cutoff
        currentDay === 4 ||  // All day Thursday
        (currentDay === 5 && currentTime < saturdayCutoffHour);  // Friday before cutoff
};


export const hasSaturdayDelivery = (shippingDates: any) => {
    return Object.values(shippingDates).some((day: any) => day.isSaturday === true);
};

export const calculateRemainingAmountForFreeShipping = (): number => {
    const state = store.getState();
    const remainingAmount = SHIPPING_MAX_DISCOUNT_AMOUNT - (state.cartStage.subTotalAmount + (state.config.cart.subTotal - state.config.cart.currentItemSubtotal));
    return remainingAmount > 0 ? parseFloat(remainingAmount.toFixed(2)) : 0;
}

export const getShippingRateByDayNumber = (dayNumber: number, productShipping: any, state: AppState) => {
    const { config, cartStage } = state ?? store.getState();
    const shippingDatesByQuantities = getShippingFromShippingChart(state);
    const shipping = shippingDatesByQuantities[`day_${dayNumber}`];

    if (!shipping) return 0;
    if (shipping.free) return 0;
    const pricing = shipping.pricing;
    const { currencyCode = 'USD' } = config.store;

    const quantity = cartStage.totalQuantity + (config.cart.totalQuantity - config.cart.currentItemQuantity);

    let price = getPriceFromPriceChart(pricing, quantity, currencyCode);

    if (cartStage.deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP) {
        return price - (price * cartStage.deliveryMethod.discount) / 100;
    }
    return price;
}
export const getDiscountedPrice = (originalPrice: number, discountPercentage: number): number => {
    return Math.min(((discountPercentage / 100) * originalPrice), SHIPPING_MAX_DISCOUNT_AMOUNT);
}