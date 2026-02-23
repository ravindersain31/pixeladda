import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateFilters = createAction("config/updateFilters", prepare);

export default updateFilters;
