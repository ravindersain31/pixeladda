import styled from "styled-components";
import { Row, Col, Button, Input, Modal } from "antd";
import YSPButton from "@react/editor/components/Button";

export const SaveYourDesignWrapper = styled.div`
  display: flex;
  flex-direction: column;

  h5 {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 5px;
    text-align: center;
    font-size: 14px;
  }
`;

export const DeliveryNote = styled.div`
  font-family: "Montserrat" !important;
  border-color: rgb(216, 216, 216);
  background: rgb(239, 242, 248);
  border-style: dashed;
  font-size: 12px;
  text-align: center;
  padding: 10px;
  border-width: 1px;
  margin: 5px -5px;
 


  @media (max-width: 480px) {
    margin: 10px 0;
    border-radius: 5px;
  }
  .order-page {
    color: #1677ff;
    cursor: pointer;
  }
  .live-chat{
    white-space: nowrap;
    color: #1677ff;
    cursor: pointer;
  }
  a {
    color: #1677ff;
  }
  span {
    color: #1677ff;
    cursor: pointer;
  }
`;

export const TotalAmount = styled.div`
  font-size: 14px;
  text-align: center;
  margin-top: 10px;
`;

export const IncludedList = styled(Row)`
`;

export const IncludedItem = styled(Col)`
  display: flex;
  align-items: center;
  font-size: 11px;
  font-weight: 500;

  img {
    width: 9px;
    margin-top: -1px;
    margin-right: 7px;
    height: auto;
    max-width: 100%;
    display: inline-block;
    vertical-align: top;
  }
`;

export const quoteButtonBase = `
  font-family: "Montserrat" !important;
  display: flex;
  justify-content: center;
  padding: 4px 15px;
  margin-bottom: 10px;
  font-size: 14px !important;
  align-self: center;
  margin-bottom: 5px;
  border-radius: 4px;
  border: solid 1px var(--primary-color);
  background: #fff;
  color: #000;
  font-weight: 500;
  &:hover,
  &:active {
    background: var(--primary-color)!important;
    color: #fff !important;
  }

  @media (max-width: 480px) {
    font-size: 11px !important;
  }
`;

export const QuoteActionsWrapper = styled.div`
    display: flex;
    justify-content: center;
    gap: 5px;

    @media (max-width: 480px) {
        gap: 5px;
        flex-wrap: wrap;
        width: 100%;
    }
`;

export const SaveYourDesignButton = styled.button`${quoteButtonBase}`;
export const EmailQuoteButton = styled.button`${quoteButtonBase}`;
export const DownloadQuoteButton = styled.button`${quoteButtonBase}`;
export const ContactUsButton = styled.button`${quoteButtonBase}`;
export const NewQuoteButton = styled.button`${quoteButtonBase}`;
export const OrderNowButton = styled.button`${quoteButtonBase}`;

export const OrderDesignRow = styled(Row)`
    display: flex;
    justify-content: center;
    margin-top: 10px;
    gap: 10px;

    @media (max-width: 480px) {
        gap: 5px;
        flex-wrap: wrap;
        width: 100%;
    }
`;

export const ContactUsIncludedEveryPurchaseRow = styled(Row)`
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 6px;

    .included-every-purchase{
      margin-bottom: 0px;
      @media (max-width: 480px) {
        font-size: 11px !important;
      }
    }
    @media (max-width: 480px) {
        gap: 5px;
        flex-wrap: wrap;
        width: 100%;
    }
`;

export const StyledModal = styled(Modal)`
  .ant-form-item {
    margin-bottom: 10px;
  }

  a {
    color: rgba(var(--bs-link-color-rgb), var(--bs-link-opacity, 1));
    text-decoration: none !important;
  }
`;

export const EmailLabel = styled.label`
`;

export const EmailInput = styled(Input)`
`;

export const SaveButton = styled(YSPButton)`
    font-family: "Montserrat" !important;
    box-shadow: 0 2px 0 rgba(55, 13, 81, 0.19);
    border-color: var(--primary-color);
    background: #fff;
    color: #000 !important;
    font-weight: 500;
    
    &:hover,
    &:active {
      background: var(--primary-color)!important;
      color: #fff !important;
    }
    @media (max-width: 480px) {
      border-radius: 4px;
    }
    
    &:disabled {
      background-color: var(--primary-color) !important;
      opacity: 0.65;
    }
`;

export const IncludedEveryPurchaseMobile = styled.div`

`;