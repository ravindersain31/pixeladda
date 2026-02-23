import React from "react";

export interface StepCardProps {
    id?: string;
    title: string | React.ReactNode;
    stepNumber?: string | number;
    highlightColor?: string;
    children: React.ReactNode
    scrollable?: boolean,
    scrollHeight?: number,
}