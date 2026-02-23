import styled from "styled-components";
import {Card, Badge, Popover} from 'antd';
import {BadgeProps} from "antd/lib";

export const StyledCard = styled(Card)`
  text-align: center;
  border: none;

  .ant-card-body {
    padding: 10px;
  }
`;

export const AddonImage = styled.div`
  height: 150px;
  width: auto;
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
  }

  @media (max-width: 1366px) {
    height: 135px;
  }
`;

export const AddonNameContainer = styled.div`
  display: flex;
  justify-content: center;

  button {
    position: absolute;
    right: 5px;
    height: auto;
    border: none;
    padding: 0;
    box-shadow: none;
    width: 16px !important;
    min-width: 16px !important;
  }
`;

export const AddonName = styled.div`
  font-size: 12px;
  color: #000;
  font-weight: 500;
  @media (max-width: 1366px) {
    font-size: 11px;
  }
  @media (max-width: 770px) {
    font-size: 10px;
  }
  @media (max-width: 400px) {
    font-size: 9px;
  }
`;

export const StyledPopover: any = styled(Popover)`

`;


export const PopoverContent = styled.div`
  font-family: "Montserrat", sans-serif;

  @media (max-width: 860px) {
    font-size: 13px;
  }
  @media (max-width: 480px) {
    font-size: 11px;
  }
`;

export const StyledBadgeRibbon = styled(Badge.Ribbon)<BadgeProps>`
  ${({text}) => !text && `display: none`};
  font-size: 10px;
  line-height: 1.8;
  inset-inline-end: -18px !important;
`;