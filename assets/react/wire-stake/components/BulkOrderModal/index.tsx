import { Row, Col, Input, Select, DatePicker, Form, message, Spin, InputNumber } from "antd";
import ReCAPTCHA from "react-google-recaptcha";
import { useRef, useState } from "react";
import dayjs from "dayjs";
import { StyledModal, ContactHelpWrapper, ContactCard, HeaderText, ContactRow, IconCircle, StyledButton } from './styled';
import { isMobile } from "react-device-detect";
import { formatPhone } from "@react/editor/helper/editor";
import { hasNoEmoji } from "assets/web/js/emojiValidator";

const BulkOrderModal = ({ open, onClose }: { open: boolean; onClose: () => void }) => {
    const [form] = Form.useForm();
    const [captchaToken, setCaptchaToken] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [recaptchaLoading, setRecaptchaLoading] = useState(true);
    const recaptchaRef = useRef<ReCAPTCHA>(null);
    const [showRecaptcha, setShowRecaptcha] = useState<boolean>(false);
    const attemptsRef = useRef(0);
    const MAX_ATTEMPT_COUNT = 10;
    const [value, setValue] = useState("");

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const formatted = formatPhone(e.target.value);
        setValue(formatted);
        form.setFieldsValue({
            phoneNumber: formatted,
        });
    };

    const handleShowRecaptcha = async () => {
        if (showRecaptcha && !captchaToken) {
            message.error("Please click on I'm not a Robot");
            return;
        }
        attemptsRef.current++;
        if (attemptsRef.current > MAX_ATTEMPT_COUNT) {
            setShowRecaptcha(true);
        }
    };

    const handleSubmit = async (values: any) => {
        if (!captchaToken) {
            message.error("Please click on I'm not a Robot");
            return;
        }

        setSubmitting(true);

        try {
            const response = await fetch("/api/create-bulk-order", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    ...values,
                    deliveryDate: values.deliveryDate ? values.deliveryDate.format("YYYY-MM-DD") : null,
                    recaptchaToken: captchaToken,
                    attempts: attemptsRef.current,
                }),
            });

            const data = await response.json();
            if (data.success) {
                message.success("Your request has been successfully submitted. We will contact you within one hour with more information.");
                form.resetFields();
                setCaptchaToken(null);
                attemptsRef.current = 0;
                recaptchaRef.current?.reset();
                setShowRecaptcha(false);
                onClose();
            } else {
                message.error(data.error || "Failed to submit bulk order");
            }
        } catch (err) {
            message.error("Server error while submitting bulk order");
        } finally {
            setSubmitting(false);
        }
    };

    const LiveChat = (event: React.MouseEvent) => {
        //@ts-ignore
        Tawk_API.toggle();
        onClose();
    };

    const noEmojiValidator = (_: any, value: string) => {
        return hasNoEmoji(value)
            ? Promise.resolve()
            : Promise.reject(new Error("Emojis are not allowed."));
    };

    return (
        <StyledModal
            title="Bulk Order Request"
            open={open}
            onCancel={onClose}
            footer={null}
            width={700}
            rootClassName="bull-order-request-modal"
            afterClose={() => form.resetFields()}
        >
            <Form
                form={form}
                layout="vertical"
                onFinish={handleSubmit}
                // onFinishFailed={handleShowRecaptcha}
                requiredMark={(label, { required }) =>
                    required ? (
                        <>
                            {label}
                            <span style={{ color: "red", marginLeft: 4 }}>*</span>
                        </>
                    ) : (
                        label
                    )
                }
            >
                <Row gutter={16}>
                    <Col xs={12} md={8}>
                        <Form.Item
                            name="firstName"
                            label="First Name"
                            rules={[{ required: true, message: "First Name is required" }, { validator: noEmojiValidator}]}
                        >
                            <Input placeholder="Enter first name" onKeyDown={(e) => {
                                if (!/^[a-zA-Z\s]*$/.test(e.key)) {
                                    e.preventDefault();
                                }
                            }} />
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={8}>
                        <Form.Item
                            name="lastName"
                            label="Last Name"
                            rules={[{ required: true, message: "Last Name is required" }, { validator: noEmojiValidator }]}
                        >
                            <Input placeholder="Enter last name" onKeyDown={(e) => {
                                if (!/^[a-zA-Z\s]*$/.test(e.key)) {
                                    e.preventDefault();
                                }
                            }} />
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={8}>
                        <Form.Item
                            name="phoneNumber"
                            label="Phone Number"
                            rules={[
                                { required: true, message: "Phone Number is required" },
                                {
                                    pattern: /^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$/,
                                    message: "Please enter a valid phone number"
                                },
                            ]}
                        >
                            <Input
                                placeholder="Enter phone number"
                                maxLength={14}
                                value={value}
                                onChange={handleChange}
                            />
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={8}>
                        <Form.Item
                            name="email"
                            label="Email Address"
                            rules={[{ type: "email", required: true, message: "Valid Email is required" }, { validator: noEmojiValidator }]}
                        >
                            <Input placeholder="Enter email address" />
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={8}>
                        <Form.Item name="company" label="Company" rules={[{ validator: noEmojiValidator }]}>
                            <Input placeholder="Enter company name (optional)" />
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={8}>
                        <Form.Item name="quantity" label="Quantity">
                            <InputNumber
                                style={{ width: "100%" }}
                                type="number"
                                inputMode="numeric"
                                placeholder="Enter quantity (optional)"
                                min={0}
                                onKeyDown={(e: any) => {
                                    const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'];
                                    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                                        e.preventDefault();
                                        const inputElement = e.target;
                                        inputElement.select();
                                        return;
                                    }
                                    if (!allowedKeys.includes(e.key)) {
                                        const key = Number(e.key)
                                        if (isNaN(key) || e.key === null || e.key === ' ') {
                                            // Prevent non-numeric characters from being entered.
                                            e.preventDefault();
                                        }
                                    }
                                }}
                                changeOnWheel={false} />
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={8}>
                        <Form.Item name="budget" label="Budget">
                            <Select placeholder="Select budget (optional)" popupMatchSelectWidth={false}>
                                <Select.Option value="No Budget Preference">No Budget Preference</Select.Option>
                                <Select.Option value="Under $1,000">Under $1,000</Select.Option>
                                <Select.Option value="$1,000 - $5,000">$1,000 - $5,000</Select.Option>
                                <Select.Option value="$5,000 - $10,000">$5,000 - $10,000</Select.Option>
                                <Select.Option value="Above $10,000">Above $10,000</Select.Option>
                            </Select>
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={8}>
                        <Form.Item name="deliveryDate" label="Delivery Date">
                            <DatePicker
                                style={{ width: "100%" }}
                                placeholder="MM/DD/YYYY (optional)"
                                format="MM/DD/YYYY"
                                disabledDate={(current) => current && current <= dayjs().endOf("day")}
                            />
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={8}>
                        <Form.Item name="interestedProducts" label="Interested Products" rules={[{ validator: noEmojiValidator }]}>
                            <Input placeholder="Enter products (optional)" />
                        </Form.Item>
                    </Col>

                    <Col xs={12} md={24}>
                        <Form.Item name="comment" label="Comment" style={{ marginBottom: 0 }} rules={[{ validator: noEmojiValidator }]}>
                            <Input.TextArea placeholder="Enter message (optional)" rows={2} />
                        </Form.Item>
                    </Col>
                </Row>

                {/* {showRecaptcha && ( */}
                <Row justify="center">
                    <Col>
                        {recaptchaLoading && <Spin />}
                        <div style={{ display: recaptchaLoading ? "none" : "block" }}>
                            <ReCAPTCHA
                                ref={recaptchaRef}
                                sitekey={window.recaptchaSiteKey}
                                onChange={(token) => setCaptchaToken(token)}
                                asyncScriptOnLoad={() => setRecaptchaLoading(false)}
                                style={{ transform: "scale(0.7)" }}
                            />
                        </div>
                    </Col>
                </Row>
                {/* )} */}

                <Row justify="center">
                    <Col>
                        <StyledButton
                            type="primary"
                            htmlType="submit"
                            loading={submitting}
                            disabled={submitting}
                        >
                            Submit
                        </StyledButton>
                    </Col>
                </Row>
            </Form>

            <ContactHelpWrapper>
                <HeaderText>
                    <p>Need Help?</p>
                    {!isMobile && <p>Choose your preferred way to connect with us</p>}
                </HeaderText>
                <ContactRow justify={'center'}>
                    <ContactCard>
                        <IconCircle><i className="far fa-comment" /></IconCircle>
                        <StyledButton className="bulk-live-chat" type='link' onClick={LiveChat}>Live Chat</StyledButton>
                    </ContactCard>
                    <ContactCard>
                        <IconCircle><i className="fas fa-phone" /></IconCircle>
                        <StyledButton type='link' href="tel: +1-877-958-1499">Call Now</StyledButton>
                    </ContactCard>
                    <ContactCard>
                        <IconCircle><i className="far fa-envelope" /></IconCircle>
                        <StyledButton type='link' href="mailto: sales@yardsignplus.com">Send Email</StyledButton>
                    </ContactCard>
                </ContactRow>
            </ContactHelpWrapper>
        </StyledModal>
    );
};

export default BulkOrderModal;
