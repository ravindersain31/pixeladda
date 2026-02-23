import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const refresh = createAction("config/refresh", prepare);

export default refresh;
