import React, { useEffect, useState } from "react";
import { Button, Form, message, Row, Col, Space, Typography, Card } from "antd";
import { PlusOutlined, SaveOutlined } from "@ant-design/icons";
import ProofTemplateVariantForm from "./Item";
import axios from "axios";

const { Title, Text } = Typography;

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
    initialTemplates: any[];
    variantChoices: string[];
    frameVariants: any[];
}

export const MAX_FILE_SIZE = 6 * 1024 * 1024; // 6MB in bytes
export const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

const ProofTemplateForm = ({ initialTemplates = [], variantChoices = [], frameVariants = [] }: Props) => {
    const [variants, setVariants] = useState<ProofTemplate[]>([]);
    const [loading, setLoading] = useState(false);
    const [deletedVariants, setDeletedVariants] = useState<number[]>([]);
    const [deletedFrames, setDeletedFrames] = useState<number[]>([]);

    useEffect(() => {
        if (initialTemplates?.length) {
            const normalized = initialTemplates.map((tpl) => ({
                id: tpl.id,
                size: tpl.size,
                imageFile: tpl.imageUrl ? {
                    uid: `server-${tpl.id}`,
                    name: tpl.size || "template.png",
                    status: "done",
                    url: tpl.imageUrl,
                } : null,
                imageUrl: tpl.imageUrl,
                proofFrameTemplates: (tpl.proofFrameTemplates || []).map((frame: any) => ({
                    id: frame.id,
                    frameType: frame.frameType,
                    imageFile: frame.imageUrl ? {
                        uid: `server-frame-${frame.id}`,
                        name: frame.frameType || "frame.png",
                        status: "done",
                        url: frame.imageUrl,
                    } : null,
                    imageUrl: frame.imageUrl,
                })),
            }));
            setVariants(normalized);
        }
    }, [initialTemplates]);

    const addVariant = () => {
        setVariants([
            ...variants,
            {
                size: "",
                imageFile: null,
                proofFrameTemplates: [],
                _action: 'create'
            }
        ]);
    };

    const removeVariant = (index: number) => {
        const variant = variants[index];
        if (variant.id) {
            setDeletedVariants([...deletedVariants, variant.id]);
        }
        setVariants(variants.filter((_, i) => i !== index));
    };

    const getSelectedSizes = (currentIndex: number): string[] => {
        return variants
            .filter((_, index) => index !== currentIndex)
            .map(v => v.size)
            .filter(Boolean);
    };

    // Get selected frame types for a specific variant
    const getSelectedFrameTypes = (variantIndex: number, currentFrameIndex: number): string[] => {
        return variants[variantIndex].proofFrameTemplates
            .filter((_, index) => index !== currentFrameIndex)
            .map(f => f.frameType)
            .filter(Boolean);
    };

    const handleVariantChange = (index: number, updatedVariant: ProofTemplate) => {
        const newVariants = [...variants];

        for (let i = 0; i < updatedVariant.proofFrameTemplates.length; i++) {
            const frame = updatedVariant.proofFrameTemplates[i];
            if (frame.imageFile instanceof File) {
                if (!validateImageFile(frame.imageFile, `Variant #${index + 1}, Frame #${i + 1} image`)) {
                    return; // Don't update if validation fails
                }
            }
        }

        // Track if this is an update to existing variant
        if (updatedVariant.id && !updatedVariant._action) {
            updatedVariant._action = 'update';
        }

        newVariants[index] = updatedVariant;
        setVariants(newVariants);
    };

    const handleFrameDelete = (frameId: number) => {
        setDeletedFrames([...deletedFrames, frameId]);
    };

    const validateImageFile = (file: File, fieldName: string): boolean => {
        // Check file type
        if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
            message.error(`${fieldName}: Only JPEG, PNG, and WebP images are allowed`);
            return false;
        }

        // Check file size
        if (file.size > MAX_FILE_SIZE) {
            message.error(`${fieldName}: File size must be less than 6MB (current: ${(file.size / 1024 / 1024).toFixed(2)}MB)`);
            return false;
        }

        return true;
    };

    const handleSubmit = async () => {
        setLoading(true);
        try {
            const formData = new FormData();

            // Only send changed/new variants
            const changedVariants = variants.filter(v => !v.id || v._action);

            // Send templates data (even if empty array)
            formData.append("templates", JSON.stringify(
                changedVariants.map(v => ({
                    id: v.id,
                    size: v.size,
                    _action: v._action || (v.id ? 'update' : 'create'),
                    hasNewImage: v.imageFile instanceof File,
                    proofFrameTemplates: v.proofFrameTemplates.map(f => ({
                        id: f.id,
                        frameType: f.frameType,
                        _action: f._action || (f.id ? 'update' : 'create'),
                        hasNewImage: f.imageFile instanceof File,
                    }))
                }))
            ));

            // Add deleted IDs
            formData.append("deletedVariants", JSON.stringify(deletedVariants));
            formData.append("deletedFrames", JSON.stringify(deletedFrames));

            // Check if there's anything to save
            if (changedVariants.length === 0 && deletedVariants.length === 0 && deletedFrames.length === 0) {
                message.info("No changes to save");
                setLoading(false);
                return;
            }

            // Append files with proper indexing
            changedVariants.forEach((variant, vIndex) => {
                if (variant.imageFile instanceof File) {
                    const key = variant.id
                        ? `template_${variant.id}_image`
                        : `template_new_${vIndex}_image`;
                    formData.append(key, variant.imageFile);
                }

                variant.proofFrameTemplates.forEach((frame, fIndex) => {
                    if (frame.imageFile instanceof File) {
                        const key = frame.id
                            ? `frame_${frame.id}_image`
                            : `frame_new_${vIndex}_${fIndex}_image`;
                        formData.append(key, frame.imageFile);
                    }
                });
            });

            await axios.post("/proof-template/save", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            message.success("Proof Templates saved successfully");

            // Clear deleted arrays
            setDeletedVariants([]);
            setDeletedFrames([]);

            // Reload page to refresh data
            window.location.reload();
        } catch (err: any) {
            console.error("Failed to save templates:", err);
            message.error(err.response?.data?.error || "Failed to save templates");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div>
            <div style={{
                marginBottom: 16,
                display: 'flex',
                flexDirection: window.innerWidth < 768 ? 'column' : 'row',
                justifyContent: 'space-between',
                alignItems: window.innerWidth < 768 ? 'stretch' : 'center',
                gap: 12
            }}>
                <Title level={3} style={{ margin: 0, fontSize: window.innerWidth < 768 ? 20 : 24 }}>
                    Proof Templates
                </Title>
                <Space direction={window.innerWidth < 768 ? 'vertical' : 'horizontal'} style={{ width: window.innerWidth < 768 ? '100%' : 'auto' }}>
                    <Button
                        type="dashed"
                        onClick={addVariant}
                        icon={<PlusOutlined />}
                        block={window.innerWidth < 768}
                    >
                        Add Template Variant
                    </Button>
                    <Button
                        type="primary"
                        onClick={handleSubmit}
                        loading={loading}
                        icon={<SaveOutlined />}
                        block={window.innerWidth < 768}
                    >
                        Save All Changes
                    </Button>
                </Space>
            </div>

            <Row gutter={[12, 12]}>
                {variants.map((variant, index) => (
                    <Col xs={24} sm={24} md={24} lg={12} xl={12} key={index}>
                        <ProofTemplateVariantForm
                            variant={variant}
                            variantIndex={index}
                            onChange={(updated) => handleVariantChange(index, updated)}
                            onRemove={() => removeVariant(index)}
                            onFrameDelete={handleFrameDelete}
                            variantChoices={variantChoices}
                            frameVariants={frameVariants}
                            validateImageFile={validateImageFile}
                            selectedSizes={getSelectedSizes(index)}
                            getSelectedFrameTypes={(frameIndex) => getSelectedFrameTypes(index, frameIndex)}
                        />
                    </Col>
                ))}
            </Row>

            {variants.length === 0 && (
                <Card style={{ textAlign: 'center', padding: window.innerWidth < 768 ? 20 : 40 }}>
                    <Text type="secondary" style={{ fontSize: window.innerWidth < 768 ? 13 : 14 }}>
                        No template variants yet. Click "Add Template Variant" to get started.
                    </Text>
                </Card>
            )}
        </div>
    );
};

export default ProofTemplateForm;