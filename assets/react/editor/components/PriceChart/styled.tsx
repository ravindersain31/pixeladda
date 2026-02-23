import { Table } from "antd";
import styled from "styled-components";

export const StyledTable = styled(Table)`
    padding-bottom: 3px;
    background: #f3f3f7;

    table{
        border-collapse: collapse;
    }

    @media (max-width: 480px) {
        padding: 5px;
    }

    .ant-table-container {
        border-top: 1px solid #d9d9d9;
        border-left: 1px solid #d9d9d9;
        border-right: 1px solid #d9d9d9;
        font-size: 13px;
    }

    .ant-table-body {
        overflow: auto !important;
    }

    thead {
        tr {
            th {
                padding: 3px 8px !important;
                text-align: center !important;

                &:before {
                    display: none;
                }

                &.ant-table-cell {
                    border-right: 1px solid #d9d9d9;
                    background: #e9eff1;
                }
            }
        }
    }

    tbody {
        tr {
            &.ant-table-row {
                td {
                    border-right: 1px solid #d9d9d9;
                    padding: 0 !important;
                    text-align: center;

                    &:first-child {
                        background: #e9eff1;
                    }

                    &:last-child {
                        border-right: none;
                    }
                }


                &:hover {
                    td {
                        &.ant-table-cell-row-hover {
                            background: none;
                        }

                        &:first-child {
                            background: #e9eff1;
                        }
                    }
                }
            }
        }
    }

    @media (max-width: 660px) {
        .ant-table-header, .ant-table-body {
            .first-col {
                min-width: 75px;
            }
        }
    }


`;

export const ValueContainer = styled.div`
    padding: 3px 8px;

  &.active {
    background: var(--primary-color);
    color: #fff;
  }
`;

export const BulkCol = styled.div`
    padding: 0 8px;
`;