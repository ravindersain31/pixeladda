import React, { useContext, useEffect, useState } from "react";
import { CloudUploadOutlined, PlusOutlined, DeleteOutlined } from '@ant-design/icons';
import {
    StyledDiv,
    StyledUpload,
    UploadButton,
} from './styled';
import type { UploadProps } from 'antd/es/upload';
import type { UploadFile } from 'antd/es/upload/interface';
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import CanvasContext from "@react/editor/context/canvas.ts";
import fabric from "@react/editor/canvas/fabric.ts";
import AdditionalNote from "@react/editor/components/AdditionalNote";
import { isMobile } from "react-device-detect";
import useArtworkUpload from "@react/editor/plugin/useArtworkUpload";
import actions from "@react/editor/redux/actions";
import { CanvasProperties } from "@react/editor/canvas/utils";
import { consolidateAllArtworks } from "@react/editor/helper/canvas.ts";
import AddQrCode from "../TextEditor/AddQrCode";
import UploadSourceModal from "../../ChooseYourDesign/CustomDesign/UploadSourceModal";
import { GlobalCameraStyleFix, GlobalModalZIndexOverride } from "../../ChooseYourDesign/CustomDesign/styled";

interface UploaderProps {
    title: string;
    side: string;
    fileList: UploadFile[];
    onChange: (info: any) => void;
    onRemove: (file: UploadFile) => void;
}
const UploadArtwork: React.FC<UploaderProps> = ({
    side,
    title,
    fileList: initialFileList,
    onChange,
    onRemove,
}) => {

    const [uploadModalVisible, setUploadModalVisible] = useState(false);

    const config = useAppSelector((state) => state.config);

    const editor = useAppSelector((state) => state.editor);

    const canvas = useAppSelector(state => state.canvas);

    const [fileList, setFileList] = useState<UploadFile[]>(initialFileList || []);

    const [uploadingFiles, setUploadingFiles] = useState<UploadFile[]>([]);

    const dispatch = useAppDispatch();

    const canvasContext = useContext(CanvasContext);

    useEffect(() => {
        setFileList(initialFileList || []);
    }, [initialFileList]);


    useEffect(() => {
        if (!canvasContext.canvas) return;
        canvasContext.canvas.fire("canvas:updated");
    }, [canvasContext.canvas]);

    useEffect(() => {
        if (!canvasContext.canvas) return;

        const sync = () => syncFileListWithCanvas();
        canvasContext.canvas.on("canvas:updated", sync);

        return () => {
            canvasContext.canvas.off("canvas:updated", sync);
        };
    }, []);

    useEffect(() => {
        const currentData = canvas.data?.[canvas.view];
        const objects = (currentData && typeof currentData === 'string' ? JSON.parse(currentData) : currentData)?.objects || [];

        const files = objects.filter((o: any) => o.custom?.type === 'artwork').map((artwork: any) => {
            return {
                uid: artwork.custom.id,
                name: artwork.custom.type || 'artwork',
                status: 'done',
                response: {
                    url: artwork.src,
                },
                thumbUrl: artwork.src,
            };
        });

        setFileList(files);
        onChange({ fileList: files });
    }, [canvas.data, canvas.view]);

    const addImageToCanvas = (url: string, uid: string) => {
        const modifiedUrl = modifyImageUrl(url);
        const existingObjects = canvasContext.canvas.getObjects() || [];
        const isDuplicate = existingObjects.some(
            (obj: any) =>
                obj.custom?.type === 'artwork' &&
                obj.custom?.id === uid
        );

        fabric.util.loadImage(modifiedUrl, (img: any) => {
            const image = new fabric.Image(img);
            image.left = 20;
            image.top = 20;
            image.scaleToWidth(100);
            image.scaleToHeight(100);
            image.custom = {
                id: uid,
                type: 'artwork'
            };
            canvasContext.canvas.add(image);
            canvasContext.canvas.requestRenderAll();
            canvasContext.canvas.setActiveObject(image);
            canvasContext.canvas.fire("canvas:updated");
        });
        useArtworkUpload();
    };


    const syncFileListWithCanvas = () => {
        const canvasObjects = canvasContext.canvas.getObjects() || [];
        const artworksOnCanvas = canvasObjects.filter(
            (obj: any) => obj.custom?.type === "artwork"
        );

        setFileList((prev) => {
            const fileMap = new Map(prev.map((f) => [f.uid, f]));

            const updatedList: UploadFile[] = [];

            artworksOnCanvas.forEach((artwork: any) => {
                const uid = artwork.custom?.id;
                if (!uid) return;

                const url =
                    artwork?.src ||
                    artwork?._originalElement?.currentSrc ||
                    artwork?._originalElement?.src ||
                    artwork.getSrc?.() ||
                    "";
                if (fileMap.has(uid)) {
                    updatedList.push(fileMap.get(uid)!);
                } else {
                    updatedList.push({
                        uid,
                        name: artwork.custom?.type || "artwork",
                        status: "done",
                        response: { url },
                        thumbUrl: url,
                    });
                }
            });

            const uniqueList = Array.from(
                new Map(updatedList.map((item) => [item.uid, item])).values()
            );

            const prevUids = prev.map((f) => f.uid).sort().join(",");
            const newUids = uniqueList.map((f) => f.uid).sort().join(",");
            if (prevUids !== newUids) {
                onChange({ fileList: uniqueList });
                return uniqueList;
            }

            return prev;
        });
    };


    const modifyImageUrl = (url?: string): string => {
        if (url?.includes("https://static.yardsignplus.com/fit-in/1000x1000/")) {
            return url;
        }
        return url && url.endsWith(".gif")
            ? url
            : url?.replace(
                "https://static.yardsignplus.com/",
                "https://static.yardsignplus.com/fit-in/1000x1000/"
            ) ?? "";
    };

    const handleChange: UploadProps['onChange'] = ({ fileList: newFileList }) => {
        for (const file of uploadingFiles) {
            const match = newFileList.find(({ uid }) => uid === file.uid);
            if (match && match.status === 'done') {
                addImageToCanvas(match.response.url, match.uid);
            }
        }

        setUploadingFiles(newFileList.filter((file) => file.status === 'uploading'));

        const updatedFileList = newFileList.map((file) => ({
            ...file,
            thumbUrl: file.response?.url && file.response.url.endsWith(".gif") ? file.response.url : modifyImageUrl(file.response?.url) || file.thumbUrl,
            error: file.status === "error" ? new Error(file.response?.message) : null,
        }));

        setFileList(updatedFileList);
        onChange({ fileList: updatedFileList });
    };

    const identifyArtworkInObjects = (currentCanvas: any) => {
        const uniqueArtworks = consolidateAllArtworks(
            canvas.data?.front,
            canvas.data?.back,
            currentCanvas?.objects || []
        );

        dispatch(actions.editor.updateUploadedArtworks(uniqueArtworks));
    }

    const onDelete = (file: UploadFile) => {
        if (canvasContext?.canvas) {
            const objects = canvasContext.canvas.getObjects() || [];
            objects.forEach((object) => {
                if (object.custom?.type === 'artwork' && object.custom?.id === file.uid) {
                    canvasContext.canvas.remove(object);
                    canvasContext.canvas.requestRenderAll();
                }
            });
        }

        const newFileList = fileList.filter(f => f.uid !== file.uid);

        setFileList(newFileList);
        onChange({ fileList: newFileList });

        if (onRemove) {
            onRemove(file);
        }
    }

    const isArtworkDuplicate = (canvas: any, uid: string): boolean => {
        const objects = canvas.getObjects() || [];

        return objects.some(
            (obj: any) =>
                obj.custom?.type === "artwork" &&
                obj.custom?.id === uid
        );
    };


    return <>
        <StyledUpload
            action={config.links.upload_artwork}
            listType="picture-card"
            fileList={fileList}
            onDownload={(file: UploadFile) => addImageToCanvas(file.response.url, file.uid)}
            onChange={handleChange}
            onRemove={(file: UploadFile) => {
                onDelete(file);
                return true;
            }}
            openFileDialogOnClick={false}
            showUploadList={{
                showRemoveIcon: true,
                showPreviewIcon: false,
                showDownloadIcon: true,
                downloadIcon: <PlusOutlined style={{ color: '#fff' }} title="Add to Canvas" />,
            }}
        >
            <UploadButton onClick={() => setUploadModalVisible(true)}>

                <div><CloudUploadOutlined /></div>
                <div>

                    <p className="mb-0">Click here to upload</p>
                    <span className="text-muted">or drag and drop file to this area </span>
                </div>
            </UploadButton>
            <GlobalModalZIndexOverride />
            <GlobalCameraStyleFix />
            <UploadSourceModal
                visible={uploadModalVisible}
                fileList={fileList}
                onClose={() => setUploadModalVisible(false)}
                onFileListChange={(newList) => {
                    setFileList(newList);
                    onChange({ fileList: newList });
                }}
                onUploadSuccess={(file) => {
                    const url = file.response?.url || file.url || file.thumbUrl;
                    if (isArtworkDuplicate(canvasContext.canvas, file.uid)) {
                        return;
                    }
                    if (url) {
                        addImageToCanvas(url, file.uid);
                    }
                }}
                uploadUrl={config.links.upload_artwork}
            />
        </StyledUpload>
        <StyledDiv>
            <AddQrCode />
            <AdditionalNote showNeedAssistance={isMobile ? true : false} />
        </StyledDiv>
    </>
}

export default UploadArtwork;