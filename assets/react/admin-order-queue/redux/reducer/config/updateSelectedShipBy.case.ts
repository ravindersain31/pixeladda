import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";

const updateSelectedShipByCase: CaseReducer = (state: AppState, action) => {
    state.config.selectedOrderShipBy = {
        id: '2',
        order: null,
        shipBy: null,
        shippingService: null,
        printerName: null,
        notes: null,
        driveLink: 'action.payload.driveLink',
        printStatus: 'status',
        warehouseOrderLogs: []
    }
}

export default updateSelectedShipByCase;