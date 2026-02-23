import React, { memo } from "react";
import { StepProps } from "@orderSample/utils/interface";
import StepCard from "@orderSample/components/Cards/StepCard";
import AdditionalComments from "@orderSample/components/AdditionalNote";

const AddYourComments = ({stepNumber = 5}: StepProps) => {
    return (
        <>
            <StepCard title="Add Your Comments" stepNumber={stepNumber}>
                <AdditionalComments />
            </StepCard>
        </>
    );
};

export default memo(AddYourComments);