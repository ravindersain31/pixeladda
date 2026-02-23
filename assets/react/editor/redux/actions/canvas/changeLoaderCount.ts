import {createAction} from "@reduxjs/toolkit";

const prepare = (count?: number) => {
    return {
        payload: {
            count: count,
        }
    }
};

const changeLoaderCount = createAction("canvas/changeLoaderCount", prepare);

export default changeLoaderCount;