import {Controller} from "@hotwired/stimulus";
import axios from "axios";

export default class extends Controller {

    static targets = ["widget"]

    dataUrl: string = this.data.get('dataUrlValue') as string;

    connect() {
        // @ts-ignore
        const widgetTarget = this.widgetTarget as HTMLElement;
        axios.get(this.dataUrl).then((response) => {
            widgetTarget.textContent = response.data.value;
        }).catch((error) => {});
    }
}
