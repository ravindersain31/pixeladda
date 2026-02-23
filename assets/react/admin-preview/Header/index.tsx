import React from 'react';
import {
    HeaderWrapper
} from "./styled.tsx";
import { isPromoStore } from '@react/editor/helper/editor.ts';

const Header = ({ item }: any) => {
    const bgColor = isPromoStore() ? "#25549b" : "#6f4c9e";

    return <HeaderWrapper id="page-header" bg={bgColor}>
        <h4>Preview of {item.name} for Order Id {item.orderId}</h4>
    </HeaderWrapper>

}

export default Header;