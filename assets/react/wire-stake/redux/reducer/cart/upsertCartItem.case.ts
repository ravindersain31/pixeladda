import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@wireStake/redux/reducer/interface.ts";

const upsertCartItem: CaseReducer = (state: AppState, action) => {

    const { items, subTotal, totalAmount, totalQuantity } = action.payload;

    state.cartStage.items = items;
    state.cartStage.totalQuantity = Number(totalQuantity);
    state.cartStage.subTotalAmount = Number(subTotal);
    state.cartStage.totalAmount = Number(totalAmount);
}

export default upsertCartItem;
