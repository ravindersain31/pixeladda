import { Button } from "antd";
import styled from "styled-components";

export const LockObjectContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const LockAllButtonContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const LockButton = styled(Button)<{ $isLock: boolean }>`
  margin: 5px;
  border-radius: 5px;
  height: auto;
  min-height: auto;
  padding: 8px;
  background: ${({ $isLock }) => ($isLock ? "var(--primary-color)" : "#fff")} !important;
  color: ${({ $isLock }) => ($isLock ? "#fff" : "var(--primary-color)")} !important;
  border: 2px solid var(--primary-color);
  font-size: 10px;
  svg {
    font-size: 20px;
  }
`;