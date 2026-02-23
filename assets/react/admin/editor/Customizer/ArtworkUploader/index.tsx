import {useContext, useState} from "react";
import {
    ArtworkContainer,
    ArtworkLoadButton,
    ArtworkLoaderForm,
    ArtworkLoaderInput
} from "./styled.tsx";
import fabric from "@react/admin/editor/canvas/fabric.ts";
import CanvasContext from "@react/admin/editor/context/canvas.ts";

const ArtworkUploader = (props: any) => {

    const [file, setFile] = useState<any>(null);

    const [loading, setLoading] = useState<boolean>(false);

    const canvasContext = useContext(CanvasContext);

    const onFileChange = (e: any) => {
        const file = e.target.files[0];
        setFile(file);
    }

    const onUpload = () => {
        setLoading(true);
        const reader = new FileReader();
        reader.onload = (e: any) => {
            const file = e.target.result;
            uploadImageFromDataURL(file).then((imageUrl: any) => {
                fabric.util.loadImage(imageUrl, (img: any) => {
                    const image = new fabric.Image(img);
                    image.left = 20;
                    image.top = 20;
                    image.scaleToWidth(100);
                    image.scaleToHeight(100);
                    image.custom = {
                        id: 'artwork-' + Math.random().toString(36).substring(2, 15),
                        type: 'artwork'
                    };
                    canvasContext.canvas.add(image);
                    canvasContext.canvas.requestRenderAll();
                    canvasContext.canvas.setActiveObject(image);
                    setLoading(false);
                });
            });
        }
        reader.readAsDataURL(file);
    }


    const uploadImageFromDataURL = (imageData: any) => {
        return new Promise((resolve, reject) => {
            const data = new FormData();
            data.append('file', imageData);
            fetch(props.uploadDataImage, {
                method: 'POST',
                body: data
            }).then((res) => {
                res.json().then((data) => {
                    resolve(data.imageUrl);
                }).catch((e) => {
                    reject(e);
                });
            }).catch((e) => {
                reject(e);
            });
        });
    }

    return <ArtworkContainer>
        <label>Upload Artwork</label>
        <ArtworkLoaderForm>
            <ArtworkLoaderInput type="file" onChange={onFileChange}/>
            <ArtworkLoadButton type="default" onClick={() => onUpload()} disabled={loading}>
                {loading ? 'Uploading...' : 'Upload'}
            </ArtworkLoadButton>
        </ArtworkLoaderForm>
    </ArtworkContainer>
}

export default ArtworkUploader;