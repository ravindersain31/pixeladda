import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface";

const updateLists: CaseReducer = (state: AppState, action) => {
    const { sourceListId, destinationListId, updatedOrders } = action.payload;
};

export default updateLists;
