export {};

declare global {
  interface Window {
    amazon: any;
  }
}

function loadAmazonPayButton(buttonId: string, configId: string) {
  const buttonContainer = document.getElementById(buttonId);
  const configEl = document.getElementById(configId) as HTMLInputElement | null;

  if (!buttonContainer || !configEl) return;

  buttonContainer.style.display = "none";

  const merchantId = configEl.dataset.merchantId || "";
  const publicKeyId = configEl.dataset.publicKeyId || "";
  const algorithm = configEl.dataset.algorithm || "AMZN-PAY-RSASSA-PSS-V2";
  const signature = configEl.dataset.signature || "";
  const amount = parseFloat(configEl.dataset.amount || "0.00");
  const currency = configEl.dataset.currency || "USD";
  const payloadRaw = configEl.dataset.payload || "";

  try {
    JSON.parse(payloadRaw);
  } catch (e) {
    console.error("Invalid payloadJSON:", payloadRaw);
    return;
  }

  const render = () => {
    if (!window.amazon?.Pay) return;

    try {
      window.amazon.Pay.renderButton(`#${buttonId}`, {
        merchantId,
        publicKeyId,
        ledgerCurrency: currency,
        productType: "PayAndShip",
        placement: "Cart",
        buttonColor: "Gold",
        estimatedOrderAmount: {
          amount,
          currencyCode: currency,
        },
        createCheckoutSessionConfig: {
          payloadJSON: payloadRaw,
          signature,
          algorithm,
        },
      });

      buttonContainer.style.display = "block";
    } catch (err) {
      console.error("Amazon Pay render failed:", err);
    }
  };

  const script = document.createElement("script");
  script.src = "https://static-na.payments-amazon.com/checkout.js";
  script.async = true;
  script.onload = render;
  script.onerror = () => {
    console.error("Failed to load Amazon Pay script");
  };

  buttonContainer.style.display = "none";
  document.head.appendChild(script);
}

document.addEventListener("DOMContentLoaded", () => {
  loadAmazonPayButton("AmazonPayButton", "amazon-pay-config");
  loadAmazonPayButton("AmazonPayButtonChoose", "amazon-pay-config-choose");
  loadAmazonPayButton("AmazonPayButtonLink", "amazon-pay-config-link");
});