import {Controller} from "@hotwired/stimulus";
import axios from "axios";

export default class extends Controller {

    static targets = ["widget2"]

    dataUrl2: string = this.data.get('dataUrl2Value') as string;

    connect() {
        // @ts-ignore
        const widget2Target = this.widget2Target as HTMLElement;
        axios.get(this.dataUrl2).then((response) => {
            widget2Target.textContent = !Number.isInteger(response.data.value) ? response.data.value.toFixed(2) : response.data.value;
        }).catch((error) => {});
    }
}
