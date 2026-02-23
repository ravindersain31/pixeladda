import styled from "styled-components";
import { Modal, Upload } from 'antd';


export const StyledUpload = styled(Upload)`
  margin-bottom: 0.5rem !important;

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
    border-width: 2px !important;
    margin-bottom: 0 !important;

    .ant-upload {
      width: 100% !important;
    }
  }
`;

export const UploadButton = styled.div`
  width: 100%;
  padding: 15px;

  svg {
    font-size: 50px;
    color: #8e8e8e;
  }
`;

export const StyledModal = styled(Modal)`
`;

export const StyledDiv = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
`;