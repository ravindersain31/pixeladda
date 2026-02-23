import React, { memo, useCallback, useEffect, useMemo, useState } from "react";
import { shallowEqual } from "react-redux";
import { Button, Col, Row, Collapse } from "antd";

import { EntryType, StepProps } from "@orderSample/utils/interface";
import StepCard from "@orderSample/components/Cards/StepCard";
import VariantCard from "@orderSample/components/Cards/VariantCard";
import CustomSizeCard from "@orderSample/components/Cards/CustomSizeCard";
import { VariantProps } from "@orderSample/redux/reducer/config/interface";
import { useAppDispatch, useAppSelector } from "@orderSample/hook";
import actions from "@orderSample/redux/actions";
import { buildCartItem } from "@orderSample/utils/helper";
import { MAX_ALLOWED_QUANTITY } from "@orderSample/utils/constant";
import SampleAlert from "@orderSample/components/SampleAlert";
import { ItemProps } from "@orderSample/redux/reducer/cart/interface";
import { StyledCollapse, StyledButton } from "./styled";
import { isPromoStore } from "@react/editor/helper/editor";

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
    const [activeKey, setActiveKey] = useState<string[]>([]);

    const variants = useAppSelector((state) => state.config.product.variants, shallowEqual);
    const customVariants = useAppSelector((state) => state.config.product.customVariant, shallowEqual);
    const cartStage = useAppSelector((state) => state.cartStage, shallowEqual);
    const [customSizesInCart, setCustomSizesInCart] = useState<EntryType[]>([]);
    const dispatch = useAppDispatch();
    const urlParams = new URLSearchParams(window.location.search);
    const cartIdFromUrl = urlParams.get('cartId') ?? null;

    useEffect(() => {
        const hasCustomSize = Object.values(cartStage.items).some(
            (item: ItemProps) => item.sku.includes("CUSTOM-SIZE")
        );

        setActiveKey(hasCustomSize ? ["1"] : []);

        if (cartIdFromUrl) {
            const customSizesInCartEdit: EntryType[] = Object.values(cartStage.items).filter(
                (item: ItemProps) => item.sku.includes("CUSTOM-SIZE")
            ).map((item: ItemProps) => ({
                id: String(item.id),
                width: item.templateSize?.width || 24,
                height: item.templateSize?.height || 18,
                quantity: item.quantity || 0
            }));
            setCustomSizesInCart(customSizesInCartEdit);
        }
    }, [])

    const handleQuantityChange = useCallback(
        (quantity: number, variant: VariantProps) => {
            if (isNaN(quantity) || quantity < 0) {
                console.warn(`Invalid quantity: ${quantity}`);
                return;
            }
            const updatedCartItem = buildCartItem(variant, quantity);
            dispatch(actions.cartStage.upsertCartItem(updatedCartItem));
        },
        [dispatch]
    );

    const handleCustomSizesUpdate = async (entries: EntryType[]) => {
        if (!customVariants[0]) return;

        const baseVariant = customVariants[0];

        entries.forEach((entry, idx) => {

            if (isNaN(entry.quantity) || entry.quantity < 0) {
                console.warn(`Invalid quantity: ${entry.quantity}`);
                return;
            }

            const variant = { ...baseVariant, id: entry.id };
            const updatedCartItem = buildCartItem(variant, entry.quantity, {
                width: entry.width,
                height: entry.height,
            });

            dispatch(actions.cartStage.upsertCartItem(updatedCartItem));
        });
    };

    const toggleCollapse = () => {
        setActiveKey((prev) => (prev.length ? [] : ["1"]));
    };

    const isActive = Object.values(cartStage.items).some((item: ItemProps) => item.quantity > 0 && item?.sku?.includes("CUSTOM-SIZE"));

    const customSizeContent = (
        <Row justify="center" style={{ marginTop: 0 }}>
            <Col xs={24} md={20} lg={12}>
                <CustomSizeCard
                    isActive={isActive}
                    name={customVariants[0]?.name || ""}
                    label={customVariants[0]?.label || ""}
                    image={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/WxH.webp" : "https://static.yardsignplus.com/product/img/CUSTOM-SIZE/custom-size.png"}
                    onChange={handleCustomSizesUpdate}
                    existingEntries={customSizesInCart.length > 0 ? customSizesInCart : undefined}
                    onClose={() => setActiveKey([])}
                />
            </Col>
        </Row>
    );

    const collapseItems = [
        {
            key: "1",
            label: null,
            children: customSizeContent,
            showArrow: false,
        },
    ];

    return (
        <StepCard title="Choose Your Sizes (WxH in Inches)" stepNumber={stepNumber} id="choose-your-sizes">
            <SampleAlert />

            <Row gutter={[8, 8]}>
                {variants.map((variant) => {
                    const quantity = cartStage.items[variant.id]?.quantity ?? null;

                    const ribbons = useMemo(() => {
                        return ribbonMeta[variant.name] || { texts: [], colors: [] };
                    }, [variant.name]);

                    return (
                        <Col key={variant.id} xs={12} sm={12} md={6} lg={5}>
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

            <Row justify="center" style={{ margin: 0 }}>
                <Col>
                    <StyledButton type={activeKey.length ? "primary" : "default"} onClick={toggleCollapse} isPromoStore={isPromoStore()}>
                        Order Custom Sizes
                    </StyledButton>
                </Col>
            </Row>

            <Row justify="center" style={{ marginTop: 0 }}>
                <Col span={24}>
                    <StyledCollapse
                        items={collapseItems}
                        activeKey={activeKey}
                        onChange={(key: string | string[]) => setActiveKey(Array.isArray(key) ? key : [key])}
                    />
                </Col>
            </Row>
        </StepCard>
    );
};

export default memo(ChooseYourSizes);
