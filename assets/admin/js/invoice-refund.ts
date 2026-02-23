interface InvoiceButton extends HTMLButtonElement {
    dataset: {
        gateway: 'paypal' | 'stripe';
        invoiceId: string;
        invoiceNumber: string;
        fullAmount: string;
        refundedAmount: string;
        remainingAmount: string;
        action: string;
        invoiceUrl?: string;
    };
}

const copyBtn = document.getElementById('copyInvoiceLinkBtn') as HTMLButtonElement | null;
const linkInput = document.getElementById('invoiceLinkInput') as HTMLInputElement | null;
const refundModalBody = document.getElementById('refundModalBody') as HTMLElement | null;

document.addEventListener('click', (event: MouseEvent) => {
    const target = event.target as HTMLElement;

    if (target && target.classList.contains('share-invoice-btn')) {
        const btn = target as InvoiceButton;
        const gateway = btn.dataset.gateway || 'paypal'; // default to paypal
        const modalTitle = document.querySelector('#shareInvoiceModal .modal-title') as HTMLElement | null;

        if (modalTitle) {
            modalTitle.textContent =
                gateway === 'stripe'
                    ? 'Stripe Invoice Share Link'
                    : 'PayPal Invoice Share Link';
        }

        if (linkInput && copyBtn) {
            linkInput.value = btn.dataset.invoiceUrl || '';
            copyBtn.textContent = 'Copy';
        }

        linkInput?.addEventListener('click', () => {
            const url = linkInput.value.trim();
            if (url && url.startsWith('http')) {
                window.open(url, '_blank', 'noopener,noreferrer');
            }
        });
    }

    if (target === copyBtn && linkInput) {
        linkInput.select();
        document.execCommand('copy');
        copyBtn.textContent = 'Copied!';
        setTimeout(() => (copyBtn.textContent = 'Copy'), 2000);
    }

    if (target && target.classList.contains('refund-invoice-btn')) {
        const btn = target as InvoiceButton;
        const gateway = btn.dataset.gateway;
        const invoiceId = btn.dataset.invoiceId;
        const invoiceNumber = btn.dataset.invoiceNumber;
        const fullAmount = parseFloat(btn.dataset.fullAmount || '0');
        const refundedAmount = parseFloat(btn.dataset.refundedAmount || '0');
        const remainingAmount = parseFloat(btn.dataset.remainingAmount || '0');
        const actionUrl = btn.dataset.action;

        const refundTitle = document.getElementById('refundModalTitle');
        if (refundTitle) {
            refundTitle.textContent =
                gateway === 'stripe'
                    ? 'Refund Stripe Invoice'
                    : 'Refund PayPal Invoice';
        }

        if (!refundModalBody) return;
        refundModalBody.innerHTML = `
            <p>
                Invoice <strong>#${invoiceNumber}</strong><br>
                <span class="text-muted">Total Amount:</span>
                <span>$${fullAmount.toFixed(2)}</span><br>
                ${refundedAmount > 0
                    ? `
                        <span class="text-muted">Refunded Amount:</span>
                        <span>$${refundedAmount.toFixed(2)}</span><br>
                        <span class="text-muted">Remaining Refundable:</span>
                        <span class="${remainingAmount <= 0 ? 'text-danger' : 'text-success'}">
                            $${remainingAmount.toFixed(2)}
                        </span>
                    `
                    : `
                        <span class="text-muted">Remaining Refundable:</span>
                        <span class="text-success">$${fullAmount.toFixed(2)}</span>
                    `}
            </p>
            <form id="refundForm${invoiceId}" method="post" action="${actionUrl}">
                <div class="mb-3">
                    <label for="refundAmount${invoiceId}" class="form-label">
                        Partial Refund Amount (optional)
                    </label>
                    <input type="number" class="form-control"
                        step="0.01" name="refundAmount"
                        id="refundAmount${invoiceId}"
                        value="${remainingAmount}"
                        placeholder="Leave blank for full refund (up to $${remainingAmount.toFixed(2)})"
                        max="${remainingAmount}" required>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Confirm Refund</button>
                </div>
            </form>
        `;

        const refundForm = document.getElementById(`refundForm${invoiceId}`) as HTMLFormElement | null;
        if (refundForm) {
            refundForm.addEventListener('submit', () => {
                const submitBtn = refundForm.querySelector('button[type="submit"]') as HTMLButtonElement;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            });
        }
    }
});
