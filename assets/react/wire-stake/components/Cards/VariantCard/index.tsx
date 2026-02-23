import React from "react";
import { Button, Flex } from "antd";
import { isMobile } from "react-device-detect";
import { CheckOutlined, EditOutlined, QuestionCircleOutlined } from "@ant-design/icons";


// internal imports
import {
    StyledCard,
    VariantImage,
    VariantName,
    InputQuantity,
    Checkmark,
    StyledBadgeRibbon,
    StyledBadgeEdit,
    StyledPopover, PopoverContent
} from "./styled";

import { MAX_ALLOWED_QUANTITY } from "@wireStake/utils/constant";
import { Frame } from "@wireStake/utils/interface";


interface Props {
    title: string;
    name: string;
    label: string;
    image: string;
    value: number | null;
    helpText?: string | React.ReactNode;
    onChange?: (value: number) => void;
    active?: boolean;
    isEdit?: boolean;
    ribbonText?: string[];
    ribbonColor?: string[];
    productId: number | string;
}

const VariantCard = ({
    title,
    name,
    label,
    image,
    value,
    onChange,
    helpText,
    active = false,
    isEdit = false,
    ribbonText = [],
    ribbonColor = [],
}: Props) => {
    const hasBackgroundColor = name === Frame.WIRE_STAKE_10X30;

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        const allowedKeys = ["Backspace", "Delete", "ArrowLeft", "ArrowRight", "Tab", "Enter"];
        if (e.ctrlKey || e.metaKey) {
            return false;
        }
        if (!allowedKeys.includes(e.key) && (isNaN(Number(e.key)) || e.key === " ")) {
            e.preventDefault();
        }
    };

    const handleChange = (val: string | number | null) => {
        const parsed = parseInt(val as string, 10);
        if (onChange) onChange(!isNaN(parsed) ? parsed : 0);
    };

    const ribbons = ribbonText.map((text, i) => ({
        text,
        color: ribbonColor[i] || "#1d4e9b",
    }));

    const cardClasses = `${active ? "active" : ""} ${isMobile ? "mobile-device" : ""}`;

    return (
        <StyledCard className={cardClasses} $hasBackgroundColor={hasBackgroundColor}>
            {ribbons.map((ribbon, index) => (
                <StyledBadgeRibbon
                    key={`${ribbon.text}-${index}`}
                    text={ribbon.text}
                    color={ribbon.color}
                    style={{ top: `${8 + index * 20}px` }}
                />
            ))}

            {active && (
                <Checkmark className="checkmark">
                    <CheckOutlined style={{ color: "#fff" }} />
                </Checkmark>
            )}

            {isEdit && ribbonText.length > 0 && (
                <StyledBadgeEdit
                    title={ribbonText[0]}
                    style={{ top: `${3 + ribbonText.length * 15}px` }}
                    text={<EditOutlined style={{ color: "#fff" }} />}
                />
            )}

            <VariantImage className={isMobile ? "mobile-device" : ""}>
                <img src={image} alt={title} />
            </VariantImage>

            <VariantName className={isMobile ? "mobile-device" : ""} $textBold={hasBackgroundColor}>
                {label || title}
            </VariantName>

            <Flex className="variant-quantity">
                <InputQuantity
                    type="text"
                    inputMode="numeric"
                    placeholder="Enter Qty"
                    min={0}
                    max={MAX_ALLOWED_QUANTITY}
                    maxLength={MAX_ALLOWED_QUANTITY.toString().length}
                    precision={0}
                    value={value && value > 0 ? value.toString() : ""}
                    onChange={handleChange}
                    onKeyDown={handleKeyDown}
                    changeOnWheel={false}
                />
                {helpText && (
                    <StyledPopover
                        trigger="hover"
                        content={<PopoverContent>{helpText}</PopoverContent>}
                    >
                        <Button className="question-icon" shape="circle" icon={<QuestionCircleOutlined />} />
                    </StyledPopover>
                )}
            </Flex>
        </StyledCard>
    );
};

export default React.memo(VariantCard);
