import { Controller } from "@hotwired/stimulus";
import { Modal } from "bootstrap";

export default class extends Controller {
  modal: Modal | null = null;
  connect() {
    this.modal = Modal.getOrCreateInstance(this.element);
    document.addEventListener("modal:close", () => {
      setTimeout(() => {
        if (this.modal) {
          this.modal.hide();
          const backdropElement = document.querySelector(".modal-backdrop");
          if (backdropElement) {
            backdropElement.remove();
          }
          document.querySelectorAll<HTMLElement>('[data-auto-dismiss]').forEach((element) => {
              const duration = parseInt(element.getAttribute('data-auto-dismiss') || '5000', 10);
              if (!isNaN(duration) && duration > 0) {
                  setTimeout(() => {
                      element.remove();
                  }, duration);
              }
          });
        }
      }, 2000);
    });
  }
}
