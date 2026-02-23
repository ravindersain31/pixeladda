import Raact, { lazy, memo } from "react";

// internal imports
const ChooseYourSizes = lazy(() => import("@orderSample/components/Steps/ChooseYourSizes"));
const ChooseYourSides = lazy(() => import("@orderSample/components/Steps/ChooseYourSides"));
const AddYourComments = lazy(() => import("@orderSample/components/Steps/AddYourComments"));
const ChooseYourShape = lazy(() => import("@orderSample/components/Steps/ChooseYourShape"));
const ChooseDeliveryDate = lazy(() => import("@orderSample/components/Steps/ChooseDeliveryDate"));
const ReviewOrderDetails = lazy(() => import("@orderSample/components/Steps/ReviewOrderDetails"));

const Steps = memo(() => {
    return (
        <>
            <ChooseYourSizes />
            <ChooseYourSides />
            <ChooseYourShape />
            <ChooseDeliveryDate />
            <AddYourComments />
            <ReviewOrderDetails />
        </>
    );
})

export default Steps;