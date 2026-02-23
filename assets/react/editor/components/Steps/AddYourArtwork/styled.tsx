import { Button, Drawer, Row } from 'antd';
import styled from 'styled-components';

export const StyledDrawer = styled(Drawer)`
  .ant-drawer-header-title {
    flex-direction: row-reverse;
  }
`;

export const StyledButton = styled(Button)`
    border-color: var(--primary-color);
    background: #fff;
    color: #000;
    transition: none;
    font-size: 12px;
    box-shadow: 1px 1px #000;
    width: 100%;
    span {
      vertical-align: middle;
    }

    &:hover{
      background-color: var(--primary-color) !important;
    }
`;

export const StyledRow = styled(Row)`
  width: 100%;
`;