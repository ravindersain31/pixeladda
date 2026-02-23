import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";

const updateWarehouseOrderLogs: CaseReducer = (state: AppState, action) => {

    const { id, logs } = action.payload;

    state.config.lists = state.config.lists.map(list => ({
        ...list,
        warehouseOrders: list.warehouseOrders.map(order => order.id === id ? { ...order, logs } : order)
    }));

};

export default updateWarehouseOrderLogs;