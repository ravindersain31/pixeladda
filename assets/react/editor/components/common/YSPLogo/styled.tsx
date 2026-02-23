import { Button, Popover } from "antd";
import styled from "styled-components";

export const YSPLogoWrapper = styled.div`
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
      border-width: 21px 19px 0 0;
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

export const StyledButton = styled(Button) <{ $disabled: boolean }>`
  border: ${({ $disabled }) =>
    $disabled
      ? "1px solid transparent !important"
      : "1px solid var(--primary-color) !important"};
  height: auto;
  outline: 0;
  padding: 0px 18px;
  font-size: 13px;
  box-shadow: none;
  color: ${({ $disabled }) => $disabled ? "rgb(13, 110, 253)" : ""};
  background: initial;

  .checkmark {
    display: block;
  }

  &:focus {
    outline: 0;
  }

  button {
    right: 0;
    position: absolute;
    border: 0;
    background: transparent;
    height: 22px !important;
    bottom: 0;
    padding: 0 !important;
    width: 17px !important;
    min-width: 17px !important;
    .anticon {
      font-size: 13px !important;
    }

    @media screen and (max-width: 480px) {
      height: 21px !important;
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

export const StyledPopover: any = styled(Popover)`

`;

export const PopoverContent = styled.div`
  font-family: "Montserrat", sans-serif;
  max-width: 315px;

  button {
    padding: 0 !important;
    height: auto !important;

    @media (max-width: 480px) {
      font-size: 11px !important;
    }
  }

  @media (max-width: 860px) {
    font-size: 13px;
  }
  @media (max-width: 480px) {
    font-size: 11px;
  }
`;