import { message } from "antd";
import axios from "axios";

document.addEventListener('DOMContentLoaded', function () {
    const numericInputsOnly = document.querySelectorAll('[data-numeric-input]') as NodeListOf<HTMLInputElement>;
    const formatPhoneNumberOnly = document.querySelectorAll('[data-phone-input]') as NodeListOf<HTMLInputElement>;
    const formatTelephoneOnly = document.querySelectorAll('[data-telephone-input]') as NodeListOf<HTMLInputElement>;
    const emailInputs = document.querySelectorAll('[data-save-cart-email]') as NodeListOf<HTMLInputElement>;
    const quantityInputs = document.querySelectorAll('.disable-scroll');

    function preventscrollEvent() {
        quantityInputs.forEach(function (input) {
            input.addEventListener('click', function () {
                input.classList.remove('disable-scroll');
            });
            input.addEventListener('wheel', preventScroll);
        });

        function preventScroll(e: any) {
            e.preventDefault();
        }
    }

    function formatPhoneNumber(input: HTMLInputElement) {
        let inputValue = input.value.replace(/\D/g, "");
        if (inputValue.length === 10) {
            const formattedNumber = inputValue.replace(/(\d{3})(\d{3})(\d{4})/, '($1)-$2-$3');
            input.value = formattedNumber;
            input.dispatchEvent(new Event("change", { bubbles: true }));
        } else {
            input.value = inputValue;
            input.dispatchEvent(new Event("change", { bubbles: true }));
        }
    }

    function formatTelephone(input: HTMLInputElement) {
        let inputValue = input.value.replace(/\D/g, "");
        let formattedNumber = '';

        if (inputValue.length > 13) {
            inputValue = inputValue.slice(0, 13);
        }

        if (inputValue.length > 0) {
            formattedNumber += `(${inputValue.slice(0, 3)}`;
        }
        if (inputValue.length > 3) {
            formattedNumber += `)-${inputValue.slice(3, 6)}`;
        }
        if (inputValue.length > 6) {
            formattedNumber += `-${inputValue.slice(6, 10)}`;
        }

        if (inputValue.length > 10) {
            for (let i = 10; i < inputValue.length; i += 3) {
                formattedNumber += `-${inputValue.slice(i, i + 3)}`;
            }
        }

        input.value = formattedNumber;
        input.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function addPhoneInputListener(inputs: NodeListOf<HTMLInputElement>) {
        if (inputs) {
            inputs.forEach((input) => {
                input.addEventListener('input', function () {
                    formatPhoneNumber(input);
                });
            })
        }
    }

    function addTelephoneInputListener(inputs: NodeListOf<HTMLInputElement>) {
        if (inputs) {
            inputs.forEach((input) => {
                input.addEventListener('input', function () {
                    formatTelephone(input);
                });
            })
        }
    }

    function numberOnly(inputs: NodeListOf<HTMLInputElement>) {
        if (inputs) {
            inputs.forEach((input) => {
                input.addEventListener("input", function () {
                    input.value = input.value.replace(/\D/g, "");
                });
            });
        }
    }

    function addSaveCartEmailListener(inputs: NodeListOf<HTMLInputElement>) {
        inputs.forEach((input) => {
            input.addEventListener("blur", () => {
                if (validateEmail(input.value)) {
                    saveAbandonedCart(input.value);
                }
            });
        });
    }

    function validateEmail(email: string): boolean {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailRegex.test(email)) {
            return true;
        } else {
            // message.error("Invalid email address format.", 2);
            return false;
        }
    }


    numberOnly(numericInputsOnly);
    addPhoneInputListener(formatPhoneNumberOnly);
    addTelephoneInputListener(formatTelephoneOnly);
    addSaveCartEmailListener(emailInputs);
    preventscrollEvent()
});

document.body.addEventListener('input', (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.matches('#orderNumberForm #track_order_telephone')) {
        const inputValue = target.value.replace(/\D/g, '');
        if (inputValue.length === 10) {
            const formattedNumber = inputValue.replace(/(\d{3})(\d{3})(\d{4})/, '($1)-$2-$3');
            target.value = formattedNumber;
        } else {
            target.value = inputValue;
        }
    }
});

const saveAbandonedCart = async (email: string) => {
    try {
        await axios.post(`/api/checkout/save-cart-email`, { email });
        // message.success("Cart saved successfully.", 2);
    } catch (error: any) {
        // message.error("Error saving cart: " + error.message, 2);
        console.error("Error saving cart: " + error.message, 2);
    }
};