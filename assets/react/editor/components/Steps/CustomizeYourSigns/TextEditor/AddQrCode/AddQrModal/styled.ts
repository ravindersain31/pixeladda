import { Modal, Button } from "antd";
import styled from "styled-components";

export const AddQrCodeContainer = styled.div`
    position: absolute;
    left: -9999px;
    top: -9999px;
    visibility: hidden;
`;

export const StyledModal = styled(Modal)`
    .ant-modal-body {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        text-align: center;
        min-height: 100px;
    }

    .ant-modal-header,
    .ant-modal-footer {
        text-align: center;

        .ant-modal-title {
            font-size: 18px;
        }
    }
`;

export const StyledButton = styled(Button)`
    background: var(--primary-color);
    border: var(--primary-color);
    color: #FFF;
    text-transform: capitalize;
    margin-bottom: 10px;
    padding: 4px 15px;
    font-size: 15px !important;
    font-weight: 500;
`;