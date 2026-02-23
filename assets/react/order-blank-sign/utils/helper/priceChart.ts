import { VariantProps } from "@orderBlankSign/redux/reducer/config/interface";
import store from "@orderBlankSign/redux/store";
import { Frame } from "@orderBlankSign/utils/interface";

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

export const getVariantPriceByQty = (
    key: string,
    quantity: number
): number | null => {
    const { config } = store.getState();
    const pricing = config?.product?.pricing;

    const variantPricing = pricing?.variants?.['pricing_' + key]?.pricing;
    if (!variantPricing) return null;

    const quantities = pricing?.quantities || [];
    if (isNaN(quantity) || quantity <= 0) {
        return variantPricing['qty_' + quantities[0]]?.usd ?? null;
    }

    const applicableQty = quantities.filter(qty => qty <= quantity).pop();
    if (!applicableQty) {
        return variantPricing['qty_' + quantities[quantities.length - 1]]?.usd ?? null;
    }

    return variantPricing['qty_' + applicableQty]?.usd ?? null;
};