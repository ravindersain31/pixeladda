import React from 'react';
import { Button } from 'antd';
import { QuestionCircleOutlined } from '@ant-design/icons';
import {
    StyledCard,
    AddonImage,
    AddonNameContainer,
    AddonName,
    StyledPopover,
    PopoverContent,
    StyledBadgeRibbon,
} from './styled';
import { isMobile } from "react-device-detect";
import { TooltipPlacement } from 'antd/lib/tooltip';

interface Props {
    title: string;
    imageUrl?: string;
    ribbonText?: string | string[];
    ribbonColor?: string | string[];
    placement?: TooltipPlacement;
    helpText?: string | React.ReactNode;
    disable?: boolean;
}

const SingleVariant = ({ title = 'Addon', imageUrl, ribbonText, ribbonColor, helpText, placement, disable = false }: Props) => {
    const ribbons = Array.isArray(ribbonText) ? ribbonText : [ribbonText];
    const colors = Array.isArray(ribbonColor) ? ribbonColor : [ribbonColor];

    return (
        <StyledCard style={{ opacity: disable ? 0.5 : 1 }}>
            {ribbons.map((text, index) => (
                <StyledBadgeRibbon
                    key={index}
                    text={text}
                    color={colors[index] || colors[0]}
                    style={{ top: `${-3 + index * 20}px` }}
                />
            ))}
            {imageUrl && (
                <AddonImage className={isMobile ? 'mobile-device addon-image' : 'addon-image'}>
                    <img src={imageUrl} alt={title} />
                </AddonImage>
            )}
            <AddonNameContainer>
                {title && <AddonName className="addon-name">{title}</AddonName>}
                {helpText && (
                    <StyledPopover
                        trigger="hover"
                        placement={placement}
                        content={<PopoverContent>{helpText}</PopoverContent>}
                    >
                        <Button shape="circle" icon={<QuestionCircleOutlined />} />
                    </StyledPopover>
                )}
            </AddonNameContainer>
        </StyledCard>
    );
};

export default SingleVariant;