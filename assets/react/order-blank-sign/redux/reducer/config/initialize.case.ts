import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/order-blank-sign/redux/reducer/interface";
import { ICartItem } from "@orderBlankSign/redux/reducer/cart/interface";

const initializeCase: CaseReducer<AppState> = (state, action) => {
    const { product, links, initialData, cartOverview, cart } = action.payload;

    state.config.cart = { ...state.config.cart, ...cartOverview };

    if (cart && cart.items) {
        for (const item of Object.values(cart.items) as ICartItem[]) {
            const quantity = Number(item.quantity) || 0;
            const totalAmount = Number(item.data?.totalAmount) || 0;
            const shipping = Number(item.data?.shipping?.amount) || 0;

            state.config.cart.currentItemQuantity += quantity;
            state.config.cart.currentItemSubtotal += totalAmount;
            state.config.cart.currentItemShipping = item.data.shipping;

            if (item.data?.isWireStake && item.data?.name) {
                const frameName = item.data.name;
                if (!state.config.cart.currentFrameQuantity[frameName]) {
                    state.config.cart.currentFrameQuantity[frameName] = 0;
                }
                state.config.cart.currentFrameQuantity[frameName] += quantity;
            }
        }
    }

    state.config.product = product;
    state.config.links = links;
    state.config.initialData = initialData;
    state.config.initialized = true;
};

export default initializeCase;