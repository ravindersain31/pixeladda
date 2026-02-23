import React from 'react';
import { CardBody, CardHeader, CardWrapper } from './styled';

interface CustomCardProps {
    title: string;
    children: React.ReactNode;
}

const CustomCard = ({ title, children }: CustomCardProps) => {
    return (
        <CardWrapper>
            <CardHeader>{title}</CardHeader>
            <CardBody>{children}</CardBody>
        </CardWrapper>
    );
};

export default CustomCard;
