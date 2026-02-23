import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const removeShipByList = createAction("config/removeShipByList", prepare);

export default removeShipByList;