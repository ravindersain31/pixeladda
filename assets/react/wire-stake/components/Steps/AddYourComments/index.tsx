import React, { memo } from "react";
import { StepProps } from "@wireStake/utils/interface";
import StepCard from "@wireStake/components/Cards/StepCard";
import AdditionalComments from "@wireStake/components/AdditionalNote";

const AddYourComments = ({stepNumber = 3}: StepProps) => {
    return (
        <>
            <StepCard title="Add Your Comments" stepNumber={stepNumber}>
                <AdditionalComments />
            </StepCard>
        </>
    );
};

export default memo(AddYourComments);