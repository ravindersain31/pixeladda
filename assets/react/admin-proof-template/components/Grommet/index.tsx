import React, { useEffect, useState } from "react";
import { Button, message, Row, Col, Space, Typography, Card } from "antd";
import { PlusOutlined, SaveOutlined } from "@ant-design/icons";
import ProofGrommetTemplateItem from "./Item";
import axios from "axios";
import { MAX_FILE_SIZE, ALLOWED_IMAGE_TYPES } from "../Variant";

const { Title, Text } = Typography;

interface GrommetTemplate {
    id?: number;
    grommetColor: string;
    imageFile: any;
    imageUrl?: string;
    _action?: 'create' | 'update' | 'delete';
}

interface Props {
    initialGrommetTemplates: any[];
    grommetColors: any[];
}

const ProofGrommetTemplateForm = ({ 
    initialGrommetTemplates = [], 
    grommetColors = []
}: Props) => {
    const [grommets, setGrommets] = useState<GrommetTemplate[]>([]);
    const [loading, setLoading] = useState(false);
    const [deletedGrommets, setDeletedGrommets] = useState<number[]>([]);

    useEffect(() => {
        if (initialGrommetTemplates?.length) {
            const normalized = initialGrommetTemplates.map((tpl) => ({
                id: tpl.id,
                grommetColor: tpl.grommetColor,
                imageFile: tpl.imageUrl ? {
                    uid: `server-${tpl.id}`,
                    name: `grommet-${tpl.id}.png`,
                    status: "done",
                    url: tpl.imageUrl,
                } : null,
                imageUrl: tpl.imageUrl,
            }));
            setGrommets(normalized);
        }
    }, [initialGrommetTemplates]);

    const addGrommet = () => {
        setGrommets([
            ...grommets,
            {
                grommetColor: "",
                imageFile: null,
                _action: 'create'
            }
        ]);
    };

    const removeGrommet = (index: number) => {
        const grommet = grommets[index];
        if (grommet.id) {
            setDeletedGrommets([...deletedGrommets, grommet.id]);
        }
        setGrommets(grommets.filter((_, i) => i !== index));
    };

    const getSelectedColors = (currentIndex: number): string[] => {
        return grommets
            .filter((_, index) => index !== currentIndex)
            .map(g => g.grommetColor)
            .filter(Boolean);
    };

    const handleGrommetChange = (index: number, updatedGrommet: GrommetTemplate) => {
        const newGrommets = [...grommets];

        if (updatedGrommet.imageFile instanceof File) {
            if (!validateImageFile(updatedGrommet.imageFile, `Grommet #${index + 1} image`)) {
                return;
            }
        }

        if (updatedGrommet.id && !updatedGrommet._action) {
            updatedGrommet._action = 'update';
        }

        newGrommets[index] = updatedGrommet;
        setGrommets(newGrommets);
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

            const changedGrommets = grommets.filter(g => !g.id || g._action);

            formData.append("grommets", JSON.stringify(
                changedGrommets.map(g => ({
                    id: g.id,
                    grommetColor: g.grommetColor,
                    _action: g._action || (g.id ? 'update' : 'create'),
                    hasNewImage: g.imageFile instanceof File,
                }))
            ));

            formData.append("deletedGrommets", JSON.stringify(deletedGrommets));

            if (changedGrommets.length === 0 && deletedGrommets.length === 0) {
                message.info("No changes to save");
                setLoading(false);
                return;
            }

            changedGrommets.forEach((grommet, index) => {
                if (grommet.imageFile instanceof File) {
                    const key = grommet.id
                        ? `grommet_${grommet.id}_image`
                        : `grommet_new_${index}_image`;
                    formData.append(key, grommet.imageFile);
                }
            });

            await axios.post("/proof-template/save-grommets", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            message.success("Grommet Templates saved successfully");

            setDeletedGrommets([]);
            window.location.reload();
        } catch (err: any) {
            console.error("Failed to save grommet templates:", err);
            message.error(err.response?.data?.error || "Failed to save grommet templates");
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
                    Grommets
                </Title>
                <Space direction={window.innerWidth < 768 ? 'vertical' : 'horizontal'} style={{ width: window.innerWidth < 768 ? '100%' : 'auto' }}>
                    <Button
                        type="dashed"
                        onClick={addGrommet}
                        icon={<PlusOutlined />}
                        block={window.innerWidth < 768}
                    >
                        Add Grommet
                    </Button>
                    <Button
                        type="primary"
                        onClick={handleSubmit}
                        loading={loading}
                        icon={<SaveOutlined />}
                        block={window.innerWidth < 768}
                    >
                        Save Grommets
                    </Button>
                </Space>
            </div>

            <Row gutter={[12, 12]}>
                {grommets.map((grommet, index) => (
                    <Col xs={24} sm={12} md={8} lg={6} xl={6} key={index}>
                        <ProofGrommetTemplateItem
                            grommet={grommet}
                            index={index}
                            onChange={(updated) => handleGrommetChange(index, updated)}
                            onRemove={() => removeGrommet(index)}
                            grommetColors={grommetColors}
                            validateImageFile={validateImageFile}
                            selectedColors={getSelectedColors(index)}
                        />
                    </Col>
                ))}
            </Row>

            {grommets.length === 0 && (
                <Card style={{ textAlign: 'center', padding: window.innerWidth < 768 ? 20 : 40 }}>
                    <Text type="secondary" style={{ fontSize: window.innerWidth < 768 ? 13 : 14 }}>
                        No grommet templates yet. Click "Add Grommet" to get started.
                    </Text>
                </Card>
            )}
        </div>
    );
};

export default ProofGrommetTemplateForm;