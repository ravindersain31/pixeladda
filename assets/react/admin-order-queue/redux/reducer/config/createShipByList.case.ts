import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";
import { ListProps, OrdersShipBy } from "./interface";
import { normalizeDate } from "@react/admin-order-queue/helper";

const createShipByList: CaseReducer = (state: AppState, action) => {
    const { lists, printer } = action.payload;

    if (printer !== state.config.printer) return;

    lists.forEach((list: ListProps) => {
        if (state.config.lists.some(existingList => existingList.id === list.id)) return;

        const newList: ListProps = {
            ...list,
            shipBy: normalizeDate(list.shipBy),
            warehouseOrders: [],
        };

        const index = state.config.lists.findIndex(existingList =>
            new Date(normalizeDate(existingList.shipBy)) > new Date(normalizeDate(list.shipBy))
        );

        index === -1 ? state.config.lists.push(newList) : state.config.lists.splice(index, 0, newList);
    });
};

export default createShipByList;