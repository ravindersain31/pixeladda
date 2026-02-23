import styled from "styled-components";
import { Button, Input, Space } from "antd";

export const StyledButton = styled(Button)`
  color: #000 !important;
  background-color: #fff !important;
  border-color: var(--primary-color) !important;
  border-radius: 3px !important;
  box-shadow: 2px 3px 2px #000 !important;
  &:hover {
    color: #fff !important;
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
  }
  &.handle-select-image {
    margin-top: 8px;
    width: auto;
  }
`;

export const GoogleImage = styled.div`
  padding: 16px;
  text-align: center;
`;

export const StyledSpace = styled(Space)`
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 8px;
`;

export const GoogleImageInput = styled(Input)`
  width: 250px;
  max-width: 90vw;
`;

export const ScrollContainer = styled.div`
  max-height: 400px;
  overflow-y: auto;
  margin-top: 20px;
  padding: 10px;
  border: 1px solid #ddd;
  background: #fafafa;
`;

export const FileItemDiv = styled.div`
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 16px;
`;

export const FileItem = styled.div`
  width: 160px;
  text-align: center;
`;
