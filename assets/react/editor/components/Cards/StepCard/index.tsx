import React from 'react';
import {StepCardProps} from './props.tsx';
import {
    StyledCard
} from './styled.tsx';
import { useAppSelector } from '@react/editor/hook.ts';
import { isMobile } from 'react-device-detect';
import { isPromoHost } from '@react/editor/helper/editor.ts';

const CardHeader = ({stepNumber, title}: any) => {
    const editor = useAppSelector(state => state.editor);
    const subTotalAmount = editor.subTotalAmount;
    const isGrommetStep = title === "Choose Your Grommets (3/8 Inch Hole)" && isMobile;
    return (
        <>
            <span className='step-header'>
            <span className="step-number">Step {stepNumber}</span>
            <span className="step-title">{title}</span>
            </span>
            <span className="step-total">
                {!isGrommetStep && (
                    <>
                        <span className="separator">Total&nbsp;</span>
                        Qty: {editor.totalQuantity}
                        <span className="separator">&nbsp;|&nbsp;</span>
                        <span className="price">Price: ${(subTotalAmount.toFixed(2))}</span>
                    </>
                )}
            </span>
        </>
    );
}

export const highlightColors: { [step: string]: string } = {
    'step_1': isPromoHost() ? '#25549b' : '#704D9F',
    'step_2': '#1500D6',
    'step_3': '#00AEC9',
    'step_4': '#00CF61',
    'step_5': '#078E89',
    'step_6': '#007704',
    'step_7': '#E59100',
    'step_8': '#C81F12',
    'step_9': '#BE009E',
    'step_10': '#2D87BF',
    'step_11': isPromoHost() ? '#25549b' : '#704D9F',
    'step_12': isPromoHost() ? '#25549b' : '#704D9F',
};


const Card = ({ id, title, stepNumber, highlightColor, scrollable = false, scrollHeight = 250, children }: StepCardProps) => {
    return <StyledCard
        id={id}
        title={<CardHeader title={title} stepNumber={stepNumber} />}
        color={highlightColor || highlightColors[`step_${stepNumber}`]}
        scrollable={scrollable}
        scrollHeight={scrollHeight}
    >
        {children}
    </StyledCard>
}

export default Card;