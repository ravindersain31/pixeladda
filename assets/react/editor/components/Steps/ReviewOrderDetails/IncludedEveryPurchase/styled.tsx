import styled from "styled-components";
import { Row, Col, Button, Modal } from "antd";

export const EveryPurchaseModal = styled.div`
  font-family: "Montserrat" !important;
  display: flex;
  justify-content: center;
  margin-bottom: 5px;
`;

export const IncludedList = styled(Row)`
  display: flex;
  flex-direction: column;
  align-content: center;
`;

export const IncludedItem = styled(Col)`
  font-family: "Montserrat" !important;
  display: flex;
  align-items: center;
  font-size: 14px;
  font-weight: 600;

  .promo-img {
    width: 20px !important;
  }
  
  img {
    width: 20px;
    margin-top: -1px;
    margin-right: 7px;
    height: auto;
    max-width: 100%;
    display: inline-block;
    vertical-align: top;
  }

  @media (min-width: 767px) {
    font-size: 16px;
    img {
      margin-right: 10px;
    }
  }
`;

export const IncludedEveryPurchaseButton = styled(Button)`
  font-family: "Montserrat" !important;
  display: flex;
  justify-content: center;
  align-self: center;
  margin-bottom: 5px;
  border-radius: 4px;
  border-color: var(--primary-color);
  background: #fff;
  color: #000;
  font-weight: 500;
  &:hover, &:active {
    color: #fff;
    background-color: var(--primary-color) !important;
  }
`;

export const StyledModal = styled(Modal)`
  .ant-modal-title, .ant-anchor-link {
    font-family: "Montserrat" !important;
    font-weight: 800;
    margin-top: 20px;
    font-size: 14px;
    line-height: 1.5715;
    word-wrap: break-word;
  }
  .ysp-rewards {
    padding-bottom: 20px;
    a {
      color: rgba(var(--bs-link-color-rgb), var(--bs-link-opacity, 1)) !important;
      font-weight: 600;
    }
  }
  .ant-form-item {
    margin-bottom: 10px;
  }
  .ant-modal-content {
    padding: 10px 0;
  }
  .ant-modal-title {
    color: var(--primary-color);
  }
  @media (min-width: 767px) {
    .ant-modal-title {
      font-size: 21px;
    }
  }
`;
