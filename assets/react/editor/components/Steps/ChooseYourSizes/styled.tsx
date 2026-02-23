import styled from "styled-components";
import { Badge, Card, Col, InputNumber, Popover } from "antd";
import {BadgeProps} from "antd/lib";
import Collapse, { CollapseProps } from "antd/es/collapse";


export const StyledCard = styled(Card)<{ $hasBackgroundColor?: boolean; $hasRibbonColor?: boolean; $isHandFans?: boolean; }>`
  text-align: center;
  margin: 5px;
  border: 2px solid #d9d9d9;
  cursor: pointer;

  ${({ $hasBackgroundColor }) => 
    $hasBackgroundColor ? `
      background: var(--light-color);
    ` : ''
  }

  ${({ $hasRibbonColor }) => 
    $hasRibbonColor ? `
      border-color: #3398d9 !important;
      border-width: 2px;
      border-style: solid;
    ` : ''
  }

  .ant-card-body {
    padding: ${({ $isHandFans }) => ($isHandFans ? "6px" : "10px")};
    position: relative;
  }

  &.active {
    border-color: var(--primary-color);

    .ant-card-body {
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

      .checkmark {
        display: block !important;
      }

      .editmark {
        &:before {
          display: flex !important;
          content: '';
          top: 0;
          right: 0;
          z-index: 99;
          position: absolute;
          width: 0;
          height: 0;
          border-style: solid;
          border-width: 36px 0 0 36px;
          border-color: #2ac474 transparent transparent;
          background: none;
        }
      }
    }
  }

  &.mobile-device {
    margin: 5px;
    height: 180px;
  }
`;

export const VariantImage = styled.div`
  height: 120px;
  width: auto;
  margin-bottom: 10px;
  display: flex;
  justify-content: center;
  align-items: center;

  img, svg {
    margin: auto;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
  }
  
  &.mobile-device {
    height: 100px;
    margin-bottom: 0;
  }

  @media (max-width: 1280px) {
    height: 135px;
  }
`;

export const UserLogin = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  background: #fff;
  border: 2px solid white;
  max-width: 100%;
  padding: 5px 0;
`;

export const LoginButton = styled.div`
  background: linear-gradient(90deg, #FFCA2C 0%, #FF7A00 100%);
  text-transform: uppercase;
  color: #fff !important;
  padding: 8px 15px;
  border: none;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  text-align: center;
  color: #000 !important;
  .click-to-login{
    color: #fff !important;
  }
`;

export const VariantName = styled.div<{ $textBold?: boolean; $isHandFans?: boolean; }>`
  font-size: ${({ $isHandFans }) => ($isHandFans ? "12px" : "16px")};
  margin-bottom: 10px;
  ${({ $textBold }) => $textBold ? `font-weight: 500;` : ''}

  &.mobile-device {
    font-size: 13px;
    margin-bottom: 0;
  }

  @media (max-width: 1280px) {
    font-size: 14px;
  }
`;

export const InputQuantity = styled(InputNumber)`
  width: 100%;

  input {
    text-align: center!important;
  }
`;

export const Checkmark = styled.div`
  position: absolute;
  width: 30px;
  top: 0;
  z-index: 99;
  left: -3px;
  display: none;
`;

export const Editmark = styled.div`
  position: absolute;
  width: 30px;
  top: 0;
  z-index: 99;
  right: 0;

  .anticon {
    z-index: 999;
    position: relative;
    right: -6px;
  }
`;

export const ContactSupport = styled.div`
  font-size: 18px;
  color: rgba(0, 0, 0, 0.85);
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  padding: 5px;
  flex-direction: column;

  p {
    margin: 0;
    min-height: 35px;
    background: #eff2f8;
    border-style: dotted;
    border-width: 2px;
    border-color: rgb(216, 216, 216);
    flex-wrap: wrap;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    width: 100%;
    border-radius: 5px;
    font-size: 12px;
    line-height: 16px;
    text-align: center;
    padding: 8px;
    button {
      background: transparent;
      border: none;
      padding: 0 !important;
      font-size: 12px !important;
      line-height: 16px !important;
      height: unset !important;

      &:hover {
        color: #1677ff !important;
      }

      span {
        vertical-align: text-top;
      }
    }
    &.normal-text {
      display: flex;
      text-align: center;
      line-height: 1.4;
      justify-content: center;
      align-items: center;
    }

    @media (max-width: 667px) {
      flex-wrap: wrap;
      justify-content: center;
    }

  }
  @media (max-width: 1580px) {
    .packed {
      font-size: 11px;
    }
  }
  @media (max-width: 1180px) {
    .packed {
      font-size: 10px;
    }
  }
  @media (max-width: 1100px) {
    padding: 4px;
    .normal-text.mobile {
      line-height: 3.3!important;
    }
  }
  @media (max-width: 480px) {
    p {
      line-height: 16px;
      text-align: center;
    }
    padding: 5px;
    .normal-text {
      line-height: 1.7!important;
    }
  }
`;

export const StyledBadgeRibbon = styled(Badge.Ribbon)<BadgeProps>`
  ${({text}) => !text && `display: none`};
  font-size: 10px;
  font-weight: bold;
  line-height: 1.8;
  inset-inline-end: -2px !important;
  top: ${({ style }) => style?.top || '8px'};
  margin: -17px;

  @media (max-width: 480px) {
    inset-inline-end: -1px !important;
  }
`;

export const StyledBadgeEdit = styled(Badge.Ribbon)<BadgeProps>`
  font-size: 10px;
  line-height: 1.8;
  font-weight: bold;
  inset-inline-end: -20px !important;
  top: ${({ title }) => (title ? "20px" : "0px")};
  color: #2ac474;
  background-color: #2ac474;

  @media (max-width: 480px) {
    inset-inline-end: -20px !important;
  }
`;

export const InputSizeHeight = styled(InputNumber)`
  width: 100%;
  input {
    text-align: center !important;
    color: rgba(0, 0, 0, 0.88) !important;
  }
`;

export const InputSizeWidth = styled(InputNumber)`
  width: 100%;
  input {
    text-align: center !important;
    color: rgba(0, 0, 0, 0.88) !important;
  }
`;

export const StyledCollapse = styled(Collapse)<CollapseProps>`
  .ant-collapse-header {
    display: none !important;
  }
  .ant-collapse-content-box {
    padding: 0 !important;
  }
  background: transparent !important;
  .ant-card:not(.active) {
    border: 1px solid #d9d9d9;
  }
  .ant-card-body {
    background: #f3f3f7;
  }
`;

export const StyledColCustomSize = styled(Col)<{ $active: boolean }>`
  display: flex;
  justify-content: center;
  align-items: center;

  button {
    font-size: 14px !important;
    color: ${({ $active }) => ($active ? "#fff" : "#1c202b")};
    border-color: ${({ $active }) => ($active ? "#fff" : "var(--primary-color)")};
    box-shadow: none;
    font-weight: 500;
    &:hover {
      color: #fff !important;
      background: var(--primary-color) !important;
    }
  }
`;

export const BiggerSizeMessage = styled.div`
    font-size: 12px;
    cursor: auto;
    margin-top: 5px;
    text-align: center;
`;

export const AdditionalNoteBox = styled.div`
  font-size: 18px;
  color: rgba(0, 0, 0, 0.85);
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  padding: 5px 5px 0 5px;
  flex-direction: column;
`;