import { Tooltip } from "bootstrap";
import Popover from "bootstrap/js/dist/popover.js";
import { enableStickyView } from "./sticky-view";

document.addEventListener('DOMContentLoaded', () => {
    const apiKey = "11bef1d5a6";
    const apiEndpoint = "https://api.shopperapproved.com/aggregates/reviews/36686?token=" + apiKey + "&xml=false";
    fetch(apiEndpoint, {
        headers: {
            Authorization: `Bearer ${apiKey}`,
            "Content-Type": "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (!data.error_code) {
                const reviewCount = data.total_reviews;
                $(".rating-star-count").text(reviewCount);
            }
        })
        .catch((error) => {
        });

    const searchInput: HTMLInputElement | null = document.querySelector('.navbar-search-desktop');
    const searchContainer: HTMLElement | null = document.querySelector('.search-container-1');

    if (searchInput && searchContainer) {
        searchInput.addEventListener('focus', () => {
            searchContainer.style.display = 'block';
        });

        document.addEventListener('click', (event) => {
            const target = event.target as HTMLElement;

            if (!searchContainer.contains(target)) {
                searchContainer.style.display = 'none';
            }
        });

        searchInput.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    }

    document.querySelectorAll<HTMLElement>('[data-auto-dismiss]').forEach((element) => {
        const duration = parseInt(element.getAttribute('data-auto-dismiss') || '5000', 10);
        if (!isNaN(duration) && duration > 0) {
            setTimeout(() => {
                element.remove();
            }, duration);
        }
    });

    const repeatOrderModals: NodeListOf<HTMLButtonElement> = document.querySelectorAll('.repeat-order-modal');
    const proceedToShoppingCartBtns: NodeListOf<HTMLAnchorElement> = document.querySelectorAll('.proceed-cart-modal');

    repeatOrderModals.forEach((repeatOrderModal) => {
        repeatOrderModal.addEventListener('click', (event) => {
            event.preventDefault();
            const href = repeatOrderModal.getAttribute('data-href');
            if (href) {
                proceedToShoppingCartBtns.forEach((btn) => {
                    btn.href = href;
                });
            }
        });
    });

    window.addProcessOnBtn = function (btn: any) {
        btn.prop('disabled', true);
        btn.html('<i class="fa fa-circle-notch fa-spin me-2"></i> Processing...');
    }

    window.removeProcessFromBtn = function (btn: any, content: string = 'Submit') {
        btn.prop('disabled', false);
        btn.html(content);
    }

    window.addProcessingOrderOverlay = function () {
        const element = document.getElementById('processing-express-order-message');
        if (element) {
            element.classList.remove('d-none');
        }
    }

    window.removeProcessingOrderOverlay = function () {
        const element = document.getElementById('processing-express-order-message');
        if (element) {
            element.classList.add('d-none');
        }
    }

    const discountPopup = document.getElementById('discount-popup');
    const discountPopupDialog = document.querySelector('#discount-popup .modal-dialog');
    const discountPopupButton = document.getElementById('discount-popup-button');
    const closeDiscountPopup = document.getElementById('close-discount-popup');
    const fullPrice = document.getElementById('full-price');
    const shopNowPopup = document.getElementById('shop-now-popup');

    // if (discountPopup && discountPopupButton && (localStorage.getItem('popup-subscribe') === null || localStorage.getItem('popup-subscribe') === '0')) {
    //     discountPopupButton.click();

    //     discountPopup.addEventListener('click', function(e) {
    //         if(e.target === discountPopup && e.target !== discountPopupDialog) {
    //             localStorage.setItem('popup-subscribe', '1');
    //             document.cookie = "popup-subscribe=true";
    //         }
    //     });
    //     if (closeDiscountPopup && fullPrice && shopNowPopup) {
    //         closeDiscountPopup.addEventListener('click', function(e) {
    //             localStorage.setItem('popup-subscribe', '1');
    //             document.cookie = "popup-subscribe=true";
    //         });
    //         fullPrice.addEventListener('click', function(e) {
    //             localStorage.setItem('popup-subscribe', '1');
    //             document.cookie = "popup-subscribe=true";
    //         });
    //     }
    // }

    const discountForm = document.getElementById('discount-form');
    let checkInterval: NodeJS.Timeout;

    function checkDiscountFlash() {
        const discountFlash = document.getElementById('discount-flash');
        if (discountFlash && discountFlash.textContent === "success") {
            localStorage.setItem('popup-subscribe', '1');
            document.cookie = "popup-subscribe=true";
            if (discountForm && shopNowPopup) {
                discountForm.style.display = 'none';
                shopNowPopup.removeAttribute('hidden');
                shopNowPopup.style.display = 'block';
            }
            clearInterval(checkInterval);
        }
    }

    if (discountForm) {
        discountForm.addEventListener('submit', (event) => {
            checkInterval = setInterval(checkDiscountFlash, 500);
        });
    }

    window.addEventListener('beforeunload', () => {
        clearInterval(checkInterval);
    });

    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    if (popoverTriggerList) {
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new Popover(popoverTriggerEl, {
            html: true,
            sanitize: false
        }));
    }

    function initializePopovers() {
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        if (popoverTriggerList) {
            const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new Popover(popoverTriggerEl, {
                html: true,
                sanitize: false
            }));
        }
    };

    function initializeToolTips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        if (tooltipTriggerList) {
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl, {
                html: true,
                sanitize: false
            }));
        }
    }

    initializePopovers();
    initializeToolTips();

    const collapseButton = document.getElementById('order-custom-size') as HTMLButtonElement;
    const customWrapper = document.getElementById('customSizeCollapse') as HTMLDivElement;

    if (collapseButton) {
        collapseButton.addEventListener('click', function () {
            const isExpanded = collapseButton.getAttribute('aria-expanded') === 'true';
            collapseButton.setAttribute('aria-expanded', (!isExpanded).toString());

            if (!isExpanded) {
                customWrapper.classList.remove('mb-2');
                collapseButton.classList.remove('ysp-bg-purple', 'text-white');
                collapseButton.classList.add('btn-default', 'ysp-purple');
            } else {
                collapseButton.classList.remove('btn-default', 'ysp-purple');
                collapseButton.classList.add('ysp-bg-purple', 'text-white');
                customWrapper.classList.add('mb-2');
            }
        });
    }

    const config = { childList: true, subtree: true };

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length > 0) {
                initializePopovers();
            }
        });
    });

    if (customWrapper) {
        observer.observe(customWrapper, config);
    }

    const sizes = document.querySelectorAll<HTMLInputElement>('.wire-stake-input');

    const handleShippingSelection = () => {
        const totalAmountElement = document.getElementById('total-subtotal') as HTMLInputElement;
        if (totalAmountElement && totalAmountElement.value) {
            const totalAmount = parseFloat(totalAmountElement.value);
            if (!isNaN(totalAmount)) {
                if (totalAmount >= 50) {
                    const radioButtons = document.querySelectorAll<HTMLInputElement>('input[id^="order_wire_stake_shipping_"]');
                    radioButtons.forEach((radioButton) => {
                        if (radioButton.dataset.price === "0" && radioButton.dataset.discount === "0") {
                            radioButton.click();
                        }
                    });
                } else {
                    document.getElementById("order_wire_stake_shipping_3")?.click();
                }
            }
        }
    };

    const observeTotalAmount = () => {
        const totalAmountElement = document.getElementById('total-subtotal') as HTMLInputElement;
        if (totalAmountElement && totalAmountElement.value) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                        setTimeout(() => {
                            handleShippingSelection();
                        }, 1500);
                    }
                });
            });

            observer.observe(totalAmountElement, {
                attributes: true
            });
        }
    };

    // Initialize observer once
    observeTotalAmount();

    if (sizes.length > 0) {
        sizes.forEach((size) => {
            let previousValue = size.value;  // Track the initial value

            size.addEventListener('input', () => {
                const newValue = size.value;
                // Only proceed if the value has changed
                if (newValue !== previousValue) {
                    previousValue = newValue; // Update the previous value

                    size.dispatchEvent(new Event('change', { bubbles: true }));

                    // No need to create observer here, observer is already set up
                    setTimeout(() => {
                        handleShippingSelection();
                    }, 1500);
                }
            });
        });
    }

    function initializeScrollToTopButton() {
        const scrollToTopBtn = document.getElementById("scrollToTopBtn") as HTMLButtonElement;

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        }

        function toggleScrollToTopButton() {
            const scrollY = window.scrollY || window.pageYOffset;
            const threshold = 1000;

            if (scrollY > threshold) {
                scrollToTopBtn.classList.remove("d-none");
                scrollToTopBtn.classList.add("d-block");
            } else {
                scrollToTopBtn.classList.remove("d-block");
                scrollToTopBtn.classList.add("d-none");
            }
        }

        scrollToTopBtn.addEventListener("click", scrollToTop);
        window.addEventListener("scroll", toggleScrollToTopButton);

        toggleScrollToTopButton();
    }

    function initializePasswordVisibility() {
        function togglePasswordVisibility(inputId: string, eyeIconId: string) {
            const passwordInput = document.getElementById(inputId) as HTMLInputElement;
            const eyeIcon = document.getElementById(eyeIconId) as HTMLElement;

            if (passwordInput && eyeIcon) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                }
            }
        }

        function updatePopoverContent(element: HTMLElement, inputId: string) {
            const passwordField = document.getElementById(inputId) as HTMLInputElement;
            if (passwordField) {
                const newContent = passwordField.type === 'password' ? 'Show Password' : 'Hide Password';
                element.setAttribute('data-bs-content', newContent);
                const popover = Popover.getInstance(element);
                if (popover) popover.dispose();
                const newPopover = new Popover(element);
                setTimeout(() => newPopover.show(), 50);
                setTimeout(() => newPopover.hide(), 3050);
            }
        }

        const passwordFields = [
            { inputId: 'password', eyeIconId: 'eye-icon' },
            { inputId: 'create_account_password_second', eyeIconId: 'eye-icon' },
            { inputId: 'customer_password_password_second', eyeIconId: 'eye-icon' },
        ];

        const confirmPasswordFields = [
            { inputId: 'confirm-password', eyeIconId: 'eye-icon-confirm' },
            { inputId: 'create_account_password_first', eyeIconId: 'eye-icon-confirm' },
            { inputId: 'customer_password_password_first', eyeIconId: 'eye-icon-confirm' },
        ];

        const revealPassword = document.getElementById('toggle-password') as HTMLSpanElement;
        const revealConfirmPassword = document.getElementById('toggle-confirm-password') as HTMLSpanElement;
        const passwordContainers = document.querySelectorAll('.password-container');

        const config = { childList: true, subtree: true };

        passwordContainers.forEach((container) => {
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach((node) => {
                        if (node instanceof HTMLElement && node.classList.contains('invalid-feedback')) {
                            adjustPasswordSpanPosition(container as HTMLElement, 'added');
                        }
                    });
                    mutation.removedNodes.forEach((node) => {
                        if (node instanceof HTMLElement && node.classList.contains('invalid-feedback')) {
                            adjustPasswordSpanPosition(container as HTMLElement, 'removed');
                        }
                    })
                });
            });

            observer.observe(container, config);
        });

        function adjustPasswordSpanPosition(container: HTMLElement, type: string) {
            const passwordSpan = container.querySelector('span[id*="password"]') as HTMLSpanElement;
            const input = container.querySelector('input') as HTMLInputElement;

            if (passwordSpan) {
                if (type === 'added') {
                    input.style.paddingRight = '60px';
                    passwordSpan.style.top = '35%';
                    passwordSpan.style.right = '35px';
                } else {
                    input.style.paddingRight = '30px';
                    passwordSpan.style.top = '51%';
                    passwordSpan.style.right = '10px';
                }
            }
        }

        if (revealPassword) {
            passwordFields.forEach((field) => {
                revealPassword.addEventListener('click', () => {
                    togglePasswordVisibility(field.inputId, field.eyeIconId)
                    updatePopoverContent(revealPassword, field.inputId);
                }
                );
            });
        }

        if (revealConfirmPassword) {
            confirmPasswordFields.forEach((field) => {
                revealConfirmPassword.addEventListener('click', () => {
                    togglePasswordVisibility(field.inputId, field.eyeIconId);
                    updatePopoverContent(revealConfirmPassword, field.inputId);
                }
                );
            });
        }
    }

    initializeScrollToTopButton();
    initializePasswordVisibility();

    const proofPopoverEl = document.getElementById('order-proof-approve-popover');
    if (proofPopoverEl) {
        const proofPopover = new Popover(proofPopoverEl, {
            html: true,
            sanitize: false,
            trigger: 'manual'
        });

        proofPopover.show();

        setTimeout(() => {
            proofPopover.hide();
        }, 5000);
    }

    const liveChatTriggers = document.querySelectorAll('.live-chat-trigger');
    if (liveChatTriggers.length > 0) {
        liveChatTriggers.forEach(element => {
            element.addEventListener('click', function (e) {
                e.preventDefault();
                // @ts-ignore
                Tawk_API.toggle();
            });
        });
    }

    enableStickyView("header.sticky-top", "#yspAccordionSidebar");

    const container = document.querySelector('#blogSidebarAccordion');
    if (container) {
        container.addEventListener('change', (event) => {
            const target = event.target as HTMLInputElement;
            if (target.matches('.category-radio')) {
                const url = target.dataset.url;
                if (url) {
                    window.location.href = url;
                }
            }
        });
    }
});