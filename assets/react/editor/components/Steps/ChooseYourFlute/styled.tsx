import { Badge, BadgeProps, Button } from "antd";
import styled from "styled-components";

export const Overlay = styled.div`
  height: 100%;
  width: 100%;
  position: absolute;
  top: 50%;
  left: 50%;
  border-radius: 5px;
  transform: translate(-50%, -50%);
  background-color: rgb(0 0 0 / 50%);
  color: white;
  padding: 10px;
  pointer-events: none;
  text-align: center;
  align-content: center;
`;

export const StyledRibbon = styled(Badge.Ribbon)<BadgeProps>`
  font-size: 10px;
  line-height: 1.8;
  z-index: 1;
  top: ${({ title }) => (title ? "38px" : "0px")};
  color: white;
  background-color: red;

  button {
    border: 0;
    padding: 0;
    margin: 0;
    background: transparent;
    min-width: 12px !important;
    height: 12px;
    line-height: 1;
    width: 14px !important;
    min-width: 14px !important;
    height: 14px;
    margin: 0;
    margin-left: 2px;
    svg {
      font-size: 12px !important;
      color: white !important;
    }
  }
`;

export const StyledButton = styled(Button)`
  border: 0;
  height: auto;
  outline: 0;
  padding: 0px 18px;
  font-size: 11px;
  box-shadow: none;
  color: rgb(13, 110, 253);

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

export const InputStyled = styled.div`
  display: flex ;  
  align-items: center;
  gap: 8px;
  justify-content: center;
  padding: 0 10px;

  .anticon-question-circle {
    font-size: 20px;
    color: #555;
    margin-bottom: 12px
  }
`;

export const StyledPopoverButton = styled(Button)`
  border: 0;
  height: auto;
  outline: 0;
  padding: 0px 18px;
  font-size: 12px;
  box-shadow: none;
  color: rgb(13, 110, 253);

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

  @media screen and (max-width: 480px) {
    font-size: 11px;
  }
`;