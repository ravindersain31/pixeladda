import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const prepareCartData: CaseReducer = (state: AppState, action) => {
    state.editor.readyForCart = false;

    state.editor.items[state.canvas.item.productId].canvasData = state.canvas.data;

    state.editor.readyForCart = true;
}

export default prepareCartData;