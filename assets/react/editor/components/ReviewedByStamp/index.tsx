import { ReviewedByNote, Text } from "./styled.tsx";
import React from "react";
import ReviewStamp from "../common/ReviewedByStamp/index.tsx";

const ReviewedByStamp = () => {
    return <>
        <ReviewedByNote>
            <ReviewStamp />
            <Text>
                Order online or call now <a href="tel: +1-877-958-1499">+1-877-958-1499</a>. we will email you a digital proof in 1 hour. once approved, we will begin production.
            </Text>
        </ReviewedByNote>
    </>;
}
export default ReviewedByStamp;