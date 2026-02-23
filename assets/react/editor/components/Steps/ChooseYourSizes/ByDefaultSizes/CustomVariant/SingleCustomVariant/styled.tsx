import { Row,Button } from "antd";
import styled from "styled-components";

export const EnterSizeWrapper = styled(Row)<{$hasMultipleSizes : boolean}>`
  text-align: left;
  padding: 5px 10px;
  .cross {
    padding: 4px;

    @media screen and (max-width: 460px) {
      padding: ${({ $hasMultipleSizes }) => $hasMultipleSizes ? "4px 0px" : "4px"};
    }
  }
  .ant-input-number {
    color: #fff !important;
  }
  .bulk-discounts{
    font-size:12px;
    font-weight: 500;
    @media screen and (max-width: 1400px) {
      font-size: 11px;
    }
    @media screen and (max-width: 1023px) {
      display: none;
    }
    @media screen and (max-width: 767px) {
      display: inline;
    }
    @media screen and (max-width: 460px) {
      font-size: ${({ $hasMultipleSizes }) => $hasMultipleSizes ? "9px" : "10px"};
    }
    @media screen and (max-width: 363px) {
      font-size: ${({ $hasMultipleSizes }) => $hasMultipleSizes ? "8px" : "9px"};
    }
    @media screen and (max-width: 300px) {
      font-size: ${({ $hasMultipleSizes }) => $hasMultipleSizes ? "7px" : "8px"};
    }
  }
  @media screen and (max-width: 667px) {
    padding: 5px 0;
  }
`;

export const Label = styled.label`
  font-size: 14px;
`;

export const Title = styled.span<{$hasMultipleSizes : boolean}>`
  font-size:12px;
  font-weight: 500;
  @media screen and (max-width: 1400px) {
    font-size: 11px;
  }
  @media screen and (max-width: 1023px) {
    display: none;
  }
  @media screen and (max-width: 767px) {
    display: inline;
  }
  @media screen and (max-width: 460px) {
    font-size: ${({ $hasMultipleSizes }) => $hasMultipleSizes ? "9px" : "10px"};
  }
  @media screen and (max-width: 363px) {
    font-size: ${({ $hasMultipleSizes }) => $hasMultipleSizes ? "8px" : "9px"};
  }
  @media screen and (max-width: 300px) {
    font-size: ${({ $hasMultipleSizes }) => $hasMultipleSizes ? "7px" : "8px"};
  }
`;

export const PopoverContent = styled.div`
  color: white;
`;

export const QuestionButton = styled(Button)`
  position: absolute;
  background: #ededed;
  border: none;
  width: 12px !important;
  min-width: 12px !important;
  height: 13px;
  padding-top: 0;
  padding-bottom: 0;
  margin: 0 3px;
  margin-top: 5px;
  font-size: 10px;
  &:hover{
    color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
  }
  &:focus{
    color: black;
    border-color: black;
  }
  @media screen and (max-width: 300px) {
    margin-top: 6px;
  }
  .anticon-question-circle {
    font-size: 14px !important;
    @media (max-width: 768px) {
      svg{
        width:0.7rem;
        height:0.7rem;
      }
    }
  }
`;

export const CloseButton = styled(Button)`
    background: none;
    border: none;
    position: absolute;
    right: 10px;
    top: 0;
    box-shadow: none;
`;

export const BiggerSizeMessage = styled.div`
    font-size: 12px;
    cursor: auto;
    margin-top: 5px;
`;

export const AddAnotherSize = styled(Button)`
  font-size: 12px !important;
  margin-top: 5px;
  box-shadow: none;
  border: 1px solid var(--primary-color);
  color: #1c202b;
  font-weight: 500;

  &:hover, &:active {
    color: #fff !important;
    background-color: var(--primary-color);
  }
`;

export const DeleteButton = styled(Button)`
  vertical-align: middle;
  margin-top: 3px;
`;