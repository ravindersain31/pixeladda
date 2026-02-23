import Popover from "bootstrap/js/dist/popover.js";

document.addEventListener('DOMContentLoaded', function () {
    const numericInputsOnly = document.querySelectorAll('[data-numeric-input]') as NodeListOf<HTMLInputElement>;
    const formatPhoneNumberOnly = document.querySelectorAll('[data-phone-input]') as NodeListOf<HTMLInputElement>;
    const numericAndDecimalInputsOnly = document.querySelectorAll('[data-numeric-decimal-input]') as NodeListOf<HTMLInputElement>;

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

    function addPhoneInputListener(inputs: NodeListOf<HTMLInputElement>) {
        if (inputs) {
            inputs.forEach((input) => {
                input.addEventListener('input', function () {
                    formatPhoneNumber(input);
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

    function numberWithDecimals(inputs: NodeListOf<HTMLInputElement>) {
        if (inputs) {
            inputs.forEach((input) => {
                input.addEventListener("input", function () {
                    let value = input.value.replace(/[^0-9.]/g, '');
                    if (value.indexOf('.') !== -1) {
                        const parts = value.split('.');
                        value = parts[0] + '.' + parts.slice(1).join('').slice(0, 2);
                    }
                    input.value = value;
                });
            });
        }
    }

    numberOnly(numericInputsOnly);
    numberWithDecimals(numericAndDecimalInputsOnly);
    addPhoneInputListener(formatPhoneNumberOnly);

    const generatePasswordButton = document.getElementById('generatePassword') as HTMLButtonElement;
    const passwordInput = document.querySelector('input[name="user_password[password]"]') as HTMLInputElement;
    const copyDriveLinkBtn = document.getElementById('copyDriveLinkBtn') as HTMLButtonElement;
    const proofFileInput = document.querySelectorAll('.proof-file-input') as NodeListOf<HTMLInputElement>;

    if (proofFileInput) {
        proofFileInput.forEach(input => {
            input.addEventListener('change', () => {
                const helpText = document.getElementById(input.getAttribute('aria-describedby') as string);
                if (helpText) {
                    const existingLink = helpText.querySelector('.existing-file-link');
                    if (existingLink) {
                        existingLink.remove();
                    }
                }
            });
        });
    }

    function copyToClipboardShareDriveLink(text: string): void {
        if (!copyDriveLinkBtn) return;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    copyDriveLinkBtn.innerHTML = '<i class="fa fa-check me-1"></i> Copied!';
                    setTimeout(() => {
                        copyDriveLinkBtn.innerHTML = '<i class="fa fa-copy me-1"></i> Share Drive Link';
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy text: ', err);
                    alert('Failed to copy the drive link.');
                });
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                copyDriveLinkBtn.innerHTML = '<i class="fa fa-check me-1"></i> Copied!';
                setTimeout(() => {
                    copyDriveLinkBtn.innerHTML = '<i class="fa fa-copy me-1"></i> Share Drive Link';
                }, 2000);
            } catch (err) {
                alert('Failed to copy the drive link.');
            }
            document.body.removeChild(textArea);
        }
    }

    async function handleCopyDriveLink() {
        if (!copyDriveLinkBtn) return;

        const orderNumber = copyDriveLinkBtn.getAttribute('data-orderId');
        let driveLink = copyDriveLinkBtn.getAttribute('data-drive-link');

        if (!orderNumber) {
            alert('Order number not available.');
            return;
        }

        if (!driveLink) {
            copyDriveLinkBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Creating...';
            try {
                const response = await fetch(`/api/orders/${orderNumber}/create-drive-folder`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Failed to create folder');

                const data = await response.json();
                driveLink = data.driveLink;

                if (!driveLink) throw new Error('No drive link returned from API');

                copyDriveLinkBtn.setAttribute('data-drive-link', driveLink);
            } catch (err) {
                console.error(err);
                alert('Failed to create Google Drive folder.');
                copyDriveLinkBtn.innerHTML = '<i class="fa fa-copy me-1"></i> Share Drive Link';
                return;
            }
        }

        copyToClipboardShareDriveLink(driveLink);
    }

    copyDriveLinkBtn?.addEventListener('click', handleCopyDriveLink);

    generatePasswordButton?.addEventListener('click', () => {
        const randomPassword = generateRandomPassword(12);
        passwordInput.value = randomPassword;
        copyToClipboard(randomPassword);
    });

    function generateRandomPassword(length: number): string {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const charLength = chars.length;
        return Array.from({ length }, () => chars.charAt(Math.floor(Math.random() * charLength))).join('');
    }


    function copyToClipboard(text: string): void {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {

            }).catch(err => {
                console.error('Could not copy text: ', err);
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    }

    function fallbackCopyToClipboard(text: string) {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        generatePasswordButton.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        alert('Copied to clipboard: ' + text);
        generatePasswordButton.removeChild(tempInput);
    }

    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    if (popoverTriggerList) {
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new Popover(popoverTriggerEl, {
            html: true
        }));
    }

    const checkbox = document.getElementById('update_address_textUpdates') as HTMLInputElement;
    const phoneInput = document.getElementById('update_address_textUpdatesNumber') as HTMLInputElement;

    const toggleRequired = (): void => {
        if (checkbox.checked) {
            phoneInput.setAttribute('required', 'required');
        } else {
            phoneInput.removeAttribute('required');
        }
    };

    checkbox.addEventListener('change', toggleRequired);

    toggleRequired();

    checkbox.onclick = (): void => {
        if (checkbox.checked) {
            phoneInput.setAttribute('required', 'required');
        } else {
            phoneInput.removeAttribute('required');
        }
    };

});