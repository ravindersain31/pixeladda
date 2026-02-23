import {Controller} from "@hotwired/stimulus";
// @ts-ignore
import AddressAutocomplete from '../lib/AddressAutocomplete.js';

export default class extends Controller {

    static targets = ['shippingAddress', 'billingAddress'];

    formName: string = 'checkout';

    async connect() {
        // @ts-ignore
        this.formName = this.element.dataset.checkoutFormNameValue;

        this.initializeGoogleGEOCode();
    }

    copyToBillingAddress(event: Event) {
        if (event.target instanceof HTMLButtonElement) {
            const originalText = event.target.innerText;
            event.target.disabled = true;
            event.target.innerText = 'Copied to Billing Address';

            // @ts-ignore
            const shippingAddress = this.shippingAddressTarget as HTMLInputElement;
            const fields = shippingAddress.querySelectorAll("input, select, textarea");
            let valueOfPhone = '';
            for (const i in Array.from(fields)) {
                const field = fields[i] as HTMLInputElement | HTMLSelectElement;
                if (field && field.id) {
                    const billingId = field.id.replace('shippingAddress', 'billingAddress');
                    const billingField = document.getElementById(billingId) as HTMLInputElement | HTMLSelectElement;
                    if (billingField && billingId.includes('state')) {
                        const countryField = document.getElementById(`${this.formName}_shippingAddress_country`) as HTMLSelectElement;
                        if (countryField) {
                            this.fetchAndUpdateStates(countryField.value, 'billingAddress', field.value).then((stateField) => {
                            });
                        }
                    } else if (billingField) {
                        billingField.value = field.value;
                        // billingField.dispatchEvent(new Event("change", {bubbles: true}));
                        if (field.value && field.value !== '') {
                            // @ts-ignore
                            $(billingField).valid();
                        }

                        if (billingField.id.includes('phone')) {
                            valueOfPhone = billingField.value;
                        }
                    }
                }
            }
            if (valueOfPhone !== '') {
                const phoneField = document.getElementById(`${this.formName}_textUpdatesNumber`) as HTMLInputElement;
                if (phoneField) {
                    phoneField.value = valueOfPhone;
                    phoneField.dispatchEvent(new Event("change", {bubbles: true}));
                    // @ts-ignore
                    $(phoneField).valid();
                }
            }
            setTimeout(() => {
                // @ts-ignore
                event.target.innerText = originalText;
                // @ts-ignore
                event.target.removeAttribute('disabled');
                saveAddressToLocalStorage("shippingAddress");
                saveAddressToLocalStorage("billingAddress");
            }, 1500);
        }
    }

    async onCountrySelect(event: Event) {
        const countryField = event.target as HTMLSelectElement;
        if (countryField && countryField.id) {
            const country = countryField.value;
            const typeOfAddress = countryField.id.includes('shippingAddress') ? 'shippingAddress' : 'billingAddress';
            await this.fetchAndUpdateStates(country, typeOfAddress);
        }
        this.autofillAddressFromLocalStorage("shippingAddress");
        this.autofillAddressFromLocalStorage("billingAddress");
    }

    async fetchAndUpdateStates(country: string, typeOfAddress: string, defaultValue: string = '') {
        const stateField = document.getElementById(`${this.formName}_${typeOfAddress}_state`) as HTMLSelectElement;
        if (stateField) {
            if (defaultValue === '') {
                defaultValue = stateField.value;
            }

            stateField.disabled = true;
            const response = await fetch(`/api/geo/states/${country}`);
            const body = await response.json();
            let htmlOptions = '<option value="">-- Select State --</option>';
            for (const i in body.data) {
                const isSelected = defaultValue === body.data[i].isoCode ? 'selected' : '';
                htmlOptions = `${htmlOptions}<option value="${body.data[i].isoCode}" ${isSelected}>${body.data[i].name}</option>`;
            }
            stateField.innerHTML = htmlOptions;
            stateField.value = defaultValue;
            stateField.removeAttribute('disabled');
            // stateField.dispatchEvent(new Event("change", { bubbles: true }));
            if (stateField.value && stateField.value !== '') {
                // @ts-ignore
                $(stateField).valid();
            }
            // stateField.dispatchEvent(new Event('change', {bubbles: true}));
        }
        return stateField;
    }

    initializeGoogleGEOCode() {
        const options = {
            // componentRestrictions: {country: "us"},
        };
        new AddressAutocomplete(`#${this.formName}_shippingAddress_addressLine1`, options, (address: any) => {
            this.fillAddress(address, 'shippingAddress');
        })
        new AddressAutocomplete(`#${this.formName}_billingAddress_addressLine1`, options, (address: any) => {
            this.fillAddress(address, 'billingAddress');
        })
    }

    fillAddress(address: any, type: string) {
        const addressFieldMapping: {
            [key: string]: string | string[]
        } = {
            'addressLine1': ['streetNumber', 'streetName'],
            'city': 'cityName',
            'state': 'stateAbbr',
            'country': 'countryAbbr',
            'zipcode': 'zipCode',
        };

        for (const field in addressFieldMapping) {
            const element = document.querySelector(`#${this.formName}_${type}_${field}`) as HTMLInputElement | HTMLSelectElement;
            if (element) {
                let fieldValue = '';
                if (Array.isArray(addressFieldMapping[field])) {
                    // @ts-ignore
                    for (const i in addressFieldMapping[field]) {
                        fieldValue = `${fieldValue} ${address[addressFieldMapping[field][i]]}`;
                    }
                } else {
                    // @ts-ignore
                    fieldValue = address[addressFieldMapping[field]];
                }
                if (field === 'state') {
                    if (address.countryAbbr === 'PR') {
                        address.countryAbbr = 'US';
                        fieldValue = 'PR';
                    }
                    this.fetchAndUpdateStates(address.countryAbbr, type, fieldValue).then((stateField) => {
                        
                    });
                } else {
                    element.value = fieldValue;
                    // @ts-ignore
                    $(element).valid();
                }
            }
        }
    }

    autofillAddressFromLocalStorage(typeOfAddress: string, formName: string = 'checkout'): void {
        const address = JSON.parse(localStorage.getItem(`${formName}_${typeOfAddress}`) || '{}') as Address;
        if (address) {
            for (const [field, value] of Object.entries(address)) {
                const fieldName = makeFieldName(typeOfAddress, field, formName);
                const fieldElement = document.querySelector<HTMLInputElement>(`[name="${fieldName}"]`);

                if(fieldElement && field === 'state') {
                    fieldElement.value = value as string;
                    fieldElement.dispatchEvent(new Event("change", {bubbles: true}));
                    break;
                }
            }
        }
    }
}
