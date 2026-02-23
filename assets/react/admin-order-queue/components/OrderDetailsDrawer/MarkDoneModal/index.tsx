import React, { memo, useState } from "react";
import { Table, Button, Space, Badge } from "antd";
import { StyledAlert, StyledModal, ViewOrderButton } from "./styled";
import { OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import { LinkOutlined } from '@ant-design/icons';
import { OrderTags } from "@react/admin-order-queue/redux/reducer/interface";
import { ORDER_STATUS_LABELS } from "@react/admin-order-queue/constants/orderStatus.enum";
import { PAYMENT_STATUS_LABELS } from "@react/admin-order-queue/constants/paymentStatus.enum";
import { MarkDoneTypes } from "@react/admin-order-queue/constants/mercure.constant";


interface OrderDetailsModalProps {
    warehouseOrder: OrderDetails;
    open: boolean;
    confirmLoading: boolean;
    handleOk: (key: MarkDoneTypes) => void;
    handleCancel: () => void;
}

const MarkDoneModal = memo(({
    warehouseOrder,
    open,
    confirmLoading,
    handleOk,
    handleCancel,
}: OrderDetailsModalProps) => {
    const [selectedType, setSelectedType] = useState<MarkDoneTypes>('MARK_DONE');

    const getLabelAndKey = (): { label: string; key: MarkDoneTypes } => {
        if (warehouseOrder?.order?.metaData?.deliveryMethod?.key === OrderTags.REQUEST_PICKUP) {
            return { label: 'Yes, I confirm, Pickup Done', key: 'PICKUP_DONE' };
        }
        if (warehouseOrder.order.metaData.isFreeFreight) {
            return { label: 'Yes, I confirm, Freight Shipping Done', key: 'FREIGHT_SHIPPING_DONE' };
        }
        if (warehouseOrder.order.shippingOrderId) {
            return { label: 'Yes, Mark Ready to Create Shipment', key: 'MARK_DONE_READY_FOR_SHIPMENT' };
        }
        if (!warehouseOrder.order.shippingOrderId) {
            return { label: 'Yes, Mark Ready to Create Shipment', key: 'MARK_DONE_READY_FOR_SHIPMENT' };
        }
        return { label: 'Yes, Mark Done', key: 'MARK_DONE' };
    };

    const { label, key } = getLabelAndKey();

    const descriptionInfo: Record<MarkDoneTypes, JSX.Element> = {
        PICKUP_DONE: <>This order is already pushed to ShippingEasy.</>,
        FREIGHT_SHIPPING_DONE:  <>Remove it from ShippingEasy, as this order is a Freight Order.</>,
        PUSH_TO_SE_MARK_DONE:  <>Are you sure you want to push this order to ShippingEasy and mark it as Done? After pushing to ShippingEasy, the order status will be updated to Entered into Shippingeasy, and the Order Queue status will be changed to Done.</>,
        MARK_DONE_READY_FOR_SHIPMENT:  <>Are you sure you want to mark this order as <b>Create Shipment</b>? This order will be moved to the <b>Create Shipment</b> tab in Order Queue.</>,
        MARK_DONE:  <>Are you sure you want to mark this order as Done? This order will be moved to the Done tab in Order Queue and marked as Entered into Shippingeasy.</>,
    };

    const descriptionWarning: Record<MarkDoneTypes, JSX.Element> = {
        PICKUP_DONE: <>Are you sure you want to mark this order as picked up?After marking as Pickup Done, the order status will be updated to <b>Completed</b>, the Order Queue status will be changed to <b>Done</b>, and the order will be moved to <b>Completed Orders</b>..</>,
        FREIGHT_SHIPPING_DONE: <>Are you sure you want to mark this order as Freight Shipping Done? After marking as Freight Shipping Done, the order status will be updated to Completed, and the Order Queue status will be changed to Done.</>,
        PUSH_TO_SE_MARK_DONE: <>Are you sure you want to push this order to ShippingEasy and mark it as Done? After pushing to ShippingEasy, the order status will be updated to Entered into Shippingeasy, and the Order Queue status will be changed to Done.</>,
        MARK_DONE_READY_FOR_SHIPMENT: <>Are you sure you want to mark this order as <b>Create Shipment</b>? This order will be moved to the <b>Create Shipment</b> tab in Order Queue.</>,
        MARK_DONE: <>Are you sure you want to mark this order as Done? This order will be moved to the Done tab in Order Queue and marked as Entered into Shippingeasy.</>,
    };

    const handleSubmit = () => {
        setSelectedType(key);
        handleOk(key);
    };

    // Data source for the Table component
    const dataSource = [
        {
            key: '1',
            name: 'Order Status',
            value: ORDER_STATUS_LABELS[warehouseOrder.order.status],
        },
        {
            key: '2',
            name: 'Payment Status',
            value: PAYMENT_STATUS_LABELS[warehouseOrder.order.paymentStatus],
        },
        {
            key: '3',
            name: 'Proof Approved',
            value: (
                <Space>
                    <Badge count={'Yes'} color="green" />
                    <Button type="link" href={warehouseOrder.driveLink || "#"} size="small" icon={<LinkOutlined />}>
                        View
                    </Button>
                </Space>
            ),
        },
    ];

    const columns = [
        {
            title: 'Name',
            dataIndex: 'name',
            key: 'name',
        },
        {
            title: 'Value',
            dataIndex: 'value',
            key: 'value',
        },
    ];

    return (
        <StyledModal
            title={
                <>
                    <ViewOrderButton type="link" href={'/orders/' + warehouseOrder.order.orderId + '/overview'} target="_blank">#{warehouseOrder.order.orderId}</ViewOrderButton>
                </>
            }
            open={open}
            onOk={handleSubmit}
            confirmLoading={confirmLoading}
            onCancel={handleCancel}
            footer={[
                <Button
                    key="submit"
                    type="primary"
                    size="small"
                    loading={confirmLoading}
                    onClick={handleSubmit}
                >
                    {label}
                </Button>,
                <Button key="back" size="small" onClick={handleCancel}>
                    Cancel
                </Button>,
            ]}
        >
            <Table 
                dataSource={dataSource}
                columns={columns}
                showHeader={false}
                size="small"
                rowClassName={(_, index) => (index % 2 === 0 ? "table-striped" : "")}
                pagination={false}
            />
            <StyledAlert
                description={descriptionWarning[key]}
                type="warning"
            />
        </StyledModal>
    );
});

export default MarkDoneModal;
