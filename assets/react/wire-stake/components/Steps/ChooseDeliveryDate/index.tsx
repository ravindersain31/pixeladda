import React, { memo, useEffect, useState, useMemo, useCallback } from "react";
import { isEmpty, isNull } from "lodash";
import { QuestionCircleOutlined } from "@ant-design/icons";
import { Button, Col, RadioChangeEvent } from "antd";
import { NumericFormat } from "react-number-format";
import { isMobile } from "react-device-detect";
import dayjs from "dayjs";

// internal imports
import { StepProps } from "@wireStake/utils/interface";
import StepCard from "@wireStake/components/Cards/StepCard";
import { useAppDispatch, useAppSelector } from "@wireStake/hook";
import { DeliveryMethod, SHIPPING_MAX_DISCOUNT_AMOUNT } from "@wireStake/redux/reducer/cart/interface";
import actions from "@wireStake/redux/actions";
import {
    calculateRemainingAmountForFreeShipping,
    checkSaturdayDeliveryEligibility,
    getShippingFromShippingChart,
    getShippingRateByDayNumber,
    hasSaturdayDelivery,
} from "@wireStake/utils/helper";
import {
    AlertMessage,
    DeliveryCost,
    MonthYear,
    StyledCard,
    StyledRadioButton,
    StyledRadioGroup,
    Date
} from "./styled";
import { StyledPopover } from "@wireStake/components/Cards/AddonCard/styled";
import { PopoverContent } from "@wireStake/components/Ribbon/styled";
import { shallowEqual } from "react-redux";
import ChooseDeliveryMethod from "@wireStake/components/Steps/ChooseDeliveryMethod";

const ChooseDeliveryDate = ({ stepNumber = 2 }: StepProps) => {
    const dispatch = useAppDispatch();

    // Using useMemo to prevent unnecessary recomputation of the selectors.
    const { config, cartStage } = useAppSelector((state) => ({
        config: state.config,
        cartStage: state.cartStage,
    }), shallowEqual);

    const isRequestPickup = cartStage.deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP;

    const [shippingDates, setShippingDates] = useState<{ [key: string]: any }>({});
    const [lastSelectedShippingDay, setLastSelectedShippingDay] = useState<any>(config.cart.currentItemShipping);
    const [showSaturdayDelivery, setShowSaturdayDelivery] = useState<boolean>(true);

    const cartSubtotal = Number(config.cart?.subTotal || 0) + Number(cartStage?.subTotalAmount || 0) - Number(config.cart?.currentItemSubtotal || 0);

    // Memoizing the shippingDates object
    const memoizedShippingDates = useMemo(() => {
        const shippingOptions = getShippingFromShippingChart({ config, cartStage });
        return shippingOptions;
    }, [config, cartStage]);

    useEffect(() => {
        const saturdayEligible = checkSaturdayDeliveryEligibility();
        setShowSaturdayDelivery(saturdayEligible);

        setShippingDates(memoizedShippingDates);

        const dates = Object.values(memoizedShippingDates);
        if (dates.length === 0) return;

        const fourthDay = dates[3] as any;
        const sixthDay = dates[dates.length - 1] as any;

        const selectedMatch = dates.find((d: any) => lastSelectedShippingDay?.day === d.day);

        const shouldApplySelected =
            selectedMatch &&
            !isNull(lastSelectedShippingDay) &&
            ((lastSelectedShippingDay.day <= fourthDay.day && cartSubtotal < 50) ||
                (lastSelectedShippingDay.day <= sixthDay.day && cartSubtotal >= 50));

        const defaultDay = cartSubtotal >= 50 ? dates[dates.length - 3] : fourthDay;

        dispatch(
            actions.cartStage.updateShipping(shouldApplySelected ? lastSelectedShippingDay : defaultDay)
        );
    }, [memoizedShippingDates, cartStage.totalQuantity, cartStage.subTotalAmount]);

    const handleShippingChange = (day: any) => {
        setLastSelectedShippingDay(day);
        dispatch(actions.cartStage.updateShipping(day));
    };

    return (
        <StepCard title={isRequestPickup ? "Choose Pickup Date" : "Choose Delivery Date"} stepNumber={stepNumber}>
            <StyledRadioGroup
                className="ant-row"
                value={cartStage.deliveryDate?.date}
                onChange={(event: RadioChangeEvent | any) => handleShippingChange(JSON.parse(event.target['data-day']))}
            >
                {cartStage.totalQuantity <= 0 && (
                    <Col span={24}>
                        <AlertMessage>Please add 1 or more quantity to see delivery dates</AlertMessage>
                    </Col>
                )}

                {cartStage.totalQuantity > 0 && cartSubtotal < 50 && (
                    <Col span={24}>
                        <AlertMessage>
                            Free shipping on orders of $50 or more. Add ${calculateRemainingAmountForFreeShipping().toFixed(2)} more to qualify!
                        </AlertMessage>
                    </Col>
                )}

                {cartStage.totalQuantity > 0 && Object.entries(shippingDates).map(([key, day]: [string, any]) => {
                    if (cartSubtotal < 50 && day.free) return null;
                    if (!showSaturdayDelivery && day.isSaturday) return null;

                    return (
                        <Col
                            key={key}
                            xs={8}
                            md={6}
                            lg={cartSubtotal > 50 ? 4 : hasSaturdayDelivery(shippingDates) && showSaturdayDelivery ? 5 : 6}
                        >
                            <StyledRadioButton value={day.date} data-day={JSON.stringify(day)}>
                                <StyledCard>
                                    <Date>{dayjs(day.date).format('DD')}</Date>
                                    <MonthYear>
                                        {isMobile ? dayjs(day.date).format("MMM, YYYY") : dayjs(day.date).format("MMMM, YYYY")}
                                    </MonthYear>
                                    <DeliveryCost>
                                        {day.free && day.discount === 0 && <span className="free-shipping">Free</span>}
                                        {!day.free && (
                                            <NumericFormat
                                                displayType="text"
                                                prefix="+$"
                                                suffix="/pc"
                                                decimalScale={2}
                                                value={getShippingRateByDayNumber(day.day, shippingDates, { config, cartStage }) / (cartStage.totalQuantity || 1)}
                                                fixedDecimalScale
                                            />
                                        )}
                                        {day.free && day.discount > 0 && (
                                            <span className="free-shipping discount-shipping">
                                                Free +{day.discount}% OFF
                                                <StyledPopover
                                                    placement="left"
                                                    content={
                                                        <PopoverContent>
                                                            <p className="text-start mb-0">
                                                                <b>Free +{day.discount}% OFF:</b>
                                                                <br />
                                                                Choose this delivery date to receive {day.discount}% off your order,
                                                                <br />
                                                                up to ${SHIPPING_MAX_DISCOUNT_AMOUNT} in savings!
                                                            </p>
                                                        </PopoverContent>
                                                    }
                                                >
                                                    <Button shape="circle" icon={<QuestionCircleOutlined />} />
                                                </StyledPopover>
                                            </span>
                                        )}
                                    </DeliveryCost>
                                </StyledCard>
                            </StyledRadioButton>
                        </Col>
                    );
                })}
            </StyledRadioGroup>
            <ChooseDeliveryMethod />
        </StepCard>
    );
};

export default memo(ChooseDeliveryDate);
