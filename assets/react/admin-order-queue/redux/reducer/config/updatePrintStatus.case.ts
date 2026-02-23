import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";

const updatePrintStatus: CaseReducer = (state: AppState, action) => {

    const { id, printStatus } = action.payload;

    state.config.lists = state.config.lists.map(list => ({
        ...list,
        warehouseOrders: list.warehouseOrders.map(order => order.id === id ? {
            ...order,
            printStatus: printStatus,
            isPause: printStatus === 'PAUSED' ? true : false,
        } : order)
    }));

};

export default updatePrintStatus;