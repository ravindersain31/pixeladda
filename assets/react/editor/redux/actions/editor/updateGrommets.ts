import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateGrommets = createAction('editor/updateGrommets', prepare);

export default updateGrommets;