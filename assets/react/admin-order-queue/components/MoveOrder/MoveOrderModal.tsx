import React from 'react';
import { Modal } from 'antd';
import { OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';
import MoveOrder from '.';

interface MoveOrderModalProps {
    open: boolean;
    warehouseOrder: OrderDetails | null;
    onClose: () => void;
}

const MoveOrderModal = ({ open, warehouseOrder, onClose }: MoveOrderModalProps) => {
    if (!warehouseOrder) return null;

    return (
        <Modal
            open={open}
            title={`Move Order #${warehouseOrder.order?.orderId}`}
            onCancel={onClose}
            footer={null}
            centered
            width={480}
        >
            <MoveOrder warehouseOrder={warehouseOrder} />
        </Modal>
    );
};

export default MoveOrderModal;
