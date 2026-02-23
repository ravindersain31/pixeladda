import React, { memo } from "react";
import { StepProps } from "@orderBlankSign/utils/interface";
import StepCard from "@orderBlankSign/components/Cards/StepCard";
import AdditionalComments from "@orderBlankSign/components/AdditionalNote";

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