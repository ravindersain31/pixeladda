import React from "react";
import { Button, Card, Form, Select, Upload, Space, Typography } from "antd";
import { UploadOutlined, DeleteOutlined } from "@ant-design/icons";
import { isMobile } from "react-device-detect";

const { Text } = Typography;

interface WireStakeTemplate {
    id?: number;
    wireStakeType: string;
    imageFile: any;
    imageUrl?: string;
    _action?: 'create' | 'update' | 'delete';
}

interface Props {
    wireStake: WireStakeTemplate;
    index: number;
    onChange: (data: WireStakeTemplate) => void;
    onRemove: () => void;
    frameVariants: any[];
    validateImageFile: (file: File, fieldName: string) => boolean;
    selectedTypes: string[];
}

const ProofWireStakeTemplateItem: React.FC<Props> = ({
    wireStake,
    index,
    onChange,
    onRemove,
    frameVariants,
    validateImageFile,
    selectedTypes
}) => {
    const handleChange = (key: string, value: any) => {
        const updated = { ...wireStake, [key]: value };

        if (wireStake.id && !wireStake._action) {
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

    const getWireStakeOptions = () => {
        if (!Array.isArray(frameVariants)) return [];

        return frameVariants.map(v => ({
            value: v.name,
            label: v.label,
            disabled: selectedTypes.includes(v.name),
        }));
    };

    return (
        <Card
            title={
                <Space direction="vertical" size={2} style={{ width: '100%' }}>
                    <Text strong style={{ fontSize: isMobile ? 13 : 14 }}>Wire Stake #{index + 1}</Text>
                    {wireStake.id && (
                        <Text type="secondary" style={{ fontSize: 10 }}>
                            ID: {wireStake.id}
                        </Text>
                    )}
                    {wireStake._action && (
                        <Text type="warning" style={{ fontSize: 10 }}>
                            {wireStake._action === 'create' ? 'New' : 'Modified'}
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
                />
            }
            size="small"
            style={{ height: '100%' }}
        >
            <Form layout="vertical" size="small">
                <Form.Item label="Wire Stake Type" required style={{ marginBottom: 12 }}>
                    <Select
                        value={wireStake.wireStakeType}
                        onChange={(v) => handleChange("wireStakeType", v)}
                        placeholder="Choose type"
                        options={getWireStakeOptions()}
                        popupMatchSelectWidth={false}
                        size="small"
                    />
                </Form.Item>
                <Form.Item label="Template Image" style={{ marginBottom: 0 }}>
                    <Upload
                        listType="picture"
                        beforeUpload={(file) => {
                            if (validateImageFile(file, `Wire Stake #${index + 1} image`)) {
                                handleChange("imageFile", file);
                            }
                            return false;
                        }}
                        onRemove={() => handleChange("imageFile", null)}
                        fileList={getFileList(wireStake.imageFile)}
                        maxCount={1}
                    >
                        <Button 
                            icon={<UploadOutlined />} 
                            size="small"
                            block
                        >
                            {wireStake.imageFile ? 'Change' : 'Upload'}
                        </Button>
                    </Upload>
                </Form.Item>
            </Form>
        </Card>
    );
};

export default ProofWireStakeTemplateItem;