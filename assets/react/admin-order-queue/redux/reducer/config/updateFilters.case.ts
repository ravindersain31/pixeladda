import { CaseReducer } from "@reduxjs/toolkit";
import AppState, { FiltersState } from "../interface";

const updateFilters: CaseReducer = (state: AppState, action) => {
    const { orderId, status, dateRange, reset, globalSearch }: FiltersState = action.payload;

    if (reset) {
        state.config.filters = {
            orderId: "",
            status: [],
            reset: false,
            dateRange: [null, null],
            globalSearch: "",
        };
    } else {
        state.config.filters = {
            ...state.config.filters,
            reset: false,
            orderId: orderId !== undefined ? orderId : state.config.filters.orderId,
            status: status !== undefined ? status : state.config.filters.status,
            dateRange: dateRange !== undefined ? dateRange : state.config.filters.dateRange,
            globalSearch: globalSearch !== undefined ? globalSearch : state.config.filters.globalSearch,
        };
    }
};

export default updateFilters;