import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {recalculateItemsFramePrice} from "@react/editor/helper/pricing.ts";

const updateFramePrice: CaseReducer = (state: AppState, action) => {
    const totalQuantityWithFrames = action.payload;

    const {items, subTotalAmount} = recalculateItemsFramePrice(
        state.editor.items,
        state.config.product.framePricing,
        totalQuantityWithFrames,
        state
    );
    state.editor.items = items;

    state.editor.subTotalAmount = subTotalAmount;
    state.editor.totalAmount = subTotalAmount + state.editor.totalShipping;
};

export default updateFramePrice;