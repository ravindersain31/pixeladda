import Raact, { lazy, memo, useState } from "react";
import BulkOrderModal from "../BulkOrderModal";

// internal imports
const PriceChart = lazy(() => import("@wireStake/components/PriceChart"));
const ChooseWireStake = lazy(() => import("@wireStake/components/Steps/ChooseWireStake"));
const ChooseDeliveryDate = lazy(() => import("@wireStake/components/Steps/ChooseDeliveryDate"));
const AddYourComments = lazy(() => import("@wireStake/components/Steps/AddYourComments"));
const ReviewOrderDetails = lazy(() => import("@wireStake/components/Steps/ReviewOrderDetails"));

const Steps = memo(() => {
    const [isBulkOrderModalOpen, setIsBulkOrderModalOpen] = useState(false);
    
    return (
        <>
            <PriceChart setIsBulkOrderModalOpen={setIsBulkOrderModalOpen} />
            <ChooseWireStake />
            <ChooseDeliveryDate />
            <AddYourComments />
            <ReviewOrderDetails />
            <BulkOrderModal open={isBulkOrderModalOpen} onClose={() => setIsBulkOrderModalOpen(false)} />
        </>
    );
})

export default Steps;