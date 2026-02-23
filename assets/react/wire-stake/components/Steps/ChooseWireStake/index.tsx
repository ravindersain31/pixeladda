import React, { memo, useCallback, useMemo } from "react";
import { shallowEqual } from "react-redux";
import { Col, Row } from "antd";

import { Frame, StepProps } from "@wireStake/utils/interface";
import StepCard from "@wireStake/components/Cards/StepCard";
import VariantCard from "@wireStake/components/Cards/VariantCard";
import { VariantProps } from "@wireStake/redux/reducer/config/interface";
import { useAppDispatch, useAppSelector } from "@wireStake/hook";
import actions from "@wireStake/redux/actions";
import { buildCartItem } from "@wireStake/utils/helper";
import { MAX_ALLOWED_QUANTITY } from "@wireStake/utils/constant";
import { getFramePriceByQty } from "@wireStake/utils/helper";

const ribbonMeta: Record<string, { texts: string[]; colors: string[] }> = {
    [Frame.WIRE_STAKE_10X30]: {
        texts: ["Best Seller", "Standard"],
        colors: ["#1d4e9b", "#3398d9", "#66b94d"],
    },
    [Frame.WIRE_STAKE_10X24]: {
        texts: ["Best Seller", "Standard"],
        colors: ["#1d4e9b", "#3398d9", "#66b94d"],
    },
    // [Frame.WIRE_STAKE_10X30_PREMIUM]: {
    //     texts: ["Premium"],
    //     colors: ["#1d4e9b", "#ff4500"],
    // },
    // [Frame.WIRE_STAKE_10X30_SINGLE]: {
    //     texts: ["Single"],
    //     colors: ["#1d4e9b", "#20b2aa"],
    // },
};

const frameHelpText: Record<string, React.ReactNode> = {
    [Frame.WIRE_STAKE_10X24]: (
        <>
            <strong>10"W x 24"H Wire Stake:</strong><br />
            Increase exposure with our durable wire H-stakes.
            All signs include corrugated holes or flutes along the
            top and bottom edges, allowing for easy and instant
            installation of wire stakes. Simply insert the wire
            stake directly into the corrugated holes. Then place
            the wire stake in any soft ground (e.g. grass or dirt)
            for support. 3.4mm thick, 10 gauge (wire diameter),
            and 0.14kg weight. Recommended for all standard
            and custom sizes with a minimum of 10" width.
        </>
    ),
    [Frame.WIRE_STAKE_10X24_PREMIUM]: (
        <>
            <strong>Premium 10"W x 24"H Wire Stake:</strong><br />
            Increase exposure with our premium, thicker,
            and greater durability wire U-stakes. All signs
            include corrugated holes along the top and
            bottom edges, allowing for easy and instant
            installation of wire stakes. Simply insert
            the wire stake directly into the corrugated
            holes. Then place the wire stake in any soft
            ground (e.g. grass or dirt) for support.
            3.4mm thickness near top, 5.0mm thickness at base,
            1/4” galvanized steel, 10 gauge (wire diameter)
            near top, 6 gauge near base, and 0.2kg weight.
            Recommended for all standard and custom
            sizes with a minimum of 10" width.
        </>
    ),
    [Frame.WIRE_STAKE_10X30_SINGLE]: (
        <>
            <strong>Single 30"H Wire Stake:</strong><br />
            Increase exposure with our durable single
            wire stakes. All signs include corrugated
            holes along the top and bottom edges,
            allowing for easy and instant installation
            of wire stakes. Simply insert the single wire
            stake directly into the corrugated holes. Then
            place the wire stake in any soft ground
            (e.g. grass or dirt) for support. 3.4mm
            thick, 10 gauge (wire diameter). Recommended
            for all standard and custom sizes requiring
            only one single stake for support.
        </>
    ),
};

const ChooseWireStake = ({ stepNumber = 1 }: StepProps) => {
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
        <StepCard title="Choose Wire Stake" stepNumber={stepNumber} id="choose-your-sizes">
            <Row gutter={[8, 8]}>
                {variants.map((variant) => {
                    const quantity = cartStage.items[variant.id]?.quantity ?? null;

                    const ribbons = useMemo(() => {
                        return ribbonMeta[variant.name] || { texts: [], colors: [] };
                    }, [variant.name]);

                    const frameQty = useMemo(() => {
                        return Number(Number(cart?.totalFrameQuantity[variant.name] || 0) - Number(cart?.currentFrameQuantity[variant.name] || 0));
                    }, [cart?.totalFrameQuantity, cart?.currentFrameQuantity, variant.name]);

                    const price = useMemo(() => getFramePriceByQty(variant.name as Frame, (quantity + frameQty) || 0), [variant.name, quantity]);
                    const priceText = price ? `$${price.toFixed(2)}` : null;

                    const updatedRibbons = priceText
                        ? { ...ribbons, texts: [priceText, ...ribbons.texts] }
                        : ribbons;

                    return (
                        <Col key={variant.id} xs={12} sm={12} md={8} lg={6}>
                            <VariantCard
                                title={variant.label || variant.name}
                                name={variant.name}
                                label={variant.label}
                                image={variant.image}
                                productId={variant.productId}
                                ribbonText={updatedRibbons.texts}
                                ribbonColor={updatedRibbons.colors}
                                helpText={frameHelpText[variant.name]}
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

export default memo(ChooseWireStake);
