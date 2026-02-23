import { useEffect } from 'react';

const useArtworkUpload = () => {
    const openEditorPreview = document.getElementById('openEditorPreview');
    const editorPreviewOffcanvas = document.getElementById('editorPreviewOffcanvas');

    if (openEditorPreview && editorPreviewOffcanvas) {
        if (!editorPreviewOffcanvas.classList.contains('show')) {
            openEditorPreview.click();
        }
    }
};

export default useArtworkUpload;
