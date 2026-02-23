// @ts-nocheck
import $ from "jquery";
import axios from "axios";
import {initGooglePay} from "./google-pay-express.ts";
import { theme } from "antd";
import { startAffirm } from "./affirm.ts";
import { handleApplePayPayment, prepareApplePay } from "./apple-pay.ts";

const BraintreeDropInID = '#braintree-drop-in';
const StripeDropInID = "#card-element";

const STRIPE_PUBLIC_KEY = window.stripePubKey ?? 'pk_live_51LnQxhGbStL11HbfKMijh6MZX8leYb2vIniItNce1F7BRZJCWTbY1FBekwWJoyzcUEEDIJ7h4ry98ITGMvEjwMsz00tYi0ykDy';

let googlePayClient;
let stripe;
let card;
let googePayClient;
let stripeClientSecret;

let cardNumberElement;
let cardExpiryElement;
let cardCvcElement;

$(document).ready(function () {
    if ($('[name*="[paymentMethod]"][value="GOOGLE_PAY"]').length > 0) {
        startGooglePay()
    }
    if ($('#braintree-drop-in').length > 0) {
        initBraintree(BraintreeDropInID);
    }
    if ($('#card-element').length > 0) {
        initStripe();
    }
})

$('[name*="[paymentMethod]"]').on('change', function (event) {
    const paymentMethod = $(this).val();
    const dropInElement = document.querySelector(BraintreeDropInID);
    if (paymentMethod === 'CREDIT_CARD' && dropInElement.innerHTML === '') {
        initBraintree(BraintreeDropInID);
    }
    if (paymentMethod === 'STRIPE' && dropInElement.innerHTML === '') {
        initStripe();
    }
    if (paymentMethod === 'GOOGLE_PAY' && $('[name*="[paymentMethod]"][value="GOOGLE_PAY"]').length > 0) {
        startGooglePay()
    }
    if (paymentMethod === 'APPLE_PAY' && $('[name*="[paymentMethod]"][value="APPLE_PAY"]').length > 0) {
        prepareApplePay();
    }
})
document.addEventListener('DOMContentLoaded', () => {
    const GPay = document.getElementById('google-pay-express-checkout');
    const approveGpay = document.getElementById('approve_proof_paymentMethod_2');
    const requestChangesGpay = document.getElementById('request_changes_paymentMethod_2');
    const paymentLinkGpay = document.getElementsByName('payment_link_paymentMethod_2');
    if (GPay || approveGpay || requestChangesGpay || paymentLinkGpay.length > 0) {
        googlePayClient = new google.payments.api.PaymentsClient({
            environment: window.googlePayEnv ?? 'PRODUCTION',
            merchantInfo: {
                merchantName: "Vertical Brands",
                merchantId: window.googleMerchantId
            },
            paymentDataCallbacks: {
                onPaymentAuthorized: onGPayPaymentAuthorized,
            }
        });
    }else{
        return;
    }
});


$('[name="approve_proof"], [name="payment_link"]').on('submit', function (event) {
    event.preventDefault();
    handleFormSubmit(event, $(this));
})

$('[name="request_changes"], [name="payment_link"]').on('submit', function (event) {
    event.preventDefault();
    handleFormSubmit(event, $(this));
})


$('[name="checkout"]').on('submit', function (event) {
    event.preventDefault();
    const form = $(this);
    const recaptcha = form.find('[name*="[recaptcha]"]');

    if (recaptcha.length > 0) {
        const value = recaptcha.val() ?? '';

        if (value === '') {
            form.find('.recaptcha-error-message').html('Please click "I\'m not a robot".');
            return;
        } else {
            form.find('.recaptcha-error-message').html('');
        }
    }

    if (form.valid()) {
        handleFormSubmit(event, form);
    }
});


const handleFormSubmit = async (event: any, form: any) => {
    const submitBtn = form.find('[name*="[submit]"][type="submit"]');
    const buttonLabel = submitBtn.html();
    const paymentMethod = $('[name*="[paymentMethod]"]:checked').val();
    const paymentNonce = $('[id*=_paymentNonce]').val();

    window.addProcessOnBtn(submitBtn);
    if (!paymentNonce && paymentMethod === 'CREDIT_CARD') {
        // window.braintreeInstance.requestPaymentMethod(function (err: any, payload: any) {
        //     if (err) {
        //         window.removeProcessFromBtn(submitBtn, buttonLabel);
        //         console.log('BraintreeInstance Error', err);
        //     } else {
        //         $('[id*=_paymentNonce]').val(payload.nonce);
        //         event.currentTarget.submit();
        //     }
        // });
    } else if (!paymentNonce && paymentMethod === 'GOOGLE_PAY') {
        googlePayClient.loadPaymentData(window.googlePayRequestData).catch(function (err) {
            alert('We are having trouble processing your payment with Google Pay. Please try again or use a different payment method.');
            window.removeProcessFromBtn(submitBtn, buttonLabel);
            console.log('err loadPaymentData', err);
        });
    }else if (!paymentNonce && paymentMethod === 'AFFIRM') {
        startAffirm(submitBtn, buttonLabel);
    } else if (!paymentNonce && paymentMethod === 'APPLE_PAY') {
        await handleApplePayPayment(form, submitBtn, buttonLabel);
    } else if (!paymentNonce && paymentMethod === 'STRIPE') {

        if (!stripeClientSecret) {
            await initStripeSecret();
        }

        try {
            const result = await stripe.confirmCardPayment(stripeClientSecret, {
                payment_method: {
                    card: cardNumberElement,
                    // billing_details: {
                    //     name: 'Jenny Rosen',
                    // }}
                }
            });

            if (result.error) {
                console.log('Stripe error:', result);
                if(result.error.type === 'card_error') {
                    displayError(result.error.message);
                }else if(result.error.type === 'rate_limit_error') {
                    displayError(result.error.message);
                }else if(result.error.type === 'validation_error') {
                    // displayError(result.error.message ?? 'An unexpected error occurred. Please try again.');
                }else {
                    displayError(result.error.message ?? 'An unexpected error occurred. Please try again.');
                }
                window.removeProcessFromBtn(submitBtn, buttonLabel);
            } else {
                console.log('Stripe success:', result);
                $("[id*=_paymentNonce]").val(result.paymentIntent.id);
                form.submit();
            }
        } catch (error) {
            console.error('Unexpected error confirming payment:', error);
            displayError("An unexpected error occurred. Please try again.");
            window.removeProcessFromBtn(submitBtn, buttonLabel);
        }
    } else {
        event.currentTarget.submit();
    }
}


function onGPayPaymentAuthorized(paymentData: any) {
    const form = $('[requirepayment="yes"]');
    if (form) {
        window.googlePaymentInstance.parseResponse(paymentData, function (err: any, result: any) {
            if (err) {
                console.log('err parseResponse', err);
            } else {
                $(form).find('[id*=_paymentNonce]').val(result.nonce);
                form.submit();
            }
        });
        return {
            transactionState: 'SUCCESS'
        };
    }
    return {
        transactionState: 'ERROR'
    }
}

const initBraintree = (elementId: string) => {
    braintree.dropin.create({
        authorization: window.braintreeClientAuth ?? 'production_rzr4cz98_35c8hz68xfzzxyx7',
        container: elementId,
    }, function (createErr: any, instance: any) {
        window.braintreeInstance = instance;
    });
}

const initStripe = async () => {
    stripe = await Stripe(STRIPE_PUBLIC_KEY);

    const elements = stripe.elements({
        appearance: { theme: 'stripe' }
    });
    const style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSize: '16px',
            '::placeholder': { color: '#aab7c4' }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    createCardElement(elements, 'cardNumber', { style, showIcon: true, placeholder: "•••• •••• •••• ••••" });
    createCardElement(elements, 'cardExpiry', { style, placeholder: "MM / YY" });
    createCardElement(elements, 'cardCvc', { style, placeholder: "***" });
};

const createCardElement = (elements, elementType, options) => {
    const element = elements.create(elementType, options);

    if (elementType === 'cardNumber') {
        cardNumberElement = element;
    }

    element.mount(`#${elementType}`);

    const errorDisplay = document.getElementById(`${elementType}-errors`);

    element.on('change', (event) => {
        if (errorDisplay) {
            errorDisplay.textContent = event.error ? event.error.message : '';
        }

        const inputField = document.getElementById(elementType);
        if (inputField) {
            inputField.classList.toggle('is-invalid', !!event.error);
        }
    });
};


const displayError = ( message: string, elementId: string = 'card-errors') => {
    const displayError = document.getElementById(elementId);
    if (displayError) {
        displayError.textContent = message;
    }
}

const initStripeSecret = async () => {
    try {

        const paymentMethod = document.getElementById("checkout-order-total-amount") as HTMLInputElement;
        const totalAmount: number = parseFloat(paymentMethod.getAttribute("data-amount") || "0.00");

        const response = await axios.post("/stripe/create-payment-intent", {
            amount: totalAmount,
        });

        if (response.data.success === false) {
            alert('We are having trouble processing your payment. Please try again or use a different payment method.');
            console.log('Error fetching Stripe secret:', response.data.response);
            return;
        }

        stripeClientSecret = response.data.response.client_secret;

    } catch (error) {
        console.error('Error fetching Stripe secret:', error);
        alert('We are having trouble processing your payment. Please try again or use a different payment method.');
    }
}

const startGooglePay = () => {
    const form = $('[requirepayment="yes"]');
    if (form && (window.googlePaymentInstance !== null || window.googlePayRequestData !== null)) {
        const formName = form.attr('name');
        const orderId = form.data('order-id');
        const paymentLink = form.data('payment-link');
        let url = '/api/google-pay/initiate?action=' + formName;
        if (orderId) {
            url += '&orderId=' + orderId;
        }
        if (paymentLink) {
            url += '&paymentLink=' + paymentLink;
        }
        initGooglePay().then((googlePaymentInstance: any) => {
            window.googlePaymentInstance = googlePaymentInstance;
            googlePayClient.isReadyToPay({
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: googlePaymentInstance.createPaymentDataRequest().allowedPaymentMethods,
                existingPaymentMethodRequired: true // Optional
            }).then(function (response) {
                if (response.result) {
                    axios.post(url).then(function (response) {
                        window.googlePayRequestData = window.googlePaymentInstance.createPaymentDataRequest(response.data.requestData);
                    });
                }
            }).catch(function (err) {
                console.log('err isReadyToPay', err);
            })
        }).catch((err: any) => {
            console.log('err initGooglePay', err);
        })
    }
}
