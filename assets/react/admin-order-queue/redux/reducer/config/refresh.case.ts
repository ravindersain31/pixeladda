import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";
import { buildLists } from "@react/admin-order-queue/helper";

const refresh: CaseReducer = (state: AppState, action) => {
    state.config.lists = action.payload.lists || [];
    state.config.ordersShipBy = action.payload.ordersShipBy || [];
    const processedData = buildLists(state);
    state.config.lists = processedData;

}

export default refresh;
