import { Controller } from "@hotwired/stimulus";
import { Modal } from "bootstrap";

export default class extends Controller {
  modal: Modal | null = null;
  connect() {
    document.addEventListener("flash:hide", () => {
        document.querySelectorAll<HTMLElement>('.alert').forEach((element) => {
            const duration = parseInt(element.getAttribute('data-auto-dismiss') || '5000', 10);
            if (!isNaN(duration) && duration > 0) {
                setTimeout(() => {
                    element.remove();
                }, duration);
            }
        });
    });
  }
}
