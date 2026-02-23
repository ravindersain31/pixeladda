import { isMobile } from "react-device-detect";
import styled from "styled-components";

export const ShareDesignWrapper = styled.div`
    position: absolute;
    top: 0px;
    right: 0px;
    font-size: 18px;
    background-color: transparent !important;
    z-index: 5;
    .ant-btn-primary {
        background: none !important;
        color: inherit !important;
        box-shadow: none !important;
        border: none !important;
    }

    @media (max-width: 768px) {
        top: 10px;
        right: auto;
        display: flex;
        margin: 0px 90px 0;
        font-size: 16px !important;
    }

    @media (max-width: 480px) {
        top: 10px;
        font-size: 14px !important;
    }
`;