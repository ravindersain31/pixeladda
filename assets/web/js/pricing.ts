interface PricingResponse {
    success: boolean;
    data?: {
        html: string;
        headingHtml: string;
        productType: {
            id: number;
            name: string;
            slug: string;
        };
    };
    error?: string;
    message?: string;
}

class PricingManager {
    private form: HTMLFormElement;
    private selectElement: HTMLSelectElement;
    private pricingContent: HTMLElement;
    private pricingHeading: HTMLElement;
    private pricingLoader: HTMLElement;
    private apiUrl: string = '';

    constructor() {
        this.form = document.getElementById('productTypePricingForm') as HTMLFormElement;
        this.selectElement = document.querySelector(`[name="product_type_pricing_form[productType]"]`) as HTMLSelectElement;
        this.pricingContent = document.getElementById('pricingTableContainer') as HTMLElement;
        this.pricingHeading = document.getElementById('pricingHeading') as HTMLElement;
        this.pricingLoader = document.getElementById('pricingLoader') as HTMLElement;

        if (!this.form) {
            return;
        }

        this.apiUrl = this.form.getAttribute('action') || '';

        this.init();
    }

    private init(): void {
        if (!this.selectElement) {
            console.error('Product type select element not found');
            return;
        }

        this.selectElement.addEventListener('change', () => {
            this.handleProductTypeChange();
        });
    }

    private handleProductTypeChange(): void {
        const selectedSlug = this.selectElement.value;

        if (!selectedSlug) {
            console.error('No product type slug selected');
            return;
        }

        this.fetchPricing(selectedSlug);
    }

    private showLoader(): void {
        if (this.pricingLoader) {
            this.pricingLoader.classList.remove('d-none');
        }
        if (this.pricingContent) {
            this.pricingContent.classList.add('loading');
        }
    }

    private hideLoader(): void {
        if (this.pricingLoader) {
            this.pricingLoader.classList.add('d-none');
        }
        if (this.pricingContent) {
            this.pricingContent.classList.remove('loading');
        }
    }

    private async fetchPricing(slug: string): Promise<void> {
        this.showLoader();

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ slug })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result: PricingResponse = await response.json();

            if (result.success && result.data) {
                this.updatePricingContent(result.data.html, result.data.headingHtml);
            } else {
                this.showError(result.error || 'Failed to load pricing data');
            }

        } catch (error) {
            console.error('Error fetching pricing:', error);
            this.showError('An error occurred while loading pricing data. Please try again.');
        } finally {
            this.hideLoader();
        }
    }

    private updatePricingContent(html: string, headingHtml: string): void {
        if (this.pricingContent) {
            this.pricingContent.innerHTML = html;
        }
        if (this.pricingHeading && headingHtml) {
            this.pricingHeading.innerHTML = headingHtml;
        }
    }

    private showError(message: string): void {
        if (this.pricingContent) {
            this.pricingContent.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <strong>Error:</strong> ${message}
                </div>
            `;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PricingManager();
});