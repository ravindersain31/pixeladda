import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const addComment = createAction("config/addComment", prepare);

export default addComment;