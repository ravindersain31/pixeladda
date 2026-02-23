import styled from "styled-components";
import {Radio} from "antd";
import {RadioButtonProps} from "antd/lib/radio/radioButton";
import Button from "@react/admin/editor/components/Button";

export const Controls = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  padding: 0 15px;
  @media (max-width: 767px) {
    padding: 0;
  }
`;


export const RadioGroup = styled(Radio.Group)`
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  margin: 0;
`;

export const LoginButton = styled(Button)`
  font-family: "Montserrat" !important;
  display: flex;
  justify-content: center;
  align-self: center;
  margin-bottom: 5px;
  box-shadow: 0 2px 0 rgba(55, 13, 81, 0.19);
  border: none;
  background: linear-gradient(90deg, #FFCA2C 0%, #FF7A00 100%);
  color: #000 !important;
  align-items: center;
  .click-to-login{
    color: #fff !important;
  }
  @media (max-width: 480px) {
    border-radius: 4px;
    font-size: 12px !important;
  }
`;

export const RadioButton = styled(Radio.Button)<RadioButtonProps>`
  height: 40px !important;
  font-size: 15px !important;
  border: 2px solid var(--primary-color) !important;
  letter-spacing: 0.5px;
  margin: 0 5px 10px !important;
  border-radius: 5px !important;
  box-shadow: 1px 1px 1px #8c8c8c;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  color: var(--primary-color) !important;
  padding-inline: 10px !important;

  span {
    display: flex;
    justify-content: center;
    flex-direction: column;
    text-align: center;
    line-height: normal;

    .variant {
      font-size: 12px;
      color: #131313;
      font-weight: 600;
    }

    .quantity {
      font-size: 10px;
      color: #9d9b9b;
      font-weight: 500;
      margin-top: 3px;
    }
  }

  &:before {
    display: none !important;
  }

  &.ant-radio-button-wrapper-checked {
    border-color: var(--primary-color) !important;
    background-color: var(--primary-color);
    span {
      .variant {
        color: #fff;
      }
      .quantity {
        color: #fff;
      }
    }
  }
`;


export const FrontBackGroup = styled(Radio.Group)`
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  margin: 0;
`;

export const PositionButton = styled(Radio.Button)<RadioButtonProps>`
  height: 35px!important;
  font-size: 13px!important;
  border: 2px solid var(--primary-color) !important;
  letter-spacing: 0.5px;
  margin: 0 5px 10px!important;
  border-radius: 5px !important;
  box-shadow: 1px 1px 1px #8c8c8c;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  color: #000 !important;

  &:before {
    display: none !important;
  }

  &.ant-radio-button-wrapper-checked {
    border-color: var(--primary-color) !important;
    background: var(--primary-color) !important;
    color: #fff !important;
  }
`;

export const CopyButton = styled(Button)`
  background-color: #FFF;
  color: #565656 !important;
  border-width: 2px;
  border-color: #d37533;
  text-transform: capitalize;
  margin-bottom: 10px;
  font-size: 13px!important;
  display: flex;
  justify-content: center;
  align-items: center;

  &:hover {
    color: #FFF !important;;
    background-color: #d37533;
    border-color: #d37533 !important;
  }
`;

export const CanvasNote = styled.div`
  text-align: left;
  border-color: rgb(216, 216, 216);
  background: rgb(239, 242, 248);
  border-style: dotted;
  border-width: 2px;
  text-transform: uppercase;
  padding: 10px 30px;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 10px;
  color: rgba(0,0,0,0.85) important;
  font-size: 12px;
  font-family: 'Quicksand', serif;
`;

