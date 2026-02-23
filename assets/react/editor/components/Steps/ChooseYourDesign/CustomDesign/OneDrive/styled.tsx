import styled from "styled-components";
import { Button  } from "antd";

export const FileItem = styled.div<{ selected?: boolean }>`
  padding: 8px 12px;
  border: 1px solid #ccc;
  margin-bottom: 8px;
  cursor: pointer;
  background: ${({ selected }) => (selected ? "#e6f7ff" : "#fff")};
  transition: background 0.2s ease;

  &:hover {
    background: ${({ selected }) => (selected ? "#d6efff" : "#f5f5f5")};
  }
`;

export const LoginButton = styled(Button)`
  color: #000;
  background-color: #fff;
  border-color: var(--primary-color) !important;
  border-radius: 3px;
  box-shadow: 2px 3px 2px #000;

  &:hover {
    color: #fff !important;
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
  }
`;

export const LogoutButton = styled(LoginButton)`
  margin-left: 10px;
`;

export const OneDrive = styled.div`
  &.ysp-one-drive {
    padding: 10px;
    text-align: center;
  }
  .onedrive-files-container {
    margin-top: 20px;
    max-height: 300px;
    overflow-y: auto;
  }
`;
;