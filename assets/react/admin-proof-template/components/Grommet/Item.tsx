import React from "react";
import { Button, Card, Form, Col, Select, Upload, Space, Typography } from "antd";
import { UploadOutlined, DeleteOutlined } from "@ant-design/icons";
import { isMobile } from "react-device-detect";

const { Text } = Typography;

interface GrommetTemplate {
    id?: number;
    grommetColor: string;
    imageFile: any;
    imageUrl?: string;
    _action?: 'create' | 'update' | 'delete';
}

interface Props {
    grommet: GrommetTemplate;
    index: number;
    onChange: (data: GrommetTemplate) => void;
    onRemove: () => void;
    grommetColors: any[];
    validateImageFile: (file: File, fieldName: string) => boolean;
    selectedColors: string[];
}

const ProofGrommetTemplateItem: React.FC<Props> = ({
    grommet,
    index,
    onChange,
    onRemove,
    grommetColors,
    validateImageFile,
    selectedColors
}) => {
    const handleChange = (key: string, value: any) => {
        const updated = { ...grommet, [key]: value };

        if (grommet.id && !grommet._action) {
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

    const getGrommetColorOptions = () => {
        if (!Array.isArray(grommetColors)) return [];

        return grommetColors.map(color => ({
            value: color.name,
            label: color.label,
            disabled: selectedColors.includes(color.name),
        }));
    };

    return (
        <Card
            title={
                <Space direction="vertical" size={2} style={{ width: '100%' }}>
                    <Text strong style={{ fontSize: isMobile ? 13 : 14 }}>Grommet #{index + 1}</Text>
                    {grommet.id && (
                        <Text type="secondary" style={{ fontSize: 10 }}>
                            ID: {grommet.id}
                        </Text>
                    )}
                    {grommet._action && (
                        <Text type="warning" style={{ fontSize: 10 }}>
                            {grommet._action === 'create' ? 'New' : 'Modified'}
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
                <Form.Item label="Grommet Color" required style={{ marginBottom: 12 }}>
                    <Select
                        value={grommet.grommetColor}
                        onChange={(v) => handleChange("grommetColor", v)}
                        placeholder="Choose color"
                        options={getGrommetColorOptions()}
                        popupMatchSelectWidth={false}
                        size="small"
                    />
                </Form.Item>
                <Form.Item label="Template Image" style={{ marginBottom: 0 }}>
                    <Upload
                        listType="picture"
                        beforeUpload={(file) => {
                            if (validateImageFile(file, `Grommet #${index + 1} image`)) {
                                handleChange("imageFile", file);
                            }
                            return false;
                        }}
                        onRemove={() => handleChange("imageFile", null)}
                        fileList={getFileList(grommet.imageFile)}
                        maxCount={1}
                    >
                        <Button 
                            icon={<UploadOutlined />} 
                            size="small"
                            block
                        >
                            {grommet.imageFile ? 'Change' : 'Upload'}
                        </Button>
                    </Upload>
                </Form.Item>
            </Form>
        </Card>
    );
};

export default ProofGrommetTemplateItem;