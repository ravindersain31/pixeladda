import styled, { createGlobalStyle } from "styled-components";
import { Modal, Upload, Divider, Spin, Tabs, Button as AntdButton } from "antd";
import Button from "@react/editor/components/Button";

interface ImageProps {
  isPromoStore?: boolean;
}

export const DragAndDropIcon = styled.img<ImageProps>`
  width: 55px;
  height: 55px;
  object-fit: contain;
  filter: ${({ isPromoStore }) =>
    isPromoStore
      ? "none"
      : "brightness(0) saturate(100%) invert(32%) sepia(15%) saturate(2000%) hue-rotate(230deg) brightness(90%) contrast(90%)"};
`;

export const OneDriveIcon = styled.img`
  width: 30px;
  height: 30px;
  object-fit: contain;
  transition: filter 0.3s ease;

  .ant-tabs-tab-active &,
  .ant-tabs-tab:hover & {
    filter: brightness(0) saturate(100%) invert(32%) sepia(15%) saturate(2000%) hue-rotate(230deg) brightness(90%) contrast(90%);
  }
`;

export const GlobalCameraStyleFix = createGlobalStyle`
  .react-html5-camera-photo > img,
  .react-html5-camera-photo > video {
    width: 100% !important;
    max-width: 100%;
    height: auto;
  }
`;
export const GlobalModalZIndexOverride = createGlobalStyle`
  :where(.ant-modal-root) .ant-modal-wrap {
    z-index: 1080 !important;
  }

  :where(.ant-modal-root) .ant-modal-mask {
    z-index: 1079 !important;
  }
  .picker-dialog {
    z-index: 1090 !important;
  }
`;

export const CameraError = styled.div`
  text-align: center;
  font-size: 12px; 
  padding: 5px;
  border-radius: 2px;
  color: #ff0000;
`;

export const StylesSpan = styled.span`
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 12;

  .icon {
    font-size: 24px;
    margin-bottom: 4px;
  }
  &.space-tab {
    margin-left: 0;
  }
  @media (min-width: 768px) {
    &.space-tab {
      margin-left: 8px;
    }
  }
`;

export const StyledButton = styled(AntdButton)`
  &.btn-ysp-upload,
  &.btn-ysp-google-upload,
  &.btn-ysp-dropbox-upload,
  &.btn-ysp-import {
    color: #000;
    background-color: #fff;
    border-color: var(--primary-color) !important;
    border-radius: 3px;
    box-shadow: 2px 3px 2px #000;
  }

  &.btn-ysp-upload:hover,
  &.btn-ysp-google-upload:hover,
  &.btn-ysp-dropbox-upload:hover,
  &.btn-ysp-import:hover {
    color: #fff !important;
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
  }
`;

export const StyledDivider = styled(Divider)`
  margin: 10px 0 !important;
`;

export const StyledModal = styled(Modal)`
  .ant-modal-content-footer {
    text-align: center;
    font-size: 12px;
    padding: 5px;
    border-radius: 2px;
  }
  .ant-upload-hint {
    font-weight: bold;
    color: #222;
  }

  .ant-upload-hint-text {
    font-weight: bold;
    font-size: 12px;
    color: #222;
    margin: 0;
  }
`;

export const TemplateList = styled.div`
  display: block;
  text-align: center;

  a {
    padding: 10px;
    color: var(--primary-color);
    font-size: 0.9rem;
    font-weight: 600;
    min-width: 80px;
    display: inline-block;
    text-align: center;

    &:hover {
      text-shadow: 0 0 10px var(--primary-color);
    }

    @media only screen and (max-width: 768px) {
      padding: 5px;
      font-size: 11px;
      min-width: 50px;
    }

    @media only screen and (max-width: 882px) {
      font-size: 11px;
      min-width: 50px;
    }
  }
`;

export const UploadCustomDesignContainer = styled.div`
  background: #f3f3f3;
  padding: 10px;
  border-radius: 5px;
  margin-bottom: 5px;
`;

export const StyledUpload = styled(Upload)`
  width: 100%;
  padding-bottom: 10px;
  @media (max-width: 480px) {
    padding: 10px 0;
  }

  .ant-upload-list-item {
    border-width: 2px !important;
  }

  .ant-upload-select {
    display: flex;
    justify-content: center;
    align-content: center;
    margin: auto;
    width: 100% !important;
    height: auto !important;
    text-align: center;
    border: 2px dotted #d9d9d9;

    .ant-upload {
      width: 100% !important;
    }
  }

  p {
    font-weight: bold;
  }
`;

export const UploaderContainer = styled.div`
  display: flex;
  justify-content: space-evenly;
  align-items: flex-start;
  .ant-upload-wrapper:not(:first-child) {
    margin-left: 10px;
    @media (max-width: 480px) {
      margin-left: 0;
    }
  }
  @media (max-width: 480px) {
    flex-wrap: wrap;
  }
  .ant-upload {
    cursor: pointer;
  }
`;

export const UploadButton = styled.div`
  width: 100%;
  padding: 5px;
  svg {
    font-size: 40px;
    color: #8e8e8e;
  }
`;

export const FileList = styled.div`
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: flex-start;
`;

export const FileItem = styled.div`
  width: 100%;
  display: flex;
  justify-content: space-between;
  padding: 5px 10px;
  border-radius: 5px;
  font-size: 13px !important;

  &:hover {
    background: #ececec;
  }
`;

export const FileName = styled.div`
  cursor: pointer;
  overflow-wrap: anywhere;

  svg {
    font-size: inherit;
    color: var(--primary-color) !important;
  }
`;

const FileBaseButton = styled(Button)`
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4px 10px;
  height: auto;
  margin: 0 5px;

  svg {
    font-size: 12px;
    color: #fff !important;
  }
`;

export const FileActions = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const FileShow = styled(FileBaseButton)`
  background: var(--primary-color);
  border-color: var(--primary-color);
`;

export const FileDelete = styled(FileBaseButton)`
  background: #bf1213;
  border-color: #bf1213;
`;

export const Uploading = styled(Spin)`
  margin-left: 10px;

  svg {
    font-size: inherit;
    color: var(--primary-color) !important;
  }
`;

export const NeedAssistanceContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0px 0px 5px 0px;
  gap: 40px;

  @media screen and (max-width: 480px) {
    gap: 10px;
    font-size: 14px;
  }

  @media screen and (max-width: 380px) {
    gap: 0;
    justify-content: space-evenly;
  }
`;

export const StyledTabs = styled(Tabs)`
  &.upload-artwork-tabs {
    .ant-tabs-nav-wrap::before,
    .ant-tabs-nav-wrap::after {
      box-shadow: none !important;
    }
  }
@media (min-width: 768px) {
  &.equal-tabs .ant-tabs-nav-list {
    display: flex !important;
    justify-content: space-between;
  }

  &.equal-tabs .ant-tabs-tab {
    flex: 1 1 auto;
    justify-content: center;
    text-align: center;
    max-width: none;
  }
}
`;

export const CameraButton = styled(Button)`
  color: #000 !important;
  background-color: #fff;
  border: 1px solid var(--primary-color) !important;
  border-radius: 3px;
  box-shadow: 2px 3px 2px rgba(0, 0, 0, 0.25);

  &:hover {
    color: #fff !important;
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
  }
`;

export const CenteredWrapper = styled.div`
  display: flex;
  justify-content: center;

  &.camera-switch {
    margin-bottom: 10px;
  }
`;

export const StyledDiv = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
`;