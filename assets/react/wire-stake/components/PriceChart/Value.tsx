import React from "react";
import { NumericFormat } from "react-number-format";
import { ValueContainer } from "@wireStake/components/PriceChart/styled";

const Value = ({ amount, active = false }: { amount: number; active: boolean }) => {
    return (
        <ValueContainer className={active ? "active" : ""}>
            <NumericFormat
                value={amount}
                displayType="text"
                thousandSeparator
                decimalScale={2}
                prefix="$"
                fixedDecimalScale
            />
        </ValueContainer>
    );
};

export default Value;