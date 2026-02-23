import React from 'react';
import { StyledCard } from '@orderBlankSign/components/Cards/StepCard/styled';
import { CardProps, StepCardProps } from '@orderBlankSign/utils/interface';
import { useAppSelector } from '@orderBlankSign/hook';
import { shallowEqual } from 'react-redux';

const CardHeader = ({ stepNumber, title }: CardProps) => {

    const totalQty = useAppSelector(state => state.cartStage.totalQuantity, shallowEqual);
    const subTotalAmount = useAppSelector(state => state.cartStage.subTotalAmount, shallowEqual);

    return (
        <>
            <span className='step-header'>
                <span className="step-number">Step {stepNumber}</span>
                <span className="step-title">{title}</span>
            </span>
            <span className="step-total">
                <>
                    <span className="separator">Total&nbsp;</span>
                    Qty: {totalQty}
                    <span className="separator">&nbsp;|&nbsp;</span>
                    <span className="price">Price: ${subTotalAmount.toFixed(2)}</span>
                </>
            </span>
        </>
    );
}

export const highlightColors: { [step: string]: string } = {
    'step_1': '#704D9F',
    'step_2': '#1500D6',
    'step_3': '#00AEC9',
    'step_4': '#00CF61',
    'step_5': '#078E89',
    'step_6': '#007704',
    'step_7': '#E59100',
    'step_8': '#C81F12',
    'step_9': '#BE009E',
    'step_10': '#2D87BF',
    'step_11': '#704D9F',
};


const Card = ({ id, title, stepNumber, highlightColor, children }: StepCardProps) => {
    return <StyledCard
        id={id}
        title={<CardHeader title={title} stepNumber={stepNumber} />}
        color={highlightColor || highlightColors[`step_${stepNumber}`]}
    >
        {children}
    </StyledCard>
}

export default Card;