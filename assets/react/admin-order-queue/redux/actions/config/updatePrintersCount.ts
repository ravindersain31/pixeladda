import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updatePrintersCount = createAction("config/updatePrintersCount", prepare);

export default updatePrintersCount;