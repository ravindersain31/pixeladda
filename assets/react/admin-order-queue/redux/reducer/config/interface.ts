import { SortOrder } from "@react/admin-order-queue/constants/sort.constants";
import { FiltersState } from "../interface";

export interface WarehouseOrderLog {
    id: string;
    content: string;
    createdAt: string;
    isManual: boolean;
    loggedBy: {
        name: string;
        email: string;
    };
}

export interface Order {
    id: string;
    orderId: string;
    shippingOrderId: string;
    isFreightRequired: boolean;
    paymentStatus: string;
    status: string;
    approvedProof: any[];
    isSuperRush: boolean;
    isRush: boolean;
    isPause: boolean;
    printFilesStatus: string
    metaData: {
        deliveryMethod: {
            key: string;
            type: string;
            label: string;
            discount: number;
        };
        isBlindShipping: boolean;
        isFreeFreight: boolean;
        isSaturdayDelivery: boolean;
        tags: {
            [key: string]: {
                name: string;
                active: boolean;
                [property: string]: any;
            };
        } | null;
        mustShip?: {
            name: string;
            date: string;
        } | null;
    };
    splitOrder: boolean;
    splitOrderTagOnly: string | null;
    totalQuantities: {
        totalQuantity: number;
        frameQuantity: number;
        frameType: string | null;
        frameTypeQty: { [key: string]: number };
        sizes: string[];
        sides: string;
        grommets: string;
    };
}


export interface ListProps {
    id: string;
    printerName: string | null;
    shipBy: string;
    createdAt: string;
    sortIndex: string;
    warehouseOrders: OrderDetails[];
}

export interface OrdersShipBy {
    [key: string]: OrderDetails[];
}

export interface WarehouseOrderGroupList {
    id: string;
}

export interface WarehouseOrderGroup {
    id: string;
    cardColor: string;
    warehouseOrderGroupList: WarehouseOrderGroupList[];
}

export interface IUser {
    id: string;
    name: string;
    email: string;
}

export interface OrderDetails {
    id: string;
    comments: string | null;
    driveLink: string | null;
    isProofPrinted: boolean;
    notes: string | null;
    order: Order;
    printStatus: string;
    proofPrintedBy: IUser;
    proofPrintedAt: string | null;
    printerName: string;
    shipBy: string;
    shippingService: string;
    updatedAt: string;
    createdAt: string;
    sortIndex: number;
    warehouseOrderGroup: WarehouseOrderGroup | null;
    warehouseOrderLogs: WarehouseOrderLog[];
}

export interface SelectedOrderShipBy {
    id: string;
    order: Order | null;
    shipBy: string | null;
    shippingService: string | null;
    printerName: string | null;
    notes: string | null;
    driveLink: string | null;
    printStatus: string | null;
    warehouseOrderLogs: WarehouseOrderLog[] | null;
}

export default interface ConfigState {
    initialized: boolean;
    lists: ListProps[];
    printer: string;
    ordersShipBy: OrdersShipBy;
    selectedOrderShipBy: SelectedOrderShipBy | null;
    sortOrder: SortOrder;
    filters: FiltersState;
    orderCount: number;
    printers: {
        [key: string]: {
            label: string;
            color: string;
            orderCount: number;
        };
    },
    urls: {
        adminURL: string;
        frontendUrl: string;
    };
}
