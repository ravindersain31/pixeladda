import {getPriceFromPriceChart, number_format} from "@react/editor/helper/pricing";
import {Flute, Frame, GrommetColor, Grommets, ImprintColor, Shape, Sides} from "@react/editor/redux/interface";
import {AddOnPrices} from "@react/editor/redux/reducer/editor/interface";
import {enhanceAddonConfig} from "@react/editor/helper/pricing";
import {Addons} from "@react/editor/redux/reducer/config/interface";
import { AddOnProps } from "@react/editor/redux/reducer/editor/interface";
import { isDisallowedFrameSize } from "@react/editor/helper/template";
import { TemplateSizeProps } from "./FormData";

interface EnhancedAddonConfig extends AddOnProps {
    unitAmount: number;
}
type AddonKey = keyof typeof Addons;

export const calculateFramePrice = (quantity: number, framePricing: any) => {
    const totalQuantityWithFrames = quantity;
    const adjustedQuantity = totalQuantityWithFrames > 0 ? totalQuantityWithFrames : 1;

    const getFramePrice = (frameType: Frame): number => {
        const pricing = framePricing.frames[`pricing_${frameType}`].pricing;
        const basePrice = getPriceFromPriceChart(pricing, adjustedQuantity);
        const price = basePrice ? basePrice : AddOnPrices.FRAME[frameType as keyof typeof AddOnPrices.FRAME];
        return parseFloat(price.toFixed(2));
    };

    const framePrices = {
        [Frame.NONE]: AddOnPrices.FRAME[Frame.NONE],
        [Frame.WIRE_STAKE_10X30]: getFramePrice(Frame.WIRE_STAKE_10X30),
        [Frame.WIRE_STAKE_10X24]: getFramePrice(Frame.WIRE_STAKE_10X24),
        [Frame.WIRE_STAKE_10X30_PREMIUM]: number_format(getFramePrice(Frame.WIRE_STAKE_10X30_PREMIUM), 2),
        [Frame.WIRE_STAKE_10X24_PREMIUM]: number_format(getFramePrice(Frame.WIRE_STAKE_10X24_PREMIUM), 2),
        [Frame.WIRE_STAKE_10X30_SINGLE]: number_format(getFramePrice(Frame.WIRE_STAKE_10X30_SINGLE), 2),
    };

    return framePrices;
};

export const buildConfigData = (
    addon: Record<AddonKey, string>,
    price: number,
    framePrices: {[key: string]: number},
    quantity: number,
    product: any,
) => {
    let newAddonConfig = {} as Record<keyof typeof Addons, any>;

    for (const [key, value] of Object.entries(addon)) {
        if (key in Addons) {
            let addonConfig : AddOnProps = Addons[key as AddonKey][value as keyof typeof Addons[keyof typeof Addons]];

            if (key === "frame") {
                const framePrice = framePrices[value];
                addonConfig = {
                    ...addonConfig,
                    amount: framePrice,
                }
            }

            const enhancedConfig = enhanceAddonConfig(addonConfig, price, product, quantity);
            newAddonConfig[key as keyof typeof Addons] = enhancedConfig;
        }
    }

    return newAddonConfig;
};

export const calculateAddonPrice = (
    addonConfig: Partial<Record<AddonKey, EnhancedAddonConfig>>,
    price: number,
    quantity: number,
    templateSize: TemplateSizeProps
): {
    unitAddOnsAmount: number;
    unitAmount: number;
    totalAmount: number;
    subTotalAmount: number;
} => {

    if (addonConfig.frame && (isDisallowedFrameSize(templateSize, addonConfig.shape?.key)) ) {
        delete addonConfig.frame;
    }
    const isHorizontal = addonConfig.flute?.key === Flute.HORIZONTAL;

    const unitAddOnsAmount = parseFloat(
        Object.entries(addonConfig)
            .reduce((sum, [key, addon]) => {
                if (isHorizontal && key === 'frame') {
                    return sum;
                }
                return sum + (addon.unitAmount || 0);
            }, 0)
            .toFixed(2)
    );

    const unitAmount = parseFloat((price + unitAddOnsAmount).toFixed(2));

    const totalAmount = parseFloat((quantity * unitAmount).toFixed(2));

    return {
        unitAddOnsAmount,
        unitAmount,
        totalAmount,
        subTotalAmount: totalAmount,
    };
};
