import { getStoreInfo } from "@react/editor/helper/editor";
import { SampleAlertWrapper } from "./styled";
const storeEmail = getStoreInfo().storeEmail;
const SampleAlert = () => {
    return (
        <SampleAlertWrapper>
            <p>Limit 3 quantity per size. We will include one free standard 10"W x 24"H wire stake. For questions or special requests please call <a href="tel:+1-877-958-1499">+1-877-958-1499</a>,
                email <a href="mailto:">{storeEmail}</a>, or message us on our <a href="javascript:void(Tawk_API.toggle())">live chat</a>
            </p>
        </SampleAlertWrapper>
    );
};

export default SampleAlert;