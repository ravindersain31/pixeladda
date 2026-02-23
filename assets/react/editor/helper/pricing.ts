import EditorState, { AddOnProps, Frame, ItemProps, PRE_PACKED_DISCOUNT, PRE_PACKED_MAX_DISCOUNT_AMOUNT, YSP_LOGO_DISCOUNT, YSP_MAX_DISCOUNT_AMOUNT } from "@react/editor/redux/reducer/editor/interface.ts";
import { isDisallowedFrameSize } from "@react/editor/helper/template.ts";
import { VariantProps, templateSizeProps } from "../redux/reducer/config/interface";
import AppState from "../redux/interface";
import { getClosestVariantFromPricing } from "@react/editor/helper/size-calc.ts";

export const getPriceFromPriceChart = (pricing: any, quantity: number, currencyCode: string = 'usd') => {
    for (const [key, value] of Object.entries(pricing)) {
        const tier: any = value;
        if (quantity >= tier.qty.from && quantity <= tier.qty.to) {
            return tier[currencyCode.toLowerCase()];
        }
    }
    return 0;
}

export const calculatePricing = (
    items: Record<string, ItemProps>,
    addonName: string,
    addonConfigs: AddOnProps | any,
    product: any
) => {
    let subTotalAmount = 0;
    const updateItems: Record<string, ItemProps> = {};

    for (const [key, item] of Object.entries(items)) {
        const isWireStake = item.isWireStake;

        if (!isWireStake) {
            item.addons[addonName] = enhanceAddonConfig(addonConfigs, item.price, product, item.quantity);

            let unitAddOnsAmount = 0;
            Object.values(item.addons).forEach((addon: any) => {
                if (hasSubAddons(addon)) {
                    unitAddOnsAmount += Object.values(addon).reduce((acc: number, subAddon: any) => {
                        return acc + (subAddon.unitAmount || 0);
                    }, 0);
                } else {
                    unitAddOnsAmount += addon.unitAmount || 0;
                }
            });

            item.unitAddOnsAmount = number_format(unitAddOnsAmount, 2);

            if (product.productType.slug === "yard-letters") {
                item.unitAmount = number_format(
                    item.price * product.productMetaData.totalSigns + item.unitAddOnsAmount,
                    2
                );
            } else {
                item.unitAmount = number_format(item.price + item.unitAddOnsAmount, 2);
            }

            item.totalAmount = number_format(item.quantity * item.unitAmount, 2);
        } else {
            item.unitAddOnsAmount = 0;
            item.unitAmount = number_format(item.price, 2);
            item.totalAmount = number_format(item.quantity * item.price, 2);
        }

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
    let itemBasePrice = itemPrice;
    if (product.productType.slug === 'yard-letters') {
        const totalSigns = product.productMetaData.totalSigns;
        itemBasePrice = itemPrice * (totalSigns);
    }

    if (Array.isArray(addonConfig)) {
        const enhancedAddons: any = {};
        let unitAmount = 0;
        addonConfig.forEach((addon) => {
            if (addon.type === Frame.PERCENTAGE) {
                unitAmount = number_format((itemBasePrice * addon.amount) / 100, 2);
            } else {
                unitAmount = addon.amount;
            }

            let quantity: number;
            if (product.productType.slug === 'yard-letters') {
                const frameTypes = product.productMetaData.frameTypes;
                quantity = frameTypes ? frameTypes[addon.key] * itemQuantity : (product.productImages.length - 1) * itemQuantity;
            } else {
                quantity = itemQuantity;
            }

            enhancedAddons[addon.key] = {
                ...addon,
                unitAmount,
                quantity,
            };
        });
        return enhancedAddons;
    } else {
        let unitAmount = 0;

        if (addonConfig.type === Frame.PERCENTAGE) {
            unitAmount = number_format((itemBasePrice * addonConfig.amount) / 100, 2);
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
    }
};

export const buildInitialData = (itemPrice: number, addons: any, editor: EditorState, quantity: number, product: any, isWireStake?: boolean) => {    // const defaultAddons: any = {
    let frameConfig: any;
    let matchingAddons: any;

    if (Array.isArray(editor.frame)) {
        matchingAddons = [];
        editor.frame.forEach((frameType) => {
            const addon = addons.frame[frameType];
            if (addon) {
                matchingAddons.push(addon);
            }
        });
        frameConfig = enhanceAddonConfig(matchingAddons, itemPrice, product, quantity);
    } else {
        frameConfig = enhanceAddonConfig(addons.frame[editor.frame], itemPrice, product, quantity);
    }

    let addonConfig: any = {
        sides: enhanceAddonConfig(addons.sides[editor.sides], itemPrice, product, quantity),
        imprintColor: enhanceAddonConfig(addons.imprintColor[editor.imprintColor], itemPrice, product, quantity),
        grommets: enhanceAddonConfig(addons.grommets[editor.grommets], itemPrice, product, quantity),
        grommetColor: enhanceAddonConfig(addons.grommetColor[editor.grommetColor], itemPrice, product, quantity),
        frame: frameConfig,
        shape: enhanceAddonConfig(addons.shape[editor.shape], itemPrice, product, quantity),
        flute: enhanceAddonConfig(addons.flute[editor.flute], itemPrice, product, quantity),
    };

    let unitAddOnsAmount = 0;

    Object.values(addonConfig).forEach((addon: any) => {
        if (hasSubAddons(addon)) {
            unitAddOnsAmount += Object.values(addon).reduce((acc: number, subAddon: any) => {
                return acc + (subAddon.unitAmount || 0);
            }, 0);
        } else {
            unitAddOnsAmount += addon.unitAmount || 0;
        }
    });

    if (isWireStake) {
        addonConfig = {};
        unitAddOnsAmount = 0;
    }

    const unitAmount = itemPrice + unitAddOnsAmount;
    return {
        unitAddOnsAmount,
        unitAmount,
        addons: addonConfig
    }
}

export const recalculateItemsFramePrice = (items: {
    [key: string]: ItemProps
}, pricing: any, totalCartQuantity: any, state: any) => {
    const updateItems: { [key: string]: ItemProps } = {};
    const { config } = state;
    const frameTypes = config.product.productMetaData.frameTypes;
    let subTotalAmount = 0;
    for (const [key, item] of Object.entries(items)) {
        const isWireStake = item.isWireStake;

        if (isWireStake) {
            const framePrice = getPriceFromPriceChart(pricing.frames[`pricing_${item.name}`].pricing, totalCartQuantity.frameQuantities[item.name]);
            if (framePrice) {
                item.price = framePrice;
                item.unitAmount = framePrice;
                item.totalAmount =  number_format(item.quantity * item.unitAmount, 2);
            }
            subTotalAmount += item.totalAmount;
            updateItems[key] = item;
            continue;
        }
        if (item.addons && item.addons.frame) {
            if (hasSubAddons(item.addons.frame)) {
                Object.entries(item.addons.frame).forEach(([frameKey, frameValue]) => {
                    const frameQuantity = ((config.cart.totalFrameQuantity[frameKey] || 0) + totalCartQuantity.frameQuantities[frameKey]) - (config.cart.currentFrameQuantity[frameKey] || 0);
                    const framePrice = getPriceFromPriceChart(pricing.frames[`pricing_${frameKey}`].pricing, frameQuantity);
                    if (framePrice) {
                        (item.addons.frame as { [key: string]: AddOnProps })[frameKey] = {
                            ...frameValue,
                            unitAmount: framePrice * (frameTypes ? frameTypes[frameKey] : (config.product.productImages.length - 1)),
                            amount: framePrice,
                            quantity: frameTypes ? frameTypes[frameKey] : (config.product.productImages.length - 1)
                        };
                    } else {
                        item.addons.frame = {
                            ...item.addons.frame,
                        };
                    }
                });
            } else {
                const frameKey = Object.keys(totalCartQuantity.frameQuantities).find(key => Object.values(Frame).includes(key as Frame)) || Frame.NONE;
                const frameQuantity = ((config.cart.totalFrameQuantity[frameKey] || 0) + totalCartQuantity.frameQuantities[frameKey]) - (config.cart.currentFrameQuantity[frameKey] || 0);
                const framePrice = getPriceFromPriceChart(pricing.frames[`pricing_${frameKey}`].pricing, frameQuantity);

                if (framePrice && item.addons.frame.key !== Frame.NONE) {
                    item.addons.frame = {
                        ...item.addons.frame,
                        unitAmount: framePrice,
                        amount: framePrice,
                        quantity: item.quantity as any,
                    };
                } else {
                    item.addons.frame = {
                        ...item.addons.frame,
                    };
                }
            }
            let unitAddOnsAmount = 0;
            Object.values(item.addons).forEach((addon: any) => {
                if (hasSubAddons(addon)) {
                    unitAddOnsAmount += Object.values(addon).reduce((acc: number, subAddon: any) => {
                        return acc + (subAddon.unitAmount || 0);
                    }, 0);
                } else {
                    unitAddOnsAmount += addon.unitAmount || 0;
                }
            });
            item.unitAddOnsAmount = unitAddOnsAmount;
            item.unitAddOnsAmount = number_format(item.unitAddOnsAmount, 2);
            if (config.product.productType.slug === 'yard-letters') {
                item.unitAmount = number_format((item.price * config.product.productMetaData.totalSigns) + item.unitAddOnsAmount, 2);
            } else {
                item.unitAmount = number_format(item.price + item.unitAddOnsAmount, 2);
            }

            item.totalAmount = number_format(item.unitAmount * item.quantity, 2);

            subTotalAmount += item.totalAmount;
        }
        updateItems[key] = item;
    }

    return {
        items: updateItems,
        subTotalAmount: number_format(subTotalAmount, 2),
    };
};

export const recalculateCustomSizePricing = (
    items: { [key: string]: ItemProps },
    state: AppState
) => {
    const { config } = state;
    const updateItems: { [key: string]: ItemProps } = {};
    const groupedItems: { [variant: string]: { key: string; item: ItemProps }[] } = {};

    for (const [key, item] of Object.entries(items)) {
        const pricingVariantName = getClosestVariantFromPricing(item.name, config.product.pricing);

        if (!groupedItems[pricingVariantName]) {
            groupedItems[pricingVariantName] = [];
        }
        groupedItems[pricingVariantName].push({ key, item });
    }

    for (const [variant, items] of Object.entries(groupedItems)) {
        const totalQuantity = items.reduce((sum, { item }) => {
            const customSizeKey = `CUSTOM_${item.customSize.closestVariant}`;
            const isCustomSizeWithQuantity = item.isCustomSize && config.cart.quantityBySizes[customSizeKey] !== undefined;
            const shouldSubtractCurrentItemQuantity = item.itemId !== null && isCustomSizeWithQuantity;

            return item.isCustomSize
                ? sum + (config.cart.quantityBySizes[customSizeKey] || 0) - (shouldSubtractCurrentItemQuantity ? config.cart.currentItemQuantity : 0) + item.quantity
                : sum;
        }, 0);

        const pricing = config.product.pricing.variants[`pricing_${variant}`].pricing;
        const itemPrice = getPriceFromPriceChart(pricing, totalQuantity);

        items.forEach(({ key, item }) => {
            updateItems[key] = item.isCustomSize
                ? {
                    ...item,
                    price: itemPrice,
                    totalAmount: parseFloat((item.quantity * item.unitAmount).toFixed(2)),
                }
                : {
                    ...item,
                };
        });
    }

    return updateItems;
};

export const updateCustomSizeData = (
    items: Record<string, ItemProps>,
    state: AppState
): Record<string, ItemProps> => {
    const { canvas, config } = state;

    const defaultImage = "https://static.yardsignplus.com/product/img/CUSTOM/24x18_65c9d52de780e083386510.webp";

    const getCustomSizeData = (item: ItemProps, isWireStake: boolean, variantName: string) => {
        const product = isWireStake ? config.wireStakeProduct : config.product;
        const categorySlug = product.category?.slug || (isWireStake ? "wire-stake" : "custom-signs");
        const image = product.variants.find((variant) => variant.name === (isWireStake ? variantName : "24x18"))?.image || defaultImage;

        return {
            ...item.customSize,
            templateSize: item.templateSize,
            sku: product.sku,
            category: categorySlug,
            isCustomSize: item.isCustomSize,
            productId: canvas.item.productId,
            closestVariant: isWireStake
                ? variantName
                : getClosestVariantFromPricing(variantName, config.product.pricing),
            image,
        };
    };

    const updated: Record<string, ItemProps> = {};

    for (const [key, itm] of Object.entries(items)) {
        const isWireStake = itm.isWireStake;
        const variantName = isWireStake
            ? itm.name
            : `${itm.templateSize.width}x${itm.templateSize.height}`;

        updated[key] = {
            ...itm,
            name: variantName,
            isCustom: config.product.isCustom,
            customSize: getCustomSizeData(itm, isWireStake, variantName),
        };
    }

    return updated;
};

export const isValidVariantSize = (templateSize: templateSizeProps, variants: VariantProps): boolean => {
    const { width, height } = templateSize;
    for (const variant of Object.entries(variants)) {
        if (variant[1].name === `${width}x${height}`) {
            return true;
        }
    }
    return false;
}

export const number_format = (value: number, decimals: number = 0): number => {
    const factor = Math.pow(10, decimals);
    return Math.round(value * factor) / factor;
};

export const hasSubAddons = (addon: any): boolean => {
    if (typeof addon === 'object' && !Array.isArray(addon)) {
        const keys = Object.keys(addon);
        if (keys.length > 0 && typeof addon[keys[0]] === 'object' && !Array.isArray(addon[keys[0]])) {
            return true;
        } else {
            return false;
        }
    }
    return false;
};

export const calculateYSPLogoDiscount = (item: any) => {
    let unitAddOnsAmount = 0;
    Object.values(item.addons).forEach((addon: any) => {
        if (hasSubAddons(addon)) {
            unitAddOnsAmount += Object.values(addon).reduce((acc: number, subAddon: any) => {
                return acc + (subAddon.unitAmount || 0);
            }, 0);
        } else {
            unitAddOnsAmount += addon.unitAmount || 0;
        }
    });
    const unitAmount = parseFloat((item.price + unitAddOnsAmount).toFixed(2));
    const discountPercentage = item.YSPLogoDiscount.hasLogo ? YSP_LOGO_DISCOUNT : 0;
    const discountAmountPerUnit = (item.price * discountPercentage) / 100;
    const totalDiscountAmountBeforeCap = number_format(discountAmountPerUnit * item.quantity, 2);
    const totalDiscountAmount = Math.min(totalDiscountAmountBeforeCap, YSP_MAX_DISCOUNT_AMOUNT);
    const discountedUnitAmount = number_format(unitAmount, 2);
    const totalAmount = number_format(unitAmount * item.quantity - totalDiscountAmount, 2);

    return {
        discountAmount: number_format(totalDiscountAmount, 2),
        discountedUnitAmount,
        totalAmount,
    };
};

export const calculateYSPLogoDiscountFromItems = (items: any[]) => {
    let subTotal = 0;
    let hasYspLogo = false;

    items.forEach(item => {
        const logoDiscount = item?.YSPLogoDiscount;
        const quantity = Number(item?.quantity ?? 0);
        
        if (logoDiscount?.hasLogo && quantity > 0) {
            subTotal += parseFloat(item?.totalAmount || 0);
            hasYspLogo = true;
        }
    });

    let YSPLogoDiscount = 0;
    if (hasYspLogo) {
        YSPLogoDiscount = Math.round((subTotal * YSP_LOGO_DISCOUNT) / 100 * 100) / 100;
        YSPLogoDiscount = Math.min(YSPLogoDiscount, YSP_MAX_DISCOUNT_AMOUNT);
    }

    return {
        YSPLogoDiscount,
        hasYspLogo,
    };
};

export const updateItemsPrePackedDiscount = (
    items: { [key: string]: ItemProps },
    productType: { slug: string | null }
) => {
    const updateItems: { [key: string]: ItemProps } = {};

    for (const [key, item] of Object.entries(items)) {
        if (productType.slug === 'yard-letters' && item.quantity > 0) {
            const originalTotal = item.totalAmount;
            let discountAmount = (originalTotal * PRE_PACKED_DISCOUNT) / 100;

            updateItems[key] = {
                ...item,
                prePackedDiscount: {
                    hasPrePacked: true,
                    discount: PRE_PACKED_DISCOUNT,
                    type: 'PERCENTAGE',
                    discountAmount: number_format(discountAmount, 2),
                },
            };
        } else {
            updateItems[key] = item;
        }
    }

    return {
        items: updateItems,
    };
};

export const getPrePackedDiscount = (items: { [key: string]: ItemProps }) => {
    const itemList = Object.values(items);

    let prePackedDiscountPercent = 0;
    const prePackedTotalDiscount = itemList.reduce((sum, item) => {
        if (item.quantity > 0 && item.prePackedDiscount?.hasPrePacked) {
            prePackedDiscountPercent = item.prePackedDiscount.discount ?? prePackedDiscountPercent;
            return sum + (item.prePackedDiscount?.discountAmount || 0);
        }
        return sum;
    }, 0);

    return {
        prePackedTotalDiscount,
        prePackedDiscountPercent
    };
};