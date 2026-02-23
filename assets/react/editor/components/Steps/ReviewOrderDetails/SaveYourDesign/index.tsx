import {
    SaveYourDesignWrapper,
    DeliveryNote,
    IncludedList,
    IncludedItem,
    SaveYourDesignButton,
    StyledModal,
    EmailInput,
    SaveButton, IncludedEveryPurchaseMobile,
    EmailQuoteButton,
    QuoteActionsWrapper,
    DownloadQuoteButton,
} from "./styled";
import {Form, message} from 'antd';
import {useEffect, useState} from "react";
import IncludedEveryPurchase from "../IncludedEveryPurchase"
import { validateEmail } from "@react/editor/helper/emailValidator";
import { useAppSelector } from "@react/editor/hook.ts";

import EmailQuote from "assets/web/home/components/QuickQuote/EmailQuote";
import axios from "axios";

interface EmailQuoteFormValues {
    orderQuoteEmail: string;
}


const SaveYourDesign = (
    {
        isAddingToCart,
        onAddToCart
    }: {
        isAddingToCart: boolean,
        onAddToCart: (data?: any) => void
    }
) => {
    const [saveForm] = Form.useForm();
    const [quoteForm] = Form.useForm();
    const [modalType, setModalType] = useState<"save" | "quote" | null>(null);

    const editor = useAppSelector(state => state.editor);

    const editorData: any = JSON.parse(JSON.stringify(editor));

    const myAccountUrl = `${window.location.origin}/customer/`;


    const items = [
        'Expedited Rush Deliveries',
        'FREE Design Previews',
        'No Tax',
        'Expert Help Always Available',
        'FREE Shipping on Qualifying Orders',
        'No Hidden Fees',
        'Bulk Discounts on Large Quantities',
        'Unlimited Proof Revisions',
        'No Minimum Order Quantities',
    ];

    const onSave = (values: any) => {
        onAddToCart(values);

        if (modalType === "save") saveForm.resetFields();
        if (modalType === "quote") quoteForm.resetFields();
    }

    const handleClose = () => {
        if (modalType === "save") saveForm.resetFields();
        if (modalType === "quote") quoteForm.resetFields();
        setModalType(null);
    };

    useEffect(() => {
        if (!isAddingToCart && modalType) {
            if (modalType === "save") {
                saveForm.resetFields();
            } else {
                quoteForm.resetFields();
            }
            setModalType(null);
        }
    }, [isAddingToCart]);

    const handleDownloadQuote = async () => {
        try {
            const response = await axios.post(
                "/cart/download-quote",
                { editor: editorData },
                { responseType: "blob" }
            );

            const blob = new Blob([response.data], { type: "application/pdf" });
            const url = window.URL.createObjectURL(blob);

            const link = document.createElement("a");
            link.href = url;
            link.download = "quote.pdf";
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            message.success("Quote downloaded successfully!");
        } catch (error) {
            message.error("Something went wrong while downloading.");
        }
    };

    return <SaveYourDesignWrapper>
        <DeliveryNote>
            We will email you a digital proof in 1 hour. Once approved, we will begin processing your order.
        </DeliveryNote>
        <IncludedEveryPurchase items={items} />
        {/* <IncludedEveryPurchaseMobile>
            <h5>Included With Every Purchase</h5>
            <IncludedList>
                {items.map((item, index) => <IncludedItem key={index} xs={24} sm={12} md={8}>
                    <img src="https://static.yardsignplus.com/assets/check-circle.png" alt="circle"/>
                    {item}
                </IncludedItem>)}
            </IncludedList>
        </IncludedEveryPurchaseMobile> */}
        <QuoteActionsWrapper>
            <SaveYourDesignButton onClick={() => setModalType("save")}>
                Save Your Design
            </SaveYourDesignButton>

            <EmailQuoteButton onClick={() => setModalType("quote")}>
                Email Quote
            </EmailQuoteButton>

            <DownloadQuoteButton onClick={handleDownloadQuote}>
                Download Quote
            </DownloadQuoteButton>
        </QuoteActionsWrapper>

        {/* EMAIL QUOTE MODAL */}
        <StyledModal
            title="Email Quote"
            open={modalType === "quote"}
            onCancel={handleClose}
            footer={null}
        >
            <p>
                Please complete the fields below to save your order. We will email you a link to your shopping cart.
                You can access your saved quotes anytime from the&nbsp;
                <a href={myAccountUrl}>My Account</a> page.
                You may also proceed to checkout and choose See Design - Pay Later.
            </p>

            <Form form={quoteForm} layout="vertical" onFinish={onSave}>
                <Form.Item
                    name="orderQuoteEmail"
                    label="Email Address"
                    rules={[
                        { required: true, message: 'This field is required' },
                        { validator: validateEmail }
                    ]}
                    validateFirst
                >
                    <EmailInput placeholder="Email" type="email" />
                </Form.Item>

                <Form.Item>
                    <SaveButton type="primary" htmlType="submit" disabled={isAddingToCart}>
                        {isAddingToCart ? 'Sending...' : 'Send Quote'}
                    </SaveButton>
                </Form.Item>
            </Form>
        </StyledModal>

        <StyledModal
            title="Your Saved Design"
            open={modalType === "save"}
            onCancel={handleClose}
            footer={null}
        >
            <p>
                Please complete the fields below to save your order. We will email you a link to your design.
                You can access your saved design anytime from the <a href={myAccountUrl}>
                My Account </a>page. You may also proceed to checkout and choose See Design - Pay Later.
            </p>
            <Form form={saveForm} layout="vertical" onFinish={onSave}>
                <Form.Item 
                    name="saveDesignEmail"
                    label="Email Address" 
                    rules={[
                        { required: true, message: 'This field is required' },
                        { validator: validateEmail }
                    ]}
                    validateFirst
                >
                    <EmailInput placeholder="Email" type="email"/>
                </Form.Item>
                <Form.Item className="save-design-footer">
                    <SaveButton type="primary" htmlType="submit" disabled={isAddingToCart}>
                        {isAddingToCart ? 'Saving Design...' : 'Save Design'}
                    </SaveButton>
                </Form.Item>
            </Form>
        </StyledModal>
    </SaveYourDesignWrapper>
}

export default SaveYourDesign;