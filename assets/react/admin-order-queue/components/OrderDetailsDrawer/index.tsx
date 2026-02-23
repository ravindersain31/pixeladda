import React, { memo, useState } from "react";
import { Space, Button, Flex, message, Modal, Table, Badge, Alert } from "antd";
import OrderDetailsForm from "../OrderDetailsForm";
import { OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import { DriveLink, StyledDrawer, ViewOrderButton } from "./styled";
import WareHouseOrderLogs from "../WareHouseOrderLogs";
import {
    LinkOutlined
} from '@ant-design/icons';
import axios from "axios";
import MarkDoneModal from "./MarkDoneModal";
import { useAppSelector } from "@react/admin-order-queue/hook";
import { initializeEpAutomation } from "@react/admin-order-queue/helper";
import { shallowEqual } from "react-redux";
import MoveOrderModal from "../MoveOrder/MoveOrderModal";
import { useBoardContext } from "@react/admin-order-queue/context/BoardContext";

interface OrderDetailsDrawerProps {
    warehouseOrder: OrderDetails | null;
    drawerVisible: boolean;
    onClose: () => void;
}

const OrderDetailsDrawer = memo(({ warehouseOrder, drawerVisible, onClose }: OrderDetailsDrawerProps) => {

    const [open, setOpen] = useState<boolean>(false);
    const [confirmLoading, setConfirmLoading] = useState<boolean>(false);
    const printer = useAppSelector((state) => state.config.printer, shallowEqual);
    const { moveOrderModalVisible, openMoveOrderModal, closeMoveOrderModal } = useBoardContext();

    if (!warehouseOrder) return null;



    const handleMarkDone = async (key: string) => {
        setConfirmLoading(true);

        try {
            const response = await axios.post('/warehouse/queue-api/warehouse-orders/mark-done', {
                id: warehouseOrder.id,
                orderId: warehouseOrder.order.orderId,
                type: key,
                printer: printer
            });

            if (response.data.success) {
                setOpen(false);
                message.success(response.data.message, 5);
                // initializeEpAutomation(warehouseOrder.order.orderId);
            } else {
                message.error('Error: ' + response.data.message, 5);
            }
        } catch (error) {
            message.error('Error marking order as done: ' + error, 5);
        } finally {
            setConfirmLoading(false);
        }
    };


    const showModal = () => {
        setOpen(true);
    };

    const handleCancel = () => {
        setOpen(false);
    };

    return (
        <StyledDrawer
            title={
                <>
                    <Flex gap={5} align="center">
                        <span style={{ fontSize: "1.2rem" }}>{warehouseOrder.order.orderId}</span>
                        <ViewOrderButton color="default" size="small" type="primary" icon={<LinkOutlined />} shape={'round'} target="_blank" href={`/orders/${warehouseOrder.order.orderId}/overview`}>
                            View
                        </ViewOrderButton>
                        {warehouseOrder.driveLink && <DriveLink color="default" size="small" type="primary" shape={'round'} target="_blank" href={warehouseOrder.driveLink || ""}>
                            Drive Link
                        </DriveLink>}
                        {<Button type="primary" size="small" onClick={showModal}>Mark Done</Button>}
                        <Button type="primary" size="small" onClick={() => openMoveOrderModal(warehouseOrder)}>Move Order</Button>
                    </Flex>
                </>
            }
            width={600}
            onClose={onClose}
            open={drawerVisible}
            extra={
                <Space>
                    <Button onClick={onClose}>Close</Button>
                </Space>
            }
        >
            <OrderDetailsForm warehouseOrder={warehouseOrder} />
            <WareHouseOrderLogs warehouseOrder={warehouseOrder} />
            <MoveOrderModal warehouseOrder={warehouseOrder} open={moveOrderModalVisible} onClose={closeMoveOrderModal} />
            <MarkDoneModal
                warehouseOrder={warehouseOrder}
                open={open}
                confirmLoading={confirmLoading}
                handleOk={handleMarkDone}
                handleCancel={handleCancel}
            />
        </StyledDrawer>
    );
});

export default OrderDetailsDrawer;
