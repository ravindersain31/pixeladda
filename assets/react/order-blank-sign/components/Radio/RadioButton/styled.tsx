import {Radio} from "antd";
import styled from "styled-components";

export const Wrapper = styled.div`
  padding: 5px;
  height: 100%;
`;

export const StyledRadioButton = styled(Radio.Button)`
  height: 100%;
  width: 100%;
  padding: 0;
  //margin: 10px;
  border: 2px solid #d9d9d9 !important;
  border-radius: 5px !important;

  &:before {
    display: none !important;
  }

  &:hover {
    &:before {
      background: none !important;
    }
  }

  &.ant-radio-button-wrapper {
      &:hover {
          border-color: var(--primary-color) !important;
      }
  }

  &.ant-radio-button-wrapper-checked {
    border-color: var(--primary-color) !important;

    .checkmark {
      display: block!important;
    }
    
    &:before {
      display: flex !important;
      content: '';
      top: 0;
      left: 0;
      z-index: 99;
      position: absolute;
      width: 0;
      height: 0;
      border-style: solid;
      border-width: 36px 36px 0 0;
      border-color: var(--primary-color) transparent transparent;
      background: none;
    }
  }
`;

export const Checkmark = styled.div`
  position: absolute;
  width: 30px;
  top: -4px;
  z-index: 99;
  left: 3px;
  display: none;
`;