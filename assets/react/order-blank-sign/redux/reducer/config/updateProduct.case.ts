import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/order-blank-sign/redux/reducer/interface.ts";

const updateProductCase: CaseReducer = (state: AppState, action) => {
    state.config.product = action.payload;
}

export default updateProductCase;