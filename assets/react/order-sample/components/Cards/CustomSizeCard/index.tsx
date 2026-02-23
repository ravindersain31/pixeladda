import React, { useState } from "react";
import { Col, Row, Typography, Tooltip } from "antd";
import {
    QuestionCircleOutlined,
    DeleteOutlined,
    CheckOutlined,
    CloseOutlined,
} from "@ant-design/icons";
import { isMobile } from "react-device-detect";

import {
    StyledCard,
    VariantImage,
    InputQuantity,
    StyledPopover,
    PopoverContent,
    Checkmark,
    AddAnotherSize,
    DeleteButton,
} from "./styled";

import {
    MAX_ALLOWED_QUANTITY,
    MAX_ALLOWED_HEIGHT,
    MAX_ALLOWED_WIDTH,
} from "@orderSample/utils/constant";
import { EntryType } from "@react/order-sample/utils/interface";

const { Text } = Typography;

const tooltipText =
    "The largest size we can produce is 48 x 96 (inches) or 96 x 48. One side must be 48 or less.";

interface Props {
    name: string;
    label: string;
    image: string;
    isActive?: boolean;
    onChange: (entries: EntryType[]) => void;
    existingEntries?: EntryType[];
    onClose?: () => void;
}

// Helper to generate unique ID
const generateUniqueId = () =>
    `${Date.now()}-${Math.random().toString(36).substring(2, 8)}`;

const convertToEntryWithId = (entries: EntryType[]): EntryType[] =>
    entries.map(entry => ({
        ...entry,
        id: entry.id || generateUniqueId(),
    }));


const CustomSizeCard = ({
    name,
    label,
    image,
    isActive,
    onChange,
    existingEntries,
    onClose,
}: Props) => {
    const cardClasses = `${isActive ? "active" : ""} ${isMobile ? "mobile-device" : ""}`;

    const [entries, setEntries] = useState<EntryType[]>(
        existingEntries
            ? convertToEntryWithId(existingEntries)
            : [{ id: generateUniqueId(), width: 24, height: 18, quantity: 0 }]
    );

    const updateEntry = (id: string, key: "width" | "height" | "quantity", value: number) => {
        const updated = entries.map(entry =>
            entry.id === id ? { ...entry, [key]: value } : entry
        );
        setEntries(updated);
        onChange(updated);
    };

    const addEntry = () => {
        const newEntry = { id: generateUniqueId(), width: 24, height: 18, quantity: 0 };
        const updated = [...entries, newEntry];
        setEntries(updated);
        onChange(updated);
    };

    const removeEntry = (id: string) => {
        const entryToRemove = [...entries.map(entry => entry.id === id && ({ ...entry, quantity: 0 }))] as EntryType[];
        onChange(entryToRemove);
        const updated = entries.filter(entry => entry.id !== id);
        setEntries(updated);
        onChange(updated);
    };

    const getMax = (otherDim: number) =>
        otherDim <= MAX_ALLOWED_HEIGHT ? MAX_ALLOWED_WIDTH : MAX_ALLOWED_HEIGHT;

    const handleClose = () => {
        const cleared = entries.map(entry => ({ ...entry, quantity: 0 }));
        setEntries(cleared);
        onChange(cleared);
        if (onClose) onClose();
    };

    return (
        <StyledCard className={cardClasses}>
            <div
                className="close-btn"
                onClick={handleClose}
                style={{ position: "absolute", top: 10, right: 10 }}
            >
                <CloseOutlined style={{ fontSize: "20px", color: "var(--primary-color)" }} />
            </div>

            {isActive && (
                <Checkmark className="checkmark">
                    <CheckOutlined style={{ color: "#fff" }} />
                </Checkmark>
            )}

            <VariantImage className={isMobile ? "mobile-device" : ""}>
                <img src={image} alt={label} />
            </VariantImage>

            <h6>Enter Custom Size</h6>

            {entries.map((entry) => (
                <Row
                    key={entry.id}
                    gutter={[8, 8]}
                    align="top"
                    justify="center"
                    wrap={false}
                    style={{ marginBottom: 8, textAlign: "left" }}
                >
                    {/* Width */}
                    <Col xs={7} sm={7} md={7} lg={7}>
                        <Tooltip title={tooltipText} color="var(--primary-color)">
                            <InputQuantity
                                type="text"
                                inputMode="numeric"
                                placeholder="Enter Width (in.)"
                                value={entry.width > 0 ? entry.width.toString() : "1"}
                                onChange={(val: any) => {
                                    const parsed = parseInt(val as string, 10);
                                    updateEntry(entry.id, "width", !isNaN(parsed) ? Math.min(parsed, getMax(entry.height)) : 0);
                                }}
                            />
                        </Tooltip>
                        <Text className="normal-text">
                            <span className="info">Enter Width (in.)</span>
                            <StyledPopover content={<PopoverContent>{tooltipText}</PopoverContent>}>
                                <QuestionCircleOutlined />
                            </StyledPopover>
                        </Text>
                    </Col>

                    {/* Separator */}
                    <Col xs={1} sm={1} md={1} lg={1} style={{ display: "flex", alignItems: "center", justifyContent: "center", bottom: '-4px' }}>
                        ×
                    </Col>

                    {/* Height */}
                    <Col xs={7} sm={7} md={7} lg={7}>
                        <Tooltip title={tooltipText} color="var(--primary-color)">
                            <InputQuantity
                                type="text"
                                inputMode="numeric"
                                placeholder="Enter Height (in.)"
                                value={entry.height > 0 ? entry.height.toString() : "1"}
                                onChange={(val: any) => {
                                    const parsed = parseInt(val as string, 10);
                                    updateEntry(entry.id, "height", !isNaN(parsed) ? Math.min(parsed, getMax(entry.width)) : 0);
                                }}
                            />
                        </Tooltip>
                        <Text className="normal-text">
                            <span className="info">Enter Height (in.)</span>
                            <StyledPopover content={<PopoverContent>{tooltipText}</PopoverContent>}>
                                <QuestionCircleOutlined />
                            </StyledPopover>
                        </Text>
                    </Col>

                    {/* Quantity */}
                    <Col xs={6} sm={6} md={6} lg={6}>
                        <InputQuantity
                            type="text"
                            inputMode="numeric"
                            placeholder="Quantity"
                            value={entry.quantity > 0 ? entry.quantity.toString() : ""}
                            onChange={(val: any) => {
                                const parsed = parseInt(val as string, 10);
                                updateEntry(entry.id, "quantity", !isNaN(parsed) ? Math.min(parsed, MAX_ALLOWED_QUANTITY) : 0);
                            }}
                        />
                        <Text className="normal-text fw-medium small">Bulk Discounts!</Text>
                    </Col>

                    {/* Delete */}
                    {entries.length > 1 && (
                        <Col>
                            <DeleteButton
                                type="primary"
                                shape="circle"
                                size="small"
                                icon={<DeleteOutlined />}
                                onClick={() => removeEntry(entry.id)}
                            />
                        </Col>
                    )}
                </Row>
            ))}

            <Row justify="center">
                <AddAnotherSize size="small" type="default" onClick={addEntry}>
                    + Add Size
                </AddAnotherSize>
            </Row>
        </StyledCard>
    );
};

export default React.memo(CustomSizeCard);
