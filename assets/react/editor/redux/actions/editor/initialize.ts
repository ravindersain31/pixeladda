import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const initialize = createAction('editor/initialize', prepare);

export default initialize;
