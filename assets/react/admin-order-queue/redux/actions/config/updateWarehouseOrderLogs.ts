import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateWarehouseOrderLogs = createAction("config/updateWarehouseOrderLogs", prepare);

export default updateWarehouseOrderLogs;