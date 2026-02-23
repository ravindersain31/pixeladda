import { Button, Card, Col, Radio, Row, Tooltip } from "antd";
import styled from "styled-components";
import RadioButton from "@orderBlankSign/components/Radio/RadioButton";

export const StyledRadioGroup = styled(Radio.Group)`
  display: block;
  @media screen and (max-width: 480px) {
    justify-content: center;
  }
`;

export const StyledRadioButton = styled(RadioButton)`
  width: 100%;
  margin: 0;
`;

export const StyledCard = styled(Card)`
  text-align: center;

  .ant-card-body {
    padding: 0;
  }

  @media (max-width: 480px) {
    border: 0;
  }

  button {
    position: absolute;
    right: 5px;
    height: auto;
    border: none;
    padding: 0;
    box-shadow: none;
    width: 16px !important;
    min-width: 16px !important;

    .anticon-question-circle{
      @media screen and (max-width: 1570px) {
        font-size: 14px !important;
      }
      @media screen and (max-width: 480px) {
        font-size: 11px !important;
      }
    }
  }
`;

export const Date = styled.div`
  font-size: 38px;
  font-weight: 500;

  @media screen and (max-width: 1500px) {
    font-size: 30px;
  }
`;

export const MonthYear = styled.div`
  font-size: 14px;
  font-weight: 500;
  color: #818181;

  @media screen and (max-width: 1500px) {
    font-size: 11px;
  }
  @media screen and (max-width: 767px) {
    font-size: 13px;
  }
`;

export const DeliveryCost = styled.div`
  margin-top: 5px;
  border-top: 1px solid #d8d8d8;
  color: var(--primary-color);
  padding: 5px 0 5px;
  font-size: 13px;
  font-weight: 600;

  .free-shipping {
    color: #007704;
    text-transform: uppercase;
  }

  .discount-shipping{
    font-size: 12px;
    @media screen and (max-width: 1570px) {
      font-size: 10px;
    }
    @media screen and (max-width: 480px) {
      font-size: 10px;
    }
    @media screen and (max-width: 400px) {
      font-size: 9px;
      margin-right: 11px;
    }
  }
`;

export const AlertMessage = styled.div`
  background: var(--background-color_1);
  padding: 10px;
  border-radius: 5px;
  font-size: 16px;
  text-align: center;
  font-weight: 500;
  color: var(--primary-color);
  width: 100%;
  margin: 5px 0;
`;

export const NoteMessage = styled.div`
  border-color: #d8d8d8;
  background: #eff2f8;
  padding: 15px;
  font-size: 12px;
  text-align: center;
  color: rgba(0, 0, 0, 0.85);
  line-height: 1.5;
  border-radius: 2px;
  margin: 5px 0;
`;

export const ShippingMethod = styled.div`
  color: var(--primary-color);
  text-align: center;
  padding: 0;
  font-size: 11px;
  font-weight: 600;
  .active {
    &::before {
      display: flex !important;
      content: "";
      top: 0;
      left: 0;
      z-index: 99;
      position: absolute;
      width: 0;
      height: 0;
      border-style: solid;
      border-width: 18px 18px 0 0;
      border-color: var(--primary-color) transparent;
      background: none;
    }
  }

  .disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
  }
`;

export const StyledButton = styled(Button)<{ $disabled: boolean }>`
  border: ${({ $disabled }) =>
    $disabled
      ? "1px solid transparent !important"
    : "1px solid var(--primary-color) !important"};
  height: auto;
  outline: 0;
  padding: 0px 18px;
  font-size: 11px;
  box-shadow: none;
  color: ${({ $disabled }) => $disabled ? "rgb(13, 110, 253)" : ""};

  .checkmark {
    display: block;
  }

  &:focus {
    outline: 0;
  }

  button {
    right: -7px;
    bottom: -7px;
    position: absolute;
    border: 0;
    background: transparent;
    .anticon {
      font-size: 11px !important;
    }
  }
`;

export const StyledCheckmark = styled.div`
  position: absolute;
  width: 24px;
  top: -2px;
  z-index: 99;
  left: -6px;
  display: none;
`;

export const StyledTooltip = styled(Tooltip)``;

export const StyledRow = styled(Row)`
  align-items: center;
  justify-content: center;
  display: flex;
  padding: 0;
  margin: 0;
  @media (max-width: 480px) {
    justify-content: space-evenly;
  }
`;

export const StyledCol = styled(Col)`
  @media (max-width: 480px) {
    width: auto !important;
    flex: 0 0 auto !important;
    max-width: initial !important;
  }
`;