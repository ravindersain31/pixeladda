import React, { memo, useState } from "react";
import { Button, Modal, message, Typography, Space } from "antd";
import { ExclamationCircleOutlined } from "@ant-design/icons";
import { StyledModal } from "./styled";
import axios from "axios";
import { IUser } from "@react/admin-order-queue/redux/reducer/config/interface";
import dayjs from "dayjs";

interface PrintProofModalProps {
    orderId: string;
    open: boolean;
    confirmLoading: boolean;
    onOk: () => void;
    onCancel: () => void;
    confirmText?: string;
    isProofPrinted?: boolean;
    proofPrintedBy?: IUser | null;
    proofPrintedAt?: string | null;
}

const PrintProofModal = memo(({
    orderId,
    open,
    confirmLoading,
    onOk,
    onCancel,
    confirmText,
    isProofPrinted = false,
    proofPrintedBy,
    proofPrintedAt
}: PrintProofModalProps) => {

    const [printLoading, setPrintLoading] = useState<boolean>(false);

    const isProofAlreadyPrinted = Boolean(proofPrintedBy && proofPrintedAt);

    const onPrintProof = async () => {
        if (isProofAlreadyPrinted) {
            Modal.confirm({
                title: "Are you sure?",
                icon: <ExclamationCircleOutlined />,
                content: "Proof has already been printed. Are you sure you want to print it again?",
                okText: "Yes, reprint",
                cancelText: "Cancel",
                onOk: () => performPrint(),
            });
        } else {
            performPrint();
        }
    };

    const performPrint = async () => {
        setPrintLoading(true);
        try {
            const response = await axios.post(`/warehouse/queue-api/order/proof`, {
                orderId,
            });

            const { data } = response;
            if (!data.success) {
                message.error('Error generating proof: ' + data.message, 5);
                return;
            }

            const proofUrl = data.data;
            message.success('Proof generated successfully');
            window.open(proofUrl, '_blank');
        } catch (error) {
            message.error('Error generating proof: ' + error, 5);
        } finally {
            setPrintLoading(false);
        }
    };

    return (
        <StyledModal
            open={open}
            onOk={onOk}
            confirmLoading={confirmLoading}
            onCancel={onCancel}
            width={450}
            footer={[
                <Button
                    style={{ borderRadius: 6 }}
                    key="print"
                    loading={printLoading}
                    type={isProofAlreadyPrinted ? 'default' : 'primary'}
                    onClick={onPrintProof}
                >
                    {isProofAlreadyPrinted ? 'Proof Already Printed' : 'Print Proof'}
                </Button>,
                <Button
                    key="confirm"
                    type="primary"
                    style={{ backgroundColor: 'green', borderColor: 'green', borderRadius: 6 }}
                    loading={confirmLoading}
                    onClick={onOk}
                >
                    {confirmText || 'Mark as Printing'}
                </Button>,
                <Button key="cancel" style={{ borderRadius: 6 }} onClick={onCancel}>
                    Cancel
                </Button>,
            ]}
        >
            <Typography.Paragraph>
                <Space direction="vertical" size="small" style={{ gap: 2 }}>
                    <Typography.Text strong>
                        Proof Status: {proofPrintedAt ? 'Printed' : 'Not Printed'}
                    </Typography.Text>
                    <Typography.Text type="secondary">
                        Printed by: {proofPrintedBy?.name || 'N/A'} | {proofPrintedBy?.email || 'N/A'}
                    </Typography.Text>
                    <Typography.Text type="secondary">
                        Printed at: {proofPrintedAt
                            ? dayjs(proofPrintedAt).format('ddd MMM D YYYY, h:mm A')
                            : 'N/A'}
                    </Typography.Text>
                </Space>
            </Typography.Paragraph>
        </StyledModal>
    );
});

export default PrintProofModal;
