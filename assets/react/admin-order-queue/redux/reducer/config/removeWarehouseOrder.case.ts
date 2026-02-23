import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";

const removeWarehouseOrder: CaseReducer = (state: AppState, action) => {

    const { warehouseOrderId, type, printer } = action.payload;

    if (printer === state.config.printer) {
        state.config.lists = state.config.lists.map(list => ({
            ...list,
            warehouseOrders: list.warehouseOrders.filter(order => order.id !== warehouseOrderId)
        }));
    }

};

export default removeWarehouseOrder;