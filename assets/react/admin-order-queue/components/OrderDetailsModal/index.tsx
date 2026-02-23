import React, { memo } from "react";
import { Button, Col, Modal, Row } from "antd";
import { StyledModal } from "./styled";
import Notes from "../Notes";
import { OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import MiniOrderInfo from "../MiniOrderInfo";
import OrderTags from "../OrderTags";
import PrintingStatus from "../PrintingStatus";
import { useBoardContext } from "@react/admin-order-queue/context/BoardContext";
import MoveOrderModal from "../MoveOrder/MoveOrderModal";

interface OrderDetailsModalProps {
    warehouseOrder: OrderDetails | null;
    modalVisible: boolean;
    onClose: () => void;
}

const OrderDetailsModal = memo(({ warehouseOrder, modalVisible, onClose }: OrderDetailsModalProps) => {

    if (!warehouseOrder) return null;
    const { moveOrderModalVisible, openMoveOrderModal, closeMoveOrderModal } = useBoardContext();
    
    return (
        <StyledModal
            title={
                <>
                    <Row align={"middle"} justify={"space-between"}>
                        <Col xs={12} sm={14} md={19} style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                            <span className="text-primary" style={{ fontSize: "1rem" }}>
                                {warehouseOrder.order.orderId}
                            </span>
                            <Button type="primary" size="small" onClick={() => openMoveOrderModal(warehouseOrder)}>Move Order</Button>
                        </Col>
                        <Col xs={10} sm={10} md={3}>
                            <PrintingStatus warehouseOrder={warehouseOrder} onClick={(e) => e.stopPropagation()} />
                        </Col>
                    </Row>
                </>
            }
            open={modalVisible}
            onCancel={onClose}
            footer={null}
            width={1000}
        >
            <div>
                <MoveOrderModal warehouseOrder={warehouseOrder} open={moveOrderModalVisible} onClose={closeMoveOrderModal} />
                <MiniOrderInfo warehouseOrder={warehouseOrder} />
                <OrderTags order={warehouseOrder} />
            </div>
            <Notes warehouseOrderId={warehouseOrder.id} comments={warehouseOrder.comments} notesRows={3} />
        </StyledModal>
    );
});

export default OrderDetailsModal;
