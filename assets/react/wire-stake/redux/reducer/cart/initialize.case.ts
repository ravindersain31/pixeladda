import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@wireStake/redux/reducer/interface.ts";
import initialState from "@wireStake/redux/reducer/cart";

const initializeCase: CaseReducer = (state: AppState, action) => {
    const {cart} = action.payload;
    if (cart) {
        state.cartStage.deliveryDate = cart?.deliveryDate || initialState.deliveryDate;
        if (cart?.items) {

            let totalQuantity = 0;
            let subTotal = 0;
            let additionalNote = '';

            const items: any = {};
            for (const key of Object.keys(cart.items)) {
                const item = cart.items[key];

                items[item.data.id] = item.data;
                items[item.data.id].itemId = item.itemId;
                items[item.data.id].previewType = item.previewType || 'image';
                items[item.data.id].quantity = item.quantity || 0;
                items[item.data.id].additionalNote = item.data.additionalNote || '';

                totalQuantity += item.quantity;
                subTotal += item.data.totalAmount;
                additionalNote = item.data.additionalNote || '';
            }

            state.cartStage.items = items;
            state.cartStage.isBlindShipping = cart.isBlindShipping;
            state.cartStage.deliveryMethod = cart.deliveryMethod || initialState.deliveryMethod;
            state.cartStage.additionalNote = additionalNote;

            state.cartStage.totalShippingDiscount = cart.totalShippingDiscount;
            state.cartStage.deliveryDate = cart.shipping;
            state.cartStage.totalShipping = parseFloat(cart.totalShipping);
            state.cartStage.totalQuantity = totalQuantity;
            state.cartStage.subTotalAmount = parseFloat(subTotal.toFixed(2));
            state.cartStage.totalAmount = (state.cartStage.subTotalAmount + state.cartStage.totalShipping);
        }
    }
}

export default initializeCase;