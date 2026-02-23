import { getPriceFromPriceChart } from "@react/editor/helper/pricing";
import { DeliverDateProp } from "@react/editor/redux/reducer/editor/interface";
import { TemplateSizeProps } from "./FormData";
import { isBiggerSize } from "@react/editor/helper/template";

export interface ShippingProps {
    day: number;
    date: string;
    amount: number;
}

export const getShippingFromShippingChart = (shipping: any, qty: number): any => {
    for (const [key, value] of Object.entries(shipping)) {
        const tier: any = value;
        const from: number = tier.from;
        const to: number = tier.to;

        if (qty >= from && (to === null || qty < to)) {
            return tier.shippingDates;
        }
    }

    return shipping['qty_1'].shippingDates;
};

export const getShippingAndDelivery = (
    qty: number,
    shippingChart: any,
    totalAmount: number,
    templateSize: TemplateSizeProps
  ): { deliveryDate: DeliverDateProp, shipping: ShippingProps } => {
    const shippingDates = Object.values(shippingChart);

    let selectedDay: any;

    if (totalAmount < 50) {
      selectedDay = shippingDates[shippingDates.length - 3];
    } else {
      selectedDay = shippingDates[shippingDates.length - 2];
    }

    const { day, isSaturday, free, date, discount, timestamp, pricing } = selectedDay;

    let amount = 0;

    if (pricing) {
      amount = getPriceFromPriceChart(pricing, qty, 'usd');
    }

    // Handle bigger size
    // if (isBiggerSize(`${templateSize.width}x${templateSize.height}`) && qty > 0) {
    //   amount *= 2;
    // }

    const deliveryDate = {
        day,
        isSaturday,
        free,
        date,
        discount,
        timestamp,
        ...(pricing ? { pricing } : { shipping : selectedDay.shipping }),
    };

    const shipping = {
      day,
      date,
      amount: amount || 0
    };

    return { deliveryDate, shipping };
  };
