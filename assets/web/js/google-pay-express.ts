// @ts-nocheck
import axios from "axios";

let expressGPayClient: any;
export const initGooglePay = () => {
    return new Promise((resolve, reject) => {
        braintree.client.create({
            authorization: window.braintreeClientAuth,
        }, function (clientErr, clientInstance) {
            if (clientErr) {
                reject(clientErr);
            } else {
                braintree.googlePayment.create({
                    client: clientInstance,
                    googlePayVersion: 2,
                    googleMerchantId: window.googleMerchantId,
                }, function (googlePaymentErr, googlePaymentInstance) {
                    window.googlePaymentInstance = googlePaymentInstance;
                    if (googlePaymentErr) {
                        reject(googlePaymentErr);
                    } else {
                        resolve(googlePaymentInstance);
                    }
                });
            }
        });
    });
}

function onGPayLoaded() {
    console.log('onGPayLoaded');
}

window.onGPayLoaded = onGPayLoaded;

document.addEventListener('DOMContentLoaded', () => {
    const GPay = document.getElementById('google-pay-express-checkout');
    const submitBtn = document.getElementById('approve_proof_paymentMethod_2');
    if (GPay || submitBtn) {
        expressGPayClient = new google.payments.api.PaymentsClient({
            environment: window.googlePayEnv ?? 'PRODUCTION',
            merchantInfo: {
                merchantName: "Vertical Brands",
                merchantId: window.googleMerchantId
            },
            paymentDataCallbacks: {
                onPaymentAuthorized: onPaymentAuthorized,
                onPaymentDataChanged: onPaymentDataChanged
            }
        });
    }else{
        return;
    }


    function onPaymentAuthorized(paymentData) {
        window.googlePaymentInstance.parseResponse(paymentData, function (err, result) {
            if (err) {
                window.removeProcessFromBtn(submitBtn, buttonLabel);
                console.log('err parseResponse', err);
            } else {
                const orderData = {
                    paymentNonce: result.nonce,
                    shippingAddress: paymentData.shippingAddress,
                    email: paymentData.email,
                }
                axios.post('/payment/google-pay/checkout', orderData).then(function (response) {
                    const order = response.data;
                    if (order.success && order.action === 'redirect') {
                        window.location.href = order.redirectUrl;
                    } else {
                        window.removeProcessingOrderOverlay();
                        alert(order.message);
                    }
                }).catch(function (err) {
                    window.removeProcessingOrderOverlay();
                    console.log('err checkout', err);
                });
            }
        });
        return {
            transactionState: 'SUCCESS'
        };
    }

    function onPaymentDataChanged() {
        return {};
    }

    const googlePayButtonContainer = document.getElementById('google-pay-express-checkout');
    if (googlePayButtonContainer !== null) {
        const button = expressGPayClient.createButton({
            buttonColor: 'default',
            buttonType: 'checkout',
            buttonSizeMode: 'fill',
            onClick: onClickGooglePaymentButton,
            allowedPaymentMethods: [] // use the same payment methods as for the loadPaymentData() API call
        });
        googlePayButtonContainer.appendChild(button);

        initGooglePay().then((googlePaymentInstance) => {
            expressGPayClient.isReadyToPay({
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: window.googlePaymentInstance.createPaymentDataRequest().allowedPaymentMethods,
                existingPaymentMethodRequired: true // Optional
            }).then(function (response) {
                if (response.result) {
                    axios.post('/api/google-pay/initiate?action=checkout').then(function (response) {
                        let requestData = response.data.requestData;
                        requestData.emailRequired = true;
                        requestData.callbackIntents = ["SHIPPING_ADDRESS", "PAYMENT_AUTHORIZATION"];
                        requestData.shippingAddressRequired = true;
                        requestData.shippingAddressParameters = {
                            allowedCountryCodes: ['US', 'CA', 'GB', 'AU'],
                            phoneNumberRequired: true
                        };

                        if(response.statusCode === 200 || response.data.success === true) {
                            googlePayButtonContainer.parentElement.classList.remove('d-none');
                        }

                        window.paymentDataRequest = window.googlePaymentInstance.createPaymentDataRequest(requestData);
                    }).catch(function (err) {
                        console.log('err /api/google-pay/initiate', err);
                    });
                } else {
                    console.log('err isReadyToPay response', response);
                }
            }).catch(function (err) {
                console.log('err isReadyToPay', err);
            });
        }).catch((err) => {
            console.log('err initGooglePay', err);
        });
    }


    function onClickGooglePaymentButton(event) {
        console.log('window.paymentDataRequest', window.paymentDataRequest);
        if(window.paymentDataRequest.transactionInfo) {
            window.addProcessingOrderOverlay();
            expressGPayClient.loadPaymentData(window.paymentDataRequest).catch(function (err) {
                window.removeProcessingOrderOverlay();
                console.log('err loadPaymentData', err);
            });
        } else {
            alert('Please wait while we are loading Google Pay...');
        }
    }

});