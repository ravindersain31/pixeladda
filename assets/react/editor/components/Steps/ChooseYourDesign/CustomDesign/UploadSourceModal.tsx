import React, { useEffect, useState } from "react";
import { Modal, Tabs, Upload, Button, message, Input, Space, Spin, Form } from "antd";
import {
  StyledModal,
  StyledTabs,
  StyledButton,
  StylesSpan,
  CenteredWrapper,
  CameraError,
  CameraButton,
  OneDriveIcon,
  DragAndDropIcon,
} from "./styled.tsx";
import { SiGoogledrive } from "react-icons/si";
import {
  CloudUploadOutlined,
  DesktopOutlined,
  GlobalOutlined,
  CameraOutlined,
  DropboxOutlined,
  GoogleOutlined,
  LeftOutlined,
  RightOutlined,
} from "@ant-design/icons";

import { useAppSelector } from "@react/editor/hook.ts";
import Camera from "react-html5-camera-photo";
import "react-html5-camera-photo/build/css/index.css";
import type { RcFile, UploadFile as AntdUploadFile, } from "antd/es/upload/interface";
import GoogleDrivePicker from "./GoogleDrivePicker";
import { handleGoogleDriveFileUpload } from "./handleGoogleDriveFileUpload.tsx";
import DropboxPicker from "./DropboxPicker.tsx";
import { handleDropboxFileUpload } from "./handleDropboxFileUpload.tsx";
import OneDrivePicker from "./OneDrive/OneDrivePicker.tsx";
import { handleOneDriveFileUpload } from "./OneDrive/handleOneDriveFileUpload.tsx";
import GoogleImageSearch from "./GoogleImage/GoogleImageSearch.tsx";
import { isMobile } from "react-device-detect";
import { imageUrlToBlob } from "@react/editor/helper/template.ts";
import { processUploadSuccess } from "./helper.ts";
import { isPromoStore } from "@react/editor/helper/editor.ts";

interface UploadSourceModalProps {
  visible: boolean;
  onClose: () => void;
  onFileListChange: (files: AntdUploadFile[]) => void;
  fileList: AntdUploadFile[];
  onUploadSuccess?: (file: AntdUploadFile) => void;
  uploadUrl?: string
}
interface UploadFileWithMethodCheck extends AntdUploadFile {
  isMethodCheck?: boolean;
}

const UploadSourceModal: React.FC<UploadSourceModalProps> = ({
  visible,
  onClose,
  onFileListChange,
  fileList: initialFileList,
  onUploadSuccess,
  uploadUrl
}) => {
  const [imageUrl, setImageUrl] = useState<string>("");
  const [isImageValid, setIsImageValid] = useState<boolean>(false);
  const [uploading, setUploading] = useState(false);
  const [cameraError, setCameraError] = useState<string | null>(null);
  const [cameraDenied, setCameraDenied] = useState<boolean>(false);
  const [facingMode, setFacingMode] = useState<"user" | "environment">(
    "environment"
  );
  const [activeKey, setActiveKey] = useState("myFiles");

  useEffect(() => {
    if (activeKey === "web" && visible) {
      if (window.focusElementById('web-url-input')) {
        return;
      }

      const observer = new MutationObserver(() => {
        const input = document.getElementById('web-url-input');
        if (input) {
          window.focusElementById('web-url-input');
          observer.disconnect();
        }
      });

      const modalContent = document.querySelector('.ant-modal-body');
      if (modalContent) {
        observer.observe(modalContent, {
          childList: true,
          subtree: true,
        });
      }

      const fallbackTimeout = setTimeout(() => {
        window.focusElementById('web-url-input');
      }, 300);

      return () => {
        observer.disconnect();
        clearTimeout(fallbackTimeout);
      };
    }
  }, [activeKey, visible]);

  const toggleFacingMode = () => {
    setFacingMode((prev) => (prev === "user" ? "environment" : "user"));
  };

  const [fileList, setFileList] = useState<AntdUploadFile[]>(
    initialFileList || []
  );
  const config = useAppSelector((state) => state.config);
  const finalUploadUrl = uploadUrl ?? config.links.upload_custom_design;

  useEffect(() => {
    setFileList(initialFileList || []);
  }, [initialFileList]);

  const allowedExtensions = [
    "jpg", "jpeg", "png", "pdf", "gif", "ai", "eps", "ppt", "pptx", "psd", "tiff", "tif", "heic", "csv", "xlsx", "xls", "zip"
  ];

  const handleClose = () => {
    setActiveKey("myFiles");
    onClose();
  };

  const beforeUpload = (file: File) => {
    const ext = file.name.toLowerCase().split(".").pop() || "";
    const isExtensionAllowed = allowedExtensions.includes(ext);
    const isSizeAllowed = file.size / 1024 / 1024 < 50;

    if (!isExtensionAllowed) {
      message.error(
        "Invalid file type. Accepted: JPG, PNG, PDF, EPS, AI, ZIP, etc."
      );
      return Upload.LIST_IGNORE;
    }
    if (!isSizeAllowed) {
      message.error("File must be smaller than 50MB!");
      return Upload.LIST_IGNORE;
    }
    return true;
  };

  const handleUploadChange = ({ fileList: incomingList }: { fileList: AntdUploadFile[] }) => {
    const uniqueFiles = Array.from(
      new Map(incomingList.map((f) => [f.uid, f])).values()
    );
    setFileList(uniqueFiles);
    onFileListChange(uniqueFiles);

    const hasUploading = uniqueFiles.some((f) => f.status === "uploading");
    setUploading(hasUploading);

    const allUploaded = uniqueFiles.every((f) => f.status === "done");
    const failedFiles = uniqueFiles.filter((f) => f.status === "error");

    if (failedFiles.length > 0) {
      failedFiles.forEach((f) =>
        message.error(f.response?.message || `${f.name} upload failed.`)
      );
      return;
    }

    if (allUploaded && uniqueFiles.length > 0) {
      setTimeout(() => {
        setUploading(false);
        processUploadSuccess(uniqueFiles, onUploadSuccess);
        message.success(
          uniqueFiles.length === 1
            ? `${uniqueFiles[0].name} uploaded successfully.`
            : "All files uploaded successfully."
        );
        handleClose();
      }, 500);
    }
  };

  const handleWebImageUrlChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { value } = e.target;
    if (!value) return;
    setImageUrl(value);
  };

  const handleWebImport = async () => {
    if (!imageUrl) return;

    try {
      setUploading(true);

      const { blob, error } = await imageUrlToBlob(imageUrl);

      if (!blob) {
        message.error(error || "Invalid image URL.");
        setUploading(false);
        return;
      }

      const formData = new FormData();
      formData.append("url", imageUrl);

      const uploadResponse = await fetch(finalUploadUrl, {
        method: "POST",
        body: formData,
      });

      const result = await uploadResponse.json();

      if (!uploadResponse.ok || result.error) {
        throw new Error(result.message || "Upload failed");
      }

      const countValue = fileList.length + 1;

      const uploadFile: UploadFileWithMethodCheck = {
        uid: `rc-upload-${Date.now()}-${countValue}`,
        name: "web-url",
        percent: 100,
        status: "done",
        url: result.url || imageUrl,
        thumbUrl: result.url || imageUrl,
        response: result,
        isMethodCheck: true,
      };

      const newFileList = [
        uploadFile,
        ...fileList.filter(
          (f) =>
            f.uid !== uploadFile.uid &&
            f.url !== uploadFile.url &&
            f.name !== uploadFile.name
        ),
      ];

      setFileList(newFileList);
      onFileListChange(newFileList);
      processUploadSuccess(newFileList, onUploadSuccess);

      setTimeout(() => {
        message.success("Image imported and uploaded successfully.");
        setImageUrl("");
        setIsImageValid(false);
        setUploading(false);
        handleClose();
      }, 100);
    } catch (error: any) {
      console.error("Failed to import/upload image:", error);
      message.error("Please enter valid URL.");
      setUploading(false);
    }
  };

  const handleTakePhoto = async (dataUri: string) => {
    try {
      setUploading(true);

      const blob = dataURItoBlob(dataUri);
      const mimeType = blob.type;
      const extension = mimeType.split("/")[1];

      const file = new File([blob], `photo_${Date.now()}.${extension}`, {
        type: mimeType,
        lastModified: Date.now(),
      }) as RcFile;

      if (file.size > 50 * 1024 * 1024) {
        message.error("File exceeds 50MB limit");
        setUploading(false);
        return;
      }

      const formData = new FormData();
      formData.append("file", file);

      const uploadResponse = await fetch(finalUploadUrl, {
        method: "POST",
        body: formData,
      });

      const result = await uploadResponse.json();

      if (!uploadResponse.ok || result.error) {
        throw new Error(result.message || "Upload failed");
      }

      const uploadFile: UploadFileWithMethodCheck = {
        uid: `rc-upload-${Date.now()}-${fileList.length + 1}`,
        lastModified: Date.now(),
        lastModifiedDate: new Date(),
        name: file.name,
        type: file.type,
        originFileObj: file,
        size: file.size,
        percent: 100,
        status: "done",
        url: result.url || "",
        thumbUrl: result.url || "",
        response: result,
        isMethodCheck: true,
      };

      const newFileList = [
        uploadFile,
        ...fileList.filter(
          (f) =>
            f.uid !== uploadFile.uid &&
            f.url !== uploadFile.url &&
            f.name !== uploadFile.name
        ),
      ];
      setFileList(newFileList);
      onFileListChange(newFileList);
      message.success("Photo uploaded successfully");
      processUploadSuccess(newFileList, onUploadSuccess);
      setTimeout(() => {
        setUploading(false);
        handleClose();
      }, 500);
    } catch (error: any) {
      console.error("Upload error:", error);
      message.error(error?.message || "Photo upload failed");
      setUploading(false);
    }
  };

  const dataURItoBlob = (dataURI: string): Blob => {
    const byteString = atob(dataURI.split(",")[1]);
    const mimeString = dataURI.split(",")[0].split(":")[1].split(";")[0];
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }
    return new Blob([ab], { type: mimeString });
  };

  function handleCameraError(error: Error) {
    if ((error as any).name === "NotAllowedError") {
      setCameraDenied(true);
      setCameraError(
        "Could not access the camera. Please make sure you've granted permission."
      );
    } else if ((error as any).name === "NotFoundError") {
      setCameraDenied(true);
      setCameraError(
        "No camera found on this device. Please connect a camera or try on a different device."
      );
    } else if ((error as any).name === "NotReadableError") {
      setCameraDenied(true);
      setCameraError(
        "Camera is already in use by another application. Please close it and try again."
      );
    } else {
      setCameraDenied(true);
      setCameraError((error as any).message || "Camera access failed.");
    }
  }
  const tabKeys = [
    "myFiles",
    "web",
    "camera",
    "dropbox",
    "onedrive",
  ];

  const switchTab = (direction: "next" | "prev") => {
    const currentIndex = tabKeys.indexOf(activeKey);
    const nextIndex =
      direction === "next"
        ? Math.min(currentIndex + 1, tabKeys.length - 1)
        : Math.max(currentIndex - 1, 0);
    setActiveKey(tabKeys[nextIndex]);
  };

  const TabIconLabel = ({
    icon,
    label,
  }: {
    icon: React.ReactNode;
    label: string;
  }) => (
    <StylesSpan className="space-tab">
      <span className="icon">{icon}</span>
      {label}
    </StylesSpan>
  );

  return (
    <StyledModal
      destroyOnClose
      title="Upload Artwork"
      open={visible}
      onCancel={handleClose}
      footer={
        <div className="ant-modal-content-footer">
          <p>
            We will email you a digital proof within 1 hour.We can create any
            design! For repeat orders, mention your old order number.Accepted
            file types: PNG, JPEG, JPG, AI, PDF, EXCEL, CSV, ZIP. Files must be less than 50 MB
            in size.
          </p>
        </div>
      }
      width={700}
    >
      <StyledTabs
        className="upload-artwork-tabs equal-tabs"
        activeKey={activeKey}
        onChange={setActiveKey}
        defaultActiveKey="myFiles"
        tabPosition="top"
        tabBarExtraContent={
          isMobile
            ? {
              left: (
                <Space>
                  <Button
                    icon={<LeftOutlined style={{ fontSize: 12 }} />}
                    onClick={() => switchTab("prev")}
                    disabled={tabKeys.indexOf(activeKey) === 0}
                    type="text"
                  />
                </Space>
              ),
              right: (
                <Space>
                  <Button
                    icon={<RightOutlined style={{ fontSize: 12 }} />}
                    onClick={() => switchTab("next")}
                    disabled={tabKeys.indexOf(activeKey) === tabKeys.length - 1}
                    type="text"
                  />
                </Space>
              ),
            }
            : undefined
        }
        items={[
          {
            key: "myFiles",
            label: <TabIconLabel icon={<DesktopOutlined />} label="My Files" />,
            children: (
              <Spin spinning={uploading} tip="Uploading...">
                <Upload.Dragger
                  name="file"
                  action={finalUploadUrl}
                  multiple
                  fileList={fileList}
                  beforeUpload={beforeUpload}
                  onChange={handleUploadChange}
                  openFileDialogOnClick={true}
                  showUploadList={false}
                  accept={allowedExtensions.map((ext) => `.${ext}`).join(",")}
                  disabled={uploading}
                >
                  <div style={{ textAlign: "center" }}>
                    {!isMobile && (
                      <>
                        <p className="ant-upload-drag-icon">
                          <DragAndDropIcon isPromoStore={isPromoStore()} src={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Promo-Multiple-File-Icon.webp" : "https://static.yardsignplus.com/storage/icons/hand-icon-ysp.svg"} alt="OneDrive" />
                        </p>
                        <p className="ant-upload-text">Drag and Drop</p>
                        <p className="ant-upload-hint">Or</p>
                      </>
                    )}
                    <StyledButton className="btn-ysp-upload">
                      Browse Files
                    </StyledButton>
                  </div>
                </Upload.Dragger>
              </Spin>
            ),
          },
          {
            key: "web",
            label: (
              <TabIconLabel icon={<GlobalOutlined />} label="Web Address" />
            ),
            children: (
              <Space direction="vertical" style={{ width: "100%" }}>
                <p className="ant-upload-hint-text">
                  Public URL of file to upload:
                </p>
                <Form>
                  <Form.Item
                    name="file"
                    rules={[
                      {
                        required: true,
                        message: "Please input your website!",
                      },
                      {
                        type: "url",
                        message: "Please enter a valid URL!",
                      },
                    ]}
                    style={{ marginBottom: '2%', width: '100%' }}
                  >
                    <Input
                      id='web-url-input'
                      name="file"
                      placeholder="https://www.yardsignplus.com/"
                      value={imageUrl}
                      onChange={handleWebImageUrlChange}
                      allowClear
                    />
                  </Form.Item>
                  <div style={{ display: "flex", justifyContent: "center" }}>
                    <StyledButton
                      className="btn-ysp-import"
                      loading={uploading}
                      onClick={handleWebImport}
                    >
                      Import Files
                    </StyledButton>
                  </div>
                </Form>

                {imageUrl && (
                  <img
                    src={imageUrl}
                    alt=""
                    style={{ display: "none" }}
                    onLoad={() => setIsImageValid(true)}
                    onError={() => setIsImageValid(false)}
                  />
                )}
              </Space>
            ),
          },
          {
            key: "camera",
            label: <TabIconLabel icon={<CameraOutlined />} label="Camera" />,
            children: (
              <Spin spinning={uploading} tip="Uploading...">
                <div>
                  {!cameraDenied ? (
                    <>
                      {isMobile && (
                        <CenteredWrapper className="camera-switch">
                          <CameraButton onClick={toggleFacingMode}>
                            Switch to {facingMode === "user" ? "Back" : "Front"}{" "}
                            Camera
                          </CameraButton>
                        </CenteredWrapper>
                      )}
                      <Camera
                        onTakePhoto={handleTakePhoto}
                        onCameraError={handleCameraError}
                        idealFacingMode={facingMode}
                        idealResolution={{ width: 640, height: 480 }}
                        isFullscreen={false}
                        sizeFactor={0.5}
                        isImageMirror={isMobile ? facingMode === "user" : true}
                      />
                      <CenteredWrapper>
                        <CameraButton onClick={handleClose}>Close</CameraButton>
                      </CenteredWrapper>
                    </>
                  ) : (
                    <CameraError>
                      <span> {cameraError || "Camera permission denied."} </span>
                    </CameraError>
                  )}
                </div>
              </Spin>
            ),
          },
          // {
          //   key: "gdrive",
          //   label: (
          //     <TabIconLabel icon={<SiGoogledrive />} label="Google Drive" />
          //   ),
          //   children: (
          //     <Spin spinning={uploading} tip="Uploading...">
          //       <GoogleDrivePicker
          //         onFileSelected={(file) => {
          //           handleGoogleDriveFileUpload({
          //             file,
          //             fileList,
          //             onFileListChange,
          //             uploadUrl: config.links.upload_custom_design,
          //             onClose,
          //             setUploading,
          //           });
          //         }}
          //       />
          //     </Spin>
          //   ),
          // },
          {
            key: "dropbox",
            label: <TabIconLabel icon={<DropboxOutlined />} label="Dropbox" />,
            children: (
              <Spin spinning={uploading} tip="Uploading...">
                <DropboxPicker
                  onFileSelected={(file) => {
                    handleDropboxFileUpload({
                      file,
                      fileList,
                      onFileListChange,
                      uploadUrl: finalUploadUrl,
                      onClose,
                      setUploading,
                      onUploadSuccess
                    });
                  }}
                />
              </Spin>
            ),
          },
          {
            key: "onedrive",
            label: (
              <TabIconLabel icon={<OneDriveIcon src="https://static.yardsignplus.com/storage/icons/onedrive.svg" alt="OneDrive" />} label="OneDrive" />
            ),
            children: (
              <Spin spinning={uploading} tip="Uploading...">
                <OneDrivePicker
                  onFileSelected={(file) => {
                    handleOneDriveFileUpload({
                      file,
                      fileList,
                      onFileListChange,
                      uploadUrl: finalUploadUrl,
                      onClose,
                      setUploading,
                      onUploadSuccess,
                    });
                  }}
                />
              </Spin>
            ),
          },
          // {
          //   key: "googleImage",
          //   label: (
          //     <TabIconLabel icon={<GoogleOutlined />} label="Image Search" />
          //   ),
          //   children: (
          //     <Spin spinning={uploading} tip="Uploading...">
          //       <GoogleImageSearch
          //         uploadUrl={config.links.upload_custom_design}
          //         fileList={fileList}
          //         onFileListChange={onFileListChange}
          //         setUploading={setUploading}
          //         onClose={onClose}
          //       />
          //     </Spin>
          //   ),
          // },
        ]}
      />
    </StyledModal>
  );
};

export default UploadSourceModal;
