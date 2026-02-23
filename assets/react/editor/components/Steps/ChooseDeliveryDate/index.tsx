import React, { useEffect, useState } from 'react';
import { Button, Col } from "antd";
import dayjs from "dayjs";
import { NumericFormat } from "react-number-format";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import StepCard from '@react/editor/components/Cards/StepCard';
import {
    StyledRadioGroup,
    StyledRadioButton,
    StyledCard,
    Date,
    MonthYear,
    DeliveryCost,
    AlertMessage,
    NoteMessage,
} from './styled';
import { RadioChangeEvent } from "antd/lib";
import actions from "@react/editor/redux/actions";
import { getShippingRateByDayNumber, hasSaturdayDelivery } from "@react/editor/helper/shipping.ts";
import { StepProps } from "../interface.ts";
import { isMobile } from 'react-device-detect';
import { isEmpty, isNull } from "lodash";
import { calculateRemainingAmountForFreeShipping } from '@react/editor/helper/quantity.ts';
import ChooseDeliveryMethod from './ChooseDeliveryMethod.tsx';
import AppState from "@react/editor/redux/reducer/interface.ts";
import { StyledPopover, PopoverContent } from '../../Cards/AddonCard/styled.tsx';
import { QuestionCircleOutlined } from "@ant-design/icons";
import { getShippingFromShippingChart, checkSaturdayDeliveryEligibility } from "@react/editor/helper/shipping.ts";
import { DeliveryMethod, DeliveryMethodProps, SHIPPING_MAX_DISCOUNT_AMOUNT ,SHIPPING_MAX_DISCOUNT_AMOUNT_10 } from '@react/editor/redux/reducer/editor/interface.ts';

const ChooseDeliveryDate = ({ stepNumber }: StepProps) => {
    const state: AppState = useAppSelector(state => state);
    const canvas = state.canvas;
    const config = state.config;
    const product = config.product;
    const editor = state.editor;

    const dispatch = useAppDispatch();

    const [shippingDates, setShippingDates] = useState<{
        [key: string]: object
    }>({});
    const [cartTotal, setCartTotal] = useState<number>(0);
    const [editItemSubtotal, setEditItemSubtotal] = useState<number>(0);
    const [lastSelectedShippingDay, setLastSelectedShippingDay] = useState<any>(config.cart.currentItemShipping);
    const [showSaturdayDelivery, setShowSaturdayDelivery] = useState<boolean>(true);
    const isRequestPickup = editor.deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP;

    const handleShippingChange = (day: any) => {
        dispatch(actions.editor.updateShipping(day));
    };

    useEffect(() => {
        setLastSelectedShippingDay(config.cart.currentItemShipping);
    }, []);

    useEffect(() => {
        const isSaturday = checkSaturdayDeliveryEligibility();
        setShowSaturdayDelivery(isSaturday);
        let shippingDatesByQuantity = getShippingFromShippingChart(state);
        setShippingDates(shippingDatesByQuantity);
        let shippingDates = Object.values(shippingDatesByQuantity);

        if (!isSaturday) {
            shippingDates = shippingDates.filter((shippingDate: any) => shippingDate.isSaturday === false);
        }

        const subTotal = (config.cart.subTotal + editor.subTotalAmount) - config.cart.currentItemSubtotal;

        const fourthShippingDay: any = shippingDates[shippingDates.length - 4];
        const sixthShippingDay: any = shippingDates[shippingDates.length - 1];

        let isEditDeliveryDate: any = [];
        if(lastSelectedShippingDay) {
            isEditDeliveryDate = shippingDates.filter((shippingDate: any) => shippingDate.day === lastSelectedShippingDay.day);
        }

        if (canvas.item.itemId && !isEmpty(isEditDeliveryDate) && !isNull(lastSelectedShippingDay) && lastSelectedShippingDay.day <= fourthShippingDay.day && subTotal < 50){
            handleShippingChange(lastSelectedShippingDay);
        }else if (canvas.item.itemId && !isEmpty(isEditDeliveryDate) && !isNull(lastSelectedShippingDay) && lastSelectedShippingDay.day <= sixthShippingDay.day && subTotal >= 50){
            handleShippingChange(lastSelectedShippingDay);
        }else if (subTotal >= 50){
            handleShippingChange(shippingDates[shippingDates.length - 3]);
        } else if (subTotal < 50){
            handleShippingChange(shippingDates[shippingDates.length - 4]);
        }else {
            handleShippingChange(shippingDates[shippingDates.length - 4]);
        }

        getSubTotalWithCart();
    }, [editor.totalQuantity, editor.subTotalAmount]);

    const getSubTotalWithCart = () => {
        const subTotal = (config.cart.subTotal + editor.subTotalAmount) - config.cart.currentItemSubtotal;
        let currentEditingItemTotal = 0;
        for (const [i, item] of Object.entries(editor.items)) {
            if (!isNull(canvas.item.itemId) && !isNull(item.itemId) && item.itemId === canvas.item.itemId) {
                currentEditingItemTotal += item.totalAmount;
            }
        }
        setEditItemSubtotal(currentEditingItemTotal);
        setCartTotal(subTotal);
    };

    return <StepCard title={isRequestPickup ? "Choose Pickup Date" : "Choose Delivery Date"} stepNumber={stepNumber}>
        <StyledRadioGroup
            className="ant-row"
            value={editor.deliveryDate.date}
            onChange={(event: RadioChangeEvent | any) => handleShippingChange(JSON.parse(event.target['data-day']))}
        >
            {editor.totalQuantity <= 0 && <Col xs={24} md={24} lg={24}>
                <AlertMessage>
                    Please add 1 or more quantity to see delivery dates
                </AlertMessage>
            </Col>}
            {editor.totalQuantity > 0 && cartTotal < 50 &&
                <Col xs={24} md={24} lg={24}>
                    <AlertMessage>Free shipping on orders of $50 or more. Add ${calculateRemainingAmountForFreeShipping().toFixed(2)} more to qualify for free shipping!</AlertMessage>
                </Col>}
            {editor.totalQuantity > 0 && Object.keys(shippingDates).map(key => {
                const day: any = shippingDates[key];
                if (cartTotal < 50 && day.free) return null;
                if(!showSaturdayDelivery && day.isSaturday) return null;
                return (
                    <Col key={key} xs={8} md={6} lg={cartTotal > 50 ? 4 : hasSaturdayDelivery(shippingDates) && showSaturdayDelivery ? 5 : 6}>
                        <StyledRadioButton value={day.date} data-day={JSON.stringify(day)}>
                            <StyledCard>
                                <Date>{dayjs(day.date).format('DD')}</Date>
                                <MonthYear>{isMobile ? dayjs(day.date).format('MMM, YYYY') : dayjs(day.date).format('MMMM, YYYY')}</MonthYear>
                                <DeliveryCost>
                                    {day.free && day.discount == 0 && <span className="free-shipping">Free</span>}
                                    {!day.free && <NumericFormat
                                        displayType="text"
                                        prefix={'+$'}
                                        suffix={'/pc'}
                                        decimalScale={2}
                                        value={getShippingRateByDayNumber(day.day, shippingDates, state)/(editor.totalQuantity || 1)}
                                        fixedDecimalScale
                                    />}
                                    {day.free && day.discount > 0 && (
                                        <span className="free-shipping discount-shipping">Free +{day.discount}% OFF
                                            <StyledPopover
                                                placement="left"
                                                content={<PopoverContent>
                                                    <p className="text-start mb-0">
                                                        <b>Free +{day.discount}% OFF:</b><br/>
                                                        Choose this delivery date to<br/>
                                                        receive {day.discount}% off your order,<br/>
                                                        up to ${day.discount === 5 ? SHIPPING_MAX_DISCOUNT_AMOUNT : SHIPPING_MAX_DISCOUNT_AMOUNT_10} in savings!
                                                    </p>
                                                </PopoverContent>}
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
}

export default ChooseDeliveryDate;