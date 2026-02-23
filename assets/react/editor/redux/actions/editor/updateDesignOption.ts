import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateDesignOption = createAction("editor/updateDesignOption", prepare);

export default updateDesignOption;