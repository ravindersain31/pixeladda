import React, { memo } from "react";
import { OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import { MiniOrderInfoWrapper, DriveLinkButton } from "./styled";
import { Space } from "antd";
import ProofCheckBox from "../ProofCheckBox";

interface PrintingStatusProps {
    warehouseOrder: OrderDetails;
    onClick?: (e: React.MouseEvent) => void;
}

const MiniOrderInfo = memo(({ warehouseOrder, onClick }: PrintingStatusProps) => {

    return (
        <MiniOrderInfoWrapper onClick={onClick} className="mini-order-info">
            <ProofCheckBox warehouseOrder={warehouseOrder} />|
            {warehouseOrder.driveLink  && <><DriveLinkButton href={warehouseOrder.driveLink ?? ""} target="_blank" rel="noopener noreferrer">Drive</DriveLinkButton> <span>|</span></>}
            {warehouseOrder.order.totalQuantities.totalQuantity > 0 && <><span>{warehouseOrder.order.totalQuantities.totalQuantity} Signs</span>|</>}
            {warehouseOrder.order.totalQuantities.frameQuantity > 0 && <> <span>{warehouseOrder.order.totalQuantities.frameQuantity} Stakes</span>|</>}
            <span>{warehouseOrder.order.totalQuantities.sizes.length > 1 ? 'Multiple' : warehouseOrder.order.totalQuantities.sizes[0]}</span>|
            <span>{warehouseOrder.order.totalQuantities.sides}</span>|
            <span>{warehouseOrder.order.totalQuantities.grommets === 'None' ? '' : warehouseOrder.order.totalQuantities.grommets}</span>
        </MiniOrderInfoWrapper>
    );
});

export default MiniOrderInfo;
