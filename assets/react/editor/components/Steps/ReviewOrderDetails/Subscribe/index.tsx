import {
  SubscribeWrapper,
  EmailInput,
  SaveButton,
  StyledForm,
  HeadingBox,
} from "./styled";
import { Form, message } from "antd";
import { useState } from "react";
import { MailOutlined } from "@ant-design/icons";
import axios from "axios";

declare global {
  interface Window {
    _klOnsite: any[];
    klaviyoEnv: string;
  }
}

const Subscribe = () => {
  const [form] = Form.useForm();
  const [isSaving, setIsSaving] = useState(false);

  const handleSubmit = async (values: any) => {
    const email = values.email;

    if (!email) {
      message.error("Please enter a valid email.");
      return;
    }

    setIsSaving(true);

    try {
      const response = await axios.post("/api/subscribe", { email });
      const result = response.data;

      if (result.success) {
        if (window.klaviyoEnv === 'production') {
          window._klOnsite = window._klOnsite || [];
          window._klOnsite.push(['identify', { email }]);
          window._klOnsite.push(['track', 'Promotional Updates', { source: 'Editor Page' }]);
        }

        message.success("Subscribed to promotional updates!");
        form.resetFields();
      } else {
        message.error(result.message || "Subscription failed.");
      }
    } catch (error) {
      message.error("Something went wrong. Please try again.");
    } finally {
      setIsSaving(false);
    }
  };

  return (
      <SubscribeWrapper>
        <HeadingBox>
          <h5>Receive promotional updates (optional)</h5>
        </HeadingBox>
        <StyledForm form={form} layout="inline" onFinish={handleSubmit}>
          <Form.Item
            name="email"
            rules={[
              { required: true, message: " " },
              { type: "email", message: " " },
            ]}
          >
            <EmailInput placeholder="Enter email address" type="email" />
          </Form.Item>
          <Form.Item>
            <SaveButton type="primary" htmlType="submit" disabled={isSaving}>
              {isSaving ? "Saving ..." : "Save Email"}
            </SaveButton>
          </Form.Item>
        </StyledForm>
      </SubscribeWrapper>
  );
};

export default Subscribe;
