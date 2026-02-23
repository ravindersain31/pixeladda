import {Controller} from "@hotwired/stimulus";
import axios from "axios";

export default class extends Controller {

    printerName?: string;

    async connect() {
        // @ts-ignore
        this.printerName = this.element.dataset.orderQueuePrinterValue;

        if (this.printerName) {
            // @ts-ignore
            setInterval(() => this.syncList(this.printerName), 10000);
        }
    }

    async syncList(printerName: string) {
        const {data} = await axios.get(`/warehouse/queue-api/printer/${printerName}`)
        for (const {list, orders} of data.lists) {
            for (const order of orders) {
                const commentsElement = document.getElementById(`comments_queue_${order.id}`);
                const commentsMaxElement = document.getElementById(`comments_maximize_${order.id}`);
                this.updateValueWhenNotTyping(commentsElement, order.comments);
                this.updateValueWhenNotTyping(commentsMaxElement, order.comments);

                const printedCountElement = document.getElementById(`printed_count_${order.id}`);
                const printedCountMaxElement = document.getElementById(`printed_count_max_${order.id}`);
                this.updateValueWhenNotTyping(printedCountElement, order.printed);
                this.updateValueWhenNotTyping(printedCountMaxElement, order.printed);

                const printStatusElement = document.getElementById(`print_status_${order.id}`);
                const printStatusMaxElement = document.getElementById(`print_status_max_${order.id}`);
                this.updateValueWhenNotTyping(printStatusElement, order.printStatus);
                this.updateValueWhenNotTyping(printStatusMaxElement, order.printStatus);
            }
        }
    }

    updateValueWhenNotTyping(element: HTMLInputElement | HTMLElement | undefined | null, value: string) {
        if (!element) return;

        const updateValue = () => {
            if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement) {
                element.value = value;
                // element.dispatchEvent(new Event('input', {bubbles: true}));
            } else {
                element.innerHTML = value;
            }
        };

        if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement) {
            if (document.activeElement === element) {
                element.addEventListener('blur', updateValue);
            } else {
                element.removeEventListener('blur', updateValue);
                updateValue();
            }
        } else if (element instanceof HTMLElement) {
            // For other HTML elements, update the value immediately
            updateValue();
        }
    }

}
