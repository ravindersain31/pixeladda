import React from "react";
import { Button, Card, Form, Row, Col, Select, Upload, Space, Typography } from "antd";
import { UploadOutlined, DeleteOutlined } from "@ant-design/icons";
import { isMobile } from "react-device-detect";

const { Text } = Typography;

interface FrameTemplate {
    id?: number;
    frameType: string;
    imageFile: any;
    imageUrl?: string;
    _action?: 'create' | 'update' | 'delete';
}

interface Props {
    frame: FrameTemplate;
    onChange: (data: FrameTemplate) => void;
    onRemove: () => void;
    frameVariants: any[];
    validateImageFile: (file: File, fieldName: string) => boolean;
    variantIndex: number;
    frameIndex: number;
    selectedFrameTypes: string[];
}

const ProofFrameTemplateForm: React.FC<Props> = ({
    frame,
    onChange,
    onRemove,
    frameVariants,
    validateImageFile,
    variantIndex,
    frameIndex,
    selectedFrameTypes
}) => {
    const handleChange = (key: string, value: any) => {
        const updated = { ...frame, [key]: value };

        // Mark as updated if it has an ID
        if (frame.id && !frame._action) {
            updated._action = 'update';
        }

        onChange(updated);
    };

    const getFileList = (file: any) => {
        if (!file) return [];
        if (file instanceof File) {
            return [{
                uid: '-1',
                name: file.name,
                status: 'done' as const,
                url: URL.createObjectURL(file),
            }];
        }
        if (file.url) {
            return [{
                uid: '-1',
                name: file.name || 'Image',
                status: 'done' as const,
                url: file.url,
            }];
        }
        return [];
    };

    // Get frame variant options from the name property
    const getFrameOptions = () => {
        if (!Array.isArray(frameVariants)) return [];

        return frameVariants.map(v => ({
            value: v.name,
            label: v.label,
            disabled: selectedFrameTypes.includes(v.name),
        }));
    };

    return (
        <Card
            size="small"
            style={{
                marginBottom: 8,
                background: "#fafafa",
            }}
            extra={
                <Space size="small">
                    {!isMobile && frame.id && (
                        <Text type="secondary" style={{ fontSize: 10 }}>
                            ID: {frame.id}
                        </Text>
                    )}
                    {!isMobile && frame._action && (
                        <Text type="warning" style={{ fontSize: 10 }}>
                            {frame._action === 'create' ? 'New' : 'Modified'}
                        </Text>
                    )}
                    <Button
                        type="text"
                        danger
                        size="small"
                        icon={<DeleteOutlined />}
                        onClick={onRemove}
                    />
                </Space>
            }
        >
            <Row gutter={[8, 8]}>
                <Col xs={24} sm={12}>
                    <Form.Item label="Frame Type" style={{ marginBottom: isMobile ? 8 : 8 }}>
                        <Select
                            value={frame.frameType}
                            onChange={(v) => handleChange("frameType", v)}
                            placeholder="Choose type"
                            size="small"
                            options={getFrameOptions()}
                            popupMatchSelectWidth={false}
                        />
                    </Form.Item>
                </Col>
                <Col xs={24} sm={12}>
                    <Form.Item label="Image" style={{ marginBottom: isMobile ? 8 : 8 }}>
                        <Upload
                            listType="picture"
                            beforeUpload={(file) => {
                                if (validateImageFile(file, `V${variantIndex + 1} Frame${frameIndex + 1}`)) {
                                    handleChange("imageFile", file);
                                }
                                return false;
                            }}
                            onRemove={() => handleChange("imageFile", null)}
                            fileList={getFileList(frame.imageFile)}
                            maxCount={1}
                        >
                            <Button 
                                icon={<UploadOutlined />} 
                                size="small"
                                block={isMobile}
                            >
                                {frame.imageFile ? 'Change' : 'Upload'}
                            </Button>
                        </Upload>
                    </Form.Item>
                </Col>
            </Row>
        </Card>
    );
};

export default ProofFrameTemplateForm;