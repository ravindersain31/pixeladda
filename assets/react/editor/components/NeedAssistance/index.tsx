import { Popover } from "antd";
import { NeedAssistance, Content } from "./styled.tsx";
import { QuestionCircleOutlined } from "@ant-design/icons";

const NeedAssistancePopover = () => {

    return <Popover
        content={<Content>
            <b>Need Assistance:</b><br/>
            For assistance, simply leave a comment and submit your order. Upload your custom design or artwork files if
            available. We can help create and align your order for free. We can create any design. Our material is 4mm
            thick corrugated plastic. For full bleed please leave a comment. We will email you a free digital proof in 1 hour.
            Once approved, we will begin production. You can Request Changes as many times as needed via the proof link.
            For repeat orders, mention your old order number. Accepted files are PNG, JPEG, JPG, Ai & PDF. Files must
            be less than 50 MB in size. Select See Design - Pay Later at checkout if you prefer to pay after you are
            satisfied with your proof. We will only begin production once you approve your proof.
        </Content>}
    >
        <NeedAssistance>Need Assistance <QuestionCircleOutlined /></NeedAssistance>
    </Popover>
}

export default NeedAssistancePopover;