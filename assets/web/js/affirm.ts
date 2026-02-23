import axios from "axios";
import { message } from "antd";

declare global {
  interface Window {
    affirm?: {
      checkout: {
        (config: { 
          public_api_key: string; 
          country_code?: string; 
          locale?: string 
        }): void;
        open: (options: {
          onFail: () => void;
          onSuccess: (data: { checkout_token: string }) => void;
          onOpen?: (token: string) => void;
          onValidationError?: (error: any) => void;
        }) => void;
      };
    };
  }
}

export async function initializeAffirmCheckout(orderDetails: any) {
  try {

    const env = orderDetails.metadata?.env || 
               (orderDetails.merchant?.public_api_key?.startsWith('ITGX') ? 'sandbox' : 'live');
    
    const scriptUrl = env === 'sandbox'
      ? 'https://cdn1-sandbox.affirm.com/js/v2/affirm.js'
      : 'https://cdn1.affirm.com/js/v2/affirm.js';
    
    if (!window.affirm) {
      await new Promise<void>((resolve, reject) => {
        const script = document.createElement('script');
        script.src = scriptUrl;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Affirm script'));
        document.head.appendChild(script);
      });
    }

    const checkoutOptions = {
      merchant: {
        user_confirmation_url: orderDetails.merchant?.user_confirmation_url || "https://www.yardsignplus.com/api/affirm/confirm",
        user_cancel_url: orderDetails.merchant?.user_cancel_url || "https://www.yardsignplus.com/api/affirm/cancel",
        public_api_key: orderDetails.merchant?.public_api_key || "",
        user_confirmation_url_action: "POST",
        name: orderDetails.merchant?.name || "YSP"
      },
      shipping: {
        name: {
          first: orderDetails.shipping?.name?.first || '',
          last: orderDetails.shipping?.name?.last || ''
        },
        address: {
          line1: orderDetails.shipping?.address?.line1 || '',
          line2: orderDetails.shipping?.address?.line2 || '', 
          city: orderDetails.shipping?.address?.city || '',
          state: orderDetails.shipping?.address?.state || '',
          zipcode: orderDetails.shipping?.address?.zipcode || '',
          country: orderDetails.shipping?.address?.country || 'US'
        },
        phone_number: orderDetails.shipping?.phone_number || '',
        email: orderDetails.shipping?.email || ''
      },
      billing: {
        name: {
          first: orderDetails.billing?.name?.first || '',
          last: orderDetails.billing?.name?.last || ''
        },
        address: {
          line1: orderDetails.billing?.address?.line1 || '',
          line2: orderDetails.billing?.address?.line2 || '',
          city: orderDetails.billing?.address?.city || '',
          state: orderDetails.billing?.address?.state || '',
          zipcode: orderDetails.billing?.address?.zipcode || '',
          country: orderDetails.billing?.address?.country || 'US'
        },
        phone_number: orderDetails.billing?.phone_number || '',
        email: orderDetails.billing?.email || ''
      },
      items: (orderDetails.items || []).map((item: any) => ({
        display_name: item.display_name,
        sku: item.sku,
        unit_price: item.unit_price,
        qty: item.qty,
        item_image_url: item.item_image_url || '', 
        item_url: item.item_url || '',             
        categories: item.categories || []          
      })),
      discounts: orderDetails.discounts || {},
      metadata: {
        mode: "modal",
        ...orderDetails.metadata
      },
      order_id: orderDetails.order_id || 'ORD-' + Date.now(),
      currency: orderDetails.currency || 'USD',
      shipping_amount: orderDetails.shipping_amount || 0,
      tax_amount: orderDetails.tax_amount || 0,
      total: orderDetails.total || 0,
      financing_program: orderDetails.financing_program
    };

    window.affirm?.checkout({
      public_api_key: checkoutOptions.merchant.public_api_key,
      country_code: 'USA',
      locale: 'en_US'
    });

    if (window.affirm?.checkout) {
        (window.affirm.checkout as any)(checkoutOptions);
    }

    window.affirm?.checkout.open({
      onFail: () => {
        message.error("Affirm payment process was cancelled");
        setTimeout(() => {
            window.location.reload();
        }, 2000);
      },
      onSuccess: async (data) => {
        try {
          const response = await axios.post('/api/affirm/confirm', {
            checkout_token: data.checkout_token,
            order_details: checkoutOptions
          });

          if (response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
          }
        } catch (error) {
          console.error("Order creation failed:", error);
          message.error("Failed to process payment");
        }
      },
      onOpen: (token) => {
        // console.log("Affirm checkout token:", token);
      },
      onValidationError: (error) => {
        message.error("Invalid checkout data");
      }
    });

  } catch (error) {
    console.error("Affirm initialization failed:", error);
    message.error("Failed to initialize payment");
  }
}

export const initiateAffirmCheckout = async (url: string, formDataObj: any): Promise<any> => {
  try {
    const response = await axios.post(url, formDataObj);
    if (response.data.success && response.data.payload) {
      return response.data.payload;
    }
    throw new Error(response.data.message || "Invalid Affirm response");
  } catch (err) {
    console.error("Affirm error", err);
    throw new Error("Unable to start Affirm payment");
  }
};

export const startAffirm = async (submitBtn: any, buttonLabel: string): Promise<void> => {
    const form = $('[requirepayment="yes"]');
    if (!form || form.length === 0) return;

    const formName: any = form.attr('name');
    const orderId = form.data('order-id');
    const paymentLink = form.data('payment-link');

    const formDataObj: any = {};
    form.serializeArray().forEach((field: any) => {
        setNestedValue(formDataObj, field.name, field.value);
    });

    let url = '/api/affirm/initiate?action=' + encodeURIComponent(formName);
    if (orderId) url += '&orderId=' + encodeURIComponent(orderId);
    if (paymentLink) url += '&paymentLink=' + encodeURIComponent(paymentLink);
    try {
        const checkoutData = await initiateAffirmCheckout(url, formDataObj);
        initializeAffirmCheckout(checkoutData);
    } catch (error) {
        console.error("Affirm Error", error);
        window.removeProcessFromBtn(submitBtn, buttonLabel);
    }
};

export function setNestedValue(obj: any, path: string, value: any): void {
    const keys = path
        .replace(/\]/g, '')
        .split(/\[/)
        .reduce((acc, key) => {
            const parts = key.split('.');
            return acc.concat(parts);
        }, [] as string[]);

    let current = obj;
    keys.forEach((key, index) => {
        if (index === keys.length - 1) {
            current[key] = value;
        } else {
            if (!current[key]) current[key] = {};
            current = current[key];
        }
    });
}

const observeAffirmErrorModalClose = () => {
  let modalAppeared = false;

  const observer = new MutationObserver(() => {
    const modal = document.querySelector('#affirm-error-modal');

    if (modal) {
      modalAppeared = true;
    }

    if (modalAppeared && !modal) {
      observer.disconnect();
      window.location.reload();
    }
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
};

observeAffirmErrorModalClose();
