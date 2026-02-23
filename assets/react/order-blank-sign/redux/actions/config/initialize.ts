import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const initialize = createAction('config/initialize', prepare);

export default initialize;
