import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const createShipByList = createAction("config/createShipByList", prepare);

export default createShipByList;