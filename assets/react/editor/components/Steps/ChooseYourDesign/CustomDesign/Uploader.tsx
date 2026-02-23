import React, { useContext, useState } from "react";
import { UploadFile } from "antd/es/upload/interface";
import { CloudUploadOutlined, DeleteOutlined, DownloadOutlined, EyeOutlined, LoadingOutlined } from "@ant-design/icons";
import { useAppSelector } from "@react/editor/hook.ts";
import {
    StyledUpload,
    UploadButton,
    FileList,
    FileItem,
    FileName,
    FileActions,
    FileDelete,
    FileShow,
    Uploading,
    GlobalModalZIndexOverride,
    GlobalCameraStyleFix,

} from "./styled.tsx";
import { useDispatch } from "react-redux";
import actions from "@react/editor/redux/actions";
import CanvasContext from "@react/editor/context/canvas.ts";
import { CanvasProperties } from "@react/editor/canvas/utils.ts";
import fabric from "@react/editor/canvas/fabric.ts";
import UploadSourceModal from "./UploadSourceModal.tsx";

interface UploaderProps {
    title: string;
    side: string;
    fileList: UploadFile[];
    onChange: (info: any) => void;
    onRemove: (file: UploadFile) => void;
}

const Uploader = (
    {
        side,
        title,
        fileList,
        onChange,
        onRemove,
    }: UploaderProps
) => {
    const [uploadModalVisible, setUploadModalVisible] = useState(false);
    const config = useAppSelector(state => state.config);

    const canvas = useAppSelector(state => state.canvas);
    const canvasContext = useContext(CanvasContext);

    const dispatch = useDispatch();

    const previewFile = (file: UploadFile) => {
        if (canvas.view !== side) {
            dispatch(actions.canvas.updateView(side, canvasContext.canvas.toJSON(CanvasProperties)));
        }
        const objects = canvasContext.canvas.getObjects();
        // @ts-ignore
        const files = canvas.data[side]?.objects || [];
        const index = files.findIndex((url: string) => url === file.url) || 0;
        const targetObject = objects.find((obj: any) => obj.custom?.id === file.uid);

        if (!targetObject) {
            return;
        }

        let groupedObjects: fabric.Object[] = [targetObject];
        let foundTarget = false;

        for (const obj of objects) {
            if (obj === targetObject) {
                foundTarget = true;
                continue;
            }
            if (foundTarget) {
                if (obj.custom?.type === "custom-design") break;
                groupedObjects.push(obj);
            }
        }

        const targetIndex = objects.indexOf(targetObject);

        groupedObjects.forEach((obj) => {
            canvasContext.canvas.bringToFront(obj);
        });

        canvasContext.canvas.requestRenderAll();
        dispatch(actions.canvas.updateActiveObject(targetIndex));
        canvasContext.canvas.setActiveObject(targetObject);
    };

    const onDelete = (file: UploadFile) => {
        dispatch(actions.canvas.updateActiveObject(0));
        onRemove(file);
    }

    const beforeUpload = (file: UploadFile) => {
        const ext = file.name.toLowerCase().split('.').pop() || '';
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'ai', 'eps', 'ppt', 'pptx', 'psd', "tiff", "tif", "heic", 'svg', 'csv', 'xls', 'xlsx','zip'];
        if (!allowedExtensions.includes(ext)) {
            const message = 'Please upload a valid file type.  Accepted files are PNG, JPEG, JPG, EPS, Ai & PDF. Files must be less than 50 MB in size.';
            alert(message);
            file.status = 'error';
            file.response.message = message;
            return false;
        }
        return true;
    }

    const handleDownload = (file: UploadFile) => {
        const fileUrl = file.url || file.response?.url;
        const fileName = file.name.replace(/[()]/g, "");

        if (!fileUrl) {
            console.error('Download error: File URL is missing');
            return;
        }
        const fileExtension = fileUrl.toLowerCase().split('.').pop();

        const directDownloadTypes = ['pdf', 'csv', 'xls', 'xlsx', 'zip'];
        if (directDownloadTypes.includes(fileExtension)) {            const a = document.createElement('a');
            a.href = fileUrl;
            a.download = fileName;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            document.body.appendChild(a);
            a.click();
            a.remove();
        } else {
            fetch(fileUrl)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                })
                .catch(error => console.error('Download error:', error));
        }
    };

    return (
      <StyledUpload
        action={config.links.upload_custom_design}
        fileList={fileList}
        onChange={onChange}
        onRemove={onRemove}
        multiple
        beforeUpload={beforeUpload}
        openFileDialogOnClick={false}
        itemRender={(originNode: React.ReactElement, file: UploadFile) => {
          const message = file.response?.message || "";
          return (
            <FileItem>
              <FileName onClick={() => previewFile(file)}>
                <span className="text-primary">{file.name}</span>
                {message && (
                  <span className="text-danger small ms-1">({message})</span>
                )}
              </FileName>
              <FileActions>
                {file.status !== "uploading" && (
                  <>
                    {/* {file.status === 'done' &&
                            <FileShow onClick={() => previewFile(file)}><EyeOutlined/></FileShow>
                        } */}
                    <FileShow onClick={() => handleDownload(file)}>
                      <DownloadOutlined />
                    </FileShow>
                    <FileDelete onClick={() => onDelete(file)}>
                      <DeleteOutlined />
                    </FileDelete>
                  </>
                )}
                {file.status === "uploading" && (
                  <Uploading indicator={<LoadingOutlined spin />} />
                )}
              </FileActions>
            </FileItem>
          );
        }}
        showUploadList={{
          showRemoveIcon: true,
          showPreviewIcon: false,
          showDownloadIcon: true,
        }}
      >
        <UploadButton onClick={() => setUploadModalVisible(true)}>
          <div>
            <CloudUploadOutlined />
          </div>
          <div>
            <p className="mb-0">{title}</p>
            <span className="text-muted">
              or drag and drop custom artwork to this area{" "}
            </span>
          </div>
        </UploadButton>
        <GlobalModalZIndexOverride />
        <GlobalCameraStyleFix />
        <UploadSourceModal
          visible={uploadModalVisible}
          fileList={fileList}
          onClose={() => setUploadModalVisible(false)}
          onFileListChange={(newList) => {
            onChange({ fileList: newList });
          }}
        />
      </StyledUpload>
    );
}

export default Uploader;