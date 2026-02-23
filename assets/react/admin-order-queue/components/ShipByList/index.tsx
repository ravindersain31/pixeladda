import React, { memo, useEffect, useMemo, useState, useContext } from "react";
import { Button, Col, message, Modal, Row } from "antd";
import { Droppable, Draggable } from "@hello-pangea/dnd";
import {
    DraggableWrapper,
    HeaderWrapper,
    ListHeader,
    ListHeaderTitle,
    OrderQueueListWrapper,
    RemoveShipByButton,
} from "./styled";
import { getShipByColor, normalizeDate } from "@react/admin-order-queue/helper";
import WareHouseOrderCard from "../WareHouseOrderCard";
import OrderDetailsModal from "../OrderDetailsModal";
import OrderDetailsDrawer from "../OrderDetailsDrawer";
import { ListProps, OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import { useAppDispatch } from "@react/admin-order-queue/hook.ts";
import actions from "@react/admin-order-queue/redux/actions";
import { CloseCircleTwoTone, SearchOutlined, CloseOutlined } from '@ant-design/icons';
import AddOrderAutoComplete from "../AddOrderAutoComplete";
import { MercureContext } from "@react/admin-order-queue/context/MercureProvider";
import axios from "axios";
import moment from "moment";
import { MERCURE_TOPICS, MercureEvent } from "@react/admin-order-queue/constants/mercure.constant";

const ShipByList = memo(({ list }: { list: ListProps }) => {

    const [modalContent, setModalContent] = useState<OrderDetails | null>(null);
    const [drawerContent, setDrawerContent] = useState<OrderDetails | null>(null);
    const [isModalVisible, setIsModalVisible] = useState<boolean>(false);
    const [drawerVisible, setDrawerVisible] = useState<boolean>(false);
    const [loading, setLoading] = useState<boolean>(false);
    const dispatch = useAppDispatch();
    const { events } = useContext(MercureContext);
    const [orderIdFromUrl, setOrderIdFromUrl] = useState<string | null>(null);

    const warehouseOrders = useMemo(() => {
        return list.warehouseOrders;
    }, [list.warehouseOrders]);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const orderId = params.get('view');
        setOrderIdFromUrl(orderId);
    }, []);

    useEffect(() => {
        if (orderIdFromUrl && warehouseOrders.length > 0) {
            const targetOrder = warehouseOrders.find(
                (order) => order.id.toString() === orderIdFromUrl
            );
            if (targetOrder && targetOrder.id.toString() === orderIdFromUrl && !isModalVisible) {
                openModal(targetOrder);
            }
        }
    }, [warehouseOrders, orderIdFromUrl]);

    useEffect(() => {
        list.warehouseOrders.forEach((order) => {
            if (order.id === drawerContent?.id) {
                setDrawerContent(order);
            }
            if (order.id === modalContent?.id) {
                setModalContent(order);
            }
        });

    }, [list.warehouseOrders, drawerContent?.id, modalContent?.id]);

    useEffect(() => {
        if (events.length > 0) {
            const relevantEvents = events.filter((event: MercureEvent) => event.topic === MERCURE_TOPICS.WAREHOUSE_ORDER_CHANGED_SHIP_BY || event.topic === MERCURE_TOPICS.WAREHOUSE_ORDER_REMOVED);
            relevantEvents.forEach((event: MercureEvent) => updateContent(event.data));
        }
    },[events])

    const updateContent = (data: any) => {
        if (drawerContent && data.warehouseOrderId === drawerContent.id) {
            closeDrawer();
        }

        if (modalContent && data.warehouseOrderId === modalContent.id) {
            closeModal();
        }
    }

    const openModal = (order: OrderDetails) => {
        setModalContent(order);
        setIsModalVisible(true);

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('view', order.id.toString());
        window.history.pushState({}, '', newUrl.toString());
    };

    const closeModal = () => {
        setModalContent(null);
        setIsModalVisible(false);

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.delete('view');
        window.history.pushState({}, '', newUrl.toString());
    };

    const openDrawer = (order: OrderDetails) => {
        setDrawerContent(order);
        setDrawerVisible(true);
    };

    const closeDrawer = () => {
        setDrawerContent(null);
        setDrawerVisible(false);
    };

    const handleDeleteShipBy = () => {
        Modal.confirm({
            title: 'Are you sure you want to delete this Ship By list?',
            content: 'This action cannot be undone. Please ensure all orders have been moved before proceeding.',
            okText: 'Yes, Delete',
            cancelText: 'Cancel',
            okType: 'danger',
            onOk: async () => {
                setLoading(true);
                try {
                    const response = await axios.delete(`/warehouse/queue-api/warehouse-orders/delete-ship-by-list/${list.id}`);
                    if (response.status === 200) {
                        message.success(response.data.message);
                    } else {
                        message.error('An error occurred while deleting the Ship By list.');
                    }
                } catch (error: any) {
                    message.error(
                        error.response?.data?.message ||
                        'Failed to delete Ship By list. Please try again later.'
                    );
                } finally {
                    setLoading(false);
                }
            },
        });
    };

    return (
        <OrderQueueListWrapper style={{ backgroundColor: getShipByColor(list.shipBy) }}>
            <HeaderWrapper>
                <Row gutter={[4, 4]} className="d-flex align-items-center board-header">
                    <Col span={12}>
                        <ListHeaderTitle>
                            Ship By: {moment(list.shipBy).format('ddd MMM D')}
                            <RemoveShipByButton
                                type="primary"
                                size="small"
                                icon={<CloseOutlined style={{ fontSize: "12px" }} />}
                                loading={loading}
                                onClick={handleDeleteShipBy}
                            />
                        </ListHeaderTitle>
                    </Col>
                    <Col span={12}>
                        <AddOrderAutoComplete shipBy={list.shipBy} listId={list.id} />
                    </Col>
                </Row>
            </HeaderWrapper>

            <Row gutter={[16, 16]} justify="center" style={{ height: "95%" }}>
              {/*   <Droppable droppableId={list.id.toString()} type="list" direction="vertical" mode="standard">
                    {(provided) => (
                        <DraggableWrapper
                            ref={provided.innerRef}
                            {...provided.droppableProps}
                            className={`orders-wrapper board-${list.id}`}
                        >
                            {warehouseOrders.map((order, index) => (
                                <Draggable key={order.id} draggableId={order.id.toString()} index={index}>
                                    {(provided, snapshot) => (
                                        <div
                                            ref={provided.innerRef}
                                            {...provided.draggableProps}
                                            {...provided.dragHandleProps}
                                        >
                                            <WareHouseOrderCard
                                                key={order.id}
                                                warehouseOrder={order}
                                            />
                                        </div>
                                    )}
                                </Draggable>
                            ))}
                            {provided.placeholder}
                        </DraggableWrapper>
                    )}
                </Droppable> */}
            </Row>

            <OrderDetailsModal
                warehouseOrder={modalContent}
                modalVisible={isModalVisible}
                onClose={closeModal}
            />

            <OrderDetailsDrawer
                warehouseOrder={drawerContent}
                drawerVisible={drawerVisible}
                onClose={closeDrawer}
            />
        </OrderQueueListWrapper>
    );
});

export default ShipByList;
