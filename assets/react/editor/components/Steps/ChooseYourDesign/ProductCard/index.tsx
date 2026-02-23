import React from 'react';
import {Button, Popover} from 'antd';
import {CheckOutlined, QuestionCircleOutlined} from '@ant-design/icons';
import {
    StyledCard,
    AddonImage,
    AddonNameContainer,
    AddonName,
    Checkmark,
} from './styled';

interface Props {
    title: string;
    imageUrl?: string;
    helpText?: string | React.ReactNode;
    onClick?: () => void;
    isActive?: boolean;
}

const ProductCard = ({title = 'Addon', imageUrl, helpText, onClick, isActive = false}: Props) => {
    return (
        <StyledCard onClick={onClick} className={isActive ? 'active' : ''}>
            <Checkmark className="checkmark">
                <CheckOutlined style={{color: "#FFF"}}/>
            </Checkmark>
            {imageUrl && <AddonImage>
                <img
                    src={imageUrl}
                    alt={title}
                />
            </AddonImage>}
            <AddonNameContainer>
                {title && <AddonName>{title}</AddonName>}
                {helpText &&
                    <Popover
                        content={helpText}
                    >
                        <Button shape="circle" icon={<QuestionCircleOutlined/>}/>
                    </Popover>
                }
            </AddonNameContainer>
        </StyledCard>
    )
}

export default ProductCard;