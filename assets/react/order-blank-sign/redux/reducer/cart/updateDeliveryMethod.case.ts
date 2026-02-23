import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@orderBlankSign/redux/reducer/interface.ts";

const updateDeliveryMethod: CaseReducer = (state: AppState, action) => {
    const method = action.payload;

    state.cartStage.deliveryMethod = method;
}

export default updateDeliveryMethod;
