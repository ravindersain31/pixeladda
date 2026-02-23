import {Button, Switch} from "antd";
import styled from "styled-components";
import {ButtonProps} from "antd/lib";

const StyledButton = styled(Button)<ButtonProps>`
  ${({type}) => type === 'primary' && `border-color: var(--primary-color);`}
  ${({type}) => type === 'primary' && `background-color: var(--primary-color);`}

  color: #fff !important;
  box-shadow: 1px 1px 3px #8c8c8c;
  font-size: 15px !important;
  font-weight: 500;
  letter-spacing: 0.5px;

  ${({size}) => size === 'large' && `height: 45px!important;`}  
`;

export const CheckboxButton = styled(Switch)`
  margin: 2px;
  border-radius: 5px;
  height: auto;
  min-width: 40px;
  min-height: auto;
  padding: 6px;
  background: #fff !important;
  border: 1px solid var(--primary-color);

  &:hover {
    background: #fcfcfc !important;
  }

  .ant-switch-handle {
    display: none;
  }

  .ant-switch-inner {
    padding: 0 !important;
    .ant-switch-inner-checked{
      svg{
        display: none;
      }
    }
    svg {
      font-size: 17px;
      top: 3px;
      position: relative;
      color: #000;
      &:hover,
      &:active {
        background: var(--primary-color);
        color: #fff;
      }
    }

    .ant-switch-inner-checked,
    .ant-switch-inner-unchecked {
      margin-inline-start: 0 !important;
      margin-inline-end: 0 !important;
    }
  }

  &.ant-switch-checked {
    background: var(--primary-color) !important;

    .ant-switch-inner {
      svg {
        color: #fff;
      }
    }
  }
`;

export default StyledButton;

