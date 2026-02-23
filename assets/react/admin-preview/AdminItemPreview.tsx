import React from 'react';
import Preview from './Preview';
import Header from './Header';
import styled from 'styled-components';
import { Row, Col } from 'antd';
import { StyledFlexCenterRow, StyledPreviewContainer } from './styled';
import { isMobile } from 'react-device-detect';

const AdminItemPreview = (props: any) => {
    const widthAndHeight = props.item.name.split('x');
    const customOriginalArtwork = props.item?.customOriginalArtwork ?? { front: [], back: [] };
    const frontArtworks = Array.isArray(customOriginalArtwork.front) ? customOriginalArtwork.front : [];
    const backArtworks = Array.isArray(customOriginalArtwork.back) ? customOriginalArtwork.back : [];

    const templateSize = {
        width: parseInt(widthAndHeight[0], 10) || 12,
        height: parseInt(widthAndHeight[1], 10) || 12,
    };

    const isDoubleSide = props.item?.addons?.sides?.key === 'DOUBLE';

    return (
        <StyledFlexCenterRow gutter={[16, 16]}>
            <Col span={24}>
                <Header item={props.item} />
            </Col>
            <StyledPreviewContainer
                isDoubleSide={isDoubleSide}
                span={isDoubleSide ? 12 : isMobile ? 24 : 12}
            >
                <Preview
                    itemId={props.itemId}
                    item={props.item}
                    side="front"
                    canvasData={props.canvasData.front}
                    templateSize={templateSize}
                    artworks={frontArtworks}
                />
            </StyledPreviewContainer>
            {isDoubleSide && (
                <StyledPreviewContainer span={12}>
                    <Preview
                        itemId={props.itemId}
                        item={props.item}
                        side="back"
                        canvasData={props.canvasData.back}
                        templateSize={templateSize}
                        artworks={backArtworks}
                    />
                </StyledPreviewContainer>
            )}
        </StyledFlexCenterRow>
    );
};

export default AdminItemPreview;
