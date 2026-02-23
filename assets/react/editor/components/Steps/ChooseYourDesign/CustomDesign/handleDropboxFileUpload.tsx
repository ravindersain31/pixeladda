import { message } from "antd";
import { processUploadSuccess } from "./helper";
import type { UploadFile as AntdUploadFile } from "antd/es/upload/interface";

export const handleDropboxFileUpload = async ({
  file,
  fileList,
  onFileListChange,
  uploadUrl,
  onClose,
  setUploading,
  onUploadSuccess,
}: {
  file: any;
  fileList: any[];
  onFileListChange: (list: any[]) => void;
  uploadUrl: string;
  onClose: () => void;
  setUploading: (val: boolean) => void;
  onUploadSuccess?: (file: AntdUploadFile) => void
}) => {
  try {
    setUploading(true);
    const blob = await fetch(file.link).then((res) => res.blob());
    const formData = new FormData();
    formData.append("file", blob, file.name);

    const res = await fetch(uploadUrl, {
      method: "POST",
      body: formData,
    });

    const result = await res.json();

    if (!res.ok || result.error) {
      throw new Error(result.message || "Upload failed");
    }

    const countValue = fileList.length + 1;
    const uploadFile = {
      uid: `rc-upload-${Date.now()}-${countValue}`,
      lastModified: Date.now(),
      lastModifiedDate: new Date(),
      name: file.name,
      size: file.bytes,
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
    setTimeout(() => {
    setUploading(false);
      message.success("File uploaded successfully.");
      onClose();
    }, 100);
  } catch (error: any) {
    console.error("Dropbox upload failed", error);
    message.error(error.message || "Dropbox upload failed");
  }
};