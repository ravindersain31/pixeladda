import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@orderSample/redux/reducer/interface.ts";
import initialState from "@orderSample/redux/reducer/cart";
import { IShape, ISides } from "./interface";

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

                items[item.data.id].addons = item.addons || {};

                totalQuantity += item.quantity;
                subTotal += item.data.totalAmount;
                additionalNote = item.data.additionalNote || '';

            }

            state.cartStage.items = items;
            state.cartStage.isBlindShipping = cart.isBlindShipping;
            state.cartStage.deliveryMethod = cart.deliveryMethod || initialState.deliveryMethod;
            state.cartStage.shape = cart.shape || IShape.SQUARE;
            state.cartStage.sides = cart.sides || ISides.SINGLE;
            state.cartStage.totalShippingDiscount = cart.totalShippingDiscount;
            state.cartStage.additionalNote = additionalNote;
            state.cartStage.deliveryDate = cart.shipping;
            state.cartStage.totalShipping = parseFloat(cart.totalShipping);
            state.cartStage.totalQuantity = totalQuantity;
            state.cartStage.subTotalAmount = parseFloat(subTotal.toFixed(2));
            state.cartStage.totalAmount = (state.cartStage.subTotalAmount + state.cartStage.totalShipping);
        }
    }
}

export default initializeCase;