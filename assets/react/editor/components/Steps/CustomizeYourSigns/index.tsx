import React from 'react';
import StepCard from "@react/editor/components/Cards/StepCard";
import {StepProps} from "../interface.ts";
import Customize from "./Customize";

const CustomizeYourSigns = ({stepNumber}: StepProps) => {
    return <StepCard title="Customize Your Signs" stepNumber={stepNumber}>
        <Customize/>
    </StepCard>
}

export default CustomizeYourSigns;