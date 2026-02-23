import React from 'react';
import { DeleteOutlined } from '@ant-design/icons';
import { ArtworkList, ArtworkItem, ArtworkImageContainer, DeleteButtonContainer } from './styled';
import { useAppDispatch, useAppSelector } from '@react/editor/hook';
import actions from '@react/editor/redux/actions';
import { useCanvasContext } from '@react/editor/context/canvas';
import { CanvasProperties } from '@react/editor/canvas/utils';

interface ArtworkListProps {
  artworkData: any[];
  type: string;
}

const ArtworkListPreview = ({ artworkData, type }: ArtworkListProps) => {
  const canvas = useAppSelector(state => state.canvas);
  const editor = useAppSelector(state => state.editor);
  const dispatch = useAppDispatch();
  const {canvas: canvasContext} = useCanvasContext();

  const removeArtwork = (id: number, side: string, type: string) => {
    const prevData: any = editor.items[canvas.item.id]?.customArtwork?.[type]?.[canvas.view || 'front'] || [];
    const updatedArtwork = prevData.filter((artwork: any) => artwork.id !== id);
    dispatch(actions.editor.updateCustomArtwork(updatedArtwork, side, type));

    const currentOriginalArtwork = editor.items[canvas.item.id]?.customOriginalArtwork?.[canvas.view || 'front'] || [];
    const updatedOriginalArtwork = currentOriginalArtwork.filter((artwork: any) => artwork.id !== id);
    
    if (updatedOriginalArtwork.length !== currentOriginalArtwork.length) {
      dispatch(actions.editor.updateCustomOriginalArtwork(updatedOriginalArtwork, side));
    }

    if(type == "YSP-LOGO") {
      dispatch(actions.canvas.updateCanvasData(canvasContext.toJSON(CanvasProperties)))
      dispatch(actions.editor.updateYSPLogoDiscount());
    }
  };

  return (
    <>
      {artworkData.length > 0 && (
        <ArtworkList>
          {artworkData.map((artwork: any, index: number) => (
            <ArtworkItem key={index}>
              <ArtworkImageContainer>
                <a href={artwork.url} target="_blank" rel="noopener noreferrer">
                  <img src={artwork.url} alt={`Artwork ${index}`} style={{ maxWidth: '100%', width: '50px' }} />
                </a>
              </ArtworkImageContainer>
              <DeleteButtonContainer>
                <DeleteOutlined
                  style={{
                    fontSize: '14px',
                    position: 'absolute',
                    right: 0,
                    bottom: 0,
                    cursor: 'pointer',
                  }}
                  onClick={() => removeArtwork(artwork.id, canvas.view || 'front', type)}
                />
              </DeleteButtonContainer>
            </ArtworkItem>
          ))}
        </ArtworkList>
      )}
    </>
  );
};

export default ArtworkListPreview;
