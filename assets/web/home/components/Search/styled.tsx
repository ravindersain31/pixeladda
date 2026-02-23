import { Button, Card, Collapse, Row } from "antd";
import { CollapseProps } from "antd/lib";
import styled from "styled-components";

export const CustomSearchWrapper = styled.div`
  .ant-card-bordered {
    border: 0 !important;
    border-radius: 0!important;
    height: 100%;
  }
  .price-tag {
    transform: rotateZ(90deg);
    margin-right: 2px;
  }
`;

export const LowestPricesGuaranteed = styled(Row)`
  color: var(--primary-color);
  font-size: 25px;
  font-weight: 800;
  font-family: "Montserrat", serif;
  font-style: italic;
  width: 100%;
  text-align: center;
  text-transform: UPPERCASE;

  @media (max-width: 768px) {
    font-size: 22px;
  }

  @media (max-width: 480px) {
    font-size: 20px;
  }
`;

export const StyledCard = styled(Card)`
  .ant-form{
    background: #ededed;
  }

  .ant-card-body {
    padding: 0 10%;
    .ant-row {
      margin: 0 !important;
      background: #ededed;
      /* padding: 10px 0; */
      .ant-input-number,
      .ant-input-number-affix-wrapper {
        width: 100%;
      }
      .ant-input-number-suffix {
        pointer-events: all !important;
        .ant-form-item-feedback-icon {
          display: none !important;
        }
      }
      .ant-input-number-prefix {
      }
      .ant-input-number-input {
        font-size: 16px;
      }
      border-radius: 5px;
    }
    .ant-col {
      .input-field {
        position: relative;
      }
    }
    .ant-form-item {
      margin: 0 !important;
    }
    .ant-form-item-control-input {
      .ant-btn-circle {
        font-size: 10px !important;
      }
    }
    @media screen and (max-width: 767px) {
      .multiply{
        margin-bottom: 15px;
      }
    }
    .buy-more{
      position:absolute;
      font-size:12px;
      font-weight: 500;
      @media screen and (max-width: 1023px) {
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
        font-size: 10px;
      }
      @media screen and (max-width: 300px) {
        font-size: 8px;
      }
    }
    
    @media screen and (max-width: 768px) {
      svg {
        font-size: 14px;
        pointer-events: none !important;
      }
    }
  }
  .ant-input-number,
  .ant-input-number-affix-wrapper {
    border-color: var(--primary-color);
    &:hover {
      border-color: var(--primary-color) !important;
    }
    &:focus {
      border-color: var(--primary-color) !important;
    }
    &:active {
      border-color: var(--primary-color) !important;
    }
    &:focus-within {
      border-color: var(--primary-color) !important;
    }
  }
  .quantity-input {
    background-color: var(--background-color_1);
  }
  .price {
    text-align: center;
    color: var(--primary-color);
    span {
      font-size: 0.9rem;
      margin: 0 !important;
      font-weight: 500;
      line-height: 1.2;
    }
    .pricing {
      margin: 0;
      font-size: 1.75rem;
      display: block;
    }
    @media screen and (max-width: 1200px) {
      .pricing{
        font-size: calc(1.3rem + .6vw);
      }
    }
    @media screen and (max-width: 767px) {
      span{
        display: inline-block;
        padding-right:4px;
        font-size: 16px;
      }
      .pricing{
        display: inline-block;
      }
    }
  }

  @media screen and (max-width: 768px) {
    .ant-input-number-affix-wrapper {
      padding-inline-start: 6px;
    }
    .ant-card-body {
      padding: 0 8px;
      .ant-row {
        .ant-input-number-input {
          font-size: 14px;
        }
      }
      .ant-col {
        margin: 0 !important;
        .quantity {
          padding-top: 0;
        }
      }
      .ant-form-item-control-input {
        text-align: center;
      }
    }
  }

  @media screen and (max-width: 480px) {
    .ant-card-body {
      .ant-form-item-control-input {
        .ant-btn-circle {
          font-size: 8px !important;
        }
      }
    }
  }
`;

export const Title = styled.span`
  position:absolute;
  font-size:12px;
  font-weight: 500;
  @media screen and (max-width: 767px) {
    display: block;
    position: initial;
    font-size: 10px;
  }
  @media screen and (max-width: 300px) {
    font-size: 8px;
  }
`;

export const OrderButton = styled(Button)`
  display: inline-flex;
  justify-content: center;
  align-items: center;
  text-transform: uppercase;
  background-color: #fff;
  color: #1c202b !important;
  border: 1px solid var(--primary-color) !important;
  font-weight: 500;
  font-size: 18px;
  border-radius: 5px;
  width: 100%;
  height: auto !important;

  &:hover {
    background-color: var(--primary-color) !important;
    color: #fff !important;
  }
  @media (max-width: 768px) {
    font-size: 15px;
    padding: 7px 40px !important;
    width: 50% !important;
  }
`;

export const PopoverContent = styled.div`
  color: white;
`;

export const QuestionButton = styled(Button)`
  position: absolute;
  background: #ededed;
  border: none;
  width: 16px !important;
  min-width: 16px !important;
  height: 16px;
  padding-top: 0;
  padding-bottom: 0;
  margin: 0 3px;
  font-size: 13px;
  &:hover{
    color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
  }
  &:focus{
    color: black;
    border-color: black;
  }
  @media (max-width: 768px) {
    width: 14px !important;
    min-width: 14px !important;
    font-size: 10px;
  }
  .anticon-question-circle {
    font-size: 14px !important;
    @media (max-width: 768px) {
      svg{
        width:0.7rem;
        height:0.7rem;
      }
    }
  }
`;

export const AccordionButton = styled(Button) <{ $isActive: boolean }>`
  font-family: "Montserrat" !important;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  padding: 10px;
  text-align: left;
  background-color: #fff;
  transition: background-color 0.3s ease;
  margin-bottom: 10px;
  color: ${({ $isActive }) => ($isActive && 'var(--primary-color)')};
  border-color:${({ $isActive }) => ($isActive && 'var(--primary-color)')};

  @media screen and (max-width: 480px) {
    margin-bottom: 7px;
  }

  p {
    margin-bottom: 0;
  }
  .anticon {
    color: var(--primary-color);
  }
`;

export const StyledCollapse = styled(Collapse) <{ $isCollapsed: boolean }>`
  font-family: "Montserrat" !important;

  .anticon {
    color: var(--primary-color) !important;
  }

  button {
    &:hover{
      color: var(--primary-color) !important;
      border-color: var(--primary-color) !important;
    }
    &:active{
      color: var(--primary-color) !important;
      border-color: var(--primary-color) !important;
    }
  }

  .ant-collapse-header{
    padding: 3px 6px !important;
    padding-bottom:  ${({ $isCollapsed }) => ($isCollapsed ? '5px' : '10px')} !important;
    color: var(--primary-color) !important;
    align-items: center !important;
    width: fit-content;

    @media screen and (max-width: 991px) {
      justify-content: center;
      margin: auto;
    }
    @media screen and (max-width: 480px) {
      padding-bottom: 3px !important;
    }

    .ant-collapse-expand-icon{
      padding-inline-start: 5px !important;
       svg {
        @media screen and (max-width: 480px) {
          font-size: 11px;
        }
       }
    }

    .ant-collapse-header-text{
      margin-inline-end: initial !important;
      flex: none !important;

      @media screen and (max-width: 480px) {
        font-size: 12px;
      }
    }

    &:hover{
      color: var(--primary-color) !important;
      background: transparent;
    }

    &:active{
      color: var(--primary-color) !important;
    }
  }

  .ant-collapse-content-box{
    padding: 0 !important;
  }

  &:focus-visible {
    outline: none !important;
  }
`;

export const StyledInnerCollapse = styled(Collapse)<CollapseProps>`
  font-family: "Montserrat" !important;
  padding-bottom: 2px;

  .ant-ribbon-text {
    font-family: "Montserrat" !important;
  }

  p {
    margin: 0;
  }

  .ant-collapse-item-active {
    .ant-collapse-header {
      margin: 0;
    }
  }

  .addon-name {
    @media screen and (max-width: 480px) {
      font-size: 9px !important;
    }
    @media screen and (max-width: 360px) {
      font-size: 8px !important;
    }
  }

  #frame .addon-name{
    @media screen and (max-width: 1600px) {
      font-size: 9px;
    }
  }

  #grommetColor {
    justify-content: flex-start;
  }

  .ant-collapse-header {
    margin: 0;
    margin-bottom: 5px;
    justify-content: space-between;
    width: 100%;
    background-color: #fff;
    border: 1px solid rgb(217, 217, 217);
    color: rgba(0, 0, 0, 0.88) !important;

    &:hover, &:focus{
      background-color: #fff;
    }
  }

  .checkmark {
    @media screen and (max-width: 768px) {
      left: -3px;
    }
    .anticon {
      color: #fff !important;
    }
  }

  .addon-image {
    height: 65px !important;
  }
`;

export const StyledAddonsCollapse = styled(Collapse)<CollapseProps>`
  font-family: "Montserrat" !important;
  width: 100%;
  margin: auto;
  text-align: center;
  transition: all 0.3s ease;
  padding-bottom: 10px;

  @media screen and (max-width: 480px) {
    padding-bottom: 3px;
  }

  .ant-collapse-header {
    display: none !important;
  }

  #frame .addon-name{
    @media screen and (max-width: 1600px) {
      font-size: 9px;
    }
    @media screen and (max-width: 1365px) {
      font-size: 8px;
    }
    @media screen and (max-width: 480px) {
      font-size: 7px !important;
    }
  }

  .checkmark .anticon {
    color: #fff !important;
  }

  .addon-image {
    height: 100px;

    @media screen and (max-width: 575px) {
      height: 70px;
    }
  }

  .checkmark {
    left: -3px;
  }

  .ant-ribbon-wrapper {
    .ant-ribbon-text{
      font-family: "Montserrat" !important;
    }
  }

  .ant-card-body {
    font-family: "Montserrat" !important;
    button{
      bottom: 2px;
    }
  }
`;

export const CustomOptionsWrapper = styled.div`
  font-family: "Montserrat" !important;
  width: 79.5%;
  margin: auto;

  @media screen and (max-width: 991px) {
    width: 75.5%;
  }
  @media screen and (max-width: 767px) {
    width: 96%;
  }
  @media screen and (max-width: 480px) {
    width: 97%;
    button {
      font-size: 12px;

      .anticon svg {
        font-size: 11px;
      }
    }
  }
`;