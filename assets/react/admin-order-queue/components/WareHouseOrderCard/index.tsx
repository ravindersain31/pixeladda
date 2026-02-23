import React, { memo, useEffect } from "react";
import { Col } from "antd";
import { CardHeaderWrapper, StyledCard, StyledCol } from "./styled";
import MiniOrderInfo from "../MiniOrderInfo";
import Notes from "../Notes";
import { ExpandAltOutlined } from "@ant-design/icons";
import { OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import OrderTags from "../OrderTags";
import PrintingStatus from "../PrintingStatus";
import { useBoardContext } from "@react/admin-order-queue/context/BoardContext";

interface WareHouseOrderCardProps {
    warehouseOrder: OrderDetails;
}

const WareHouseOrderCard = memo(({ warehouseOrder }: WareHouseOrderCardProps) => {
    const { openOrderModal } = useBoardContext();
    const mustShip = warehouseOrder.order.metaData.mustShip;
    const hasMustShip = !!(mustShip && mustShip.date);

    return (
        <StyledCol key={warehouseOrder.id} xs={24} sm={24} md={12} lg={24}>
            <StyledCard
                hoverable
                ordergroupcolor={warehouseOrder.warehouseOrderGroup?.cardColor ?? "#fff"}
                $hasmustship={hasMustShip}
            >
                <CardHeaderWrapper>
                    <strong className="text-primary">
                        {warehouseOrder.order.orderId}
                        <button
                            type="button"
                            style={{ border: "none", background: "none", cursor: "pointer", width: "auto", position: "absolute", top: 2, padding: '2px 3px' }}
                            onClick={(e) => {
                                openOrderModal(warehouseOrder);
                                e.stopPropagation();
                            }}
                        >
                            <svg width="18px" height="18px" viewBox="0 0 24.00 24.00" fill="none" xmlns="http://www.w3.org/2000/svg" transform="matrix(1, 0, 0, 1, 0, 0)"><g id="SVGRepo_bgCarrier" strokeWidth="0"></g><g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round" stroke="#CCCCCC" strokeWidth="1.584"></g><g id="SVGRepo_iconCarrier"> <path d="M21 14V16.2C21 17.8802 21 18.7202 20.673 19.362C20.3854 19.9265 19.9265 20.3854 19.362 20.673C18.7202 21 17.8802 21 16.2 21H14M10 3H7.8C6.11984 3 5.27976 3 4.63803 3.32698C4.07354 3.6146 3.6146 4.07354 3.32698 4.63803C3 5.27976 3 6.11984 3 7.8V10M15 9L21 3M21 3H15M21 3V9M9 15L3 21M3 21H9M3 21L3 15" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path> </g></svg>
                        </button>
                    </strong>
                    <strong>
                        <PrintingStatus warehouseOrder={warehouseOrder} onClick={(e) => e.stopPropagation()} />
                    </strong>
                </CardHeaderWrapper>
                <MiniOrderInfo warehouseOrder={warehouseOrder} onClick={(e) => e.stopPropagation()} />
                <OrderTags order={warehouseOrder} />
                <Notes warehouseOrderId={warehouseOrder.id} comments={warehouseOrder.comments} />
            </StyledCard>
        </StyledCol>
    );
});

export default WareHouseOrderCard;
