import React, { useEffect, useState } from "react";
import { Button, Card, Select, Upload, message, Space, Typography, List, Tag } from "antd";
import { UploadOutlined, DeleteOutlined, CloudUploadOutlined, CopyOutlined } from "@ant-design/icons";
import type { UploadFile } from "antd/es/upload/interface";
import axios from "axios";
import { StyledList, StyledUpload } from "./styled";

const { Title, Text } = Typography;

interface Storages {
    key: string;
    label: string;
    path: string;
    storage: string;
}

interface StorageOption {
    value: string;
    label: React.ReactNode;
}

interface UploadedFileInfo {
    originalName: string;
    url: string;
    size: number;
    mimeType: string;
}

interface UploadResponse {
    success: boolean;
    uploaded: UploadedFileInfo[];
    errors: Array<{ file: string; error: string }>;
    summary: {
        total: number;
        successful: number;
        failed: number;
        storage: string;
    };
}

const MAX_FILE_SIZE = 6 * 1024 * 1024; // 6MB

interface MultipleFileUploadProps {
    upload_multiple_file: string,
    storages: Storages[],
    allowedFileTypes: string[],
    maxFileSize: string
}

const MultipleFileUpload = ({ upload_multiple_file, storages, allowedFileTypes }: MultipleFileUploadProps) => {
    const [fileList, setFileList] = useState<UploadFile[]>([]);
    const [storage, setStorage] = useState<string>("defaultStorage");
    const [storageOptions, setStorageOptions] = useState<StorageOption[]>(
        Object.values(storages || {}).map((s) => ({ value: s.key, label: (<div><strong>{s.label}</strong><span style={{ fontSize: 12, color: '#888' }}>&nbsp;({s.path})</span></div>) }))
    );

    const [loading, setLoading] = useState(false);
    const [uploadedFiles, setUploadedFiles] = useState<UploadedFileInfo[]>([]);

    const validateFile = (file: File): boolean => {
        const ext = file.name.split('.').pop()?.toLowerCase();

        if (!ext || !allowedFileTypes.includes(ext)) {
            message.error(`${file.name}: File type not allowed`);
            return false;
        }
        if (file.size > MAX_FILE_SIZE) {
            message.error(
                `${file.name}: File size must be less than 6MB (current: ${(
                    file.size /
                    1024 /
                    1024
                ).toFixed(2)}MB)`
            );
            return false;
        }

        return true;
    };

    const handleBeforeUpload = (file: File) => {
        if (!validateFile(file)) {
            return Upload.LIST_IGNORE;
        }
        return false;
    };

    const handleFileChange = ({ fileList: newFileList }: { fileList: UploadFile[] }) => {
        setFileList(newFileList);
    };

    const handleUpload = async () => {
        if (fileList.length === 0) {
            message.warning("Please select files to upload");
            return;
        }

        setLoading(true);
        const formData = new FormData();

        fileList.forEach((file) => {
            if (file.originFileObj) {
                formData.append("files[]", file.originFileObj);
            }
        });

        formData.append("storage", storage);

        try {
            const response = await axios.post<UploadResponse>(
                upload_multiple_file,
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                }
            );

            if (response.data.success) {
                const { summary, uploaded, errors } = response.data;

                message.success(
                    `Successfully uploaded ${summary.successful} of ${summary.total} files`
                );

                if (errors.length > 0) {
                    errors.forEach((err) => {
                        message.error(`${err.file}: ${err.error}`);
                    });
                }

                setUploadedFiles([...uploadedFiles, ...uploaded]);
                setFileList([]);
            } else {
                message.error("Upload failed");
            }
        } catch (error: any) {
            console.error("Upload error:", error);
            message.error(error.response?.data?.error || "Upload failed");
        } finally {
            setLoading(false);
        }
    };

    const handleRemoveFile = (file: UploadFile) => {
        const newFileList = fileList.filter((f) => f.uid !== file.uid);
        setFileList(newFileList);
    };

    const handleClearAll = () => {
        setFileList([]);
    };

    const formatFileSize = (bytes: number): string => {
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + " KB";
        return (bytes / (1024 * 1024)).toFixed(2) + " MB";
    };

    const handleCopyUrl = (url: string, fileName: string) => {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url)
                .then(() => {
                    message.success(`URL copied: ${fileName}`);
                })
                .catch(() => {
                    message.error("Failed to copy URL");
                });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                message.success(`URL copied: ${fileName}`);
            } catch (err) {
                message.error("Failed to copy URL");
            }
            document.body.removeChild(textArea);
        }
    };

    return (
        <div style={{ padding: "24px", maxWidth: 1200, margin: "0 auto" }}>
            <Title level={2}>Multiple File Upload</Title>

            <Card style={{ marginBottom: 24 }}>
                <Space direction="vertical" size="large" style={{ width: "100%" }}>
                    {/* Storage Selection */}
                    <div>
                        <Text strong style={{ display: "block", marginBottom: 8 }}>
                            Select Storage Location:
                        </Text>
                        <Select
                            value={storage}
                            onChange={setStorage}
                            options={storageOptions}
                            style={{ width: "100%", maxWidth: 400 }}
                            disabled={loading}
                        />
                    </div>

                    {/* File Upload */}
                    <div>
                        <Text strong style={{ display: "block", marginBottom: 8 }}>
                            Select Files:
                        </Text>
                        <StyledUpload
                            multiple
                            listType="picture"
                            fileList={fileList}
                            beforeUpload={handleBeforeUpload}
                            onChange={handleFileChange}
                            onRemove={handleRemoveFile}
                        >
                            <Button icon={<UploadOutlined />} disabled={loading}>
                                Select Files (Max 6MB each)
                            </Button>
                        </StyledUpload>
                        <Text type="secondary" style={{ display: "block", marginTop: 8 }}>

                            {allowedFileTypes.length > 0
                                ? `Allowed file types: ${allowedFileTypes.join(", ")}`
                                : "All file types are allowed."}
                        </Text>
                    </div>

                    {/* Action Buttons */}
                    {fileList.length > 0 && (
                        <div style={{ marginTop: 16, display: "flex", gap: 16, flexWrap: "wrap" }}>
                            <Button
                                type="primary"
                                icon={<CloudUploadOutlined />}
                                onClick={handleUpload}
                                loading={loading}
                                size="large"
                                style={{ flex: "1 1 200px" }}
                            >
                                Upload {fileList.length} File{fileList.length > 1 ? "s" : ""}
                            </Button>
                            <Button
                                icon={<DeleteOutlined />}
                                onClick={handleClearAll}
                                disabled={loading}
                                size="large"
                                style={{ flex: "1 1 200px" }}
                            >
                                Clear All
                            </Button>
                        </div>
                    )}
                </Space>
            </Card>

            {/* Uploaded Files List */}
            {uploadedFiles.length > 0 && (
                <Card
                    bodyStyle={{ padding: "10px 20px" }}
                    title="Successfully Uploaded Files"
                    style={{ marginTop: 24 }}
                >
                    <StyledList
                        grid={{ gutter: 16, column: 4 }}
                        dataSource={uploadedFiles}
                        renderItem={(file: any) => (
                            <List.Item>
                                {file.mimeType === "application/pdf" ? (
                                    <div className="pdf-box">PDF</div>
                                ) : (
                                    <img src={file.url} alt={file.originalName} />
                                )}

                                <div>
                                    <a href={file.url} target="_blank" rel="noopener noreferrer">
                                        {file.originalName}
                                    </a>
                                    <div style={{ marginTop: 4 }}>
                                        <Tag color="blue">{formatFileSize(file.size)}</Tag>
                                        <Tag>{file.mimeType}</Tag>
                                    </div>
                                    <Button
                                        type="default"
                                        size="small"
                                        icon={<CopyOutlined />}
                                        onClick={() => handleCopyUrl(file.url, file.originalName)}
                                        style={{ 
                                            marginTop: 8, 
                                            borderColor: '#1890ff',
                                            color: '#1890ff',
                                            fontSize: '12px'
                                        }}
                                    >
                                        Copy URL
                                    </Button>
                                </div>

                            </List.Item>
                        )}
                    />

                </Card>
            )}
        </div>
    );
};

export default MultipleFileUpload;