import styled from "styled-components";
import { Alert, Button, Modal } from "antd";

export const StyledAlert = styled(Alert)`
    border-radius: 5px;
    margin: 5px 0;
    padding: 10px;
`;

export const StyledModal = styled(Modal)`
    .ant-modal-header {
        border-bottom: none;
        padding: 0;
        margin: 0;
    }
    .ant-modal-content {
        padding: 15px;
        border-radius: 8px;
    }
    .table-striped {
        background-color: rgba(0,0,0,.05) !important;
    }
    .ant-modal-body {

    }
    .ant-modal-footer {
        text-align: start;
    }
`;

export const ViewOrderButton = styled(Button)`
    padding: 0;
    color: #004ec2 !important;
    font-size: 16px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
`;