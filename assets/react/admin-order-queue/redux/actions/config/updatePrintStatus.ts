import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updatePrintStatus = createAction("config/updatePrintStatus", prepare);

export default updatePrintStatus;