import { Controller } from "@hotwired/stimulus";
import { message, Modal } from "antd";

declare global {
    interface Window {
        braintreeClientAuth?: string;
    }
}

declare const braintree: any;

export default class extends Controller {
    static targets = ["dropin", "newCardRadio", "submitButton", "saveOption", "saveCheckbox"];

    declare readonly dropinTarget: HTMLElement;
    declare readonly newCardRadioTarget: HTMLInputElement;
    declare readonly submitButtonTarget: HTMLButtonElement;
    declare readonly saveOptionTarget: HTMLElement;
    declare readonly saveCheckboxTarget: HTMLInputElement;

    declare readonly hasNewCardRadioTarget: boolean;
    declare readonly hasSaveOptionTarget: boolean;
    declare readonly hasSaveCheckboxTarget: boolean;

    private dropinInstance: any = null;
    private initializing = false;

    connect() {
        if (!this.hasNewCardRadioTarget) {
            this.showNewCardUI();
            this.ensureDropin();
            return;
        }

        if (this.newCardRadioTarget.checked) {
            this.showNewCardUI();
            this.ensureDropin();
        }

        const checked = this.element.querySelector<HTMLInputElement>(
            '.saved-card-radio:checked'
        );

        if (checked) {
            checked.closest('.saved-card-item')?.classList.add('selected');
        }
    }

    selectCard(event: Event) {
        if ((event.target as HTMLElement).closest('.delete-card-btn')) {
            return;
        }

        const card = event.currentTarget as HTMLElement;
        const radio = card.querySelector<HTMLInputElement>('input[type="radio"]');

        if (!radio) return;

        radio.checked = true;

        this.element
            .querySelectorAll('.saved-card-item')
            .forEach(el => el.classList.remove('selected'));

        card.classList.add('selected');

        if (radio.value === 'new') {
            this.showNewCardUI();
            this.ensureDropin();
        } else {
            this.hideNewCardUI();
            this.teardownDropin();
        }
    }

    selectNewCard() {
        this.newCardRadioTarget.checked = true;
        if (this.hasSaveCheckboxTarget) {
            this.saveCheckboxTarget.checked = false;
        }
        this.showNewCardUI();
        this.ensureDropin();
    }

    private showNewCardUI() {
        this.dropinTarget.classList.remove("d-none");

        if (this.hasSaveOptionTarget) {
            this.saveOptionTarget.classList.remove("d-none");
        }
    }

    private hideNewCardUI() {
        this.dropinTarget.classList.add("d-none");

        if (this.hasSaveOptionTarget) {
            this.saveOptionTarget.classList.add("d-none");
        }

        if (this.hasSaveCheckboxTarget) {
            this.saveCheckboxTarget.checked = false;
        }
    }

    showSpinner(button: HTMLElement) {
        button.setAttribute("disabled", "true");

        let spinner = button.querySelector(".spinner-border") as HTMLElement;
        if (!spinner) {
            spinner = document.createElement("span");
            spinner.className = "spinner-border spinner-border-sm me-2";
            button.prepend(spinner);
        }

        spinner.classList.remove("d-none");
    }

    hideSpinner(button: HTMLElement) {
        const spinner = button.querySelector(".spinner-border") as HTMLElement;
        if (spinner) spinner.classList.add("d-none");

        button.removeAttribute("disabled");
    }

    private isCreditCardSelected(): boolean {
        const form = this.submitButtonTarget.form as HTMLFormElement | null;

        if (!form) return false;

        const formPrefix = this.getFormPrefix(form);
        if (!formPrefix) {
            console.error("Form name not found");
            return false;
        }

        const input = form.querySelector<HTMLInputElement>(
            `input[name="${formPrefix}[paymentMethod]"]:checked`
        );

        return input?.value === 'CREDIT_CARD';
    }

    private async ensureDropin() {
        if (this.dropinInstance || this.initializing) return;
        if (!window.braintreeClientAuth) return;

        this.initializing = true;

        try {
            this.dropinTarget.innerHTML = "";
            this.dropinInstance = await this.createDropin();
        } catch (err) {
            console.error(err);
            message.error("Failed to initialize payment form.");
        } finally {
            this.initializing = false;
        }
    }

    private createDropin(): Promise<any> {
        return new Promise((resolve, reject) => {
            braintree.dropin.create(
                {
                    authorization: window.braintreeClientAuth,
                    container: this.dropinTarget,
                    vaultManager: true,
                },
                (err: any, instance: any) => {
                    if (err) reject(err);
                    else resolve(instance);
                }
            );
        });
    }

    private async teardownDropin() {
        if (!this.dropinInstance) return;

        await this.dropinInstance.teardown();
        this.dropinInstance = null;
        this.dropinTarget.innerHTML = "";
    }

    async submit(event: Event) {
        if (!this.isCreditCardSelected()) {
            return; 
        }

        event.preventDefault();

        const form = this.submitButtonTarget.form as HTMLFormElement | null;
        if (!form) return;

        const formPrefix = this.getFormPrefix(form);
        if (!formPrefix) {
            console.error("Form name not found");
            return;
        }

        const isGuest = !this.hasNewCardRadioTarget;
        let selectedValue = "new";

        const $btn = (window as any).$ ? (window as any).$(this.submitButtonTarget) : null;
        if ($btn && window.addProcessOnBtn) {
            window.addProcessOnBtn($btn);
        }

        if (!isGuest) {

            const isAuthenticated = await this.checkAuthentication();

            if (!isAuthenticated) {
                message.error("Your session has expired. Please log in again.");
                window.location.reload(); 
                return;
            }
            
            const selected = form.querySelector<HTMLInputElement>(
                'input[name="saved_card"]:checked'
            );

            if (!selected) {
                message.error("Please select a payment method.");
                return;
            }

            selectedValue = selected.value;
        }

        try {
            const nonceInput = form.querySelector<HTMLInputElement>(
                `input[name="${formPrefix}[paymentNonce]"]`
            );

            const tokenInput = form.querySelector<HTMLInputElement>(
                `input[name="${formPrefix}[savedCardToken]"]`
            );

            if (nonceInput) nonceInput.value = "";
            if (tokenInput) tokenInput.value = "";

            if (selectedValue === "new") {
                if (!this.dropinInstance) {
                    throw new Error("Card form not ready.");
                }

                if (!this.dropinInstance.isPaymentMethodRequestable?.()) {
                    throw new Error("Please complete card details.");
                }

                const payload = await this.dropinInstance.requestPaymentMethod();

                if (nonceInput) {
                    nonceInput.value = payload.nonce;
                }
            } else {
                if (tokenInput) {
                    tokenInput.value = selectedValue;
                }
            }

            form.submit();

        } catch (err: any) {
            console.error(err);
            message.error(err.message || "Card failed.");

            if ($btn && window.removeProcessFromBtn) {
                window.removeProcessFromBtn($btn, "Submit");
            }
            
            if (this.hasSaveCheckboxTarget) {
                this.saveCheckboxTarget.checked = false;
            }
        }
    }

    async saveNewCard() {
        if (!this.dropinInstance) return;

        this.showSpinner(this.submitButtonTarget);

        try {
            const payload = await this.dropinInstance.requestPaymentMethod();

            const response = await fetch("/api/my-account/saved-cards/add", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ nonce: payload.nonce }),
            });

            if (!response.ok) throw new Error(await response.text());

            message.success("Card saved.");
            await this.refreshSavedCards();
        } catch (err: any) {
            message.error(err.message || "Failed to save card.");
        } finally {
            this.hideSpinner(this.submitButtonTarget);
        }
    }

    async delete(event: Event) {
        const id = (event.currentTarget as HTMLElement).dataset.id;
        if (!id) return;

        Modal.confirm({
            title: "Remove Card",
            content: "Are you sure you want to remove this card?",
            okType: "danger",
            onOk: async () => {
                await fetch(`/api/my-account/saved-cards/${id}`, {
                    method: "DELETE",
                });
                await this.refreshSavedCards();
            },
        });
    }

    async refreshSavedCards() {
        const response = await fetch("/api/my-account/saved-cards/card-list");
        if (!response.ok) return;

        const html = await response.text();
        const list = this.element.querySelector(".saved-cards") as HTMLElement | null;
        if (!list) return;

        list.innerHTML = html;

        const cardsCount = list.children.length;

        if (cardsCount > 1) {
            list.style.maxHeight = "200px";
            list.style.overflowY = "auto";
        } else {
            list.style.maxHeight = "";
            list.style.overflowY = "";
        }
    }

    private getFormPrefix(form: HTMLFormElement): string {
        return form.getAttribute("name") || "";
    }

    private async checkAuthentication(): Promise<boolean> {
        try {
            const response = await fetch('/api/my-account/saved-cards/auth-check', {
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                return false;
            }

            const data = await response.json();
            return Boolean(data.authenticated);

        } catch (error) {
            console.error('Auth check failed', error);
            return false;
        }
    }

}
