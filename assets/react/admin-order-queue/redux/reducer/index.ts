import {createReducer} from "@reduxjs/toolkit";
import configInitialState from "./config";
import actions from "@react/admin-order-queue/redux/actions";
import * as configCase from "./config";

const initialState = {
    config: configInitialState,
}

const reducer = createReducer(initialState, (builder) => {
    builder.addCase(actions.config.initialize, configCase.initialize);
    builder.addCase(actions.config.updateSelectedShipBy, configCase.updateSelectedShipBy);
    builder.addCase(actions.config.updateNotes, configCase.updateNotes);
    builder.addCase(actions.config.updateLists, configCase.updateLists);
    builder.addCase(actions.config.updateWarehouseOrder, configCase.updateWarehouseOrder);
    builder.addCase(actions.config.refresh, configCase.refresh);
    builder.addCase(actions.config.updatePrintStatus, configCase.updatePrintStatus);
    builder.addCase(actions.config.updateProofPrinted, configCase.updateProofPrinted);
    builder.addCase(actions.config.updateShipByOrders, configCase.updateShipByOrders);
    builder.addCase(actions.config.removeShipByList, configCase.removeShipByList);
    builder.addCase(actions.config.createShipByList, configCase.createShipByList);
    builder.addCase(actions.config.addComment, configCase.addComment);
    builder.addCase(actions.config.updateWarehouseOrderLogs, configCase.updateWarehouseOrderLogs);
    builder.addCase(actions.config.removeWarehouseOrder, configCase.removeWarehouseOrder);
    builder.addCase(actions.config.updatePrintersCount, configCase.updatePrintersCount);
    builder.addCase(actions.config.updateFilters, configCase.updateFilters);
});

export default reducer;