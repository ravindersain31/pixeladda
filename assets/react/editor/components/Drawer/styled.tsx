import styled from "styled-components";
import {Drawer} from "antd";

export const StyledDrawer = styled(Drawer)`
  @media (max-width: 480px) {
    .ant-drawer-body {
      padding: 10px;
    }
  }
`;
