// AddYourArtwork.tsx
import React, { useState } from 'react';
import { Row, Col, Drawer } from 'antd';
import { StepProps } from '../interface';
import StepCard from '@react/editor/components/Cards/StepCard';
import BrowseArtwork from './BrowseArtwork';
import UploadArtwork from './UploadArtwork';
import { DeleteOutlined, PictureOutlined } from '@ant-design/icons';
import { useAppDispatch, useAppSelector } from '@react/editor/hook';
import CustomCard from '../../common/CustomCard';
import { StyledButton, StyledRow, StyledDrawer } from './styled';
import { isMobile } from 'react-device-detect';
import ArtworkListPreview from '../../common/ArtworkListPreview';

const AddYourArtwork = ({ stepNumber }: StepProps) => {
  const [isBrowseDrawerVisible, setBrowseDrawerVisible] = useState(false);
  const editor = useAppSelector(state => state.editor);
  const canvas = useAppSelector(state => state.canvas);
  const dispatch = useAppDispatch();

  const toggleBrowseDrawer = () => setBrowseDrawerVisible(!isBrowseDrawerVisible);

  const browseArtworkData = editor.items[canvas.item.id]?.customArtwork?.["BROWSE-ARTWORK"]?.[canvas.view || 'front'] ?? [];
  const uploadArtworkData = editor.items[canvas.item.id]?.customArtwork?.["UPLOAD-ARTWORK"]?.[canvas.view || 'front'] ?? [];

  return (
    <StepCard title="Add Your Artwork" stepNumber={stepNumber}>
      <Row gutter={[8, 8]}>
        <Col xs={24} sm={24} md={24} lg={12}>
          <CustomCard title="ADD ARTWORK">
            <StyledRow gutter={[8, 8]}>
              <Col xs={12} sm={12} md={12} lg={12}>
                <span>
                  <StyledButton type="primary" onClick={toggleBrowseDrawer}>
                    <PictureOutlined />
                    Browse Artwork
                  </StyledButton>
                  <ArtworkListPreview
                    artworkData={browseArtworkData}
                    type="BROWSE-ARTWORK"
                  />
                </span>
              </Col>
              <Col xs={12} sm={12} md={12} lg={12}>
                <span>
                  <UploadArtwork />
                  <ArtworkListPreview
                    artworkData={uploadArtworkData}
                    type="UPLOAD-ARTWORK"
                  />
                </span>
              </Col>
            </StyledRow>
          </CustomCard>
        </Col>
        <StyledDrawer
          title="Browse Artwork"
          placement="right"
          closable
          onClose={toggleBrowseDrawer}
          open={isBrowseDrawerVisible}
          width={!isMobile ? "50vw" : "100vw"}
        >
          <BrowseArtwork />
        </StyledDrawer>
      </Row>
    </StepCard>
  );
};

export default AddYourArtwork;
