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

export type EntryType = {
    id: string;
    width: number;
    height: number;
    quantity: number;
};