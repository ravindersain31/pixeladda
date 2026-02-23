import React, { memo } from 'react';
import { Tag } from 'antd';
import { OrderTagsWrapper } from './styled';
import { OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';
import { getActiveTags } from '@react/admin-order-queue/helper';
import { OrderTags as Tags } from '@react/admin-order-queue/redux/reducer/interface';
import { getShippingServiceLabel, WarehouseShippingServiceEnum } from '@react/admin-order-queue/constants/warehouse.constants';
import dayjs from 'dayjs';

const OrderTags = ({ order }: { order: OrderDetails }) => {
    const activeTags = getActiveTags(order.order.metaData.tags ?? {});

    const filteredTags = activeTags.filter(
        tag => tag.key !== Tags.BLIND_SHIPPING && tag.key !== Tags.REQUEST_PICKUP && tag.key !== Tags.SATURDAY_DELIVERY && tag.key !== Tags.SPLIT_ORDER && tag.key !== Tags.SUPER_RUSH
    );

    const hasBlindShippingTag = activeTags.some(tag => tag.key === Tags.BLIND_SHIPPING);
    const hasRequestPickupTag = activeTags.some(tag => tag.key === Tags.REQUEST_PICKUP);
    const hasSaturdayDeliveryTag = activeTags.some(tag => tag.key === Tags.SATURDAY_DELIVERY);
    const isPaused = (order?.order?.isPause || order?.printStatus === 'PAUSED') ?? false;
    const printFilesStatus = (order?.order?.printFilesStatus ?? '') === 'UPLOADED';

    return (
        <OrderTagsWrapper>
            {order.order.splitOrder && <Tag color="red" className="text-white bg-danger">{order.order.splitOrderTagOnly}</Tag>}
            {order.order.totalQuantities.frameType && <Tag color='#2db7f5' className='bg-gradient-primary-to-secondary text-white fw-bold'><strong>{order.order.totalQuantities.frameType} Stakes</strong></Tag>}
            {order.shippingService && <Tag color="black">{getShippingServiceLabel(order.shippingService as WarehouseShippingServiceEnum)}</Tag>}

            {filteredTags.map((tag: { key: string; name: string; color: string }) => (
                <Tag key={tag.key} color={tag.color}>
                    {tag.name}
                </Tag>
            ))}

            {isPaused && <Tag color={'#ff0000'}>Paused</Tag>}

            {!hasRequestPickupTag || order?.order?.metaData?.deliveryMethod?.key === Tags.REQUEST_PICKUP && (
                <Tag color="green">Request Pickup</Tag>
            )}

            {!hasBlindShippingTag || order.order.metaData.isBlindShipping && (
                <Tag color="warning">Blind Shipping</Tag>
            )}
            {order.order.isRush && <Tag color="rgb(0,97,242)">Rush</Tag>}
            {order.order.isSuperRush && (!hasSaturdayDeliveryTag || !order.order.metaData.isSaturdayDelivery) && <Tag color="red">Super Rush</Tag>}
            {!hasSaturdayDeliveryTag || order.order.metaData.isSaturdayDelivery && <Tag color="red">Super Rush: Saturday Delivery</Tag>}
            {printFilesStatus && <Tag color={'#00ac69'}>PF</Tag>}
            {order.order.metaData.mustShip?.date && <Tag color={'#007aaf'}>{order.order.metaData.mustShip.name}</Tag>}
        </OrderTagsWrapper>
    );
};

export default OrderTags;
