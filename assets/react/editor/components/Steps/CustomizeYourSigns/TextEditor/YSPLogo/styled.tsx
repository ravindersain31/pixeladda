import { Button, Popover } from "antd";
import styled from "styled-components";

export const YSPLogoButton = styled(Button)`
    border: 1px solid var(--primary-color) !important;
    background: #fff;
    color: #000;
    transition: none;
    font-size: 10px;
    padding: 0 7px;
    width: fit-content;
    margin: 7px;
    margin-bottom: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2px;

    @media screen and (max-width: 767px){
      margin: 2px 5px;
    }

    &:hover{
      background-color: var(--primary-color) !important;

      button {
        background: var(--primary-color) !important;
        color: #fff !important;
      }
    }
`;

export const StyledPopover: any = styled(Popover)`
  outline: none;
  border: none;
  min-width: 18px !important;
  width: 18px !important;
  height: 16px;
  font-size: 12px;
  padding: 0;
  background: #fff !important;

  .anticon-question-circle {
    font-size: 14px !important;
  }

  &:hover{
    background: var(--primary-color) !important;
    color: #fff !important;
  }
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