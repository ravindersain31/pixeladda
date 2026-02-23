import { Controller } from "@hotwired/stimulus";
import { Modal } from "bootstrap";

export default class extends Controller {
  connect() {
    const proceedToCartLink = document.querySelector(".proceed-cart-modal") as HTMLAnchorElement;
    document.addEventListener("modal:open", (event) => {
      if (event instanceof CustomEvent) {
        const cartUrl = event.detail.cartUrl;

        if (cartUrl && proceedToCartLink) {
          proceedToCartLink.href = cartUrl;

          const modalElement = document.querySelector("#repeatOrderModal");
          if (modalElement) {
            const modal = new Modal(modalElement);
            modal.show();
          }
        }
      }
    });
  }
}
