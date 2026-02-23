import {createAction} from "@reduxjs/toolkit";

const prepare = (product: object, currentItem: object, canvasData: object) => {
    return {
        payload: {
            product,
            currentItem,
            canvasData,
        }
    }
};

const saveProduct = createAction('storage/saveProduct', prepare);

export default saveProduct;