import { Button, Collapse, CollapseProps } from "antd";
import styled from "styled-components";

export const StyledCollapse = styled(Collapse) <CollapseProps>`
  && {
    background: transparent;
    border: none;

    .ant-collapse-item {
      border: none;
    }

    .ant-collapse-content {
      border-top: none;
      padding: 0;
    }

    .ant-collapse-header {
      display: none;
    }
  }

  .ant-collapse-content-box {
    padding: 0 !important;
  }
`;

interface ButtonProps {
  type?: "primary" | "default";
  isPromoStore?: boolean;
}

export const StyledButton = styled(Button)<ButtonProps>`
  background-color: ${(props) => (props.type === "primary" ? (props.isPromoStore ? "#25549b" : "#704e9f") : "#fff")};
  color: ${(props) => (props.type === "primary" ? "#fff" : (props.isPromoStore ? "#25549b" : "#704e9f"))};
  border: 2px solid ${(props) => (props.isPromoStore ? "#25549b" : "#704e9f")};
`;
