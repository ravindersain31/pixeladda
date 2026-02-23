import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@orderBlankSign/redux/reducer/interface";

const updateBlindShipping: CaseReducer = (state: AppState, action) => {    
    const isBlindShipping = action.payload;
    state.cartStage.isBlindShipping = isBlindShipping;
}

export default updateBlindShipping;