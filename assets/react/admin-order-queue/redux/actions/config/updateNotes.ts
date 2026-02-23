import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateNotes = createAction("config/updateNotes", prepare);

export default updateNotes;