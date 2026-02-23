import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateWarehouseOrder = createAction("config/updateWarehouseOrder", prepare);

export default updateWarehouseOrder;