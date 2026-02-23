import { ItemProps } from "@orderSample/redux/reducer/cart/interface";
import { ItemplateSizeProps, VariantProps } from "@orderSample/redux/reducer/config/interface";
import store from "@orderSample/redux/store";
import { getPriceFromPriceChart } from "@orderSample/utils/helper";
import { getClosestVariantFromPricing } from "@react/editor/helper/size-calc";

export const buildCartItem = (variant: VariantProps, quantity: number, templateSize?: ItemplateSizeProps) => {
    const { config, cartStage } = store.getState();

    const { currencyCode = 'USD' } = config.store;
    const cartQuantity = config.cart.totalQuantity;
    const currentItemQuantity = Number(config.cart.currentItemQuantity || 0);
    const quantityBySizes = config.cart.quantityBySizes;


    // Clone existing items
    let items: { [key: string]: ItemProps } = JSON.parse(JSON.stringify(cartStage.items));

    // Update the quantity for the specific variant
    items[variant.id] = {
        ...items[variant.id],
        quantity: quantity,
    };

    // Compute total quantity across all items
    let totalQuantity = Object.values(items).reduce((sum, item) => sum + item.quantity, 0);

    // Get pricing data for the variant
    const pricing = config.product.pricing.variants[`pricing_${variant.name}`].pricing;

    // Calculate the price based on the total quantity for this variant
    const totalQtyForVariant = Number(quantity);
    const itemPrice = Number(getPriceFromPriceChart(pricing, totalQtyForVariant, currencyCode).toFixed(2));

    // Update the item details
    items[variant.id] = {
        ...variant,
        ...items[variant.id],
        id: Number(variant.id),
        quantity: Number(quantity),
        price: itemPrice,
        unitAmount: itemPrice,
        unitAddOnsAmount: 0,
        totalAmount: parseFloat((quantity * itemPrice).toFixed(2)),
        addons: {},
        additionalNote: variant.additionalNote || cartStage.additionalNote
    };


    if (variant.sku.includes('CUSTOM-SIZE') && variant.isCustomSize) {
        const templatedSizeExploded = variant.template.split('x');

        const width = Number(templateSize?.width || templatedSizeExploded[0]);
        const height = Number(templateSize?.height || templatedSizeExploded[1]);

        const closestVariant = getClosestVariantFromPricing(variant.name, config.product.pricing);

        items[variant.id] = {
            ...variant,
            ...items[variant.id],
            isCustom: false,
            isSample: true,
            previewType: 'image',
            templateSize: { width, height },
            name: `${width}x${height}`,
            customSize: {
                isCustomSize: variant.isCustomSize,
                templateSize: { width, height },
                productId: variant.productId,
                parentSku: config.product.sku,
                sku: config.product.sku,
                image: variant.image,
                category: config.product.category.slug || 'sample-category',
                closestVariant: closestVariant || '6x18'
            }
        };
    }


    // Compute subtotal and total amount
    const subTotal = Object.values(items).reduce(
        (sum, item) => sum + item.totalAmount,
        0
    ).toFixed(2);

    const totalAmount = Number(subTotal) + Number(cartStage.totalShipping);

    return {
        items,
        subTotal: Number(subTotal),
        totalAmount: Number(totalAmount),
        totalQuantity: Number(totalQuantity),
    };
};