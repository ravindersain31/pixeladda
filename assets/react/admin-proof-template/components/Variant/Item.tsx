import React from "react";
import { Button, Card, Form, Row, Col, Select, Upload, Space, Typography } from "antd";
import { UploadOutlined, DeleteOutlined, PlusOutlined } from "@ant-design/icons";
import ProofFrameTemplateForm from "./Frame";
import { isMobile } from "react-device-detect";

const { Text } = Typography;

interface FrameTemplate {
    id?: number;
    frameType: string;
    imageFile: any;
    imageUrl?: string;
    _action?: 'create' | 'update' | 'delete';
}

interface ProofTemplate {
    id?: number;
    size: string;
    imageFile: any;
    imageUrl?: string;
    proofFrameTemplates: FrameTemplate[];
    _action?: 'create' | 'update' | 'delete';
}

interface Props {
    variant: ProofTemplate;
    variantIndex: number;
    onChange: (data: ProofTemplate) => void;
    onRemove: () => void;
    onFrameDelete: (frameId: number) => void;
    variantChoices: any; // Can be array or object
    frameVariants: any[];
    validateImageFile: (file: File, fieldName: string) => boolean;
    selectedSizes: string[];
    getSelectedFrameTypes: (frameIndex: number) => string[];
}

const ProofTemplateVariantForm: React.FC<Props> = ({
    variant,
    variantIndex,
    onChange,
    onRemove,
    onFrameDelete,
    variantChoices,
    frameVariants,
    validateImageFile,
    selectedSizes,
    getSelectedFrameTypes
}) => {
    const handleChange = (key: string, value: any) => {
        onChange({ ...variant, [key]: value });
    };

    const addFrame = () => {
        const newFrames = [
            ...variant.proofFrameTemplates,
            { frameType: "", imageFile: null, _action: 'create' as const },
        ];
        handleChange("proofFrameTemplates", newFrames);
    };

    const updateFrame = (frameIndex: number, updated: FrameTemplate) => {
        const newFrames = variant.proofFrameTemplates.map((f, i) =>
            i === frameIndex ? updated : f
        );
        handleChange("proofFrameTemplates", newFrames);
    };

    const removeFrame = (frameIndex: number) => {
        const frame = variant.proofFrameTemplates[frameIndex];
        if (frame.id) {
            onFrameDelete(frame.id);
        }
        const newFrames = variant.proofFrameTemplates.filter((_, i) => i !== frameIndex);
        handleChange("proofFrameTemplates", newFrames);
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

    // Convert variantChoices to array of options
    const getVariantOptions = () => {
        let options: any[] = [];

        if (Array.isArray(variantChoices)) {
            options = variantChoices.map(v => ({ value: v, label: v }));
        } else {
            options = Object.values(variantChoices).map((v: any) => ({ value: v, label: v }));
        }

        // Mark already selected sizes as disabled
        return options.map(option => ({
            ...option,
            disabled: selectedSizes.includes(option.value),
        }));
    };

    return (
         <Card
            title={
                <Space direction={isMobile ? "vertical" : "horizontal"} size="small" style={{ width: '100%' }}>
                    <Text strong style={{ fontSize: isMobile ? 14 : 16 }}>Variant #{variantIndex + 1}</Text>
                    {variant.id && (
                        <Text type="secondary" style={{ fontSize: 11 }}>
                            (ID: {variant.id})
                        </Text>
                    )}
                    {variant._action && (
                        <Text type="warning" style={{ fontSize: 11 }}>
                            {variant._action === 'create' ? '• New' : '• Modified'}
                        </Text>
                    )}
                </Space>
            }
            extra={
                <Button
                    type="text"
                    danger
                    size="small"
                    icon={<DeleteOutlined />}
                    onClick={onRemove}
                >
                    {!isMobile && "Remove"}
                </Button>
            }
            size={isMobile ? "small" : "default"}
            style={{ height: '100%' }}
        >
            <Form layout="vertical" size="small">
                <Row gutter={[8, 8]}>
                    <Col xs={24} sm={12}>
                        <Form.Item label="Template Size" required style={{ marginBottom: 12 }}>
                            <Select
                                value={variant.size}
                                onChange={(v) => handleChange("size", v)}
                                placeholder="Choose size"
                                options={getVariantOptions()}
                                popupMatchSelectWidth={false}
                                size={isMobile ? "middle" : "small"}
                            />
                        </Form.Item>
                    </Col>
                    <Col xs={24} sm={12}>
                        <Form.Item label="Template Image" style={{ marginBottom: 12 }}>
                            <Upload
                                listType="picture"
                                beforeUpload={(file) => {
                                    if (validateImageFile(file, `Variant #${variantIndex + 1} image`)) {
                                        handleChange("imageFile", file);
                                    }
                                    return false;
                                }}
                                onRemove={() => handleChange("imageFile", null)}
                                fileList={getFileList(variant.imageFile)}
                                maxCount={1}
                            >
                                <Button 
                                    icon={<UploadOutlined />} 
                                    size="small"
                                    block={isMobile}
                                >
                                    {variant.imageFile ? 'Change' : 'Upload'}
                                </Button>
                            </Upload>
                        </Form.Item>
                    </Col>
                </Row>

                <div style={{ marginTop: 12, marginBottom: 8 }}>
                    <div style={{ 
                        display: 'flex', 
                        flexDirection: isMobile ? 'column' : 'row',
                        justifyContent: 'space-between', 
                        alignItems: isMobile ? 'stretch' : 'center',
                        gap: 8
                    }}>
                        <Text strong style={{ fontSize: isMobile ? 13 : 14 }}>Frame Types</Text>
                        <Button
                            type="dashed"
                            size="small"
                            onClick={addFrame}
                            icon={<PlusOutlined />}
                            block={isMobile}
                        >
                            Add Stake
                        </Button>
                    </div>
                </div>

                <div style={{ maxHeight: isMobile ? 300 : 400, overflowY: 'auto' }}>
                    {variant.proofFrameTemplates.map((frame, fIndex) => (
                        <ProofFrameTemplateForm
                            key={fIndex}
                            frame={frame}
                            onChange={(updated) => updateFrame(fIndex, updated)}
                            onRemove={() => removeFrame(fIndex)}
                            frameVariants={frameVariants}
                            validateImageFile={validateImageFile}
                            variantIndex={variantIndex}
                            frameIndex={fIndex}
                            selectedFrameTypes={getSelectedFrameTypes(fIndex)}
                        />
                    ))}
                    {variant.proofFrameTemplates.length === 0 && (
                        <div style={{
                            textAlign: 'center',
                            padding: isMobile ? 16 : 20,
                            color: '#999',
                            background: '#fafafa',
                            borderRadius: 4,
                            fontSize: isMobile ? 12 : 14
                        }}>
                            No frame types added yet
                        </div>
                    )}
                </div>
            </Form>
        </Card>
    );
};

export default ProofTemplateVariantForm;