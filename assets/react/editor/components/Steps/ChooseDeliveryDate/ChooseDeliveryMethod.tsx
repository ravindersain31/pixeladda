import React, { useEffect, useState } from "react";
import { Button, Col, Row, Tooltip } from "antd";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import {
	AdditionalNote,
	ShippingMethod,
	StyledButton,
	StyledCheckmark,
	StyledRow,
	StyledTooltip,
} from "./styled";
import { RadioChangeEvent } from "antd/lib";
import { PopoverContent, StyledPopover } from "../../Cards/AddonCard/styled";
import { QuestionCircleOutlined } from "@ant-design/icons";
import actions from "@react/editor/redux/actions";
import { DeliveryMethod, DeliveryMethodProps } from "@react/editor/redux/reducer/editor/interface";
import { CheckOutlined } from "@ant-design/icons";
import { isMobile } from "react-device-detect";

const ChooseDeliveryMethod = () => {
	const canvas = useAppSelector((state) => state.canvas);
	const product = useAppSelector((state) => state.config.product);
	const config = useAppSelector((state) => state.config);
	const editor = useAppSelector((state) => state.editor);
	const dispatch = useAppDispatch();

	const [deliveryMethod, setDeliveryMethod] = useState<DeliveryMethodProps|any>(
		editor.deliveryMethod
	);
	const [deliveryMethods, setDeliveryMethods] = React.useState<{
		[key: string]: DeliveryMethodProps;
	}>(config.product.deliveryMethods);

	const requestPickupMethod = deliveryMethods[DeliveryMethod.REQUEST_PICKUP];

	const handleShippingChange = (day: any) => {
		dispatch(actions.editor.updateShipping(day));
	};

	useEffect(()=>{
		if(!canvas.item.itemId){
		const deliveryMethods = Object.values(product.deliveryMethods);
		setDeliveryMethod(deliveryMethods[deliveryMethods.length - 2]);
		}
	}, []);

	useEffect(() => {
		setDeliveryMethods(product.deliveryMethods);
	}, [product, editor.totalQuantity, editor.subTotalAmount]);

	useEffect(() => {
		dispatch(actions.editor.updateDeliveryMethod(deliveryMethod));
	}, [editor.deliveryDate, deliveryMethod]);

	useEffect(() => {
		handleShippingChange(editor.deliveryDate);
	}, [deliveryMethod]);

	const handleDeliveryMethodChange = (method: DeliveryMethodProps) => {
		dispatch(actions.editor.updateDeliveryMethod(method));
		setDeliveryMethod(method);
	};

	const toggleDeliveryMethod = () => {
		const methodsArray = Object.values(deliveryMethods);
		const newMethod = deliveryMethod.key === methodsArray[0].key ? methodsArray[1] : methodsArray[0];
		handleDeliveryMethodChange(newMethod);
	};

	const toggleBlindShipping = () => {
		dispatch(actions.editor.updateBlindShipping(!editor.isBlindShipping));
	};

	const handlePopoverButtonClick = (event: React.MouseEvent) => {
		event.stopPropagation();
	};

	return (
		<>
		{editor.totalQuantity > 0 && (
			<StyledRow gutter={[8, 8]}>
				<Col xs={12} sm={12} md={6} lg={6}>
					<ShippingMethod>
						<StyledButton
						onClick={toggleDeliveryMethod}
						$disabled={
							deliveryMethod.key !== DeliveryMethod.REQUEST_PICKUP
						}
						className={`${
							deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP
							? "active"
							: ""
						}`}
						>
						<StyledCheckmark
							className={
							deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP
								? "checkmark"
								: ""
							}
						>
							<CheckOutlined style={{ color: "#FFF" }} />
						</StyledCheckmark>
						{requestPickupMethod.label}
						<StyledPopover
							placement={isMobile ? "bottom" : undefined}
							content={
							<PopoverContent onClick={handlePopoverButtonClick}>
								<>
								<p className="text-start mb-0">
									<b>{requestPickupMethod.label}:</b>
									<br />
									Pickup from our Houston, TX warehouse is
									<br />
									offered for all delivery dates. Please choose
									<br />
									your delivery date. We will change your
									<br />
									order to pickup. Once ready, we will contact
									<br />
									you. Your delivery fee will be discounted by
									<br />
									50% (does not apply to same day delivery
									<br />
									requests).
								</p>
								</>
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
				</Col>
				<Col xs={12} sm={12} md={6} lg={6}>
					<ShippingMethod style={{ textAlign: "left" }}>
						<StyledButton
						onClick={toggleBlindShipping}
						$disabled={!editor.isBlindShipping}
						className={`${editor.isBlindShipping ? "active" : "" }`}
						>
						<StyledCheckmark className={editor.isBlindShipping ? "checkmark" : "" }>
							<CheckOutlined style={{ color: "#FFF" }} />
						</StyledCheckmark>
							Request Blind Shipping
						<StyledPopover
							placement={isMobile ? "right" : undefined}
							content={
							<PopoverContent onClick={handlePopoverButtonClick}>
								<>
								<p className="text-start mb-0">
									<b>Request Blind Shipping:</b>
									<br />
									Blind Shipping offers you to receive<br />
									products without our company name<br />
									listed on the shipping label.<br />
									We will not include a packing slip.<br />
									This is only recommended for resellers.<br />
								</p>
								</>
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
				</Col>
					<AdditionalNote>
						We will email you a digital proof within 1 hour for your review. Once approved we will begin production. Delivery dates rotate at 4pm CST to the next business day.
					</AdditionalNote>
		</StyledRow>
		)}
		</>
	);
};

export default ChooseDeliveryMethod;
