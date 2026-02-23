import styled from "styled-components";
import { Upload } from "antd";

export const StyledUpload = styled(Upload)`
  width: 100%;

  .ant-upload-list-item {
    display: none !important;
  }

  .ant-upload-select {
    display: flex;
    justify-content: center;
    align-content: center;
    margin: auto;
    width: 100% !important;
    height: auto !important;
    text-align: center;
    border: none !important;
    background-color: initial !important;
    margin-bottom: 0 !important;

    .ant-upload {
      width: 100% !important;
    }
  }

  p {
    font-weight: bold;
  }
`;
