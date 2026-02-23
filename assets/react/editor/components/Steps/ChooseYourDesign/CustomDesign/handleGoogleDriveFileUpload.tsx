import { message } from "antd";
import { useState } from "react";

interface UploadParams {
  file: any;
  fileList: any[];
  onFileListChange: (files: any[]) => void;
  uploadUrl: string;
  onClose: () => void;
  setUploading: (val: boolean) => void;
  token?: string;
}

export const handleGoogleDriveFileUpload = async ({
  file,
  fileList,
  onFileListChange,
  uploadUrl,
  onClose,
  setUploading,
}: Omit<UploadParams, "token">) => {
  try {
    setUploading(true);
    const tokenInput = document.querySelector<HTMLInputElement>(
      'input[name="googleToken"]'
    );
    const token = tokenInput?.value;
    if (!token) {
      message.error("Google token not found.");
      setUploading(false);
      return;
    }

    const response = await fetch(
      `https://www.googleapis.com/drive/v3/files/${file.id}?alt=media`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      }
    );

    const blob = await response.blob();
    const filename = file.name || "google-drive-file";

    const fileObj = new File([blob], filename, {
      type: blob.type || "application/octet-stream",
      lastModified: Date.now(),
    });

    const formData = new FormData();
    formData.append("file", fileObj, filename);

    const uploadResponse = await fetch(uploadUrl, {
      method: "POST",
      body: formData,
    });

    const result = await uploadResponse.json();
    if (!result.success) {
      throw new Error(result.message);
    }

    const countValue = fileList.length + 1;
    const uploadFile = {
      uid: `rc-upload-${Date.now()}-${countValue}`,
      lastModified: Date.now(),
      lastModifiedDate: new Date(),
      name: filename,
      size: fileObj.size,
      type: fileObj.type,
      originFileObj: fileObj,
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
    setTimeout(() => {
      setUploading(false);
      message.success("File uploaded successfully.");
      onClose();
    }, 100);
  } catch (error: any) {
    console.error("Upload failed:", error);
    setUploading(false);
    message.error(error.message || "Failed to upload file.");
  }
};