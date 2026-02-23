import { createAction } from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateComment = createAction('cart/updateComment', prepare);

export default updateComment;