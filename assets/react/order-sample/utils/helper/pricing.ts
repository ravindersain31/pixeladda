import { number_format } from "@react/editor/helper/pricing";
import { IAddOnProps, IFrame, ItemProps } from "@orderSample/redux/reducer/cart/interface";
import AppState, { Shape, Frame } from "@react/editor/redux/interface";

export const calculatePricing = (
    items: { [key: string]: ItemProps },
    addonName: any,
    addonConfigs: IAddOnProps | any,
    product: any
) => {
    let subTotalAmount = 0;
    const updateItems: { [key: string]: ItemProps } = {};

    for (const [key, item] of Object.entries(items)) {
        item.addons[addonName] = enhanceAddonConfig(addonConfigs, item.price, product, item.quantity);

        let unitAddOnsAmount = 0;
        Object.values(item.addons).forEach((addon: any) => {
                unitAddOnsAmount += addon.unitAmount || 0;
        });
        item.unitAddOnsAmount = unitAddOnsAmount;
        item.unitAddOnsAmount = number_format(item.unitAddOnsAmount, 2);
        item.unitAmount = number_format(item.price + item.unitAddOnsAmount, 2);
        item.totalAmount = number_format(item.quantity * item.unitAmount, 2);

        updateItems[key] = item;
        subTotalAmount += item.totalAmount;
    }

    return {
        items: updateItems,
        subTotalAmount: number_format(subTotalAmount, 2),
    };
};

export const enhanceAddonConfig = (
    addonConfig: any,
    itemPrice: number,
    product: any,
    itemQuantity: number
) => {
        let unitAmount = 0;

        if (addonConfig?.type === IFrame.PERCENTAGE) {
            unitAmount = number_format((itemPrice * addonConfig.amount) / 100, 2);
        } else {
            unitAmount = addonConfig.amount;
        }
        let quantity = itemQuantity;

        const enhancedAddon = {
            ...addonConfig,
            unitAmount,
            quantity,
        };
        return enhancedAddon;
};
export const updateFramePriceAccordingToShape = (state: AppState, items: { [key: string]: ItemProps }): { [key: string]: ItemProps } => {
    const updatedItems: { [key: string]: ItemProps } = {};
    const shape = state.editor.shape;
    const config = state.config;
    for (const key in items) {
        const item = items[key];

        // if ((item.templateSize.width < 12) && shape === Shape.CIRCLE) {
        //     if(!Array.isArray(state.editor.frame)){
        //         item.addons.frame = {
        //             ...config.addons.frame.NONE,
        //             unitAmount: AddOnPrices.FRAME[Frame.NONE],
        //         };
        //     }
        // }

        updatedItems[key] = item;
    }

    return updatedItems;
};
