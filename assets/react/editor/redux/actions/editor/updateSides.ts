import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateSides = createAction('editor/updateSides', prepare);

export default updateSides;