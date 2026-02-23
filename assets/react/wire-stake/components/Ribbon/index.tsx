import React from 'react';
import {
    StyledBadgeRibbon,
} from './styled';
import { BadgeProps } from 'antd';
import { RibbonProps } from 'antd/es/badge/Ribbon';

interface BadgeRibbonProps extends RibbonProps {
    children?: React.ReactNode;
}

const Ribbon = ({ children, ...rest }: BadgeRibbonProps) => (
    <StyledBadgeRibbon {...rest}>
        {children}
    </StyledBadgeRibbon>
);

export default Ribbon;
