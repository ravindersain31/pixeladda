import React, { useContext, useEffect, useState } from "react";
import { DownloadOutlined } from '@ant-design/icons';
import {
    StyledDivider,
    TemplateList,
    UploadCustomDesignContainer,
    UploaderContainer,
    NeedAssistanceContainer,
    StyledDiv,
} from './styled';
import Uploader from './Uploader.tsx';
import type { UploadFile } from 'antd/es/upload/interface';
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import { CustomArtwork, originalArtworkImageObject, Sides } from "@react/editor/redux/reducer/editor/interface.ts";
import AdditionalNote from "@react/editor/components/AdditionalNote";
import NeedAssistance from "@react/editor/components/NeedAssistance";
import fabric from "@react/editor/canvas/fabric.ts";
import CanvasContext from "@react/editor/context/canvas.ts";
import { CanvasProperties } from "@react/editor/canvas/utils.ts";
import { fitImageToCanvas } from "@react/editor/canvas/fitObjectsToCanvas.ts";
import useArtworkUpload from "@react/editor/plugin/useArtworkUpload.ts";
import AddQrCode from "../../CustomizeYourSigns/TextEditor/AddQrCode/index.tsx";
import useShowCanvas from "@react/editor/hooks/useShowCanvas.tsx";

const CustomDesign = () => {

    const dispatch = useAppDispatch();

    const config = useAppSelector(state => state.config);

    const canvas = useAppSelector(state => state.canvas);

    const editor = useAppSelector(state => state.editor);
    const canvasContext = useContext(CanvasContext);
    type SideType = "front" | "back";
    const showCanvas = useShowCanvas();

    const [fileList, setFileList] = useState<{
        [key: string]: UploadFile[],
    }>({
        front: [],
        back: []
    });

    const [uploadingFiles, setUploadingFiles] = useState<{
        [key: string]: UploadFile[],
    }>({
        front: [],
        back: []
    });

    const type = CustomArtwork.CUSTOM_DESIGN;

    useEffect(() => {
        const loadedFiles: { [key: string]: UploadFile[] } = { front: [], back: [] };
        const customOriginalArtworks = editor.items?.[canvas.item.id]?.customOriginalArtwork || {};

        (['front', 'back'] as SideType[]).forEach((side) => {
            const sideData = customOriginalArtworks[side];
            const processedUids = new Set<string>();

            if (sideData && Array.isArray(sideData) && sideData.length > 0) {
                loadedFiles[side] = sideData
                    .filter(item => {
                        if (processedUids.has(item.id)) return false;
                        processedUids.add(item.id);
                        return true;
                    })
                    .map((item: originalArtworkImageObject, index: number) => ({
                        ...item,
                        uid: item.id || `file-${index}`,
                        name: `#${index + 1} - ${canvas.item.name} (${item.url.split('/').pop()})`,
                        status: 'done',
                        url: item.url,
                        response: { url: item.url, originalFileUrl: item.originalFileUrl },
                    }));
            } else {
                const data = canvas.data[side];
                if (data && typeof data === 'object' && 'objects' in data) {
                    const customDesignItems = Array.isArray(data.objects) ? data.objects.filter((item: any) => item?.custom?.type === "custom-design") : [];
                    customDesignItems.forEach((item: any, index: number) => {
                        const url = item.src || '';
                        const uid = item.custom?.id || `file-${index}`;
                        const fileName = url.split('/').pop();

                        if (fileName && !processedUids.has(uid)) {
                            processedUids.add(uid);
                            loadedFiles[side].push({
                                uid: uid,
                                name: `#${index + 1} - ${canvas.item.name} (${fileName.replace(/-[^-]*\./, '.')})`,
                                status: 'done',
                                url,
                                response: { url },
                            });
                        }
                    });
                }
            }
        });
        const isUploading = Object.values(uploadingFiles).some(files => files.length > 0);

        if (!isUploading) {
            setFileList((prevState) => ({
                front: [...(loadedFiles.front || [])],
                back: [...(loadedFiles.back || [])],
            }));
            dispatch(actions.canvas.updateCanvasLoader(false));
        }
    }, [canvas.data, canvas.view, uploadingFiles, canvas.item.id]);

    const handleCanvasClear = () => {
        const hasCustomDesign = canvasContext.canvas.getObjects().some((item: any) => item?.custom?.type === "custom-design");

        if (!hasCustomDesign) {
            canvasContext.canvas.clear();
            canvasContext.canvas.requestRenderAll();
        }
    }

  const addImageToCanvas = (url: string, uid: string, side: string) => {
    const lowerUrl = url.toLowerCase();
    const isImage =
      /\.(png|jpe?g|gif|webp)(\?|$)/.test(lowerUrl);

    if (!isImage) {
      console.warn(`Skipping non-image file: ${url}`);
      return;
    }

    const modifiedUrl = modifyImageUrl(url);

    fabric.util.loadImage(modifiedUrl, (img: any) => {
      const image = new fabric.Image(img);
      fitImageToCanvas(image, canvasContext.canvas, img);
      (image as any).custom = { id: uid, type: "custom-design", side };

      const serializedImage = {
        ...image.toObject(),
        custom: { id: uid, type: "custom-design", side },
      };

      const hasPlaceholder = canvasContext.canvas.getObjects().some(
        (obj: any) => obj.__isPlaceholder
      );

      if (!hasPlaceholder) {
        const placeholder = new fabric.Rect({
          left: 0,
          top: 0,
          width: canvasContext.canvas.getWidth(),
          height: canvasContext.canvas.getHeight(),
          fill: "transparent", 
          selectable: false,
          evented: false,
        });
        (placeholder as any).__isPlaceholder = true;
        canvasContext.canvas.add(placeholder);
        canvasContext.canvas.sendToBack(placeholder);
      }

      canvasContext.canvas.getObjects().forEach((obj: any) => {
        if (obj.custom?.side && obj.custom?.side !== side) {
          canvasContext.canvas.remove(obj);
        }
      });
      canvasContext.canvas.add(image);
      canvasContext.canvas.bringToFront(image);
      canvasContext.canvas.requestRenderAll();
      canvasContext.canvas.setActiveObject(image);

      const prevData: any = editor.items?.[canvas.item.id]?.customArtwork?.[type]?.[side as "front" | "back"] || [];
      dispatch(
        actions.editor.updateCustomArtwork([...prevData, serializedImage], side, type)
      );
      dispatch(
        actions.canvas.updateCanvasData(
          canvasContext.canvas.toJSON(CanvasProperties),
          side
        )
      );
      useArtworkUpload();
    });
  };

  const addCustomOriginalArtwork = (
    url: string,
    originalFileUrl: string,
    uid: string,
    side: string
  ) => {
    const imageObject: originalArtworkImageObject = {
      id: uid,
      url,
      originalFileUrl,
    };

    const prevArtwork = editor.items?.[canvas.item.id]?.customOriginalArtwork?.[side as "front" | "back"] ||
      [];
    const updatedArtwork = [...prevArtwork, imageObject];

    dispatch(actions.editor.updateCustomOriginalArtwork(updatedArtwork, side));
  };

  const modifyImageUrl = (url?: string): string => {
    if (url?.includes("https://static.yardsignplus.com/fit-in/1000x1000/")) return url;
    return url && url.endsWith(".gif")
      ? url
      : url?.replace(
          "https://static.yardsignplus.com/",
          "https://static.yardsignplus.com/fit-in/1000x1000/"
        ) ?? "";
  };

  const onRemove = (file: UploadFile, side: SideType) => {
    const prevData: any = editor.items?.[canvas.item.id]?.customArtwork?.[type]?.[side as "front" | "back"] || [];
    const updatedCustomArtwork = prevData.filter(
      (item: any) => item.custom?.id !== file.uid
    );
    dispatch(actions.editor.updateCustomArtwork(updatedCustomArtwork, side, type));

    const prevArtwork =
      editor.items?.[canvas.item.id]?.customOriginalArtwork?.[side as "front" | "back"] ||
      [];
    const updatedArtwork = prevArtwork.filter((item: any) => item.id !== file.uid);
    dispatch(actions.editor.updateCustomOriginalArtwork(updatedArtwork, side));
    setFileList((prevState) => ({
      ...prevState,
      [side]: prevState[side].filter((item) => item.uid !== file.uid),
    }));

    if (canvas.view === side) {
      const objects = canvasContext.canvas.getObjects();
      objects.forEach((object: any) => {
        if (object.custom?.type === "custom-design" && object.custom?.id === file.uid) {
          canvasContext.canvas.remove(object);
        }
      });
      canvasContext.canvas.requestRenderAll();

      dispatch(
        actions.canvas.updateCanvasData(
          canvasContext.canvas.toJSON(CanvasProperties),
          side
        )
      );
    } else {
      const currentSideData: any = canvas.data[side];
      if (currentSideData && Array.isArray(currentSideData?.objects)) {
          const newObjects = currentSideData.objects.filter(
              (obj: any) => !(obj.custom?.type === "custom-design" && obj.custom?.id === file.uid)
          );
          
          dispatch(
              actions.canvas.updateCanvasData(
                  { ...currentSideData, objects: newObjects },
                  side
              )
          );
      }
    }
  };

  const handleChange = (info: any, side: string) => {
    const newUploadingFiles = info.fileList.filter(
      (file: any) => file.status === "uploading"
    );
    setUploadingFiles((prevState) => ({ ...prevState, [side]: newUploadingFiles }));
        const isFromMethodCheck = info.fileList[0]?.isMethodCheck;
        const completedFile = info.fileList.find((file: any) => {
            const isUploaded = file.status === "done";
            const wasUploading = uploadingFiles[side]?.some(({ uid }) => uid === file.uid);     
            return isFromMethodCheck
            ? isUploaded || wasUploading
            : isUploaded && wasUploading;
        });

        if (completedFile && completedFile.type !== "text/csv") {
           if (canvas.view !== side) {
                dispatch(actions.canvas.updateView(side, canvasContext.canvas.toJSON(CanvasProperties)));
            }
            addImageToCanvas(completedFile.response.url, completedFile.uid, side);
        }
        if (completedFile) {
            addCustomOriginalArtwork(completedFile.response.url, completedFile.response.originalFileUrl, completedFile.uid, side);
        }

        const updatedFileList = info.fileList.filter((file: any) => file.status !== 'error').map((file: any) => ({
            ...file,
            thumbUrl: file.response?.url && file.response.url.endsWith(".gif") ? file.response.url : modifyImageUrl(file.response?.url) || file.thumbUrl,
            error: file.status === 'error' ? new Error(file.response?.message ?? 'Unknown error while uploading. Please refresh the page and try again.') : null,
        }));

        if (newUploadingFiles.length > 0) {
            dispatch(actions.canvas.updateCanvasLoader(true));
        }
        setFileList((prevState) => ({
            ...prevState,
            [side]: updatedFileList,
        }));
    };

    return <>
        <UploadCustomDesignContainer>
            <UploaderContainer>
                <Uploader
                    title={`Upload Custom Artwork ${editor.sides === Sides.DOUBLE ? "Front" : ""}`}
                    side='front'
                    fileList={fileList.front}
                    onChange={(info: any) => handleChange(info, 'front')}
                    onRemove={(file: UploadFile) => onRemove(file, 'front')}
                />
                {editor.sides === Sides.DOUBLE && <Uploader
                    title={`Upload Custom Artwork ${editor.sides === Sides.DOUBLE ? "Back" : ""}`}
                    side='back'
                    fileList={fileList.back}
                    onChange={(info: any) => handleChange(info, 'back')}
                    onRemove={(file: UploadFile) => onRemove(file, 'back')}
                />}
            </UploaderContainer>
            <StyledDiv>
              {showCanvas && <AddQrCode />}
              <AdditionalNote />
            </StyledDiv>
            <StyledDivider orientation="center">Download Optional Templates</StyledDivider>
            <TemplateList>
                {config.product.variants && config.product.variants.map((variant) => {
                    return <a href={variant.customTemplate} target="_blank" key={`custom_mockup_template_${variant.name}`}>
                        <DownloadOutlined /> {variant.customTemplateLabel || variant.name}
                    </a>
                })}
            </TemplateList>
        </UploadCustomDesignContainer>
    </>
}

export default CustomDesign;