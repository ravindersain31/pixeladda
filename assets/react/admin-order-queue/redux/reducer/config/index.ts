import { ASC } from "@react/admin-order-queue/constants/sort.constants.ts";
import ConfigState, { OrderDetails, OrdersShipBy } from "./interface"

export {default as initialize} from "./initialize.case.ts";
export {default as updateSelectedShipBy} from "./updateSelectedShipBy.case.ts";
export {default as updateNotes} from "./updateNotes.case.ts";
export {default as updateLists} from "./updateLists.case.ts";
export {default as updateWarehouseOrder} from "./updateWarehouseOrder.case.ts";
export {default as refresh} from "./refresh.case.ts";
export {default as updatePrintStatus} from "./updatePrintStatus.case.ts";
export {default as updateProofPrinted} from "./updateProofPrinted.case.ts";
export {default as updateShipByOrders} from "./updateShipByOrders.case.ts";
export {default as removeShipByList} from "./removeShipByList.case.ts";
export {default as createShipByList} from "./createShipByList.case.ts";
export {default as addComment} from "./addComment.case.ts";
export {default as updateWarehouseOrderLogs} from "./updateWarehouseOrderLogs.case.ts";
export {default as removeWarehouseOrder} from "./removeWarehouseOrder.case.ts";
export {default as updatePrintersCount} from "./updatePrintersCount.case.ts";
export {default as updateFilters} from "./updateFilters.case.ts";

const initialState: ConfigState = {
    initialized: false,
    lists: [],
    printer: "",
    ordersShipBy: {},
    selectedOrderShipBy: null,
    sortOrder: ASC,
    orderCount: 0,
    printers: {},
    filters: {
        orderId: "",
        reset: false,
        status: [],
        dateRange: [null, null],
    },
    urls: {
        adminURL: "",
        frontendUrl: ""
    }
}

export default initialState;