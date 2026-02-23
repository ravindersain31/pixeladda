import React, { memo, useEffect, useState } from 'react';
import { DownOutlined } from '@ant-design/icons';
import type { MenuProps } from 'antd';
import { Dropdown, message, Space, Typography } from 'antd';
import { OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';
import { PrintingStatusWrapper } from './styled';
import { WarehouseOrderStatus, WarehouseOrderStatusEnum } from '@react/admin-order-queue/constants';
import axios from 'axios';
import { useAppDispatch, useAppSelector } from '@react/admin-order-queue/hook';
import MarkDoneModal from '../OrderDetailsDrawer/MarkDoneModal';
import PrintProofModal from '../PrintProofModal';
import { shallowEqual } from 'react-redux';
import { initializeEpAutomation } from '@react/admin-order-queue/helper';

interface PrintingStatusProps {
    warehouseOrder: OrderDetails;
    onClick: (e: React.MouseEvent) => void;
}

const PrintingStatus = memo(({ warehouseOrder, onClick }: PrintingStatusProps) => {

    const dispatch = useAppDispatch();
    const [selectedStatus, setSelectedStatus] = useState<WarehouseOrderStatusEnum>(
        warehouseOrder.printStatus as WarehouseOrderStatusEnum
    );
    const [open, setOpen] = useState(false);
    const [confirmLoading, setConfirmLoading] = useState(false);
    const [printProofModalOpen, setPrintProofModalOpen] = useState(false);
    const [selectedNewStatus, setSelectedNewStatus] = useState<WarehouseOrderStatusEnum | null>(null);
    const printer = useAppSelector((state) => state.config.printer, shallowEqual);

    useEffect(() => {
        setSelectedStatus(warehouseOrder.printStatus as WarehouseOrderStatusEnum);
    }, [warehouseOrder.printStatus]);

    const items: MenuProps['items'] = Object.entries(WarehouseOrderStatus).map(
        ([key, { label, color }]) => ({
            key,
            label: <span style={{ color }}>{label}</span>,
        })
    );

    const updateStatus = async (newStatus: WarehouseOrderStatusEnum) => {
        try {
            setSelectedStatus(newStatus);
            const response = await axios.post('/warehouse/queue-api/warehouse-orders/update-print-status', {
                id: warehouseOrder.id,
                printStatus: newStatus,
            });
            if(response.data?.error) {
                message.error(response.data.error, 5);
                return;
            }
            message.success(
                `Order Id: ${warehouseOrder.order.orderId} Print status updated to "${WarehouseOrderStatus[newStatus].label}"!`
            );
        } catch (error) {
            message.error('Error updating print status: ' + error, 5);
        }
    };

    const handleMenuClick = async ({ key }: { key: string }) => {
        if (key === selectedStatus) return;

        const newStatus = key as WarehouseOrderStatusEnum;

        if (newStatus === WarehouseOrderStatusEnum.DONE) {
            setOpen(true);
            return;
        }

        if (!warehouseOrder.isProofPrinted && newStatus === WarehouseOrderStatusEnum.PRINTING) {
            setSelectedNewStatus(newStatus);
            setPrintProofModalOpen(true);
            return;
        }

        await updateStatus(newStatus);
    };

    const handlePrintProofConfirm = () => {
        if (selectedNewStatus) {
            updateStatus(selectedNewStatus);
        }
        closePrintProofModal();
    };

    const closePrintProofModal = () => {
        setPrintProofModalOpen(false);
        setSelectedNewStatus(null);
    };

    const handleMarkDone = async (key: string) => {
        setConfirmLoading(true);
        try {
            const response = await axios.post('/warehouse/queue-api/warehouse-orders/mark-done', {
                id: warehouseOrder.id,
                orderId: warehouseOrder.order.orderId,
                type: key,
                printer: printer,
            });

            if (response.data.success) {
                message.success(response.data.message, 5);
                // initializeEpAutomation(warehouseOrder.order.orderId);
            } else {
                message.error('Error: ' + response.data.message, 5);
            }
        } catch (error) {
            message.error('Error marking order as done: ' + error, 5);
        } finally {
            setConfirmLoading(false);
            setOpen(false);
        }
    };

    return (
        <PrintingStatusWrapper onClick={onClick}>
            <Dropdown
                menu={{
                    items,
                    selectable: true,
                    defaultSelectedKeys: [selectedStatus.toString()],
                    onClick: handleMenuClick,
                }}
                trigger={['click']}
            >
                <Typography.Link>
                    <Space>
                        <span
                            style={{
                                color: WarehouseOrderStatus[selectedStatus]?.color || 'inherit',
                            }}
                        >
                            {WarehouseOrderStatus[selectedStatus]?.label || 'Select Status'}
                        </span>
                        <DownOutlined />
                    </Space>
                </Typography.Link>
            </Dropdown>

            <MarkDoneModal
                warehouseOrder={warehouseOrder}
                open={open}
                confirmLoading={confirmLoading}
                handleOk={handleMarkDone}
                handleCancel={() => setOpen(false)}
            />

            {!warehouseOrder.isProofPrinted && <PrintProofModal
                orderId={warehouseOrder.order.orderId}
                open={printProofModalOpen}
                confirmLoading={confirmLoading}
                onOk={handlePrintProofConfirm}
                onCancel={closePrintProofModal}
                isProofPrinted={warehouseOrder.isProofPrinted}
                proofPrintedBy={warehouseOrder.proofPrintedBy}
                proofPrintedAt={warehouseOrder.proofPrintedAt}
            />}
        </PrintingStatusWrapper>
    );
});

export default PrintingStatus;