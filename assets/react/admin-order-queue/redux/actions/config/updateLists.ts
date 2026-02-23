import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateLists = createAction("config/updateLists", prepare);

export default updateLists;