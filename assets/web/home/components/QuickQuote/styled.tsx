import styled from 'styled-components';
import { Button, Collapse, Form, InputNumber, Modal, Radio } from 'antd';

export const StyledModal = styled(Modal)`
    font-family: "Montserrat" !important;

    .ant-modal-title{
        align-items: center;
        display: flex;
        justify-content: space-between;

        .ant-typography{
            margin: 0;
            font-weight: 600;

            @media screen and (max-width: 480px) {
                font-size: 17px;
            }
            @media screen and (max-width: 365px) {
                font-size: 15px;
            }
        }
    }

    .ant-modal-content {
        padding: 15px 20px;
    }

    .ant-modal-close{
        top: 5px;
        inset-inline-end: 6px;
    }

    .note-ribbon{
        margin: 5px 0px !important;
    }

    .ant-collapse-item{
        border-bottom: 0.5px solid #d9d9d9;
    }

    #frame .addon-name{
        font-size: 10px !important;
        @media screen and (max-width: 375px) {
            font-size: 7px !important;
        }
    }

    #grommetColor {
        @media screen and (max-width: 767px) {
            justify-content: flex-start;
        }
    }

    .ant-collapse-header{
        padding-left: 0 !important;

        .ant-collapse-header-text{
            display: inline-block;

            span{
                color: #fff;
                padding: 14px;
            }
            p{
                display: inline;
                padding-left: 10px;
            }

            @media screen and (max-width: 480px) {
                font-size: 11px;

                span{
                    padding: 10px;
                }
            }
            @media screen and (max-width: 370px) {
                font-size: 9px;
            }
        }
        @media screen and (max-width: 480px) {
            padding: 9px 5px 3px 0px !important;
        }
        @media screen and (max-width: 370px) {
            padding: 9px 5px 0px 0px !important;
        }
    }

    .save-wrapper {
        button {
            @media screen and (max-width: 480px) {
                font-size: 13px;
                height: auto;
            }
        }
    }

    .ant-select, .ant-collapse, .ant-form-item-label, .ant-ribbon-text, .modal-title {
        font-family: "Montserrat" !important;
        font-weight: 500;
    }
    .ant-ribbon-wrapper{
        .ant-card-body{
            .addon-image{
                height: 100px;

                @media screen and (max-width: 480px) {
                    height: 70px;
                }
            }
        }
    }

    .inputSize{
        button{
            &:focus-visible{
                outline: none;
            }
        }
        .ant-card-body{
            padding: 0;
            margin-bottom: 5px;

            .ant-row {
                font-family: "Montserrat" !important;
                background-color: #f6f6f6;
                column-gap: 8px;

                @media screen and (max-width: 480px){
                    column-gap: 0;
                }

                .title{
                    position:absolute;
                    font-size:12px;
                    font-weight: 500;
                    display: block;
                    @media screen and (max-width: 767px) {
                        position: initial;
                        font-size: 10px;
                    }
                    @media screen and (max-width: 300px) {
                        font-size: 8px;
                    }
                }

                .buy-more{
                    display: block;
                    position:absolute;
                    font-size:12px;
                    font-weight: 500;
                    @media screen and (max-width: 767px) {
                        display: none;
                    }
                }
                .bulk-discounts {
                    position:absolute;
                    font-weight: 500;
                    display: none;
                    @media screen and (max-width: 767px) {
                        display: block;
                        font-size: 10px;
                        position: initial;
                    }
                    @media screen and (max-width: 400px) {
                        font-size: 9px;
                    }
                    @media screen and (max-width: 300px) {
                        font-size: 8px;
                    }
                }
                .pricing {
                    @media screen and (max-width: 767px) {
                        font-size: 21px;
                    }
                }
            }
        }
    }
`;

export const StyledSaveDesignModal = styled(Modal)`
    font-family: "Montserrat" !important;

    label, input, button, .ant-form-item-explain-error {
        font-family: "Montserrat" !important;
    }
`;

export const StyledInputNumber = styled(InputNumber)`
    font-family: "Montserrat" !important;
    width: 100%;
`;

export const StyledRadioGroup = styled(Radio.Group)`
    font-family: "Montserrat" !important;
    display: flex;
    justify-content: center;

    .ant-card-body{
        font-family: "Montserrat" !important;
        button{
            bottom: 5px;
            &:focus-visible{
                outline: none;
            }
            @media screen and (max-width: 480px) {
                font-size: 8px;
                right: 2px;
                .anticon-question-circle{
                    font-size: 12px;
                }
            }
        }
        .addon-name{
            width: 70%;
            font-size: 11px;
            @media screen and (max-width: 1000px) {
                font-size: 9px;
            }
            @media screen and (max-width: 435px) {
                font-size: 8px;
            }
            @media screen and (max-width: 375px) {
                font-size: 7px;
            }
        }
    }
`;

export const FormItem = styled(Form.Item)`
    margin-bottom: 0;
`;

export const StyledModalTitle = styled.div`
    font-family: "Montserrat" !important;
    font-size: 24px;
    color: #333;
    text-align: center;
    margin-bottom: 20px;
`;

export const StyledModalButton = styled(Button)`
    font-family: "Montserrat";
    background-color: transparent !important;
    padding: 0;
    outline: none;
    transition: none;
    height: auto;
    box-shadow: none;
    font-size: 15px;
    border: none;

    @media (min-width: 1200px) and (max-width: 1350px) {
        font-size: 13px !important;
    }

    &:hover{
        color: var(--primary-color) !important;
        background-color: transparent !important;
    }
    &:active{
        color: var(--primary-color) !important;
        background-color: transparent !important;
    }

    @media screen and (max-width: 560px) {
        font-size: 12px !important;
    }
`;

export const StyledButton = styled(Button)`
    font-family: "Montserrat" !important;
    font-weight: 500;
    background-color: var(--primary-color);
    color: #fff;
    margin: auto;
    margin-bottom: 5px;
    margin-top: 10px;
    width: 35%;
    display: block;

    @media screen and (max-width: 480px) {
        width: 50%;
        margin-bottom: 0;
    }

    &:hover {
        background-color: #65399e !important;
        color: #fff !important;
    }
`;

export const ItemImage = styled.img`
    width: 24px;
    height: 24px;
    margin-right: 10px;
`;

export const ItemText = styled.span`
    font-family: "Montserrat" !important;
    font-size: 14px;
    color: #333;
`;

export const AlertMessage = styled.div`
    font-family: "Montserrat" !important;
    background: var(--background-color_1);
    padding: 10px;
    border-radius: 5px;
    font-size: 14px;
    text-align: center;
    font-weight: 500;
    color: var(--primary-color);
    width: 100%;
    margin: 5px 0;
    font-family: "Mont"
`;