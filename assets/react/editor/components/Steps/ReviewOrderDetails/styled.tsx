import styled from "styled-components";
import {Card, Collapse, Popover, Button as AntdButton} from 'antd';
import Button from "@react/editor/components/Button";

export const StyledCard = styled(Card)`
  .ant-card-body {
    padding: 0;
  }
`;

export const TotalAmountContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  margin: 20px 0;

  h2 {
    font-size: 20px;
    font-weight: 400;
    color: #000;
  }

  h3 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 0;
    color: var(--primary-color);
  }

  @media (max-width: 480px) {
    h2 {
      font-size: 16px;
    }

    h3 {
      font-size: 20px;
    }
  }
`;

export const StyledCollapse: any = styled(Collapse)`
  .ant-collapse-item {
    .ant-collapse-header {
      padding: 3px 8px;
      font-size: 12px;

      .ant-collapse-expand-icon {
        height: 18px;

        svg {
          font-size: 11px;
        }
      }
    }
    .ant-collapse-content-box {
      padding: 0;
    }
  }

  @media (max-width: 480px) {
    .ant-collapse-item {
      .ant-collapse-header {
        font-size: 10px;
      }
    }
  }
`;

export const TableContainer = styled.div`

  table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;

    thead {
      tr {
        &.mobile-only {
          display: none;
        }

        &.desktop-only {
          display: table-row;
        }

        th {
          font-size: 15px;
          font-weight: 600;
          background: #e9eff1;
          padding: 15px 10px;
        }
      }
    }

    tbody {
      tr {
        td {
          padding: 3px 8px;
          font-size: 12px;
          color: #000000d9;
          background: #f5f5f5;
        }
      }
    }
  }

  @media (max-width: 480px) {
    table {
      thead {
        tr {
          &.mobile-only {
            display: table-row;
          }

          &.desktop-only {
            display: none;
          }

          th {
            padding: 5px 10px;

            >span {
              display: block;
            }

            small {
              font-size: 12px !important;
            }
          ;
          }
        }
      }

      tbody {
        tr {
          td {
            font-size: 10px;
          }
        }
      }
    }
  }
`;

export const AddToCartContainer = styled.div`
  padding: 30px;
  text-align: center;
  background: #393847;
  border-bottom-left-radius: 5px;
  border-bottom-right-radius: 5px;
`;

export const AddToCartButton = styled(Button)`
  display: inline-flex;
  justify-content: center;
  align-items: center;
  text-transform: uppercase;
  padding: 20px 50px !important;
  background-color: #44ad0f !important;
  border: none !important;
  box-shadow: #000 1px 1px 1px;
  font-weight: 500;
  font-size: 13px;
  border-radius: 5px;
  width: auto;
  height: auto !important;

  &:disabled {
    background-color: #41682d !important;
    color: #b2b2b2 !important;
  }
  @media (max-width: 768px) {
    padding: 10px 40px !important;
  }
`;

export const StyledPopover: any = styled(Popover)`

`;

export const PopoverContent = styled.div`
  font-family: "Montserrat", sans-serif;

  @media (max-width: 860px) {
    font-size: 13px;
  }
  @media (max-width: 480px) {
    font-size: 11px;
  }
`;

export const HelpButton = styled(AntdButton)`
  min-width: auto !important;
  width: auto !important;
  height: auto;
  border: none;
  margin-left: 5px;
  padding: 0 !important;
  padding-top: 2px !important;
  line-height: 11px !important;
`;
