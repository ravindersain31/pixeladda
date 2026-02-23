import { Button, Switch } from "antd";
import styled from "styled-components";
import { ButtonProps } from "antd/lib";

const StyledButton = styled(Button) <ButtonProps>`
  ${({ type }) => type === 'primary' && `border-color: #704D9F;`}
  ${({ type }) => type === 'primary' && `background-color: #704D9F;`}
  ${({ type }) => type === 'default' && `color: #000!important;`}
  ${({ type }) => type !== 'default' && `color: #FFF!important;`}
  
  box-shadow: 1px 1px 3px #8c8c8c;
  font-size: 15px !important;
  font-weight: 500;
  letter-spacing: 0.5px;

  ${({ size }) => size === 'large' && `height: 45px!important;`}  
`;

export const CheckboxButton = styled(Switch)`
  margin: 5px;
  border-radius: 5px;
  height: auto;
  min-height: auto;
  padding: 8px;
  background: #fff !important;
  border: 2px solid var(--color_2);

  &:hover {
    background: #fcfcfc !important;
  }

  .ant-switch-handle {
    display: none;
  }

  .ant-switch-inner {
    padding: 0 !important;
    overflow: inherit;
    margin-top: -7px;

    svg {
      font-size: 17px;
      top: 3px;
      position: relative;
      color: var(--color_2);
    }

    .ant-switch-inner-checked,
    .ant-switch-inner-unchecked {
      margin-inline-start: 0 !important;
      margin-inline-end: 0 !important;
    }
  }

  &.ant-switch-checked {
    background: var(--color_2) !important;

    .ant-switch-inner {
      svg {
        color: #fff;
      }
    }
  }
`;

export default StyledButton;

