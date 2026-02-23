import { VariantProps } from "@orderSample/redux/reducer/config/interface";
import store from "@orderSample/redux/store";

export const getVariantLabel = (variantName: string, variants: VariantProps[]) => {
    variantName = variantName.replaceAll('pricing_', '');
    for (const variant of variants) {
        if (variant.name === variantName && variant.label) {
            return variant.label;
        }
    }
    return variantName;
}

export const getPriceFromPriceChart = (pricing: any, quantity: number, currencyCode: string = 'usd') => {
    for (const [key, value] of Object.entries(pricing)) {
        const tier: any = value;
        if (quantity >= tier.qty.from && quantity <= tier.qty.to) {
            return tier[currencyCode.toLowerCase()];
        }
    }
    return 0;
}

/**
 * Returns the frame price by quantity for a given Frame enum key.
 * Returns base tier price if quantity is 0 or invalid.
 *
 * @param frameKey - enum value from Frame
 * @param quantity - quantity to get price for
 * @returns unit price or null
 */
export const getFramePriceByQty = (
    frameKey: string,
    quantity: number
): number | null => {
    const { config } = store.getState();
    const pricing = config?.product?.pricing;

    const framePricing = pricing?.variants?.['pricing_' + frameKey]?.pricing;
    if (!framePricing) return null;

    const quantities = pricing?.quantities || [];
    if (isNaN(quantity) || quantity <= 0) {
        return framePricing['qty_' + quantities[0]]?.usd ?? null;
    }

    const applicableQty = quantities.filter(qty => qty <= quantity).pop();
    if (!applicableQty) {
        return framePricing['qty_' + quantities[quantities.length - 1]]?.usd ?? null;
    }

    return framePricing['qty_' + applicableQty]?.usd ?? null;
};