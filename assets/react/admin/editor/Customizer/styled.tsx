import {Radio} from "antd";
import styled from "styled-components";
import {RadioButtonProps} from "antd/lib/radio/radioButton";

export const RadioGroup = styled(Radio.Group)`
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 20px 0;
`;

export const RadioButton = styled(Radio.Button)<RadioButtonProps>`
  height: 45px!important;
  font-size: 15px !important;
  border: 2px solid var(--primary-color)!important;
  letter-spacing: 0.5px;
  margin: 0 10px !important;
  border-radius: 5px !important;
  box-shadow: 1px 1px 3px #8c8c8c;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  color: var(--primary-color)!important;
  
  &:before {
    display: none !important;
  }
  
  &.ant-radio-button-wrapper-checked {
    background-color: var(--primary-color)!important;
    border-color: var(--primary-color)!important;
    color: #FFF!important;
  }

`;

export const TabContent = styled.div`
    
`;
