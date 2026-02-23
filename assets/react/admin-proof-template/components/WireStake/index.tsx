import React, { useEffect, useState } from "react";
import { Button, message, Row, Col, Space, Typography, Card, Divider } from "antd";
import { PlusOutlined, SaveOutlined } from "@ant-design/icons";
import ProofWireStakeTemplateItem from "./Item";
import axios from "axios";
import { MAX_FILE_SIZE, ALLOWED_IMAGE_TYPES } from "../Variant";

const { Title, Text } = Typography;

interface WireStakeTemplate {
    id?: number;
    wireStakeType: string;
    imageFile: any;
    imageUrl?: string;
    _action?: 'create' | 'update' | 'delete';
}

interface Props {
    initialWireStakeTemplates: any[];
    frameVariants: any[];
}

const ProofWireStakeTemplateForm = ({ 
    initialWireStakeTemplates = [], 
    frameVariants = []
}: Props) => {
    const [wireStakes, setWireStakes] = useState<WireStakeTemplate[]>([]);
    const [loading, setLoading] = useState(false);
    const [deletedWireStakes, setDeletedWireStakes] = useState<number[]>([]);

    useEffect(() => {
        if (initialWireStakeTemplates?.length) {
            const normalized = initialWireStakeTemplates.map((tpl) => ({
                id: tpl.id,
                wireStakeType: tpl.wireStakeType,
                imageFile: tpl.imageUrl ? {
                    uid: `server-${tpl.id}`,
                    name: `wirestake-${tpl.id}.png`,
                    status: "done",
                    url: tpl.imageUrl,
                } : null,
                imageUrl: tpl.imageUrl,
            }));
            setWireStakes(normalized);
        }
    }, [initialWireStakeTemplates]);

    const addWireStake = () => {
        setWireStakes([
            ...wireStakes,
            {
                wireStakeType: "",
                imageFile: null,
                _action: 'create'
            }
        ]);
    };

    const removeWireStake = (index: number) => {
        const wireStake = wireStakes[index];
        if (wireStake.id) {
            setDeletedWireStakes([...deletedWireStakes, wireStake.id]);
        }
        setWireStakes(wireStakes.filter((_, i) => i !== index));
    };

    const getSelectedTypes = (currentIndex: number): string[] => {
        return wireStakes
            .filter((_, index) => index !== currentIndex)
            .map(w => w.wireStakeType)
            .filter(Boolean);
    };

    const handleWireStakeChange = (index: number, updatedWireStake: WireStakeTemplate) => {
        const newWireStakes = [...wireStakes];

        if (updatedWireStake.imageFile instanceof File) {
            if (!validateImageFile(updatedWireStake.imageFile, `Wire Stake #${index + 1} image`)) {
                return;
            }
        }

        if (updatedWireStake.id && !updatedWireStake._action) {
            updatedWireStake._action = 'update';
        }

        newWireStakes[index] = updatedWireStake;
        setWireStakes(newWireStakes);
    };

    const validateImageFile = (file: File, fieldName: string): boolean => {
        if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
            message.error(`${fieldName}: Only JPEG, PNG, and WebP images are allowed`);
            return false;
        }

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

            const changedWireStakes = wireStakes.filter(w => !w.id || w._action);

            formData.append("wireStakes", JSON.stringify(
                changedWireStakes.map(w => ({
                    id: w.id,
                    wireStakeType: w.wireStakeType,
                    _action: w._action || (w.id ? 'update' : 'create'),
                    hasNewImage: w.imageFile instanceof File,
                }))
            ));

            formData.append("deletedWireStakes", JSON.stringify(deletedWireStakes));

            if (changedWireStakes.length === 0 && deletedWireStakes.length === 0) {
                message.info("No changes to save");
                setLoading(false);
                return;
            }

            changedWireStakes.forEach((wireStake, index) => {
                if (wireStake.imageFile instanceof File) {
                    const key = wireStake.id
                        ? `wirestake_${wireStake.id}_image`
                        : `wirestake_new_${index}_image`;
                    formData.append(key, wireStake.imageFile);
                }
            });

            await axios.post("/proof-template/save-wire-stakes", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            message.success("Wire Stake Templates saved successfully");

            setDeletedWireStakes([]);
            window.location.reload();
        } catch (err: any) {
            console.error("Failed to save wire stake templates:", err);
            message.error(err.response?.data?.error || "Failed to save wire stake templates");
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
                    Wire Stakes
                </Title>
                <Space direction={window.innerWidth < 768 ? 'vertical' : 'horizontal'} style={{ width: window.innerWidth < 768 ? '100%' : 'auto' }}>
                    <Button
                        type="dashed"
                        onClick={addWireStake}
                        icon={<PlusOutlined />}
                        block={window.innerWidth < 768}
                    >
                        Add Wire Stake
                    </Button>
                    <Button
                        type="primary"
                        onClick={handleSubmit}
                        loading={loading}
                        icon={<SaveOutlined />}
                        block={window.innerWidth < 768}
                    >
                        Save Wire Stakes
                    </Button>
                </Space>
            </div>

            <Row gutter={[12, 12]}>
                {wireStakes.map((wireStake, index) => (
                    <Col xs={24} sm={12} md={8} lg={6} xl={6} key={index}>
                        <ProofWireStakeTemplateItem
                            wireStake={wireStake}
                            index={index}
                            onChange={(updated) => handleWireStakeChange(index, updated)}
                            onRemove={() => removeWireStake(index)}
                            frameVariants={frameVariants}
                            validateImageFile={validateImageFile}
                            selectedTypes={getSelectedTypes(index)}
                        />
                    </Col>
                ))}
            </Row>

            {wireStakes.length === 0 && (
                <Card style={{ textAlign: 'center', padding: window.innerWidth < 768 ? 20 : 40 }}>
                    <Text type="secondary" style={{ fontSize: window.innerWidth < 768 ? 13 : 14 }}>
                        No wire stake templates yet. Click "Add Wire Stake" to get started.
                    </Text>
                </Card>
            )}
        </div>
    );
};

export default ProofWireStakeTemplateForm;