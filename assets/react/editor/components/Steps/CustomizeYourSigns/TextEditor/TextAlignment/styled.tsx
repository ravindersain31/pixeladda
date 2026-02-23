import styled from "styled-components";
import { Tooltip } from "antd";
import { CheckboxButton } from "@react/editor/components/Button";

export const TextAlignmentContainer = styled.div`
  display: flex;
  flex-wrap: wrap;

  @media screen and (max-width: 768px) {
    button {
      margin: 0 5px 5px 5px;
    }
  }
  @media screen and (max-width: 370px) {
    button {
      margin: 0 2px;
    }
  }
`;

export const StyledTooltip = styled(Tooltip)`
  .ant-tooltip-inner {
    background-color: #fff;
    color: #000;
    border: 1px solid #d9d9d9;
  }
  .ant-tooltip-arrow {
    border-color: #d9d9d9;
  }
`;

const BaseCheckboxButton = styled(CheckboxButton)`
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  border: 1px solid #d9d9d9;
  background-color: ${props => (props.checked ? "var(--primary-color)" : "white")};
  color: ${props => (props.checked ? "white" : "black")};
  &:hover {
    background-color: ${props => (props.checked ? "var(--primary-color)" : "#f0f0f0")};
  }
  &:disabled {
    background-color: #f0f0f0;
    cursor: not-allowed;
  }
`;

export const AlignLeft = styled(BaseCheckboxButton)``;
export const AlignCenter = styled(BaseCheckboxButton)``;
export const AlignRight = styled(BaseCheckboxButton)``;