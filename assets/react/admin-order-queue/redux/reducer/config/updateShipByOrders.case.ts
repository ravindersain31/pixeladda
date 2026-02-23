import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface";
import { OrderDetails } from "./interface";
import { buildLists } from "@react/admin-order-queue/helper";

const updateShipByOrders: CaseReducer = (state: AppState, action) => {
    const { shipByOrders, printer } = action.payload;

    if(printer === state.config.printer) {
        const existingOrdersShipBy = state.config.ordersShipBy;
        
        const updatedOrdersShipBy = {} as Record<string, OrderDetails[]>;
        
        const incomingOrderIds = new Set<number>();
        Object.values(shipByOrders).forEach((orders) => {
            (orders as OrderDetails[]).forEach(order => {
                incomingOrderIds.add(Number(order.id));
            });
        });

        Object.entries(shipByOrders).forEach(([shipByDate, newOrders]) => {
            updatedOrdersShipBy[shipByDate] = newOrders as OrderDetails[];
        });

        Object.entries(existingOrdersShipBy).forEach(([shipByDate, existingOrders]) => {
            if (!updatedOrdersShipBy[shipByDate]) {
                const filteredOrders = existingOrders.filter(order => 
                    !incomingOrderIds.has(Number(order.id))
                );

                if (filteredOrders.length > 0) {
                    updatedOrdersShipBy[shipByDate] = filteredOrders;
                }
            } else {
                updatedOrdersShipBy[shipByDate] = updatedOrdersShipBy[shipByDate].filter(
                    (order, index, self) => 
                        self.findIndex(o => o.id === order.id) === index
                );
            }
        });

        state.config.ordersShipBy = updatedOrdersShipBy;
        const processedData = buildLists(state);
        state.config.lists = processedData;
    }
};

export default updateShipByOrders;