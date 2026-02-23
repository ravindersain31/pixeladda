import styled from "styled-components";
import {Card, Popover} from 'antd';

export const StyledCard = styled(Card)`
  text-align: center;
  margin: 5px;
  cursor: pointer;
  position: relative;
  border-width: 2px !important;

  .ant-card-body {
    padding: 10px;
  }

  &.active {
    border-color: var(--primary-color) !important;

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
    }
  }
`;

export const AddonImage = styled.div`
  height: 150px;

  img, svg {
    margin: auto;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
  }
  .ant-skeleton {
    width: 100%!important;
    min-height: 200px!important;
    .ant-skeleton-image {
      width: 100%!important;
      min-height: 200px!important;
    }
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
  }
`;

export const AddonName = styled.div`
  font-size: 14px;

  @media (max-width: 1280px) {
    font-size: 14px;
  }

  @media (max-width: 480px) {
    font-size: 13px;
  }
`;

export const Checkmark = styled.div`
  position: absolute;
  width: 19px;
  top: 1px;
  z-index: 99;
  left: 3px;
  display: none;
`;