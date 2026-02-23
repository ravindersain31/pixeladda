import { AddOnPrices, AddOnProps, Frame, ItemProps, PRE_PACKED_DISCOUNT, Shape, YSP_LOGO_DISCOUNT } from "@react/editor/redux/reducer/editor/interface.ts";
import { buildInitialData, getPriceFromPriceChart, recalculateCustomSizePricing, updateCustomSizeData } from "@react/editor/helper/pricing.ts";
import { copyFrontToBackWhenEmpty, mapCustomUploadedFileWhenOtherHasQtyZero } from "@react/editor/helper/canvas.ts";
import store from "@react/editor/redux/store.ts";
import { isDisallowedFrameSize, updateFramePriceAccordingToShape } from "./template";
import { getClosestVariantFromPricing } from "@react/editor/helper/size-calc.ts";
import { FrameTypes } from "./stakes";
import AppState from "../redux/interface";
import { updateItemsYSPLogoDiscount } from "./editor";

export const recalculateOnUpdateQuantity = (item: ItemProps, quantity: number) => {
    const isWireStake = item.isWireStake;
    if (typeof item.templateSize === 'undefined') {
        item = JSON.parse(JSON.stringify(item));
        item.templateSize = {
            width: isWireStake ? 12 : Number(item.name.split('x')[0]),
            height: isWireStake ? 12 : Number(item.name.split('x')[1]),
        };
    }
    const state = store.getState();

    const { currencyCode = 'USD' } = state.config.store;
    const cartQuantity = state.config.cart.totalQuantity;
    const currentItemQuantity = state.config.cart.currentItemQuantity;
    const quantityBySizes = state.config.cart.quantityBySizes;
    const { productType, productMetaData } = state.config.product;

    let items: {
        [key: string]: ItemProps
    } = JSON.parse(JSON.stringify(state.editor.items));

    const isNewItem = !items[item.productId];

    const templateJson = item.templateJson || {};
    // no need to store template json in editor state which is used on add to card
    delete item.templateJson;

    items[item.id] = {
        ...items[item.id],
        quantity: quantity,
    };

    let totalQuantity = 0;
    for (const [key, value] of Object.entries(items)) {
        totalQuantity += value.quantity;
    }

    const itemPrev = items[item.id] || {};
    let pricing;
    let pricingVariantName;

    if (isWireStake) {
        pricingVariantName = item.name;
        pricing = state.config.product.framePricing.frames[`pricing_${pricingVariantName}`].pricing;
    } else {
        pricingVariantName = getClosestVariantFromPricing(item.name, state.config.product.pricing);
        pricing = state.config.product.pricing.variants[`pricing_${pricingVariantName}`].pricing;
    }
    const isCustomSize = item.isCustomSize;
    const hasValidCustomSize = isCustomSize && quantityBySizes[`CUSTOM_${pricingVariantName}`] !== undefined;
    const hasValidItemId = item.itemId !== null;
    let baseQuantity = hasValidCustomSize ? quantityBySizes[`CUSTOM_${pricingVariantName}`] : quantityBySizes[item.name] || 0;
    const shouldSubtractCurrentItemQuantity = hasValidItemId && (isCustomSize ? hasValidCustomSize : true);

    const totalSigns = productMetaData.totalSigns ?? 0;
    const isYardLetters = productType.slug === 'yard-letters' && quantity > 0;

    const subtractQty = shouldSubtractCurrentItemQuantity
        ? currentItemQuantity * (isYardLetters ? totalSigns : 1)
        : 0;

    const totalQty = isYardLetters
        ? baseQuantity + totalSigns * quantity - subtractQty
        : baseQuantity + quantity - subtractQty;

    let itemPrice = getPriceFromPriceChart(pricing, totalQty, currencyCode);
    let itemBasePrice = getPriceFromPriceChart(pricing, totalQty, currencyCode);
    if (state.config.product.productType.slug === 'yard-letters' && quantity > 0) {
        itemBasePrice = itemPrice * (totalSigns * quantity);
    }

    const newItem = buildInitialData(itemPrice, state.config.addons, state.editor, quantity, state.config.product, isWireStake);
    itemPrev.additionalNote = item.additionalNote ?? '';
    itemPrev.addons = newItem.addons;
    itemPrev.unitAmount = newItem.unitAmount;
    itemPrev.unitAddOnsAmount = newItem.unitAddOnsAmount;
    itemPrev.isEmailArtworkLater = state.editor.isEmailArtworkLater;
    itemPrev.isHelpWithArtwork = state.editor.isHelpWithArtwork;
    itemPrev.templateSize = item.isCustomSize ? item.templateSize : {
        width: isWireStake ? 12 : Number(item.name.split('x')[0]),
        height: isWireStake ? 12 : Number(item.name.split('x')[1]),
    };

    const itemAddons = itemPrev.addons || {};
    const unitAddOnsAmount = parseFloat(Object.values(itemAddons).reduce((acc: number, addon: any) => acc + (addon.unitAmount || 0), 0).toFixed(2));
    const unitAmount = parseFloat((itemBasePrice + unitAddOnsAmount).toFixed(2));

    items[item.id] = {
        ...item,
        ...items[item.id],
        quantity: quantity,
        price: itemPrice,
        id: item.id,
        unitAmount: unitAmount,
        unitAddOnsAmount: unitAddOnsAmount,
        totalAmount: parseFloat((quantity * unitAmount).toFixed(2)),
        addons: itemPrev.addons || {},
        additionalNote: item.additionalNote ?? "",
        isEmailArtworkLater: itemPrev.isEmailArtworkLater,
        isHelpWithArtwork: itemPrev.isHelpWithArtwork,
        templateSize: itemPrev.templateSize,
        YSPLogoDiscount: itemPrev.YSPLogoDiscount || {
            hasLogo: false,
            discount: YSP_LOGO_DISCOUNT,
            type: "PERCENTAGE",
            discountAmount: 0,
        },
        canvasData: itemPrev.canvasData || {
            front: templateJson,
            back: null,
        },
        customArtwork: item.customArtwork || {},
        prePackedDiscount: itemPrev.prePackedDiscount || {
            hasPrePacked: false,
            discount: PRE_PACKED_DISCOUNT,
            type: 'PERCENTAGE',
            discountAmount: 0
        },
    };

    items = copyFrontToBackWhenEmpty(items, state.editor.sides);

    if (state.config.product.isCustom) {
        items = mapCustomUploadedFileWhenOtherHasQtyZero(items, item, state.canvas.data);
    }

    items = updateCustomSizeData(items, state);

    items = recalculateCustomSizePricing(items, state);

    items = updateFramePriceAccordingToShape(state, items);
    items = updateItemsYSPLogoDiscount(items);

    const prevItemTotalAmount = itemPrev.totalAmount || 0;

    const itemTotalAmount = items[item.id].totalAmount;
    const subTotal = (state.editor.subTotalAmount - prevItemTotalAmount) + itemTotalAmount;
    const totalAmount = subTotal + state.editor.totalShipping;

    return {
        items,
        subTotal,
        totalAmount,
        totalQuantity,
    }
}


const removeOtherSkus = (items: {
    [key: string]: ItemProps
}, item: ItemProps) => {
    const updateItems: {
        [key: string]: ItemProps
    } = {};
    for (const [key, item] of Object.entries(items)) {
        updateItems[key] = item;
    }
    return updateItems;
}

export const calculateCartTotalFrameQuantity = (state?: AppState): {
    totalQuantity: number;
    frameQuantities: { [key: string]: number };
} => {
    const currentState = state ?? store.getState();
    const { editor, config } = currentState;

    const isYardLetters = config.product.isYardLetters;
    const productMetaData = config.product.productMetaData;
    const frameTypes = productMetaData.frameTypes;

    let totalQuantityWithFrames = 0;
    const frameQuantities: { [key: string]: number } = {};

    if (isYardLetters) {
        if (frameTypes) {
            const filteredFrameTypes: Frame[] = Object.keys(frameTypes)
                .filter(frameType => frameTypes[frameType] !== 0 && frameTypes[frameType] !== null)
                .map(frameType => Frame[frameType as keyof typeof Frame]);

            filteredFrameTypes.forEach(frameType => {
                frameQuantities[frameType] = (config.cart.totalFrameQuantity[frameType] || 0) + (frameTypes[frameType] * editor.totalQuantity) - (config.cart.currentFrameQuantity[frameType] || 0);
            });

            totalQuantityWithFrames = Object.values(frameQuantities).reduce(
                (sum: number, quantity: number) => sum + quantity, 0
            );
        } else {
            if (Array.isArray(editor.frame)) {
                editor.frame.forEach((frameType) => {
                    frameQuantities[frameType] = (config.cart.totalFrameQuantity[frameType] || 0) + ((config.product.productImages.length - 1) * editor.totalQuantity) - (config.cart.currentFrameQuantity[frameType] || 0);
                });
                totalQuantityWithFrames = Object.values(frameQuantities).reduce(
                    (sum: number, quantity: number) => sum + quantity, 0
                );
            }
        }
    }

    if (!Array.isArray(editor.frame)) {
        const filteredItems = Object.values(editor.items).filter(item => {
            if (item.isWireStake) return true;
            return item.name ? !isDisallowedFrameSize(item.templateSize, editor.shape) : item.addons.frame.key !== "NONE";
        });

        totalQuantityWithFrames = filteredItems.map(item => item.quantity).reduce((acc, current) => acc + current, 0);

        filteredItems.forEach(item => {
            const frameType: any = item.isWireStake ? item.name : item.addons.frame.key;

            if ((item.addons.frame && item.addons.frame.key !== Frame.NONE) || item.isWireStake) {
                frameQuantities[frameType] = (frameQuantities[frameType] || 0) + item.quantity;
            }
        });

        Object.keys(frameQuantities).forEach(frameType => {
            frameQuantities[frameType] = (config.cart.totalFrameQuantity[frameType] || 0) + frameQuantities[frameType] - (config.cart.currentFrameQuantity[frameType] || 0);
        });
    }

    return {
        totalQuantity: totalQuantityWithFrames,
        frameQuantities: frameQuantities
    };
};

export const calculateRemainingAmountForFreeShipping = (): number => {
    const state = store.getState();
    const remainingAmount = 50 - (state.editor.subTotalAmount + (state.config.cart.subTotal - state.config.cart.currentItemSubtotal));
    return remainingAmount > 0 ? parseFloat(remainingAmount.toFixed(2)) : 0;
}