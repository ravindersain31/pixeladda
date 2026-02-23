import React, { useEffect, useState } from 'react';
import {ShoppingCartOutlined} from '@ant-design/icons';
import {useAppSelector} from "@react/editor/hook.ts";
import {
    AddToCartButton,
} from './styled.tsx';

const AddToCart = (
    {
        isAddingToCart,
        onAddToCart
    }: {
        isAddingToCart: boolean,
        onAddToCart: (data?: any) => void
    }
) => {
    const canvas = useAppSelector(state => state.canvas);
    const config = useAppSelector(state => state.config);
    const [isEditCart, setIsEditCart] = useState(false);
    const [lastSku, setLastSku] = useState<string>(config.product.sku);

    const urlParams = new URLSearchParams(window.location.search);
    const shareId = urlParams.get("shareId");

    useEffect(() => {
        setLastSku(config.product.sku);
    }, [config.product.sku]);

    useEffect(() => {
        (canvas.item.itemId || isEditCart) ? setIsEditCart(true) : setIsEditCart(false);
    }, [isEditCart, config.product.sku, canvas.item.itemId]);

    useEffect(() => {
        if (shareId) {
            setIsEditCart(false);
        } else {
            setIsEditCart(!!canvas.item.itemId);
        }
    }, [canvas.item.itemId, shareId]);
    
    const buttonText = isEditCart ? "Update Cart" : "Add to Cart";
    const buttonUpdatingText = isEditCart ? 'Updating Cart...' : 'Adding to Cart';
    return <>
        <AddToCartButton
            type="primary"
            className='add-to-cart'
            size="large"
            disabled={isAddingToCart || canvas.loading}
            onClick={() => onAddToCart()}
        >
            <ShoppingCartOutlined style={{fontSize: '30px'}}/>
            {isAddingToCart ? buttonUpdatingText : buttonText}
        </AddToCartButton>
    </>
}

export default AddToCart;