import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateImprintColor = createAction('editor/updateImprintColor', prepare);

export default updateImprintColor;