import React from "react";
import {RadioButtonProps} from "antd/lib/radio/radioButton";
import {
    StyledRadioButton,
    Checkmark,
    Wrapper,
} from "./styled.tsx";
import {CheckOutlined} from "@ant-design/icons";

interface Props extends RadioButtonProps {
    children: React.ReactNode;
}

const RadioButton = ({children, ...rest}: Props) => {
    return <Wrapper>
        <StyledRadioButton {...rest}>
            <Checkmark className="checkmark">
                <CheckOutlined style={{color: "#FFF"}}/>
            </Checkmark>
            {children}
        </StyledRadioButton>
    </Wrapper>
}

export default RadioButton;