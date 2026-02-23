import { ItemProps } from "@wireStake/redux/reducer/cart/interface";
import { VariantProps } from "@wireStake/redux/reducer/config/interface";
import store from "@wireStake/redux/store";
import { getPriceFromPriceChart } from "@wireStake/utils/helper";

export const buildCartItem = (variant: VariantProps, quantity: number) => {
    const { config, cartStage } = store.getState();

    const { currencyCode = 'USD' } = config.store;
    const cartQuantity = config.cart.totalQuantity;
    // const currentItemQuantity = config.cart.currentItemQuantity;
    const quantityBySizes = config.cart.quantityBySizes;
    const totalFrameQuantity = config.cart.totalFrameQuantity;


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
    const pricing = config.product.pricing.frames[`pricing_${variant.name}`].pricing;

    const currentItemQuantity = Number(config.cart.currentFrameQuantity[variant.name] || 0);

    // Calculate the price based on the total quantity for this variant
    const totalQtyForVariant = quantity + ((totalFrameQuantity[variant.name] || 0) - (currentItemQuantity || 0));
    const itemPrice = getPriceFromPriceChart(pricing, totalQtyForVariant, currencyCode).toFixed(2);

    // Update the item details
    items[variant.id] = {
        ...variant,
        ...items[variant.id],
        id: variant.id,
        quantity: quantity,
        price: itemPrice,
        unitAmount: itemPrice,
        unitAddOnsAmount: 0,
        totalAmount: parseFloat((quantity * itemPrice).toFixed(2)),
        addons: {},
        additionalNote: variant.additionalNote || cartStage.additionalNote
    };

    // Compute subtotal and total amount
    const subTotal = Object.values(items).reduce(
        (sum, item) => sum + item.totalAmount,
        0
    ).toFixed(2);

    const totalAmount = Number(subTotal) + Number(cartStage.totalShipping);

    return {
        items,
        subTotal: Number(subTotal),
        totalAmount,
        totalQuantity,
    };
};