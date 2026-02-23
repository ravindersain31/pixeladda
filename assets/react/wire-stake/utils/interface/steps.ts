export interface StepProps {
    stepNumber?: string | number;
}

export interface stepConfigProps {
    option: string;
}

export interface CardProps {
    title: string | React.ReactNode;
    stepNumber?: number | string;
}

export interface StepCardProps {
    id?: string;
    title: string | React.ReactNode;
    stepNumber?: string | number;
    highlightColor?: string;
    children: React.ReactNode
}

export enum Frame {
    NONE = "NONE",
    WIRE_STAKE_10X30 = "WIRE_STAKE_10X30",
    WIRE_STAKE_10X24 = "WIRE_STAKE_10X24",
    WIRE_STAKE_10X30_PREMIUM = "WIRE_STAKE_10X30_PREMIUM",
    WIRE_STAKE_10X24_PREMIUM = "WIRE_STAKE_10X24_PREMIUM",
    WIRE_STAKE_10X30_SINGLE = "WIRE_STAKE_10X30_SINGLE",
}
