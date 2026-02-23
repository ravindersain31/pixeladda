import React, { memo, useState } from "react";
import { Modal, Row, Col, message, InputNumber } from "antd";
import { shallowEqual } from "react-redux";
import { useAppSelector, useAppDispatch } from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import { MAX_ALLOWED_QUANTITY } from "../ChooseYourSizes/SingleVariant.tsx";
import { getStakeOptionByVariant } from "@react/editor/helper/stakes.tsx";
import { Frame } from "@wireStake/utils/interface";
import { AdditionalCard, InputStyled } from "./styled.tsx";
import AddonCard from "@react/editor/components/Cards/AddonCard";
import { StyledPopover, PopoverContent } from "../../Cards/AddonCard/styled.tsx";
import { QuestionCircleOutlined } from "@ant-design/icons";
import { recalculateOnUpdateQuantity } from "@react/editor/helper/quantity.ts";
import { handleNumericKeyDown } from "@react/editor/helper/editor.ts";

interface AdditionalStakesModalProps {
    visible: boolean;
    onClose: () => void;
    framePrices: { [key: string]: number };
}

const AdditionalStakesModal = ({ visible, onClose, framePrices }: AdditionalStakesModalProps) => {
    const wireStakeProduct = useAppSelector((state) => state.config.wireStakeProduct, shallowEqual);
    const items = useAppSelector((state) => state.editor.items, shallowEqual);
    const canvas = useAppSelector(state => state.canvas);
    const currentItem = useAppSelector(state => state.editor.items[canvas.item.id]);

    const dispatch = useAppDispatch();

    const [stakeQuantities, setStakeQuantities] = useState<Record<string, number>>({});

    const handleQuantityChange = (frameKey: string, quantity: number) => {
        if (!Number(quantity) && quantity !== 0) {
            return;
        }
        setStakeQuantities((prev) => ({ ...prev, [frameKey]: quantity }));
    };

    const handleSave = () => {
        if (Object.keys(stakeQuantities).length === 0) {
            message.error("Please enter at least one quantity.");
            return;
        };

        Object.entries(stakeQuantities).forEach(([frameKey, qty]) => {
            const variant = wireStakeProduct.variants.find((v: any) => v.name === frameKey);
            if (!variant) return;
            const item = JSON.parse(JSON.stringify(variant));
            const data = recalculateOnUpdateQuantity(item, qty);
            dispatch(actions.editor.updateQty(data));
            dispatch(actions.editor.refreshShipping());
        });

        message.success("Additional stakes added successfully.");
        onClose();
    };

    return (
        <Modal
            open={visible}
            title="Add Additional Stakes"
            onCancel={onClose}
            onOk={handleSave}
            okText="Save"
            cancelText="Cancel"
            centered
            width={900}
        >
            <Row gutter={[16, 16]} justify="center">
                {wireStakeProduct.variants.map((variant: any) => {
                    const option = getStakeOptionByVariant(wireStakeProduct, currentItem, framePrices, variant.name as Frame);

                    return (
                        <Col xs={12} sm={12} md={8} lg={6} key={option.key}>
                            <AdditionalCard>
                                <AddonCard
                                    title={option.title}
                                    imageUrl={option.image}
                                    ribbonText={option.ribbonText}
                                    ribbonColor={option.ribbonColor}
                                    placement="top"
                                />
                                <InputStyled>
                                    <InputNumber
                                        type="text"
                                        inputMode="numeric"
                                        placeholder={'Enter Qty'}
                                        min={0}
                                        max={MAX_ALLOWED_QUANTITY}
                                        precision={0}
                                        parser={(value: any) => parseInt(value).toFixed(0)}
                                        maxLength={MAX_ALLOWED_QUANTITY.toString().length}
                                        value={stakeQuantities[option.key] > 0 ? stakeQuantities[option.key] : null}
                                        onChange={(val: any) => handleQuantityChange(option.key, val)}
                                        style={{ width: '80%', marginBottom: 12 }}
                                        onKeyUp={(e: any) => {
                                            if (['Backspace', 'Delete'].includes(e.key)) {
                                                if (e.target.value.length <= 0) {
                                                    handleQuantityChange(option.key, 0);
                                                }
                                            }
                                        }}
                                        onKeyDown={handleNumericKeyDown()}
                                        changeOnWheel={false}
                                    />
                                    <StyledPopover
                                        trigger="hover"
                                        placement="top"
                                        overlayStyle={{ width: 300 }}
                                        content={<PopoverContent>{option.helpText}</PopoverContent>}
                                    >
                                        <QuestionCircleOutlined />
                                    </StyledPopover>
                                </InputStyled>
                            </AdditionalCard>
                        </Col>
                    );
                })}
            </Row>
        </Modal>
    );
};

export default memo(AdditionalStakesModal);
