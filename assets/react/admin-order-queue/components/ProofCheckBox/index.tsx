import React, { memo, useEffect, useState } from "react";
import { OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import { message } from 'antd';
import type { CheckboxProps } from 'antd';
import { ProofLinkButton, StyledCheckBox } from "./styled";
import axios from "axios";
import { useAppSelector } from "@react/admin-order-queue/hook";
import PrintProofModal from "../PrintProofModal";
import { shallowEqual } from "react-redux";

interface PrintingStatusProps {
    warehouseOrder: OrderDetails;
    onClick?: (e: React.MouseEvent) => void;
}

const ProofCheckBox = memo(({ warehouseOrder, onClick }: PrintingStatusProps) => {

    const frontendUrl = useAppSelector((state) => state.config.urls.frontendUrl, shallowEqual);
    const [checked, setChecked] = useState<boolean>(warehouseOrder.isProofPrinted ?? false);
    const [printProofModalOpen, setPrintProofModalOpen] = useState(false);
    const [confirmLoading, setConfirmLoading] = useState(false);
    const [pendingChecked, setPendingChecked] = useState<boolean | null>(null);

    useEffect(() => {
        setChecked(warehouseOrder.isProofPrinted ?? false);
    }, [warehouseOrder]);

    const onChange: CheckboxProps['onChange'] = (e) => {
        const isChecked = e.target.checked;

        if (isChecked && !checked) {
            setPendingChecked(isChecked);
            setPrintProofModalOpen(true);
        } else {
            handleUpdateProofPrinted(isChecked);
        }
    };

    const handleUpdateProofPrinted = async (isChecked: boolean) => {
        setConfirmLoading(true);

        try {
            const response = await axios.post('/warehouse/queue-api/warehouse-orders/update-proof-print', {
                id: warehouseOrder.id,
                proofPrinted: isChecked,
            });

            // Update state only after successful response
            setChecked(isChecked);
            message.success(
                isChecked
                    ? `Order Id: ${warehouseOrder.order.orderId} Proof printed!`
                    : `Order Id: ${warehouseOrder.order.orderId} Proof not printed!`
            );
        } catch (error) {
            message.error('Error updating proof printed: ' + error, 5);
        } finally {
            setConfirmLoading(false);
        }
    };

    const handlePrintProofConfirm = async () => {
        if (pendingChecked !== null) {
            await handleUpdateProofPrinted(pendingChecked);
            setPendingChecked(null);
        }

        setPrintProofModalOpen(false);
    };

    const closePrintProofModal = () => {
        setPrintProofModalOpen(false);
        setPendingChecked(null);
    };

    return (
        <>
            <ProofLinkButton
                type="link"
                href={`${frontendUrl}order/proof/${warehouseOrder.order.orderId}`}
                target="_blank"
            >
                Proof
            </ProofLinkButton>
            <StyledCheckBox
                onChange={onChange}
                checked={checked}
                disabled={confirmLoading}
            />
            {!warehouseOrder.isProofPrinted && (
                <PrintProofModal
                    orderId={warehouseOrder.order.orderId}
                    open={printProofModalOpen}
                    confirmLoading={confirmLoading}
                    onOk={handlePrintProofConfirm}
                    onCancel={closePrintProofModal}
                    confirmText="Mark Print"
                    isProofPrinted={warehouseOrder.isProofPrinted}
                    proofPrintedBy={warehouseOrder.proofPrintedBy}
                    proofPrintedAt={warehouseOrder.proofPrintedAt}
                />
            )}
        </>
    );
});

export default ProofCheckBox;