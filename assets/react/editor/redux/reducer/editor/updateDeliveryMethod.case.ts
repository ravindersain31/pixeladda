import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import { DeliveryMethod } from "./interface";

const updateDeliveryMethod: CaseReducer = (state: AppState, action) => {    
    const { editor, config } = state;
    editor.deliveryMethod = action.payload;

}

export default updateDeliveryMethod;