import { Controller } from "@hotwired/stimulus";
// @ts-ignore
import AddressAutocomplete from "../lib/AddressAutocomplete.js";

export default class extends Controller {
    formName = "create_account";

    connect() {
        const el = this.element as HTMLElement;
        const name = el.dataset.formName;
        if (name) this.formName = name;

        this.initializeGoogleGEOCode();
    }

    initializeGoogleGEOCode() {
        new AddressAutocomplete(
            `#${this.formName}_address`,
            {},
            (address: any) => this.fillAddress(address)
        );
    }

    async fillAddress(address: any) {
        const addressLine = `${address.streetNumber || ""} ${address.streetName || ""}`.trim();

        this.setValue("address", addressLine);
        this.setValue("city", address.cityName);
        this.setValue("zipcode", address.zipCode);

        if (address.countryAbbr === "PR") {
            address.countryAbbr = "US";
            address.stateAbbr = "PR";
        }

        if (!address.countryAbbr) return;

        this.setSelectValue("country", address.countryAbbr, address.country);

        await this.fetchAndUpdateStates(address.countryAbbr);

        if (address.stateAbbr) {
            this.setSelectValue("state", address.stateAbbr);
        }
    }

    setValue(field: string, value?: string) {
        if (!value) return;

        const el = document.getElementById(
            `${this.formName}_${field}`
        ) as HTMLInputElement;

        if (!el) return;

        el.value = value;
        // @ts-ignore
    }

    setSelectValue(field: string, value: string, label?: string) {
        const select = document.getElementById(
            `${this.formName}_${field}`
        ) as HTMLSelectElement;

        if (!select) return;

        const exists = Array.from(select.options).some(
            opt => opt.value === value
        );
     
        if (!exists) {
            const opt = document.createElement("option");
            opt.value = value;
            opt.text = label ?? value;
            select.appendChild(opt);
        }

        select.value = value;
    }

    async fetchAndUpdateStates(country: string) {
        const stateField = document.getElementById(
            `${this.formName}_state`
        ) as HTMLSelectElement;

        if (!stateField) return;

        stateField.disabled = true;

        const res = await fetch(`/api/geo/states/${country}`);
        const json = await res.json();

        let html = `<option value="">-- Select State --</option>`;
        for (const state of json.data) {
            html += `<option value="${state.isoCode}">
            ${state.name}
        </option>`;
        }

        stateField.innerHTML = html;
        stateField.disabled = false;

    }

}
