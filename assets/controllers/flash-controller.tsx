import { Controller } from '@hotwired/stimulus';

export default class extends Controller<HTMLElement> {
    static values = { autoDismiss: Number }

    declare readonly autoDismissValue: number;
    declare readonly hasAutoDismissValue: boolean;

    connect() {
        if (this.hasAutoDismissValue) {
            setTimeout(() => this.hide(), this.autoDismissValue);
        }
    }

    hide() {
        this.element.remove();
    }
}
