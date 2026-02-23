import styled from "styled-components";
import { Button, Modal, Divider } from "antd";
import { isMobile } from "react-device-detect";


export const ShareDesignButton = styled(Button)`

`;

export const ShareDesignModal = styled(Modal)`

`;

export const Preview = styled.div`
  text-align: center;
  margin: 20px 0;
`;

export const PreviewCanvas = styled.canvas`
  border: 1px dashed #ccc;
  border-radius: 8px;
`;

export const StyledWrapper = styled.div`
  display: flex;
  justify-content: center;
  gap: 16px;
  margin: 20px 0;

  ${isMobile && `
    flex-direction: column;
    width: 100%;
    gap: 16px;
    padding: 0 16px;
  `}
`;

export const DownloadButton = styled(Button)`
  font-size: 12px !important;
  border-color: var(--primary-color);
  background: #fff; 
  color: #000 !important; 
  font-weight: normal;
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;

  &:hover { 
    background: var(--primary-color) !important;
    color: #fff !important; 
  }

  ${isMobile && `
    font-size: 14px !important;
    width: 100%;
    padding: 10px 0 !important;
    max-width: 220px;
    margin: 0 auto;
    padding: 10px 0 !important;
  `}
`;

export const CopyButton = styled(Button)`
  font-size: 12px !important;
  border-color: var(--primary-color);
  background: #fff; 
  color: #000 !important; 
  font-weight: normal;
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;

  &:hover { 
    background: var(--primary-color) !important;
    color: #fff !important; 
  }

  ${isMobile && `
    font-size: 14px !important;
    width: 100%;
    padding: 10px 0 !important;
    max-width: 220px;
    margin: 0 auto;
    padding: 10px 0 !important;
  `}
`;

export const SocialIcon = styled.div`
  margin-top: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
`;

export const IconButton = styled(Button)`
  display: inline-flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;
  border: 1px solid #ccc;
  padding: 0;
  margin: 0 6px;
  
  &:hover {
    border-color: var(--primary-color);;
    color: #fff;
  }
`;

export const ShareSpan = styled.span`
  display: inline-block;
  margin-right: 10px;
  font-weight: 500;
`;


