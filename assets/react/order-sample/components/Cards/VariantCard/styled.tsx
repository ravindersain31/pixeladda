import styled from "styled-components";
import { Badge, Card, Col, InputNumber, Popover } from "antd";
import { BadgeProps } from "antd/lib";
import Collapse, { CollapseProps } from "antd/es/collapse";

/** CARD STYLES **/
export const StyledCard = styled(Card).withConfig({
  shouldForwardProp: (prop) => !["$hasBackgroundColor", "$hasRibbonColor"].includes(prop),
}) <{ $hasBackgroundColor?: boolean; $hasRibbonColor?: boolean }>`
  text-align: center;
  margin: 5px;
  border: 2px solid #d9d9d9;
  cursor: pointer;

  ${({ $hasBackgroundColor }) => $hasBackgroundColor && `background: var(--background-color_1);`}
  ${({ $hasRibbonColor }) => $hasRibbonColor && `border-color: #3398d9 !important;`}

  .ant-card-body {
    padding: 10px;
    position: relative;
  }

  &.active {
    border-color: var(--primary-color);

    .ant-card-body {
      &:before {
        display: flex !important;
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        z-index: 99;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 36px 36px 0 0;
        border-color: var(--primary-color) transparent transparent;
      }

      .checkmark {
        display: block !important;
      }

      .editmark:before {
        display: flex !important;
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        z-index: 99;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 36px 0 0 36px;
        border-color: #2ac474 transparent transparent;
      }
    }
  }

  &.mobile-device {
    height: 180px;
  }

  .variant-quantity {
    justify-content: center;
    .ant-input-number {
      width: 85%;
      @media (max-width: 767px) {
        width: 80%;
      }
    }
    .question-icon {
      position: absolute;
      right: 0;
      bottom: 8px;
      border: none;
      background: transparent;
    }
  }
`;

/** IMAGE WRAPPER **/
export const VariantImage = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  height: 150px;
  margin-bottom: 10px;

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

/** TEXT LABEL **/
export const VariantName = styled('div').withConfig({
  shouldForwardProp: (prop) => prop !== '$textBold',
}) <{ $textBold?: boolean }>`
  font-size: 14px;
  margin-bottom: 10px;
  ${({ $textBold }) => $textBold && `font-weight: 500;`}

  &.mobile-device {
    font-size: 10px;
    margin-bottom: 0;
  }

  @media (max-width: 1280px) {
    font-size: 14px;
  }
`;

/** INPUTS **/
export const InputQuantity = styled(InputNumber)`
  width: 100%;
  input {
    text-align: center !important;
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

/** CHECKMARKS **/
export const Checkmark = styled.div`
  position: absolute;
  width: 30px;
  top: 0;
  left: -3px;
  z-index: 99;
  display: none;
`;

export const Editmark = styled.div`
  position: absolute;
  width: 30px;
  top: 0;
  right: 0;
  z-index: 99;

  .anticon {
    position: relative;
    z-index: 999;
    right: -6px;
  }
`;

/** SUPPORT **/
export const ContactSupport = styled.div`
  font-size: 18px;
  color: rgba(0, 0, 0, 0.85);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100%;
  padding: 5px;

  p {
    margin: 0;
    min-height: 35px;
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    text-align: center;
    line-height: 3;
    font-size: 12px;
    background: #eff2f8;
    border: 2px dotted rgb(216, 216, 216);
    border-radius: 5px;

    button {
      background: transparent;
      border: none;
      padding: 0 !important;
      font-size: 12px !important;

      &:hover {
        color: #1677ff !important;
      }

      span {
        vertical-align: text-top;
      }
    }

    &.normal-text {
      line-height: 1.4;
    }

    @media (max-width: 667px) {
      flex-wrap: wrap;
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
      line-height: 3.3 !important;
    }
  }

  @media (max-width: 480px) {
    padding: 5px;

    p {
      line-height: 2;
    }

    .normal-text {
      line-height: 1.7 !important;
    }
  }
`;

/** BADGES **/
export const StyledBadgeRibbon = styled(Badge.Ribbon) <BadgeProps>`
  ${({ text }) => !text && `display: none;`}
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

export const StyledBadgeEdit = styled(Badge.Ribbon) <BadgeProps>`
  font-size: 10px;
  font-weight: bold;
  line-height: 1.8;
  inset-inline-end: -20px !important;
  top: ${({ title }) => (title ? "20px" : "0px")};
  color: #fff;
  background-color: #2ac474;

  @media (max-width: 480px) {
    inset-inline-end: -20px !important;
  }
`;

export const StyledPopover: any = styled(Popover)`

`;


export const PopoverContent = styled.div`
  font-family: "Montserrat", sans-serif;
  max-width: 280px;
  @media (max-width: 860px) {
    font-size: 13px;
  }
  @media (max-width: 480px) {
    font-size: 11px;
  }
`;