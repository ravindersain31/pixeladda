import { message } from "antd";
import $ from "jquery";
import { Modal } from "bootstrap";

$(document).ready(() => {
    // cart page order protection on hover showing modal
    $(".ysp-info-icon").hover(() => {
        $("#orderProtectionModel").modal("show");
    });

    // shop page accordion (having name as Product title)
    if (window.screen.width < 1191) {
        $(".shop-page .accordion-collapse").removeClass("show");
    }

    // proof page scroll to req-changes card section
    $(".order-details .req-change").on("click", () => {
        document.getElementById("request-changes-component-collapse")?.scrollIntoView();
    });

    // Increment quantity value
    function incrementValue(e: any): void {
        e.preventDefault();
        const target = e.target as HTMLElement;
        const form = target.closest("form") as HTMLFormElement;
        const input = form.querySelector('input[name="cart_item_quantity[quantity]"]') as HTMLInputElement;
        const updateQuantity = form.querySelector(".update-cart-quantity") as HTMLButtonElement;
        const sku = form.querySelector('input[name="cart_item_quantity[sku]"]') as HTMLInputElement;
        const isCustomSizeSample = form.querySelector('input[name="cart_item_quantity[isCustomSizeSample]"]') as HTMLInputElement;
        const currentVal = parseInt(input.value, 10);

        if (sku.value === 'SAMPLE' || isCustomSizeSample.value === "true") {

            if (currentVal >= 3) {
                message.error("Quantity cannot be more than 3");
                return;
            }

            if (currentVal < 3) {
                input.value = (currentVal + 1).toString();
                updateQuantity.classList.add("d-block");
                updateQuantity.classList.remove("d-none");
            }
        } else {
            if (!isNaN(currentVal) && currentVal < 100000) {
                input.value = (currentVal + 1).toString();
                updateQuantity.classList.add("d-block");
                updateQuantity.classList.remove("d-none");
            }
        }
    }

    // Decrement quantity value
    function decrementValue(e: any): void {
        e.preventDefault();
        const target = e.target as HTMLElement;
        const form = target.closest("form") as HTMLFormElement;
        const input = form.querySelector('input[name="cart_item_quantity[quantity]"]') as HTMLInputElement;
        const updateQuantity = form.querySelector(".update-cart-quantity") as HTMLButtonElement;
        const currentVal = parseInt(input.value, 10);

        if (!currentVal || isNaN(currentVal) || currentVal == 0) {
            input.value = "1";
        } else if (currentVal > 1) {
            input.value = (currentVal - 1).toString();
            updateQuantity.classList.add("d-block");
            updateQuantity.classList.remove("d-none");
        } else {
            input.value = "1";
        }
    }

    // Ensure quantity is valid on form submit
    function validateQuantityOnSubmit(e: Event): void {
        const form = e.target as HTMLFormElement;
        const input = form.querySelector('input[name="cart_item_quantity[quantity]"]') as HTMLInputElement;
        input.dispatchEvent(new Event("change", { bubbles: true }));
        const quantityValue = parseInt(input.value, 10);
        const submitButton = form.querySelector('.update-cart-quantity') as HTMLInputElement;

        submitButton.disabled = true;
        if (!quantityValue || isNaN(quantityValue) || quantityValue == 0) {
            input.value = "1";
        } else if (quantityValue > 100000) {
            input.value = "100000";
        }
    }

    // Ensure quantity is valid on input change
    function validateQuantityOnChange(e: Event): void {
        const input = e.target as HTMLInputElement;
        const quantityValue = parseInt(input.value, 10);
        const form = input.closest("form") as HTMLFormElement;
        const updateQuantity = form.querySelector(".update-cart-quantity") as HTMLButtonElement;
        const sku = form.querySelector('input[name="cart_item_quantity[sku]"]') as HTMLInputElement;
        const isCustomSizeSample = form.querySelector('input[name="cart_item_quantity[isCustomSizeSample]"]') as HTMLInputElement;

        updateQuantity.classList.add("d-block");
        updateQuantity.classList.remove("d-none");
        if (!quantityValue || isNaN(quantityValue) || quantityValue == 0) {
            input.value = "1";
        } else if (quantityValue > 100000) {
            input.value = "100000";
        } else if (sku.value === 'SAMPLE' || isCustomSizeSample.value === "true") {
            if (quantityValue > 3) {
                message.error("Quantity cannot be more than 3");
                input.value = '3';
            }
        }
    }

    function showSaveButton(e: Event): void {
        const input = e.target as HTMLInputElement;
        const quantityValue = parseInt(input.value, 10);
        const form = input.closest("form") as HTMLFormElement;
        const updateQuantity = form.querySelector(".update-cart-quantity") as HTMLButtonElement;
        const sku = form.querySelector('input[name="cart_item_quantity[sku]"]') as HTMLInputElement;
        const isCustomSizeSample = form.querySelector('input[name="cart_item_quantity[isCustomSizeSample]"]') as HTMLInputElement;

        updateQuantity.classList.add("d-block");
        updateQuantity.classList.remove("d-none");

        if (sku.value === 'SAMPLE' || isCustomSizeSample.value === "true") {
            if (quantityValue > 3) {
                message.error("Quantity cannot be more than 3");
                input.value = '3';
            }
        }
    }

    document.querySelectorAll(".button-plus").forEach((button) => {
        button.addEventListener("click", incrementValue);
    });

    document.querySelectorAll(".button-minus").forEach((button) => {
        button.addEventListener("click", decrementValue);
    });

    const forms = document.querySelectorAll('form[name="cart_item_quantity"]');
    forms.forEach((form) => {
        form.addEventListener("submit", validateQuantityOnSubmit);
        const input = form.querySelector('input[name="cart_item_quantity[quantity]"]') as HTMLInputElement;
        input.addEventListener("change", validateQuantityOnChange);
        input.addEventListener("input", showSaveButton);
    });

    // ===== DESIGN PROOF APPROVAL LOGIC =====
    const needProofForm = document.getElementById('need-proof-form') as HTMLFormElement | null;
    const designModalElement = document.getElementById('designPreviewModal') as HTMLElement | null;
    const approveBtn = document.getElementById('approveDesignBtn') as HTMLButtonElement | null;
    const statusContainer = document.getElementById('need-proof-status');

    const initializeNeedProof = (): void => {
        if (!needProofForm) return;
        const updateUrl = needProofForm.dataset.updateUrl;
        const approveUrl = needProofForm.dataset.approveUrl;
        const needProofRadios = needProofForm.querySelectorAll<HTMLInputElement>('input[name="need_proof[needProof]"]');
        const designApprovedField = needProofForm.querySelector<HTMLInputElement>('input[name="need_proof[designApproved]"]');

        let designModal: Modal | null = null;

        if (designModalElement) {
            designModal = new Modal(designModalElement);
        }

        if (designModal && designApprovedField) {
            const needProofValue = needProofForm.querySelector<HTMLInputElement>('input[name="need_proof[needProof]"]:checked')?.value;
            const isApproved = designApprovedField.value === '1';

            if (needProofValue === '0' && !isApproved) {
                setTimeout(() => {
                    designModal?.show();
                }, 350);
            }
        }

        function updateStatusUI(needProof: boolean, designApproved: boolean): void {
            if (!statusContainer) return;

            if (needProof === false && designApproved) {
                statusContainer.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Design Approved!</strong> Your order will proceed without a proof review.
                    </div>
                `;
            } else if (needProof === false && designApproved === false) {
                statusContainer.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Approval Required:</strong> Please review and approve your design before checkout.
                    </div>
                `;
            } else {
                statusContainer.innerHTML = '';
            }
        }

        async function saveNeedProof(): Promise<{ needProof: boolean, designApproved: boolean } | null> {
            if (!updateUrl) return null;

            const formData = new FormData(needProofForm!);

            try {
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        return {
                            needProof: data.needProof,
                            designApproved: data.designApproved
                        };
                    }
                }
            } catch (error) {
                console.error('Error saving need proof:', error);
            }

            return null;
        }

        needProofRadios?.forEach(radio => {
            radio.addEventListener('change', async () => {
                const selectedValue = radio.value;
                const isApproved = designApprovedField?.value === "1";

                if (selectedValue === "0") {
                    updateStatusUI(false, isApproved);
                    setTimeout(() => designModal?.show(), 200);
                    return;
                }

                if (selectedValue === "1" && !isApproved) {
                    updateStatusUI(true, isApproved);
                    return;
                }

                if (selectedValue === "1" && isApproved) {
                    designApprovedField.value = "0";
                    const result = await saveNeedProof();
                    if (result) {
                        updateStatusUI(result.needProof, result.designApproved);
                    }
                    return;
                }
            });
        });


        approveBtn?.addEventListener('click', async () => {
            if (!approveUrl) return;

            const originalText = approveBtn.innerHTML;

            approveBtn.disabled = true;
            approveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Approving...`;

            try {
                const response = await fetch(approveUrl, { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    if (designApprovedField) {
                        designApprovedField.value = '1';
                    }

                    const noRadio = Array.from(needProofRadios || []).find(r => r.value === '0') as HTMLInputElement;
                    if (noRadio) noRadio.checked = true;

                    updateStatusUI(data.needProof, data.designApproved);
                    designModal?.hide();
                }

            } catch (error) {
                console.error(error);
            } finally {
                approveBtn.disabled = false;
                approveBtn.innerHTML = originalText;
            }
        });

        designModalElement?.addEventListener('hidden.bs.modal',  async () => {
            const isApproved = designApprovedField?.value === '1';

            if (!isApproved) {
                const yesRadio = Array.from(needProofRadios || []).find(r => r.value === '1') as HTMLInputElement;
                if (yesRadio) {
                    yesRadio.checked = true;
                    const result = await saveNeedProof();
                    if (result) {
                        updateStatusUI(result.needProof, result.designApproved);
                    }
                }
            }
        });
    }
    initializeNeedProof();
});