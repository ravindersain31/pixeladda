import styled from "styled-components";
import {Radio} from "antd";
import {RadioButtonProps} from "antd/lib/radio/radioButton";


export const RadioGroup = styled(Radio.Group)`
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 5px;
`;


export const RadioButton = styled(Radio.Button)<RadioButtonProps>`
  border: 2px solid var(--primary-color) !important;
  letter-spacing: 0.5px;
  margin: 0 5px !important;
  border-radius: 5px !important;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  color: var(--primary-color) !important;
  padding: 0.2812rem 13px;
  height: 100%;

  &:before {
    display: none !important;
  }

  &.ant-radio-button-wrapper-checked {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: #FFF !important;
  }

  &.ant-radio-button-wrapper-disabled {
    background-color: #FFF !important;
    cursor: not-allowed;
    opacity: 0.65;

  }

  svg {
    font-size: 17px;
    position: relative;
    top: 2px;
  }
`;
