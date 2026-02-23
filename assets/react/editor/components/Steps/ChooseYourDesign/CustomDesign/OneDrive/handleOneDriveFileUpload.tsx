import { message } from "antd";
import { OneDriveFile } from "./OneDrivePicker";
import type { UploadFile as AntdUploadFile } from "antd/es/upload/interface";
import { processUploadSuccess } from "../helper";

interface UploadParams {
  file: OneDriveFile;
  fileList: any[];
  onFileListChange: (files: any[]) => void;
  uploadUrl: string;
  onClose: () => void;
  setUploading: (val: boolean) => void;
  onUploadSuccess?: (file: AntdUploadFile) => void
}

export const handleOneDriveFileUpload = async ({
  file,
  fileList,
  onFileListChange,
  uploadUrl,
  onClose,
  setUploading,
  onUploadSuccess
}: UploadParams) => {
  try {
    setUploading(true);

    if (!file["@microsoft.graph.downloadUrl"]) {
      message.error("No download URL found for OneDrive file.");
      return;
    }

    const blob = await (
      await fetch(file["@microsoft.graph.downloadUrl"])
    ).blob();

    const formData = new FormData();
    formData.append("file", blob, file.name);

    const res = await fetch(uploadUrl, {
      method: "POST",
      body: formData,
    });

    const result = await res.json();

    const countValue = fileList.length + 1;
    const uploadFile = {
      uid: `rc-upload-${Date.now()}-${countValue}`,
      lastModified: Date.now(),
      lastModifiedDate: new Date(),
      name: file.name,
      size: blob.size,
      type: blob.type,
      originFileObj: blob,
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

    onFileListChange(newFileList);
    processUploadSuccess(newFileList, onUploadSuccess);
    onClose();
  } catch (error) {
    console.error("Upload failed", error);
    message.error("Failed to upload OneDrive file.");
  } finally {
    setUploading(false);
  }
};