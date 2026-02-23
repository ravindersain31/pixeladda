import { Controller } from "@hotwired/stimulus";
import axios from "axios";
import * as bootstrap from "bootstrap";
import { message, Modal } from "antd";

type RuleSet = {
    required?: boolean;
    email?: boolean;
    minlength?: number;
    maxlength?: number;
};

type ValidationRules = Record<string, RuleSet>;

type AddressTag = "home" | "work" | "business";

interface GeoCode {
    id: number;
    name: string;
    isoCode: string;
}

interface AddressData {
    id?: number;
    firstName?: string;
    lastName?: string;
    email?: string;
    phone?: string;
    addressLine1?: string;
    addressLine2?: string;
    city?: string;
    state?: string | GeoCode;
    country?: string | GeoCode;
    zipcode?: string;
    addressTag?: string;
    otherAddressTag?: string;
    isDefault?: boolean;
}

export default class extends Controller {
    static targets = [
        "form",
        "typeHolder",
        "badgeHolder",
        "locationHolder",
        "typeSelect",
        "otherTagHolder"
    ];

    selectedIdValue: number | null = null;

    declare readonly formTarget: HTMLFormElement;
    declare readonly typeHolderTarget: HTMLElement;
    declare readonly badgeHolderTarget: HTMLElement;
    declare readonly locationHolderTarget: HTMLElement;
    declare readonly typeSelectTarget: HTMLSelectElement;
    declare readonly otherTagHolderTarget: HTMLElement;

    declare readonly hasFormTarget: boolean;
    declare readonly hasTypeHolderTarget: boolean;
    declare readonly hasBadgeHolderTarget: boolean;
    declare readonly hasLocationHolderTarget: boolean;
    declare readonly hasTypeSelectTarget: boolean;
    declare readonly hasOtherTagHolderTarget: boolean;

    private modal!: bootstrap.Modal;
    private currentEditId?: number | null = null;
    private addressType: string = "shippingAddress";

    private iconMap: Record<AddressTag, string> = {
        home: "fa-home",
        work: "fa-briefcase",
        business: "fa-building",
    };

    connect() {
        if (this.hasFormTarget) {
            this.addressType = this.formTarget.dataset.addressType || "shippingAddress";
        }

        const modalEl = document.getElementById("checkoutAddressModal") || document.getElementById("addressModal");
        if (modalEl) {
            this.modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            modalEl.addEventListener("show.bs.modal", () => {
                const titleEl = document.getElementById("addressModalTitle");

                if (titleEl) {
                    titleEl.textContent =
                        typeof this.currentEditId === "number"
                            ? "Edit Address"
                            : "Add New Address";
                }

                if (this.hasFormTarget) {
                    this.restoreSelection();
                }
            });

            modalEl.addEventListener("hidden.bs.modal", () => {
                if (this.hasFormTarget) {
                    this.resetForm();
                }
                this.currentEditId = null;
            });
        }

        if (this.hasFormTarget) {
            const restored = this.restoreSelectedAddressFromLocalStorage();

            if (!restored) {
                const defaultRadio = this.formTarget.querySelector<HTMLInputElement>(
                    'input[name="selectedAddress"]:checked'
                );

                if (defaultRadio?.dataset.address) {
                    const data = JSON.parse(defaultRadio.dataset.address);
                    this.selectedIdValue = data.id;
                    this.updateDefaultAddressSection(data);
                    this.fillCheckoutAddress(data, "checkout", "shippingAddress");
                    this.fillCheckoutAddress(data, "checkout", "billingAddress");
                }
            }
        }
    }

    add() {
        this.resetForm();
        this.modal.show();
    }

    async edit(event: Event) {
        const button = event.currentTarget as HTMLElement;
        this.showLoader(button);

        const id = button.dataset.addressId;
        if (!id) return this.hideLoader(button);

        this.currentEditId = parseInt(id);

        try {
            const response = await axios.get(`/api/my-account/address/${id}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });

            let data: AddressData = response.data.data;

            if (data.country && typeof data.country === "object") data.country = data.country.isoCode;
            if (data.state && typeof data.state === "object") data.state = data.state.isoCode;

            this.fillForm(data);

            if (data.country) {
                await this.fetchAndUpdateStates(data.country, this.addressType, data.state ?? "", this.formTarget.name);
            }

            this.modal.show();
        } catch (error: any) {
            console.error(error);
            message.error(error.response?.data?.message || "Failed to load address.");
        } finally {
            this.hideLoader(button);
        }
    }

    async delete(event: Event) {
        const button = event.currentTarget as HTMLElement;
        this.showLoader(button);

        const id = button.dataset.addressId;
        if (!id) return this.hideLoader(button);

        Modal.confirm({
            title: "Delete Address",
            content: "Are you sure? This action cannot be undone.",
            okText: "Yes",
            cancelText: "No",
            okType: "danger",
            onOk: async () => {
                try {
                    const response = await axios.delete(`/api/my-account/address/${id}`, {
                        headers: { "X-Requested-With": "XMLHttpRequest" }
                    });

                    if (response.data.success) {
                        
                        const selectedId = localStorage.getItem("selected_checkout_address_id");
                        clearAddressFromLocalStorage();
                        if (selectedId == id) {
                            localStorage.removeItem("selected_checkout_address_id");
                        }

                        message.success(response.data.message);
                        this.refreshAddressList(response.data.data.addresses);
                    } else {
                        message.error(response.data.message);
                    }
                } catch (error: any) {
                    console.error(error);
                    message.error(error.response?.data?.message || "Failed to delete address.");
                } finally {
                    this.hideLoader(button);
                }
            },
            onCancel: () => this.hideLoader(button)
        });
    }

    async submit(event: Event) {
        event.preventDefault();

        const isValid = this.validateAddress(this.addressType, this.formTarget.name);
        if (!isValid) return;

        if (!this.formTarget.checkValidity()) {
            this.formTarget.reportValidity();
            return;
        }

        const button = document.getElementById("saveAddressBtn") as HTMLButtonElement;
        const loader = document.getElementById("saveAddressLoader") as HTMLElement;
        const btnText = document.getElementById("saveAddressBtnText") as HTMLElement;

        if (!this.formTarget) return;

        button.disabled = true;
        loader.classList.remove("d-none");
        btnText.textContent = "Saving...";

        const formDataObj = this.convertFormToJson(this.formTarget);

        try {
            let response;
            if (this.currentEditId) {
                response = await axios.put(`/api/my-account/address/${this.currentEditId}`, formDataObj, {
                    headers: { "X-Requested-With": "XMLHttpRequest", "Content-Type": "application/json" }
                });
            } else {
                response = await axios.post(`/api/my-account/address`, formDataObj, {
                    headers: { "X-Requested-With": "XMLHttpRequest", "Content-Type": "application/json" }
                });
                if(formDataObj.isDefault) {
                    localStorage.removeItem("selected_checkout_address_id");
                    clearAddressFromLocalStorage();
                }
            }

            if (response.data.success) {
                this.modal.hide();
                message.success(response.data.message);
                this.refreshAddressList(response.data.data.addresses);
                this.resetForm();
                this.currentEditId = undefined;
            } else {
                message.error(response.data.message);
            }
        } catch (error: any) {
            console.error(error);
            message.error(error.response?.data?.message || "Something went wrong.");
        } finally {
            button.disabled = false;
            loader.classList.add("d-none");
            btnText.textContent = "Save Address";
        }
    }

    async setAsDefault(event: Event) {
        const button = event.currentTarget as HTMLElement;
        this.showLoader(button);

        const id = button.dataset.addressId;
        if (!id) return this.hideLoader(button);

        try {
            const response = await axios.post(`/api/my-account/address/${id}/default`, {}, {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });

            if (response.data.success) {

                localStorage.removeItem("selected_checkout_address_id");
                clearAddressFromLocalStorage();
                
                message.success(response.data.message);
                this.refreshAddressList(response.data.data.addresses);
            } else {
                message.error(response.data.message);
            }
        } catch (error: any) {
            console.error(error);
            message.error(error.response?.data?.message || "Something went wrong.");
        } finally {
            this.hideLoader(button);
        }
    }

    selectAddress(event: MouseEvent) {
        const target = event.currentTarget as HTMLElement;
        const radio = target.querySelector<HTMLInputElement>("input[type='radio']");
        if (!radio) return;

        radio.checked = true;

        const allOptions = this.formTarget.querySelectorAll<HTMLElement>('.address-option');
        allOptions.forEach(opt => opt.classList.remove('selected'));

        target.classList.add('selected');
    }

    toggleOtherType() {
        if (!this.hasOtherTagHolderTarget || !this.hasTypeSelectTarget) return;

        const isOther = this.typeSelectTarget.value === "other";
        this.otherTagHolderTarget.classList.toggle("d-none", !isOther);
    }

    restoreSelection() {
        if (!this.selectedIdValue) return;

        const radio = this.formTarget.querySelector<HTMLInputElement>(
            `input[name='selectedAddress'][value='${this.selectedIdValue}']`
        );
        if (!radio) return;

        radio.checked = true;

        const allOptions = this.formTarget.querySelectorAll<HTMLElement>('.address-option');
        allOptions.forEach(opt => opt.classList.remove('selected'));

        const parent = radio.closest('.address-option') as HTMLElement;
        if (parent) parent.classList.add('selected');
    }

    async saveChanges() {
        const button = document.querySelector<HTMLButtonElement>(
            'button[data-action="saved-addresses#saveChanges"]'
        );
        if (!button) return;

        this.showLoader(button); 

        try {
            const selected = this.formTarget.querySelector<HTMLInputElement>(
                "input[name='selectedAddress']:checked"
            );
            if (!selected) return console.warn("No address selected");

            const address = JSON.parse(selected.dataset.address!);
            this.selectedIdValue = address.id;

            this.updateDefaultAddressSection(address);

            await this.fillCheckoutAddress(address, "checkout", "shippingAddress");
            await this.fillCheckoutAddress(address, "checkout", "billingAddress");

            localStorage.setItem("selected_checkout_address_id", String(address.id));

            const selectedId = localStorage.getItem("selected_checkout_address_id");
            if (selectedId != address.id) {
                localStorage.removeItem("selected_checkout_address_id");
                clearAddressFromLocalStorage();
            }
            
            saveAddressToLocalStorage("billingAddress");
            saveAddressToLocalStorage("shippingAddress");

            this.modal.hide();
        } finally {
            this.hideLoader(button); 
        }
    }

    removeSection() {
        const section = document.getElementById("addressSection");
        if (!section) return;

        Modal.confirm({
            title: "Delete Default Address",
            content: "Are you sure? Changes cannot be undone.",
            okText: "Delete",
            cancelText: "Cancel",
            okButtonProps: { danger: true },
            onOk: () => section.remove()
        });
    }

    clearCheckoutAddressData() {
        if (typeof (window as any).clearAddressFromLocalStorage === "function") {
            (window as any).clearAddressFromLocalStorage();
        }

        localStorage.removeItem("selected_checkout_address_id");
    }

    private resetForm() {
        this.formTarget.reset();

        if (this.hasOtherTagHolderTarget) {
            this.otherTagHolderTarget.classList.add("d-none");

            const input = this.otherTagHolderTarget.querySelector<HTMLInputElement>("input");
            if (input) input.value = "";
        }
    }

    private fillForm(data: AddressData) {
        if (this.hasTypeSelectTarget && this.hasOtherTagHolderTarget) {
            const predefined = ["home", "work", "business"];

            if (data.addressTag === "other") {
                this.typeSelectTarget.value = "other";

                const input = this.otherTagHolderTarget.querySelector<HTMLInputElement>("input");
                if (input) {
                    input.value = data.otherAddressTag ?? "";
                }

                this.otherTagHolderTarget.classList.remove("d-none");
            } else if (data.addressTag && predefined.includes(data.addressTag)) {
                this.typeSelectTarget.value = data.addressTag;
                this.otherTagHolderTarget.classList.add("d-none");
            } else {
                this.otherTagHolderTarget.classList.add("d-none");
            }
        }

        Object.entries(data).forEach(([key, value]) => {
            const field = this.formTarget.querySelector<HTMLInputElement | HTMLSelectElement>(
                `[name$="[${key}]"]`
            );

            if (!field) return;

            if (field instanceof HTMLInputElement && field.type === "checkbox") {
                field.checked = Boolean(value);
            } else {
                field.value = value != null ? String(value) : "";
            }
        });
    }

    private validateAddress(typeOfAddress: string, formName: string = "save_address"): boolean {
        const rules = window.generateValidationRules(typeOfAddress, formName) as ValidationRules;
        const messages = window.generateValidationMessages(typeOfAddress, formName) as any;

        for (const [inputName, ruleSet] of Object.entries(rules)) {
            const input = document.querySelector<HTMLInputElement>(`[name="${inputName}"]`);
            if (!input) continue;

            const value = input.value.trim();
            const msg = messages[inputName];

            if (ruleSet.required && !value) {
                message.error(msg.required);
                input.focus();
                return false;
            }

            if (ruleSet.email && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    message.error(msg.email);
                    input.focus();
                    return false;
                }
            }

            if (inputName.includes("phone") && value) {
                const phoneRegex =
                    /^\s*(?:\+?(\d{1,3}))?[-. (]*(\d{3})[-. )]*(\d{3})[-. ]*(\d{4})(?: *x(\d+))?\s*$/;

                if (!phoneRegex.test(value)) {
                    message.error("Please enter a valid phone number.");
                    input.focus();
                    return false;
                }
            }
        }

        return true;
    }

    async fillCheckoutAddress(
        address: AddressData,
        formName: string,
        type: string
    ): Promise<void> {
        const mapping: Record<string, keyof AddressData> = {
            firstName: "firstName",
            lastName: "lastName",
            addressLine1: "addressLine1",
            addressLine2: "addressLine2",
            city: "city",
            state: "state",
            country: "country",
            zipcode: "zipcode",
            phone: "phone",
            email: "email",
        };

        const countryValue =
            typeof address.country === "object"
                ? address.country.isoCode
                : address.country || "";

        let stateValue =
            typeof address.state === "object"
                ? address.state.isoCode
                : address.state || "";

        let countryCode = countryValue;
        if (countryValue === "PR") {
            countryCode = "US"; 
            stateValue = "PR";
        }

        this.toggleCheckoutFields(formName, type, true);

        for (const field in mapping) {
            const key = mapping[field];
            const fieldId = `${formName}_${type}_${field}`;

            const element = document.querySelector<HTMLInputElement | HTMLSelectElement>(
                `#${fieldId}`
            );

            if (!element) continue;

            let value = address[key];

            if (typeof value === "object" && value !== null) {
                value = value.isoCode;
            }

            if (field === "country") {
                element.value = countryCode;
                ($(element) as any).valid();
                continue;
            }

            if (field === "state") {
                await this.fetchAndUpdateStates(countryCode, type, stateValue);
                continue;
            }

            element.value = value ? String(value) : "";
            ($(element) as any).valid();
        }

        const textUpdatesNumberField = document.querySelector<HTMLInputElement>(`#${formName}_textUpdatesNumber`);
        if (textUpdatesNumberField) {
            textUpdatesNumberField.value = address.phone || '';
        }

        this.toggleCheckoutFields(formName, type, false);
        saveAddressToLocalStorage("billingAddress");
        saveAddressToLocalStorage("shippingAddress");
    }

    restoreSelectedAddressFromLocalStorage(): boolean {
        const savedId = localStorage.getItem("selected_checkout_address_id");
        if (!savedId) return false;

        const radio = this.formTarget.querySelector<HTMLInputElement>(
            `input[name="selectedAddress"][value="${savedId}"]`
        );

        if (!radio) return false;

        radio.checked = true;

        const data = radio.dataset.address ? JSON.parse(radio.dataset.address) : null;
        if (!data) return false;

        this.selectedIdValue = data.id;
        this.updateDefaultAddressSection(data);
        this.fillCheckoutAddress(data, "checkout", "shippingAddress");
        this.fillCheckoutAddress(data, "checkout", "billingAddress");

        return true;
    }

    updateDefaultAddressSection(address: any) {
        if (!this.hasTypeHolderTarget) return;

        const displayTag =
            address.addressTag === "other" && address.otherAddressTag
                ? address.otherAddressTag
                : address.addressTag || "Home";

        this.typeHolderTarget.textContent = this.toTitleCase(displayTag);
        const addressLine2 = address.addressLine2 ? ` ${address.addressLine2},` : '';

        if (this.hasLocationHolderTarget) {
            this.locationHolderTarget.textContent = `${address.addressLine1}, ${addressLine2} ${address.city}, ${address.stateName} ${address.zipcode}, ${address.countryName}`;
        }

        if (!this.hasBadgeHolderTarget) return;

        if (address.isDefault) {
            this.badgeHolderTarget.textContent = "DEFAULT";
            this.badgeHolderTarget.classList.add("badge", "badge-purple-soft", "text-ysp-purple");
        } else {
            this.badgeHolderTarget.textContent = "";
            this.badgeHolderTarget.classList.remove("badge", "badge-purple-soft", "text-ysp-purple");
        }
    }

    toggleCheckoutFields(formName: string, type: string, disable: boolean) {
        const selector = `[id^="${formName}_${type}_"]`;
        const elements = document.querySelectorAll<HTMLInputElement | HTMLSelectElement>(selector);

        elements.forEach(el => {
            if (disable) {
                el.setAttribute("disabled", "disabled");
            } else {
                el.removeAttribute("disabled");
            }
        });
    }

    private showLoader(button: HTMLElement) {
        const spinner = button.querySelector(".saveAddressLoader") as HTMLElement;
        if (spinner) spinner.classList.remove("d-none");
        button.setAttribute("disabled", "true");
    }

    private hideLoader(button: HTMLElement) {
        const spinner = button.querySelector(".saveAddressLoader") as HTMLElement;
        if (spinner) spinner.classList.add("d-none");
        button.removeAttribute("disabled");
    }

    private refreshAddressList(addresses: AddressData[]) {
        const container = document.querySelector(".addresses-listing");
        if (!container) return;

        if (!addresses.length) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No saved addresses</h4>
                    <p class="text-muted">Click "Add New Address" to get started</p>
                </div>
            `;
            return;
        }

        container.innerHTML = addresses.map(addr => {
           
            const iconClass =
                addr.addressTag && addr.addressTag in this.iconMap
                    ? this.iconMap[addr.addressTag as AddressTag]
                    : "fa-location-arrow";

            const icon = `<i class="fas ${iconClass} text-ysp-purple"></i>`;

            const isDefaultBadge = addr.isDefault ? `<span class="badge badge-indigo-soft text-ysp-purple">DEFAULT</span>` : '';
            const setDefaultBtn = !addr.isDefault ? `
                <button class="btn btn-ysp-outline btn-sm" data-action="saved-addresses#setAsDefault" data-address-id="${addr.id}">
                    <span class="spinner-border spinner-border-sm me-2 d-none saveAddressLoader" role="status"></span>
                    Set As Default
                </button>` : '';

                const tag =
                    addr.addressTag === "other"
                        ? addr.otherAddressTag || "Other"
                        : addr.addressTag || "Home";

            return `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card address-card h-100 rounded-4 hover-shadow">
                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-1">
                                    <div class="address-icon">${icon}</div>
                                    
                                    <h5 class="card-title m-0">
                                        ${tag.charAt(0).toUpperCase() + tag.slice(1)}
                                    </h5>
                                </div>
                                ${isDefaultBadge}
                            </div>

                            <div class="address-details">
                                <p class="mb-1 text-truncate" style="max-width:250px;" title="${addr.addressLine1}">
                                    <i class="fas fa-map-marker-alt text-ysp-purple"></i>
                                    ${addr.addressLine1}
                                </p>
                                <p class="mb-1 text-muted">${addr.city ?? ''}, ${this.getName(addr.state)} ${addr.zipcode ?? ''}</p>
                                <p class="mb-1 text-muted">${this.getName(addr.country)}</p>
                            </div>

                            <div class="address-actions d-flex align-items-end ${addr.isDefault ? 'justify-content-end' : 'justify-content-between'}">
                                ${setDefaultBtn}

                                <div>
                                    <button class="btn btn-outline-primary btn-sm" data-action="saved-addresses#edit" data-address-id="${addr.id}">
                                        <span class="spinner-border spinner-border-sm me-2 d-none saveAddressLoader"></span>
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="btn btn-outline-danger btn-sm" data-action="saved-addresses#delete" data-address-id="${addr.id}">
                                        <span class="spinner-border spinner-border-sm me-2 d-none saveAddressLoader"></span>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    private convertFormToJson(form: HTMLFormElement): any {
        const formData = new FormData(form);
        const json: any = {};

        form.querySelectorAll("input[type='checkbox']").forEach(el => {
            const checkbox = el as HTMLInputElement;
            const name = checkbox.name.replace(/^save_address\[/, "").replace(/\]$/g, "");
            json[name] = checkbox.checked;
        });

        formData.forEach((value, key) => {
            const input = form.querySelector(`[name="${key}"]`) as HTMLInputElement;
            if (input && input.type === "checkbox") return;

            const keys = key.replace(/^save_address\[/, "").replace(/\]$/g, "").split("][");
            let current = json;
            keys.forEach((k, index) => {
                if (index === keys.length - 1) {
                    current[k] = value;
                    if (k.toLowerCase() === 'zipcode') current['zipCode'] = value; 
                } else {
                    current[k] = current[k] || {};
                    current = current[k];
                }
            });
        });

        return json;
    }

    private getName(val: string | GeoCode | undefined, fallback: string = ''): string {
        if (!val) return fallback;
        return typeof val === 'string' ? val : val.name ?? fallback;
    }

    private toTitleCase(str: string): string {
        return str.replace(/\w\S*/g, (txt) =>
            txt.charAt(0).toUpperCase() + txt.slice(1).toLowerCase()
        );
    }

    private async fetchAndUpdateStates(country: string, typeOfAddress: string, defaultValue: string = '', formName: string = 'checkout') {
        const stateField = document.getElementById(`${formName}_${typeOfAddress}_state`) as HTMLSelectElement;
        if (stateField) {
            if (!defaultValue) defaultValue = stateField.value;
            stateField.disabled = true;

            const response = await fetch(`/api/geo/states/${country}`);
            const body = await response.json();

            let htmlOptions = '<option value="">-- Select State --</option>';
            for (const i in body.data) {
                const isSelected = defaultValue === body.data[i].isoCode ? 'selected' : '';
                htmlOptions += `<option value="${body.data[i].isoCode}" ${isSelected}>${body.data[i].name}</option>`;
            }

            stateField.innerHTML = htmlOptions;
            stateField.value = defaultValue;
            stateField.removeAttribute('disabled');
            (($(stateField) as any).valid)?.();
        }
        return stateField;
    }
}
