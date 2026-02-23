import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateSelectedShipBy = createAction("config/updateSelectedShipBy", prepare);

export default updateSelectedShipBy;
