import { Button, Col, Modal, Row } from "antd";
import styled from "styled-components";

export const StyledModal = styled(Modal)`
    @media screen and (max-width: 480px) {
        top: 30px;
    }

    .ant-modal-content {
        padding: 0;

        .ant-modal-header {
            background: rgb(216, 216, 216);
            padding: 13px 24px;
            margin-bottom: 15px;

            .ant-modal-title {
                font-size: 20px;

                @media screen and (max-width: 480px) {
                    font-size: 16px;
                }
            }
        }
        .ant-modal-body{
            padding: 0px 20px 20px 24px;

            .ant-form-item {
                margin-bottom: 14px;

                label {
                    @media screen and (max-width: 480px) {
                        font-size: 12px;
                    }
                }

                input,
                textarea,
                .ant-select-selector,
                .ant-picker {
                    @media screen and (max-width: 480px) {
                    font-size: 12px;
                    }
                }

                input::placeholder,
                textarea::placeholder {
                    @media screen and (max-width: 480px) {
                    font-size: 12px;
                    }
                }
            }
        }
    }
`;
export const ContactHelpWrapper = styled.div`
    margin-top: 10px;
`;

export const HeaderText = styled.div`
    text-align: center;
    margin-bottom: 16px;

    & > p {
        margin: 0;
    }
    & > p:first-child {
        font-size: 1.25rem;
        font-weight: bold;
    }
`;

export const ContactRow = styled(Row)`
    gap: 16px;

    @media screen and (max-width: 480px) {
        flex-wrap: nowrap;
        justify-content: space-between;

        & > div {
            flex: 1;
            max-width: none;
        }
    }
`;

export const ContactCard = styled(Col)`
    flex: 1;
    max-width: 240px;
    text-align: center;
    border: 1px solid rgb(232, 232, 232);
    padding: 16px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;

    h4, p {
        margin: 0;
    }

    h4 {
        font-size: 1.3rem;
    }

    @media screen and (max-width: 480px) {
        max-width: 120px;
        padding: 5px;
        gap: 4px;
        
        h4 {
            font-size: 12px;
        }
        a, p {
            font-size: 10px;
        }

        svg, i {
            font-size: 16px;
        }
    }
`;

export const IconCircle = styled.div`
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: var(--primary-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;

    svg {
        color: white;
    }
`;

export const StyledButton = styled(Button)`
    font-family: "Montserrat" !important;
    display: flex;
    align-items: center;
    justify-content: center;
    align-self: center;
    margin-bottom: 5px;
    box-shadow: 0 2px 0 rgba(55, 13, 81, 0.19);
    border-color: var(--primary-color);
    background: #fff;
    color: #000;
    &:hover,
    &:active {
        background:var(--primary-color)!important;
        color: #fff !important;
    }

    &.bulk-live-chat {
        height: 30px;       
        font-size: 13px;
        padding: 0 16px;    
    }

    @media (max-width: 480px) {
        border-radius: 4px;
        height: auto;
        padding: 2px 9px;
        font-size: 12px;

        &.bulk-live-chat {
            height: 22px;     
            font-size: 11px;
            padding: 0 10px;
        }
    }
`;
