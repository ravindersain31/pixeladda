import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";

export default class extends Controller<HTMLElement> {
    connect(): void {
        const modalEl = this.element.closest(".modal") as HTMLElement | null;
        if (!modalEl) return;

        modalEl.addEventListener("hidden.bs.modal", async () => {
            const liveRoot = modalEl.querySelector<HTMLElement>("[data-controller='live']");
            const form = modalEl.querySelector<HTMLFormElement>("form");

            if (liveRoot) {
                try {
                    const component = await getComponent(liveRoot);
                    await component.action("resetForm");
                    return;
                } catch (error) {
                    console.error("LiveComponent resetForm error:", error);
                }
            }

            if (form) {
                const validator =
                    ($(form).data("validator")) ||
                    ($(form).data("jqueryValidation"));

                if (validator) {
                    validator.resetForm();
                }

                $(form).find(".error").removeClass("error");
                $(form).find("label.error").remove();

                form.reset();

                if (
                    typeof (window as any).grecaptcha !== "undefined" &&
                    (window as any).grecaptcha?.enterprise?.reset
                ) {
                    try {
                        (window as any).grecaptcha.enterprise.reset();
                    } catch (_) {}
                }
            }
        });
    }
}
