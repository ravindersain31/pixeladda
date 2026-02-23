import React, { memo, useCallback, useMemo } from "react";
import { shallowEqual } from "react-redux";
import { Col, Row } from "antd";

import { Frame, StepProps } from "@orderBlankSign/utils/interface";
import StepCard from "@orderBlankSign/components/Cards/StepCard";
import VariantCard from "@orderBlankSign/components/Cards/VariantCard";
import { VariantProps } from "@orderBlankSign/redux/reducer/config/interface";
import { useAppDispatch, useAppSelector } from "@orderBlankSign/hook";
import actions from "@orderBlankSign/redux/actions";
import { buildCartItem, getEffectiveQuantity } from "@orderBlankSign/utils/helper";
import { MAX_ALLOWED_QUANTITY } from "@orderBlankSign/utils/constant";
import { getVariantPriceByQty } from "@orderBlankSign/utils/helper";
import { isMobile } from "react-device-detect";

const ribbonMeta: Record<string, { texts: string[]; colors: string[] }> = {
    "24x18": {
        texts: ["Best Seller", "Standard"],
        colors: ["#3398d9", "#66b94d"],
    },
    "18x12": {
        texts: ["Best Seller"],
        colors: ["#3398d9"],
    },
};

const ChooseYourSizes = ({ stepNumber = 1 }: StepProps) => {
    const variants = useAppSelector(state => state.config.product.variants, shallowEqual);
    const cartStage = useAppSelector(state => state.cartStage, shallowEqual);
    const cart = useAppSelector(state => state.config.cart, shallowEqual);
    const dispatch = useAppDispatch();

    const handleQuantityChange = useCallback(
        (quantity: number, variant: VariantProps) => {
            if (isNaN(quantity) || quantity < 0 || quantity > MAX_ALLOWED_QUANTITY) {
                console.warn(`Invalid quantity: ${quantity}`);
                return;
            }

            const updatedCartItem = buildCartItem(variant, quantity);
            dispatch(actions.cartStage.upsertCartItem(updatedCartItem));
        },
        [dispatch]
    );

    return (
        <StepCard title={isMobile ? "Choose Your Sizes" : "Choose Your Sizes (WxH in Inches)"} stepNumber={stepNumber} id="choose-your-sizes">
            <Row gutter={[8, 8]}>
                {variants.map((variant) => {
                    const quantity = cartStage.items[variant.id]?.quantity ?? null;

                    const ribbons = useMemo(() => {
                        return ribbonMeta[variant.name] || { texts: [], colors: [] };
                    }, [variant.name]);

                    const qty = useMemo(
                        () => getEffectiveQuantity(variant, cart, cartStage),
                        [variant, cart, cartStage]
                    );

                    const price = useMemo(
                        () => getVariantPriceByQty(variant.name, qty),
                        [variant.name, qty]
                    );

                    const priceText = price ? `$${price.toFixed(2)}` : null;

                    const updatedRibbons = priceText
                        ? { ...ribbons, texts: [priceText, ...ribbons.texts] }
                        : ribbons;

                    return (
                        <Col key={variant.id} xs={12} sm={12} md={8} lg={5}>
                            <VariantCard
                                title={variant.label || variant.name}
                                name={variant.name}
                                label={variant.label}
                                image={variant.image}
                                productId={variant.productId}
                                ribbonText={ribbons.texts}
                                ribbonColor={ribbons.colors}
                                value={quantity}
                                active={!!quantity && quantity > 0}
                                onChange={(qty) => handleQuantityChange(qty, variant)}
                            />
                        </Col>
                    );
                })}
            </Row>
        </StepCard>
    );
};

export default memo(ChooseYourSizes);
