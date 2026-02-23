window.addEventListener('DOMContentLoaded', event => {
    // Activate feather

    // Enable tooltips globally
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Enable popovers globally
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        // Uncomment Below to persist sidebar toggle between refreshes
        // if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
        //     document.body.classList.toggle('sidenav-toggled');
        // }
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sidenav-toggled'));
        });
    }

    // Close side navigation when width < LG
    const sidenavContent = document.body.querySelector('#layoutSidenav_content');
    if (sidenavContent) {
        sidenavContent.addEventListener('click', event => {
            const BOOTSTRAP_LG_WIDTH = 992;
            if (window.innerWidth >= 992) {
                return;
            }
            if (document.body.classList.contains("sidenav-toggled")) {
                document.body.classList.toggle("sidenav-toggled");
            }
        });
    }

    // Add active state to sidbar nav links
    let activatedPath = window.location.pathname.match(/([\w-]+\.html)/, '$1');

    if (activatedPath) {
        activatedPath = activatedPath[0];
    } else {
        activatedPath = 'index.html';
    }

    const targetAnchors = document.body.querySelectorAll('[href="' + activatedPath + '"].nav-link');

    targetAnchors.forEach(targetAnchor => {
        let parentNode = targetAnchor.parentNode;
        while (parentNode !== null && parentNode !== document.documentElement) {
            if (parentNode.classList.contains('collapse')) {
                parentNode.classList.add('show');
                const parentNavLink = document.body.querySelector(
                    '[data-bs-target="#' + parentNode.id + '"]'
                );
                parentNavLink.classList.remove('collapsed');
                parentNavLink.classList.add('active');
            }
            parentNode = parentNode.parentNode;
        }
        targetAnchor.classList.add('active');
    });

    $('form').on('submit', function (e) {
        // Check if the form is valid
        if (this.checkValidity()) {
            $(this).find('button[type=submit]:not(.no-processing)').prop('disabled', true).html('Processing...');
        } else {
            e.preventDefault(); // Prevent form submission if validation fails
        }
    });

    $('.btn.process').on('click', function () {
        $(this).addClass('disabled');
        $(this).text('Processing...');
    });

    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
        const duration = parseInt(element.getAttribute('data-auto-dismiss') || '5000', 10);
        if (!isNaN(duration) && duration > 0) {
            setTimeout(() => {
                element.remove();
            }, duration);
        }
    });

    const elements = document.querySelectorAll('input[id^="paypal_invoice_items"], textarea[id^="paypal_invoice_items"]');
    if (elements) {
        elements.forEach(function (element) {
            // console.log(element.tagName)
            if (element.tagName.toLowerCase() === 'textarea') {
                element.textContent = element.getAttribute('temp-value') || '';
            } else {
                element.value = element.getAttribute('temp-value') || '';
            }
            element.dispatchEvent(new Event("change", {bubbles: true}));
        });
    }

    $(function () {
        const triggerInputElements = document.querySelectorAll('.trigger-input');
        triggerInputElements.forEach(function (element) {
            element.dispatchEvent(new Event('input', {bubbles: true}));
        });
    });
});
