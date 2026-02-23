import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";
import { buildLists } from "@react/admin-order-queue/helper";

const initializeCase: CaseReducer = (state: AppState, action) => {
    state.config.initialized = true;
    state.config.lists = action.payload.lists || [];
    state.config.ordersShipBy = action.payload.ordersShipBy || [];
    state.config.lists = buildLists(state);
    state.config.printer = action.payload.printer;
    state.config.filters = {
        ...state.config.filters,
        ...action.payload.filters
    };
    state.config.urls = action.payload.urls;
}

export default initializeCase;
