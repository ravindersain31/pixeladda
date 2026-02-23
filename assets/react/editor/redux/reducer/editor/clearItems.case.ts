import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const clearItemsCase: CaseReducer = (state: AppState, action) => {
    state.editor.items = {};
    state.editor.subTotalAmount = 0;
    state.editor.totalAmount = 0;
    state.editor.totalQuantity = 0;
}

export default clearItemsCase;