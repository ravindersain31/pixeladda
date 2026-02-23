import { useDispatch } from 'react-redux';
import useMercureSubscriber from '../context/useMercureSubscriber';
import { MERCURE_TOPICS } from '../constants/mercure.constant';
import actions from '../redux/actions';

export const useMercureActions = () => {
    const dispatch = useDispatch();

    const handleWarehouseOrderUpdatedNotes = (eventData: any) => {
        if (eventData.id) {
            dispatch(actions.config.updateNotes({ id: eventData.id, comments: eventData.comments }))
        };
    };

    const handleWarehouseOrderPrintStatus = (eventData: any) => {
        if (eventData.id) {
            dispatch(actions.config.updatePrintStatus({ id: eventData.id, printStatus: eventData.printStatus }))
        };
    };

    const handleWarehouseOrderProofPrinted = (eventData: any) => {
        if (eventData.id) {
            dispatch(actions.config.updateProofPrinted({ id: eventData.id, isProofPrinted: eventData.isProofPrinted }))
        };
    };

    const handleWarehouseOrderSortIndex = (eventData: any) => {
        if (eventData.shipByOrders && eventData.printer) {
            dispatch(actions.config.updateShipByOrders({ shipByOrders: eventData.shipByOrders, printer: eventData.printer }))
        };
    };

    const handleWarehouseRemoveShipByList = (eventData: any) => {
        if (eventData.id && eventData.isDeleted) {
            dispatch(actions.config.removeShipByList({ id: eventData.id, isDeleted: eventData.isDeleted }))
        };
    };

    const handleWarehouseCreateShipByList = (eventData: any) => {
        if (eventData.lists && eventData.printer) {
            dispatch(actions.config.createShipByList({ lists: eventData.lists, printer: eventData.printer }))
        };
    };

    const handleWarehouseOrderLogs = (eventData: any) => {
        if (eventData.id && eventData.logs) {
            dispatch(actions.config.updateWarehouseOrderLogs({ id: eventData.id, logs: eventData.logs }))
        };
    };

    const handleWarehouseOrderUpdated = (eventData: any) => {
        if (eventData.id && eventData.warehouseOrder) {
            dispatch(actions.config.updateWarehouseOrder(eventData.warehouseOrder))
        };
    };

    const handleWarehouseUpdateShipByList = (eventData: any) => {
        if (eventData.shipByOrders && eventData.printer) {
            dispatch(actions.config.updateShipByOrders({ shipByOrders: eventData.shipByOrders, printer: eventData.printer }))
        };
    };

    const handleRemoveWarehouseOrder = (eventData: any) => {
        if (eventData.warehouseOrderId && eventData.printer) {
            dispatch(actions.config.removeWarehouseOrder({ warehouseOrderId: eventData.warehouseOrderId, printer: eventData.printer }))
        };
    };

    const handleMarkDone = (eventData: any) => {
        if (eventData.warehouseOrderId && eventData.type && eventData.printer) {
            dispatch(actions.config.removeWarehouseOrder({ warehouseOrderId: eventData.warehouseOrderId, type: eventData.type, printer: eventData.printer }));
        }
    };

    const handleWarehousePrintersCount = (eventData: any) => {
        if (eventData.printers) {
            dispatch(actions.config.updatePrintersCount({ printers: eventData.printers }));
        }
    };

    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_UPDATED_NOTES, handleWarehouseOrderUpdatedNotes);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_UPDATED_PRINT_STATUS, handleWarehouseOrderPrintStatus);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_UPDATED_PRINTED, handleWarehouseOrderProofPrinted);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_UPDATED_SORT_INDEX, handleWarehouseOrderSortIndex);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_REMOVE_SHIP_BY_LIST, handleWarehouseRemoveShipByList);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_CREATED_SHIP_BY_LIST, handleWarehouseCreateShipByList);
    // useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_UPDATE_LOGS, handleWarehouseOrderLogs);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_UPDATED, handleWarehouseOrderUpdated);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_UPDATED_SHIP_BY_LIST, handleWarehouseUpdateShipByList);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_REMOVED, handleRemoveWarehouseOrder);

    // MARK DONE EVENTS
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_MARK_DONE, handleMarkDone);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_FREIGHT_SHIPPING_DONE, handleMarkDone);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_PICKUP_DONE, handleMarkDone);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_PUSH_TO_SE, handleMarkDone);
    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_ORDER_MARK_DONE_READY_FOR_SHIPMENT, handleMarkDone);

    useMercureSubscriber(MERCURE_TOPICS.WAREHOUSE_PRINTERS_COUNT, handleWarehousePrintersCount);

    // You can uncomment and add more subscribers here as needed, following the same pattern
    // useMercureSubscriber(MERCURE_TOPICS.TASK_UPDATED, handleTaskUpdated);
    // useMercureSubscriber(MERCURE_TOPICS.TASK_DELETED, handleTaskDeleted);
    // useMercureSubscriber(MERCURE_TOPICS.COLUMN_UPDATED, handleColumnUpdated);

    return {};
};
