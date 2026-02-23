import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const removeWarehouseOrder = createAction("config/removeWarehouseOrder", prepare);

export default removeWarehouseOrder;