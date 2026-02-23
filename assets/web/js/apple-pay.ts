// @ts-nocheck
import axios from "axios";
import { setNestedValue } from "./affirm";
import { message } from "antd";

let applePayRequestData = null;
let applePayInstance = null;
let isApplePaySessionActive = false;

export async function prepareApplePay() {
    const form = $('[requirepayment="yes"]');
    if (!form.length) return;

    const formName = form.attr('name') || 'checkout';
    const orderId = form.data('order-id') || null;
    const paymentLink = form.data('payment-link') || null;

    try {
        await preloadApplePayData(formName, orderId, paymentLink);
        await initApplePayInstance(window.braintreeClientAuth);
    } catch (err) {
        console.error('Failed to initialize Apple Pay:', err);
    }
}

export async function handleApplePayPayment(form, submitBtn, buttonLabel) {
    if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
        message.error("Apple Pay is not supported.");
        return window.removeProcessFromBtn(submitBtn, buttonLabel);
    }

    try {
        if (!getApplePayRequestData()) {
            const formName = form.attr('name') || 'order';
            const orderId = form.data('order-id') || null;
            const paymentLink = form.data('payment-link') || null;

            await preloadApplePayData(formName, orderId, paymentLink);
        }

        if (!getApplePayRequestData()) {
            return window.removeProcessFromBtn(submitBtn, buttonLabel);
        }

        await initApplePayInstance(window.braintreeClientAuth);

        const formData = {};
        form.serializeArray().forEach((field) => {
            setNestedValue(formData, field.name, field.value);
        });

        startApplePaySession({
            onComplete: (response) => {
                if (response?.cancelled || !response?.data?.success) {
                    window.removeProcessFromBtn(submitBtn, buttonLabel);
                }
            },
            orderId: form.data('order-id'),
            paymentLink: form.data('payment-link'),
            formAction: form.attr('name'),
            formData: formData,
        });

    } catch (err) {
        window.removeProcessFromBtn(submitBtn, buttonLabel);
    }
}

export function preloadApplePayData(action = 'checkout', orderId = null, paymentLink = null) {
    let url = `/api/apple-pay/initiate?action=${encodeURIComponent(action)}`;
    if (orderId) url += `&orderId=${encodeURIComponent(orderId)}`;
    if (paymentLink) url += `&paymentLink=${encodeURIComponent(paymentLink)}`;

    return axios.post(url)
        .then(response => {
            applePayRequestData = response.data.success ? response.data.requestData : null;
        })
        .catch(err => {
            applePayRequestData = null;
        });
}

export function getApplePayRequestData() {
    return applePayRequestData;
}

export function initApplePayInstance(clientAuth) {
    return new Promise((resolve, reject) => {
        if (applePayInstance) return resolve(applePayInstance);

        braintree.client.create({ authorization: clientAuth }, (clientErr, clientInstance) => {
            if (clientErr) return reject(clientErr);

            braintree.applePay.create({ client: clientInstance }, (applePayErr, instance) => {
                if (applePayErr) return reject(applePayErr);
                applePayInstance = instance;
                resolve(instance);
            });
        });
    });
}

export function startApplePaySession({
    onComplete,
    checkoutUrl = '/payment/apple-pay/checkout',
    orderId = null,
    paymentLink = null,
    formAction = null,
    formData = {},
    isExpress = false
}) {
   
    if (isApplePaySessionActive) return;  
    if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) return;

    const data = getApplePayRequestData();
    if (!data || !applePayInstance) {
        return;
    }

    const request = applePayInstance.createPaymentRequest({
        currencyCode: data.currencyCode,
        countryCode: data.countryCode,
        requiredShippingContactFields: isExpress ? data.requiredShippingContactFields : [],
        lineItems: data.lineItems || [],
        total: {
            label: data.merchantName,
            amount: data.totalPrice,
            type: 'final',
        },
    });

    const session = new ApplePaySession(3, request);
    isApplePaySessionActive = true;

    session.onvalidatemerchant = (event) => {
        applePayInstance.performValidation({
            validationURL: event.validationURL,
            displayName: data.merchantName,
        }).then(merchantSession => {
            session.completeMerchantValidation(merchantSession);
        }).catch(() => {
            session.abort();
            isApplePaySessionActive = false;
        });
    };

    session.onpaymentauthorized = (event) => {
        applePayInstance.tokenize({ token: event.payment.token })
            .then(payload => {
                return axios.post(checkoutUrl, {
                    paymentNonce: payload.nonce,
                    shippingAddress: event.payment.shippingContact || {},
                    email: event.payment.shippingContact?.emailAddress || '',
                    orderId: orderId,
                    paymentLink: paymentLink,
                    formAction: formAction,
                    formData: formData,
                    isExpress: isExpress,
                });
            })
            .then(response => {
                const { success, redirect_url, message: errorMessage } = response.data;

                if (success) {
                    session.completePayment(ApplePaySession.STATUS_SUCCESS);
                    isApplePaySessionActive = false;

                    if (redirect_url) window.location.href = redirect_url;
                    if (onComplete) onComplete(response);
                } else {
                    session.completePayment(ApplePaySession.STATUS_FAILURE);
                    isApplePaySessionActive = false;
                    message.error(errorMessage || 'Payment failed. Please try again.');
                    setTimeout(() => window.location.reload(), 2000);
                }
            })
            .catch(error => {
                session.completePayment(ApplePaySession.STATUS_FAILURE);
                isApplePaySessionActive = false;

                const errorMsg = error.response?.data?.message || 'Payment failed. Please try again.';
                message.error(errorMsg);
                setTimeout(() => window.location.reload(), 2000);
                console.error('Apple Pay Error:', error);
            });
    };

    session.oncancel = () => {
        isApplePaySessionActive = false;
        if (onComplete) onComplete({ cancelled: true });
    };

    session.begin();
}

const hideApplePayIfUnsupported = async () => {
    if (!window.ApplePaySession || !(await ApplePaySession.canMakePayments())) {
        document.querySelectorAll('[data-payment-method="APPLE_PAY"]')?.forEach(input => {
            const wrapper = input.closest('.payment-method-choice') || input.closest('.form-check');
            wrapper.style.display = 'none'; 
        });
    }
};

document.addEventListener('DOMContentLoaded', async () => {
    await hideApplePayIfUnsupported();

    const container = document.getElementById('apple-pay-express-checkout');
    if (!container || !window.ApplePaySession || !ApplePaySession.canMakePayments()) return;

    await preloadApplePayData(); 
    await initApplePayInstance(window.braintreeClientAuth); 

    const button = document.createElement('button');
    button.className = 'apple-pay-button';
    button.style.cssText = 'appearance: -apple-pay-button; width: 100%; height: 40px;';
    container.appendChild(button);
    container.classList.remove('d-none');
 
    button.addEventListener('click', (e) => {
        e.preventDefault();
        startApplePaySession({
            onComplete: () => {},
            isExpress: true, 
        }); 
    });
});