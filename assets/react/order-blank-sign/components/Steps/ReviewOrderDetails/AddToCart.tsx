import React, { useEffect, useState } from 'react';
import {ShoppingCartOutlined} from '@ant-design/icons';

// internal imports
import { useAppSelector } from "@orderBlankSign/hook";
import { AddToCartButton} from './styled';

const AddToCart = (
    {
        isAddingToCart,
        onAddToCart
    }: {
        isAddingToCart: boolean,
        onAddToCart: (data?: any) => void
    }
) => {

    const config = useAppSelector(state => state.config);
    const [isEditCart, setIsEditCart] = useState(false);
    const [lastSku, setLastSku] = useState<string>(config.product.sku);

    const urlParams = new URLSearchParams(window.location.search);
    const cartIdFromUrl = urlParams.get('cartId') ?? null;

    useEffect(() => {
        setLastSku(config.product.sku);
    }, [config.product.sku]);

    useEffect(() => {
        cartIdFromUrl === null ? setIsEditCart(false) : setIsEditCart(true);
    }, [isEditCart, config.product.sku]);


    const buttonText = isEditCart ? "Update Cart" : "Add to Cart";
    const buttonUpdatingText = isEditCart ? 'Updating Cart...' : 'Adding to Cart';
    return <>
        <AddToCartButton
            type="primary"
            className='add-to-cart'
            size="large"
            disabled={isAddingToCart}
            onClick={() => onAddToCart()}
        >
            <ShoppingCartOutlined style={{fontSize: '30px'}}/>
            {isAddingToCart ? buttonUpdatingText : buttonText}
        </AddToCartButton>
    </>
}

export default AddToCart;