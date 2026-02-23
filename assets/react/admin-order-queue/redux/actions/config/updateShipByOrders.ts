import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateShipByOrders = createAction("config/updateShipByOrders", prepare);

export default updateShipByOrders;