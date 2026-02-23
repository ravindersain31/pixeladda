import React, { memo, useEffect, useMemo } from "react";
import { Button, Col, Row } from "antd";
import { QuestionCircleOutlined, CheckOutlined } from "@ant-design/icons";
import { isMobile } from "react-device-detect";

// internal imports
import { useAppDispatch, useAppSelector } from "@orderBlankSign/hook.ts";
import {
    ShippingMethod,
    StyledButton,
    StyledCheckmark,
    StyledRow,
    StyledCol,
} from "@orderBlankSign/components/Steps/ChooseDeliveryDate/styled";
import {
    PopoverContent,
    StyledPopover,
} from "@orderBlankSign/components/Cards/AddonCard/styled";
import actions from "@orderBlankSign/redux/actions";
import { DeliveryMethod, IDeliveryMethodProps } from "@orderBlankSign/redux/reducer/cart/interface";

const ChooseDeliveryMethod = memo(() => {
    const dispatch = useAppDispatch();
    const config = useAppSelector((state) => state.config);
    const cartStage = useAppSelector((state) => state.cartStage);

    const deliveryMethod = cartStage.deliveryMethod;

    const deliveryMethods = useMemo(() => config.product.deliveryMethods,[config.product.deliveryMethods]);

    const requestPickupMethod = useMemo(() => deliveryMethods[DeliveryMethod.REQUEST_PICKUP],[deliveryMethods]);

    const handlePopoverButtonClick = (event: React.MouseEvent) => {
        event.stopPropagation();
    };

    useEffect(() => {
        dispatch(actions.cartStage.updateDeliveryMethod(deliveryMethod));
    }, [cartStage.deliveryDate, deliveryMethod]);

    const handleShippingChange = (day: any) => {
        dispatch(actions.cartStage.updateShipping(day));
	};

    useEffect(() => {
        handleShippingChange(cartStage.deliveryDate);
	}, [deliveryMethod]);

    const toggleDeliveryMethod = () => {
        const isPickupSelected = deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP;

        const newMethod: IDeliveryMethodProps = isPickupSelected ? deliveryMethods[DeliveryMethod.DELIVERY] : deliveryMethods[DeliveryMethod.REQUEST_PICKUP];
        dispatch(actions.cartStage.updateDeliveryMethod(newMethod));
    };

    const toggleBlindShipping = () => {
        dispatch(actions.cartStage.updateBlindShipping(!cartStage.isBlindShipping));
    };

    if (cartStage.totalQuantity <= 0) return null;

    return (
        <StyledRow gutter={[8, 8]}>
            {/* Pickup Option */}
            <StyledCol xs={12} sm={12} md={6} lg={6}>
                <ShippingMethod>
                    <StyledButton
                        onClick={toggleDeliveryMethod}
                        $disabled={deliveryMethod.key !== DeliveryMethod.REQUEST_PICKUP}
                        className={deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP ? "active" : ""}
                    >
                        <StyledCheckmark className={deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP ? "checkmark" : ""}>
                            <CheckOutlined style={{ color: "#FFF" }} />
                        </StyledCheckmark>
                        {requestPickupMethod.label}
                        <StyledPopover
                            placement={isMobile ? "bottom" : undefined}
                            content={
                                <PopoverContent onClick={handlePopoverButtonClick}>
                                    <p className="text-start mb-0">
                                        <b>{requestPickupMethod.label}:</b><br />
                                        Pickup from our Houston, TX warehouse is<br />
                                        offered for all delivery dates. Please choose<br />
                                        your delivery date. We will change your<br />
                                        order to pickup. Once ready, we will contact<br />
                                        you. Your delivery fee will be discounted by<br />
                                        50% (does not apply to same day delivery<br />
                                        requests).
                                    </p>
                                </PopoverContent>
                            }
                        >
                            <Button
                                shape="circle"
                                icon={<QuestionCircleOutlined />}
                                onClick={handlePopoverButtonClick}
                            />
                        </StyledPopover>
                    </StyledButton>
                </ShippingMethod>
            </StyledCol>

            {/* Blind Shipping Option */}
            <StyledCol xs={12} sm={12} md={6} lg={6}>
                <ShippingMethod style={{ textAlign: "left" }}>
                    <StyledButton
                        onClick={toggleBlindShipping}
                        $disabled={!cartStage.isBlindShipping}
                        className={cartStage.isBlindShipping ? "active" : ""}
                    >
                        <StyledCheckmark className={cartStage.isBlindShipping ? "checkmark" : ""}>
                            <CheckOutlined style={{ color: "#FFF" }} />
                        </StyledCheckmark>
                        Request Blind Shipping
                        <StyledPopover
                            placement={isMobile ? "right" : undefined}
                            content={
                                <PopoverContent onClick={handlePopoverButtonClick}>
                                    <p className="text-start mb-0">
                                        <b>Request Blind Shipping:</b><br />
                                        Blind Shipping offers you to receive<br />
                                        products without our company name<br />
                                        listed on the shipping label.<br />
                                        We will not include a packing slip.<br />
                                        This is only recommended for resellers.
                                    </p>
                                </PopoverContent>
                            }
                        >
                            <Button
                                shape="circle"
                                icon={<QuestionCircleOutlined />}
                                onClick={handlePopoverButtonClick}
                            />
                        </StyledPopover>
                    </StyledButton>
                </ShippingMethod>
            </StyledCol>
        </StyledRow>
    );
});

export default ChooseDeliveryMethod;
