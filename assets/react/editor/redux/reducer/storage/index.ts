import StorageState from "./interface.ts";

export {default as saveProduct} from "./saveProduct.case.ts";

const initialState: StorageState = {
    products: {}
}

export default initialState;