import { message } from "antd";
import { GoogleImageResult } from "./GoogleImageSearch";

interface UploadParams {
  image: GoogleImageResult;
  fileList: any[];
  onFileListChange: (files: any[]) => void;
  uploadUrl: string;
  onClose: () => void;
  setUploading: (val: boolean) => void;
}

export const handleGoogleImageUpload = async ({
  image,
  fileList,
  onFileListChange,
  uploadUrl,
  onClose,
  setUploading,
}: UploadParams) => {
  try {
    setUploading(true);
    const imageUrl = image.link;
    const formData = new FormData();
    formData.append("url", imageUrl);
    const res = await fetch(uploadUrl, {
      method: "POST",
      body: formData,
    });

    const result = await res.json();
    if (!result.success) {
      message.error(result.message || "Upload failed. Please try again.");
      return;
    }

    const countValue = fileList.length + 1;
    const uploadFile = {
      uid: `rc-upload-${Date.now()}-${countValue}`,
      lastModified: Date.now(),
      lastModifiedDate: new Date(),
      name: "google-image.jpg",
      percent: 100,
      status: "done",
      url: result.url || "",
      thumbUrl: result.url || "",
      response: result,
      isMethodCheck: true,
    };

    onFileListChange([uploadFile, ...fileList]);
    onClose();
  } catch (error) {
    console.error("Google image upload failed", error);
    message.error("Failed to upload Google image.");
  } finally {
    setUploading(false);
  }
};
