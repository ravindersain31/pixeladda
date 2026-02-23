import { useEffect } from 'react';
import { isMobile } from 'react-device-detect';

const useCanvasChange = (onCanvasChange: () => void) => {

    useEffect(() => {
        const openEditorPreview = document.getElementById('openEditorPreview');
        const editorPreviewOffcanvas = document.getElementById('editorPreviewOffcanvas');

        if (openEditorPreview && editorPreviewOffcanvas && isMobile) {
            const onClickHandler = () => onCanvasChange();

            openEditorPreview.addEventListener("click", onClickHandler);
            editorPreviewOffcanvas.addEventListener("click", onClickHandler);

            return () => {
                openEditorPreview.removeEventListener("click", onClickHandler);
                editorPreviewOffcanvas.removeEventListener("click", onClickHandler);
            };
        }
        
    }, [onCanvasChange]);
};

export default useCanvasChange;
