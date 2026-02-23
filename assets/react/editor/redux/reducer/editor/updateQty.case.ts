import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateQtyCase: CaseReducer = (state: AppState, action) => {
    state.editor.items = action.payload.items;
    state.editor.totalQuantity = action.payload.totalQuantity;
    state.editor.subTotalAmount = action.payload.subTotal;
    state.editor.totalAmount = action.payload.totalAmount;
}

export default updateQtyCase;