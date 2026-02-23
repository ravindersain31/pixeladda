import React from 'react'
import { StyledSaveDesignModal } from './styled'
import { Form, FormInstance } from 'antd'
import { EmailInput, SaveButton } from '@react/editor/components/Steps/ReviewOrderDetails/SaveYourDesign/styled'

interface EmailQuote {
    isSaveDesignModalVisible: boolean;
    showEmailQuoteModal: (value: boolean) => void;
    emailQuoteForm: FormInstance;
    handleEmailQuoteSubmit: (values: any) => void;
    isOrderQuote: boolean;
}

const EmailQuote = ({ isSaveDesignModalVisible, showEmailQuoteModal, emailQuoteForm, handleEmailQuoteSubmit, isOrderQuote }: EmailQuote) => {

    return (
        <>
            <StyledSaveDesignModal
                title="Email Quote"
                open={isSaveDesignModalVisible}
                okText="Save"
                onCancel={() => showEmailQuoteModal(false)}
                footer={null}
                afterClose={() => emailQuoteForm.resetFields()}
            >
                <p>
                    Please complete the fields below to save your order. We will email you a link to your design. You may
                    also proceed to checkout and choose See Design - Pay Later.
                </p>
                <Form form={emailQuoteForm} layout="vertical" onFinish={handleEmailQuoteSubmit}>
                    <Form.Item name={['orderQuoteEmail']} label="Email Address" rules={[{ required: true, message: 'This field is required' }, { type: 'email', message: 'The email must be valid email address' }]}>
                        <EmailInput placeholder="Email" type="email" />
                    </Form.Item>
                    <Form.Item className='text-center'>
                        <SaveButton type="primary" htmlType="submit" loading={isOrderQuote}>
                            {isOrderQuote ? 'Sending Quote...' : 'Email Quote'}
                        </SaveButton>
                    </Form.Item>
                </Form>
            </StyledSaveDesignModal>
        </>
    )
}

export default EmailQuote