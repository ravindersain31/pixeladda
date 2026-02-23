import styled from 'styled-components'
import {
    Tabs as StyledTabs,
} from 'antd';
import {TabsProps} from "antd/lib";

export const Tabs = styled(StyledTabs)<TabsProps>`
    font-family: "Montserrat", serif;
    line-height: 1.5715;
    font-variant: tabular-nums;
    font-feature-settings: "tnum";
    position: relative;

    .ant-tabs-nav {
        margin: 0;

        &::before {
            border-width: 3px;
            border-color: #f3f3f7;
        }

        .ant-tabs-nav-wrap {
            padding: 0 !important;

            .ant-tabs-tab {
                background: #fff;
                border-color: #f3f3f7;
                border-width: 3px;
                border-radius: 5px 5px 0 0;
                margin-right: 2px;

                .ant-tabs-tab-btn {
                    color: #000;
                }

                &.ant-tabs-tab-active {
                    background: #f3f3f7;

                    .ant-tabs-tab-btn {
                        color: #000;
                        font-weight: 500;
                    }
                }
            }
        }
    }

    .ant-tabs-content-holder {
        background: #f3f3f7;
        padding: 10px;
        @media (max-width: 480px) {
            padding: 5px;
        }

        .ant-tabs-content {
            position: inherit;
        }
    }

    @media (max-width: 860px) {
        .ant-tabs-nav-wrap {
            &::before {
                content: "<" !important;
                color: #000;
                text-align: center;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 23px;
                background: #fff;
                box-shadow: 5px 0 10px 10px rgba(0, 0, 0, 0.08) !important;
            }

            &::after {
                content: ">" !important;
                color: #000;
                text-align: center;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 23px;
                background: #fff;
                box-shadow: -10px 0 10px 5px rgba(0, 0, 0, 0.08) !important;
            }
        }
    }

    @media (max-width: 767px) {
        .ant-tabs-nav {
            &::before {
                border-width: 1px;
            }

            .ant-tabs-nav-wrap {
                .ant-tabs-tab {
                    border-width: 2px;
                    padding: 5px 10px;
                    font-size: 12px;
                }
            }
        }
    }

    .help-artwork-tab,
    .email-artwork-tab {
        .ant-card-body {
            padding: 0;
        }
    }
`;

export const TabPane = styled.div`

`;