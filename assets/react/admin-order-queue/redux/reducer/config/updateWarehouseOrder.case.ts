import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";
import { OrderDetails } from "./interface";

const updateWarehouseOrder: CaseReducer = (state: AppState, action) => {

    const warehouseOrder: OrderDetails = action.payload;

    state.config.lists = state.config.lists.map((list) => {
        return {
            ...list,
            warehouseOrders: list.warehouseOrders.map((order) => {
                return order.id === warehouseOrder.id ? warehouseOrder : order;
            }),
        };
    });
};

export default updateWarehouseOrder;