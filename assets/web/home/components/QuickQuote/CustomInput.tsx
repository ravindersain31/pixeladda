import React from 'react';
import { Row, Col, Tooltip, Form, InputNumber, Popover } from 'antd';
import { QuestionCircleOutlined } from '@ant-design/icons';
import {
    CustomSearchWrapper,
    StyledCard,
    Title,
    QuestionButton,
    PopoverContent,
} from '../Search/styled'; // Adjust the import path
import { StyledInputNumber } from "./styled";
import { isMobile } from 'react-device-detect';
import { FormInstance } from 'antd';

interface CustomInputProps {
    maxWidth: number;
    maxHeight: number;
    templateSize: { width: number; height: number };
    showWidthSuffix: boolean;
    setShowWidthSuffix: (value: boolean) => void;
    showHeightSuffix: boolean;
    setShowHeightSuffix: (value: boolean) => void;
    handleSizeChange: (width: number, height: number) => void;
    handleQuantityChange: (value: number) => void;
    form: FormInstance;
    quantity: number;
    calculatedData: { [key: string]: number };
  }

const CustomInput = ({
    maxWidth,
    maxHeight,
    templateSize,
    showWidthSuffix,
    setShowWidthSuffix,
    showHeightSuffix,
    setShowHeightSuffix,
    handleSizeChange,
    handleQuantityChange,
    form,
    quantity,
    calculatedData
}: CustomInputProps) => {

    const MAX_ALLOWED_QUANTITY = 100000;
    const tooltipText = "The largest size we can produce is 48 x 96 (width x height) or 96 x 48 in inches.";

    return (
        <CustomSearchWrapper className='inputSize'>
            <StyledCard>
                <Row
                    justify="center"
                    align="middle"
                    className="fw-bold py-2 w-100"
                    gutter={isMobile ? [8, 8] : [16, 16]}
                >
                    <Col xs={7} sm={7} md={6}>
                        <Tooltip
                            title={showWidthSuffix ? tooltipText : ""}
                            open={showWidthSuffix}
                            onOpenChange={setShowWidthSuffix}
                            color="var(--primary-color)"
                            overlayStyle={{ fontSize: "12px", width: "200px" }}
                        >
                            <Form.Item
                                name="width"
                                rules={[{ required: true, message: "Please enter width" }]}
                                status={form.getFieldError("width") ? "error" : ""}
                                help={false}
                                className="input-field"
                                hasFeedback
                            >
                                <InputNumber
                                    placeholder={isMobile ? "Width (in.)" : "Enter Width (inches)"}
                                    min={1}
                                    precision={0}
                                    type="text"
                                    inputMode="numeric"
                                    max={maxWidth}
                                    value={templateSize.width}
                                    onKeyUp={(e: any) => {
                                        setShowWidthSuffix(e.target.value > maxWidth);
                                    }}
                                    onChange={(value: any) =>
                                        handleSizeChange(Number(value), templateSize.height)
                                    }
                                    onKeyDown={(e: any) => {
                                        setShowWidthSuffix(e.target.value > maxWidth);
                                        const isNumericInput = /^[0-9\b]+$/;
                                        if (
                                            !(
                                                isNumericInput.test(e.key) ||
                                                [
                                                    "Backspace",
                                                    "Delete",
                                                    "ArrowLeft",
                                                    "ArrowRight",
                                                    "Tab",
                                                    "Enter",
                                                ].includes(e.key)
                                            )
                                        ) {
                                            e.preventDefault();
                                        }
                                        if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                                            e.preventDefault();
                                            const inputElement = e.target;
                                            inputElement.select();
                                            return;
                                        }
                                        const notAllowedKeys = [".", "e", "-"];
                                        if (notAllowedKeys.includes(e.key)) {
                                            e.preventDefault();
                                        }
                                    }}
                                    changeOnWheel={false}
                                />
                            </Form.Item>
                            <Title className='title'>{isMobile ? "Width (in.)" : "Enter Width (inches)"}
                                <Popover
                                    placement="bottom"
                                    color="var(--primary-color)"
                                    overlayStyle={{ fontSize: "12px", width: "200px" }}
                                    content={<PopoverContent>{tooltipText}</PopoverContent>}
                                >
                                    <QuestionButton
                                        shape="circle"
                                        icon={<QuestionCircleOutlined />}
                                    />
                                </Popover>
                            </Title>
                        </Tooltip>
                    </Col>

                    <span className="multiply">x</span>
                    <Col xs={7} sm={7} md={6}>
                        <Tooltip
                            title={showHeightSuffix ? tooltipText : ""}
                            open={showHeightSuffix}
                            onOpenChange={setShowHeightSuffix}
                            color="var(--primary-color)"
                            overlayStyle={{ fontSize: "12px", width: "200px" }}
                        >
                            <Form.Item
                                name="height"
                                rules={[{ required: true, message: "Please enter height" }]}
                                status={form.getFieldError("height") ? "error" : ""}
                                help={false}
                                className="input-field"
                                hasFeedback
                            >
                                <InputNumber
                                    placeholder={isMobile ? "Height (in.)" : "Enter Height (inches)"}
                                    min={1}
                                    precision={0}
                                    type="text"
                                    inputMode="numeric"
                                    max={maxHeight}
                                    value={templateSize.height}
                                    onKeyUp={(e: any) => {
                                        setShowHeightSuffix(e.target.value > maxHeight);
                                    }}
                                    onChange={(value: any) =>
                                        handleSizeChange(templateSize.width, Number(value))
                                    }
                                    onKeyDown={(e: any) => {
                                        setShowHeightSuffix(e.target.value > maxHeight);
                                        const isNumericInput = /^[0-9\b]+$/;
                                        if (
                                            !(
                                                isNumericInput.test(e.key) ||
                                                [
                                                    "Backspace",
                                                    "Delete",
                                                    "ArrowLeft",
                                                    "ArrowRight",
                                                    "Tab",
                                                    "Enter",
                                                ].includes(e.key)
                                            )
                                        ) {
                                            e.preventDefault();
                                        }
                                        if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                                            e.preventDefault();
                                            const inputElement = e.target;
                                            inputElement.select();
                                            return;
                                        }
                                        const notAllowedKeys = [".", "e", "-"];
                                        if (notAllowedKeys.includes(e.key)) {
                                            e.preventDefault();
                                        }
                                    }}
                                    changeOnWheel={false}
                                />
                            </Form.Item>
                            <Title className="title">{isMobile ? "Height (in.)" : "Enter Height (inches)"}
                                <Popover
                                    placement="bottom"
                                    color="var(--primary-color)"
                                    overlayStyle={{ fontSize: "12px", width: "200px" }}
                                    content={<PopoverContent>{tooltipText}</PopoverContent>}
                                >
                                    <QuestionButton
                                        shape="circle"
                                        icon={<QuestionCircleOutlined />}
                                    />
                                </Popover>
                            </Title>
                        </Tooltip>
                    </Col>

                    <Col xs={7} sm={7} md={5}>
                        <Form.Item
                            name="quantity"
                            help={false}
                            initialValue={1}
                            status={form.getFieldError("quantity") ? "error" : ""}
                            rules={[{ required: true, message: 'Please enter a quantity!' }]}
                        >
                            <StyledInputNumber
                                placeholder={isMobile ? "Enter Qty" : "Enter Quantity"}
                                min={1}
                                className="quantity-input"
                                precision={0}
                                type="text"
                                inputMode="numeric"
                                max={MAX_ALLOWED_QUANTITY}
                                onChange={(value: any) => handleQuantityChange(value)}
                                onKeyDown={(e: any) => {
                                    if (e.ctrlKey || e.metaKey) {
                                        return;
                                    }
                                    const isNumericInput = /^[0-9\b]+$/;
                                    if (
                                        !(
                                            isNumericInput.test(e.key) ||
                                            [
                                                "Backspace",
                                                "Delete",
                                                "ArrowLeft",
                                                "ArrowRight",
                                                "Tab",
                                                "Enter",
                                            ].includes(e.key)
                                        )
                                    ) {
                                        e.preventDefault();
                                    }
                                    const notAllowedKeys = [".", "e", "-"];
                                    if (notAllowedKeys.includes(e.key)) {
                                        e.preventDefault();
                                    }
                                }}
                                changeOnWheel={false}
                            />
                        </Form.Item>
                        <Title>Enter Quantity</Title>
                    </Col>

                    <Col xs={24} sm={24} md={5} className="price py-2">
                        <span>
                            <i className="fa-solid fa-tags price-tag"></i>
                            Price Each:
                        </span>
                        <span className="pricing">${calculatedData.totalAmount >= 0 ? (calculatedData.totalAmount / (quantity ?? 1)).toFixed(2) : 0.00}</span>
                    </Col>
                </Row>
            </StyledCard>
        </CustomSearchWrapper>
    )
};

export default CustomInput;
