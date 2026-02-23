import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateProofPrinted = createAction("config/updateProofPrinted", prepare);

export default updateProofPrinted;