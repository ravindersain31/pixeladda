import Raact, { lazy, memo, useState } from "react";
import BulkOrderModal from "../BulkOrderModal";

// internal imports
const PriceChart = lazy(() => import("@orderBlankSign/components/PriceChart"));
const ChooseYourSizes = lazy(() => import("@react/order-blank-sign/components/Steps/ChooseYourSizes"));
const ChooseDeliveryDate = lazy(() => import("@orderBlankSign/components/Steps/ChooseDeliveryDate"));
const AddYourComments = lazy(() => import("@orderBlankSign/components/Steps/AddYourComments"));
const ReviewOrderDetails = lazy(() => import("@orderBlankSign/components/Steps/ReviewOrderDetails"));

const Steps = memo(() => {
    const [isBulkOrderModalOpen, setIsBulkOrderModalOpen] = useState(false);
    
    return (
        <>
            <PriceChart setIsBulkOrderModalOpen={setIsBulkOrderModalOpen} />
            <ChooseYourSizes />
            <ChooseDeliveryDate />
            <AddYourComments />
            <ReviewOrderDetails />
            <BulkOrderModal open={isBulkOrderModalOpen} onClose={() => setIsBulkOrderModalOpen(false)} />
        </>
    );
})

export default Steps;