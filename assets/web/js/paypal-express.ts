import {loadScript} from "@paypal/paypal-js";
import {message} from "antd";
import axios from "axios";

let paypal: any;

async function loadPaypal() {
    try {
        const container = document.getElementById("paypal-button-container");
        if (container) {
            const clientIdInput = document.getElementById("paypal-client-id") as HTMLInputElement;
            const currency = clientIdInput.getAttribute("data-currency") || "USD";
            const totalAmount: number = parseFloat(clientIdInput.getAttribute("data-amount") || "0.00");
            const clientId = clientIdInput.value;
        
            paypal = await loadScript({
                clientId: clientId,
                currency: currency,
                dataPageType: "checkout",
                components: ["buttons", "marks", "messages"],
                disableFunding: "card",
            });

            if (paypal) {
                await renderPaypalButtons(paypal, totalAmount);
            }
        }
    } catch (error) {
        handleLoadError(error);
    }
}

async function renderPaypalButtons(paypal: any, totalAmount: number) {
    try {
        const actions = await paypal.Buttons({
            createOrder,
            onApprove,
            style: {
                layout: "vertical",
                label: "checkout",
            },
        });
        await actions.render("#paypal-button-container");
    } catch (error) {
        handleRenderError(error);
    }
}

const createOrder = async (data: any, actions: any) => {
    const response = await axios.post(`/api/paypal-express/initiate`);
    if (response.data.success) {
        const order = response.data.order;
        localStorage.setItem('processingOrderId', order.orderId);
        return order.gatewayId;
    }
    return null;
}

const onApprove = async (data: any, actions: any) => {
    try {
        const orderId = localStorage.getItem('processingOrderId');
        if (orderId) {
            const response = await axios.post(`/api/paypal-express/response/${orderId}`, {data});

            if (!response.data.success) {
                handleError(response.data.message);
            } else {
                handleSuccess(response.data.message);
            }

            if (response.data.redirect) {
                window.location = response.data.redirect;
            }
            localStorage.removeItem('processingOrderId')
        } else {
            message.error("We are not able to process your order. Please try again or contact support.", 5000);
        }
    } catch (e: any) {
        handleError(e.message);
    }
}

function handleError(error: any) {
    message.error("An error occurred: " + error, 5000);
}

function handleSuccess(success: any) {
    message.success(success, 5000);
}

function handleLoadError(error: any) {
    console.error("Failed to load the PayPal JS SDK script", error);
    message.error("Failed to load PayPal JS SDK: " + error, 5000);
}

function handleRenderError(error: any) {
    console.error("Failed to render the PayPal Buttons", error);
    message.error("Failed to render PayPal Buttons: " + error, 5000);
}

const paypalButton = document.getElementById("paypal-button-container");
if (paypalButton) loadPaypal();

