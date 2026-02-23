import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateAdditionalNote = createAction('editor/updateAdditionalNote', prepare);

export default updateAdditionalNote;