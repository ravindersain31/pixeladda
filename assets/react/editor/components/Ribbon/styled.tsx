import styled from "styled-components";
import { Card, Badge, Popover } from "antd";
import { BadgeProps } from "antd/lib";
import { RibbonProps } from "antd/es/badge/Ribbon";

export const StyledPopover: any = styled(Popover)``;

export const PopoverContent = styled.div`
  @media (max-width: 860px) {
    font-size: 13px;
  }
  @media (max-width: 480px) {
    font-size: 11px;
  }
`;

export const StyledBadgeRibbon = styled(Badge.Ribbon)<RibbonProps>`
  ${({ text }) => !text && `display: none`};
  font-size: 10px;
  line-height: 1.8;
  font-weight: 500;
  top: 30px;
`;
