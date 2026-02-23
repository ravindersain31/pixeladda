import { Button, Modal } from "antd";
import styled from "styled-components";

export const PopoverContent = styled.div`
  color: white;
`;

export const ShippingMethod = styled.div`
  color: var(--primary-color);
  text-align: center;
  padding: 0;
  font-size: 11px;
  font-weight: 600;
  display: inline-block;

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

export const StyledCheckmark = styled.div`
  position: absolute;
  width: 24px;
  top: -2px;
  z-index: 99;
  left: -6px;
  display: none;
`;

export const StyledButton = styled(Button) <{ $disabled: boolean }>`
  border: ${({ $disabled }) =>
    $disabled
      ? "1px solid transparent !important"
      : "1px solid var(--primary-color) !important"};
  height: auto;
  outline: 0;
  padding: 0px 18px;
  font-size: 11px;
  box-shadow: none;
  color: ${({ $disabled }) => $disabled ? "rgb(13, 110, 253)" : "var(--primary-color)"};
  margin-right: 1px;
  background-color: transparent;

  .checkmark {
    display: block;
  }

  &:focus {
    outline: 0;
  }

  button {
    min-width: 15px !important;
    width: 15px !important;
    right: 2px;
    bottom: -7px;
    position: absolute;
    border: 0;
    background: transparent;
    .anticon {
      font-size: 11px !important;
    }
  }
`;

export const ContentWrapper = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background-color: #ffffff;
  overflow: hidden;
`;

export const BodyWrapper = styled.div`
  padding: 10px 15px;

  h4 {
    color: var(--primary-color);
    font-style: italic;
    font-weight: 700;
    margin: 0;
    margin-top: 5px;

    @media screen and (max-width: 480px) {
      font-size: 18px;
    }
  }
  div{
    margin-bottom: 10px;
    text-align: left;

    .description{
      padding-right: 5px;
    }
    @media screen and (max-width: 640px) {
      font-size: 11px;
    }
    @media screen and (max-width: 365px) {
      font-size: 10px;
    }
    div{
      margin-bottom: 0;
      text-align: center;

      button{
        padding: 0 !important;
        height: auto !important;

        &:hover{
          color: var(--primary-color) !important;
        }
        @media screen and (max-width: 640px) {
          font-size: 11px !important;
        }
        @media screen and (max-width: 365px) {
          font-size: 10px !important;
        }
      }
      .active{
        padding-right: 5px !important;
        padding-left: 13px !important;

        &::before{
          border-width: 23px 18px 0 0 !important;

          @media screen and (max-width: 640px) {
            border-width: 19px 17px 0 0 !important;
          }
          @media screen and (max-width: 365px) {
            border-width: 16px 16px 0 0 !important;
          }
        }

      }
    }
  }
  .freight{
    margin-right: 4px;
    @media screen and (max-width: 640px) {
      font-size: 12px;
    }
    @media screen and (max-width: 365px) {
      font-size: 11px;
    }
  }
`;

export const Image = styled.img`
  width: 100%;
  height: auto;
`;

export const HeaderWrapper = styled.div`
  display: flex;
  align-items: center;
  justify-content: flex-start;
  background-color: var(--primary-color);
  padding: 7px 10px;
  width: 100%;
  position: relative;

  &::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -10px;
    height: 10px;
    background-color: #8ed5ab;
  }

  h4{
    font-style: italic;
    margin: 0;
    margin-left: 15px;
    font-weight: 700;

    @media screen and (max-width: 480px) {
      font-size: 13px;
    }
  }
`;

export const FooterWrapper = styled.div`
  background-color: var(--primary-color);
  width: 100%;

  h5 {
    color: #fff !important;
    padding: 10px;
    margin: 0;
    font-size: 15px;

    @media screen and (max-width: 480px) {
      font-size: 11px;
      padding: 7px;
    }
    @media screen and (max-width: 365px) {
      font-size: 10px;
    }
  }
  a{
    color: #69b1ff;
    font-weight: 600;
  }
  button {
    padding: 0 !important;
    color: #69b1ff;
    font-weight: 600;
    @media screen and (max-width: 480px) {
      font-size: 11px !important;
    }
    @media screen and (max-width: 365px) {
      font-size: 10px !important;
    }
  }
`;

export const StyledModal = styled(Modal)`
  .ant-modal-content {
    background-color: var(--primary-color);
    padding: 0;

    .ant-modal-close{
      color: #fff;
      @media screen and (max-width: 480px) {
        inset-inline-end: 8px !important;
        top: 10px !important;
      }
    }
  }
`;