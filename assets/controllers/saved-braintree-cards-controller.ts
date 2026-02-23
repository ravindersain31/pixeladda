import { Controller } from "@hotwired/stimulus";
import { message, Modal } from "antd";
import axios from "axios";
import { Modal as bootstrapModal } from 'bootstrap';

declare global {
    interface Window {
        braintreeClientAuth?: string;
    }
}

declare const braintree: any;

export default class extends Controller {
    static targets = ["dropin", "saveButton", "list", "button", "errorBox", "errorMessage"];

    declare readonly dropinTarget: HTMLElement;
    declare readonly saveButtonTarget: HTMLButtonElement;
    declare readonly listTarget: HTMLElement;
    declare readonly hasSaveButtonTarget: boolean;
    declare readonly hasListTarget: boolean;
    declare readonly buttonTargets: HTMLElement[];

    declare readonly errorBoxTarget: HTMLElement;
    declare readonly errorMessageTarget: HTMLElement;
    declare readonly hasErrorBoxTarget: boolean;
    declare readonly hasErrorMessageTarget: boolean;

    private dropinInstance: any = null;
    private initializing = false;
    private errorTimeout?: number;

    async openModal() {
        if (this.dropinInstance) {
            this.dropinInstance.clearSelectedPaymentMethod();

            this.clearModalError();

            return;
        }
        setTimeout(() => this.ensureDropin(), 50);
    }

    private async ensureDropin(): Promise<void> {
        if (this.dropinInstance || this.initializing) return;

        if (!window.braintreeClientAuth) {
            console.error("Missing Braintree authorization token");
            return;
        }

        this.initializing = true;

        try {
            this.dropinTarget.innerHTML = "";
            this.dropinInstance = await this.createDropin();
            this.setupDropinEvents();

        } catch (err) {
            console.error("Braintree init failed:", err);
            message.error("Failed to initialize card fields. Please refresh and try again.");
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

    private setupDropinEvents() {
        if (!this.dropinInstance) return;
    }

    async save() {
        await this.ensureDropin();

        this.clearModalError(); 

        if (!this.dropinInstance) {
            this.showModalError("Card fields not ready yet.");
            return;
        }

        if (!this.dropinInstance.isPaymentMethodRequestable?.()) {
            this.showModalError("Please complete all card details before saving.");
            return;
        }

        this.showSpinner(this.saveButtonTarget); 

        try {
            const payload = await this.dropinInstance.requestPaymentMethod();

            await axios.post("/api/my-account/saved-cards/add", {
                nonce: payload.nonce,
            });

            message.success("New card has been saved successfully.");
            this.closePaymentModal();

            await this.refreshList();
        } catch (err: any) {
            console.error("Save failed:", err);

            let errorMessage = "Your bank declined this card. Please try another card.";

            if (axios.isAxiosError(err)) {
                const data = err.response?.data;

                if (data?.details) {
                    errorMessage = data.details;
                } else if (data?.error) {
                    errorMessage = data.error;
                }
            }
            this.showModalError(errorMessage);
            this.clearModalCardErrors();
        } finally {
            this.hideSpinner(this.saveButtonTarget);
        }
    }

    async delete(event: Event) {
        const button = event.currentTarget as HTMLElement;
        this.showSpinner(button);

        const id = button.dataset.id;
        if (!id) return;

        Modal.confirm({
            title: "Remove Card",
            content: "Are you sure you want to remove this card?",
            okText: "Remove",
            okType: "danger",
            cancelText: "Cancel",
            onOk: async () => {
                try {
                    await axios.delete(`/api/my-account/saved-cards/${id}`);

                    message.success("Card has been removed successfully.");
                    await this.refreshList();

                } catch (err: any) {
                    console.error("Remove failed:", err);

                    let errorMessage = "Failed to remove card.";

                    if (axios.isAxiosError(err)) {
                        const data = err.response?.data;

                        if (data?.error) {
                            errorMessage = data.error;
                        } else if (err.message) {
                            errorMessage = err.message;
                        }
                    }

                    message.error(errorMessage);

                } finally {
                    this.hideSpinner(button);
                }
            },
            onCancel: () => {
                this.hideSpinner(button);
            }
        });
    }

    async setDefault(event: Event) {
        const button = event.currentTarget as HTMLElement;
        this.showSpinner(button);

        const id = button.dataset.id;
        if (!id) return;

        Modal.confirm({
            title: "Set Default Card",
            content: "Do you want to set this card as default?",
            okText: "Yes",
            cancelText: "No",
            onOk: async () => {
                try {
                    await axios.post(`/api/my-account/saved-cards/${id}/default`);

                    message.success("Card has been set as default successfully.");
                    await this.refreshList();

                } catch (err: any) {
                    console.error("Set default failed:", err);

                    let errorMessage = "Failed to set default card.";

                    if (axios.isAxiosError(err)) {
                        const data = err.response?.data;

                        if (data?.error) {
                            errorMessage = data.error;
                        } else if (err.message) {
                            errorMessage = err.message;
                        }
                    }

                    message.error(errorMessage);
                } finally {
                    this.hideSpinner(button);
                }
            },
            onCancel: () => {
                this.hideSpinner(button);
            }
        });
    }

    async refreshList() {
        try {
            const response = await axios.get(
                "/api/my-account/saved-cards/my-account-list",
                { responseType: "text" }
            );

            this.listTarget.innerHTML = response.data;

        } catch (err: any) {
            console.error("Failed to refresh Cards:", err);
            message.error("Unable to refresh Cards.");
        }
    }

    async closeModal() {
        if (this.dropinInstance) {
            await this.dropinInstance.teardown();
            this.dropinInstance = null;
            this.dropinTarget.innerHTML = "";
            this.clearModalError();
        }
    }

    private closePaymentModal() {
        const modalEl = document.getElementById("savedCardModal");
        if (!modalEl) return;

        const modal = bootstrapModal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    private showSpinner(button: HTMLElement) {
        const spinner = button.querySelector<HTMLElement>('.savedBraintreeCardsLoader');
        if (spinner) spinner.classList.remove('d-none');
        button.setAttribute('disabled', 'true');
    }

    private hideSpinner(button: HTMLElement) {
        const spinner = button.querySelector<HTMLElement>('.savedBraintreeCardsLoader');
        if (spinner) spinner.classList.add('d-none');
        button.removeAttribute('disabled');
    }

    clearModalCardErrors() {
        this.errorTimeout = window.setTimeout(() => {
            if (this.dropinInstance) {
                this.dropinInstance.clearSelectedPaymentMethod();

                this.clearModalError();

                return;
            }
        }, 4000); 
    }

    showModalError(message: string) {
        if (!this.hasErrorBoxTarget) return;

        if (this.errorTimeout) {
            clearTimeout(this.errorTimeout);
        }

        this.errorMessageTarget.textContent = message;
        this.errorBoxTarget.classList.remove("d-none");

        this.errorTimeout = window.setTimeout(() => {
            this.clearModalError();
        }, 4000); 
    }

    clearModalError() {
        if (!this.hasErrorBoxTarget) return;

        this.errorMessageTarget.textContent = "";
        this.errorBoxTarget.classList.add("d-none");
    }
}
