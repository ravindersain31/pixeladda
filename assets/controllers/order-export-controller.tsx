import { Controller } from '@hotwired/stimulus';

interface OrderRow {
    order_id: string;
    order_date: string;
    store_name: string;
    customer_first_name: string;
    customer_last_name: string;
    customer_email: string;
    customer_phone: string;
    address_line_1: string;
    address_line_2: string;
    city: string;
    state: string;
    zipcode: string;
    country: string;
    item_name: string;
    item_type: string;
    item_sku: string;
    category: string;
    productType: string;
    item_quantity: number;
    item_unit_price: number;
    item_addons_amount: number;
    item_shipping_amount: number;
    item_total_amount: number;
    order_sub_total: number;
    order_shipping_amount: number;
    order_discount: number;
    order_total_amount: number;
    payment_method: string;
    payment_status: string;
    order_status: string;
    is_super_rush: string;
    coupon_code: string;
}

interface MetaResponse {
    success: boolean;
    meta?: {
        total_orders: number;
        start_date: string;
        end_date: string;
    };
    error?: string;
}

interface BatchResponse {
    success: boolean;
    data?: Array<OrderRow | OrderRow[]>;
    pagination?: {
        total: number;
        limit: number;
        offset: number;
        current_count: number;
        has_more: boolean;
    };
    error?: string;
}

export default class extends Controller {
    static targets = [
        'startDate',
        'endDate',
        'exportBtn',
        'loader',
        'loaderText',
        'progressBar',
        'progressFill',
        'progressPercent',
        'successAlert',
        'errorAlert',
        'errorMessage',
        'stats'
    ];

    static values = {
        apiUrl: String,
        metaUrl: String,
        batchSize: { type: Number, default: 100 }
    };

    declare readonly startDateTarget: HTMLInputElement;
    declare readonly endDateTarget: HTMLInputElement;
    declare readonly exportBtnTarget: HTMLButtonElement;
    declare readonly loaderTarget: HTMLElement;
    declare readonly loaderTextTarget: HTMLElement;
    declare readonly progressBarTarget: HTMLElement;
    declare readonly progressFillTarget: HTMLElement;
    declare readonly progressPercentTarget: HTMLElement;
    declare readonly successAlertTarget: HTMLElement;
    declare readonly errorAlertTarget: HTMLElement;
    declare readonly errorMessageTarget: HTMLElement;
    declare readonly statsTarget: HTMLElement;
    declare readonly hasStatsTarget: boolean;
    declare readonly hasProgressPercentTarget: boolean;

    declare apiUrlValue: string;
    declare metaUrlValue: string;
    declare batchSizeValue: number;

    private allOrdersData: OrderRow[] = [];
    private isExporting: boolean = false;

    connect(): void {
        console.log('Order Export Controller connected');
        this.setDefaultDates();
    }

    setDefaultDates(): void {
        const today = new Date();
        const sixMonthsAgo = new Date();
        sixMonthsAgo.setMonth(today.getMonth() - 6);

        this.startDateTarget.value = this.formatDate(sixMonthsAgo);
        this.endDateTarget.value = this.formatDate(today);
        this.endDateTarget.max = this.formatDate(today);
    }

    async export(event: Event): Promise<void> {
        event.preventDefault();

        if (this.isExporting) {
            return;
        }

        const startDate = this.startDateTarget.value;
        const endDate = this.endDateTarget.value;

        if (!startDate || !endDate) {
            this.showError('Please select both start and end dates');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            this.showError('Start date must be before or equal to end date');
            return;
        }

        this.isExporting = true;
        this.allOrdersData = [];
        this.hideAlerts();
        this.showLoader();
        this.disableButton();

        try {
            // Step 1: Get metadata (total count)
            this.updateLoaderText('Fetching order information...');
            const meta = await this.fetchMeta(startDate, endDate);

            if (!meta.success || !meta.meta) {
                throw new Error(meta.error || 'Failed to fetch order metadata');
            }

            const totalOrders = meta.meta.total_orders;

            if (totalOrders === 0) {
                this.showError('No orders found in the selected date range');
                this.hideLoader();
                this.enableButton();
                this.isExporting = false;
                return;
            }

            this.updateLoaderText(`Found ${totalOrders} orders. Starting export...`);

            // Step 2: Fetch all orders in batches
            await this.fetchAllOrders(startDate, endDate, totalOrders);

            // Step 3: Convert to CSV
            this.updateLoaderText('Converting to CSV...');
            this.updateProgress(95);
            await this.sleep(300);

            const csv = this.convertToCSV(this.allOrdersData);

            // Step 4: Download
            this.updateLoaderText('Preparing download...');
            this.updateProgress(100);
            await this.sleep(300);

            this.downloadCSV(csv, `orders_export_${startDate}_to_${endDate}.csv`);

            // Success
            this.hideLoader();
            this.showSuccess(`Successfully exported ${this.allOrdersData.length} rows!`);
            this.showStats(totalOrders, this.allOrdersData.length);

        } catch (error) {
            console.error('Export error:', error);
            this.showError(error instanceof Error ? error.message : 'An unknown error occurred');
            this.hideLoader();
        } finally {
            this.enableButton();
            this.isExporting = false;
        }
    }

    async fetchMeta(startDate: string, endDate: string): Promise<MetaResponse> {
        const url = `${this.metaUrlValue}?start_date=${startDate}&end_date=${endDate}`;
        const response = await fetch(url);
        return await response.json();
    }

    async fetchAllOrders(startDate: string, endDate: string, totalOrders: number): Promise<void> {
        let offset = 0;
        let batchNumber = 0;
        let totalFetched = 0;

        while (offset < totalOrders) {
            batchNumber++;

            this.updateLoaderText(
                `Fetching batch ${batchNumber}... (${totalFetched}/${totalOrders} orders)`
            );

            const progress = Math.min((totalFetched / totalOrders) * 90, 90);
            this.updateProgress(progress);

            try {
                const batchData = await this.fetchBatch(startDate, endDate, offset);

                if (!batchData.success || !batchData.data) {
                    throw new Error(batchData.error || 'Failed to fetch batch');
                }

                for (const orderRows of batchData.data) {
                    if (Array.isArray(orderRows)) {
                        this.allOrdersData.push(...orderRows);
                    } else {
                        this.allOrdersData.push(orderRows);
                    }
                }

                totalFetched += batchData.pagination!.current_count;
                offset += this.batchSizeValue;

                if (!batchData.pagination!.has_more) {
                    break;
                }

                await this.sleep(100);

            } catch (error) {
                console.error(`Error in batch ${batchNumber}:`, error);
                throw new Error(`Failed at batch ${batchNumber}: ${error instanceof Error ? error.message : 'Unknown error'}`);
            }
        }

        this.updateLoaderText(`Fetched all ${totalFetched} orders!`);
    }

    async fetchBatch(startDate: string, endDate: string, offset: number): Promise<BatchResponse> {
        const url = `${this.apiUrlValue}?start_date=${startDate}&end_date=${endDate}&limit=${this.batchSizeValue}&offset=${offset}`;
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    convertToCSV(data: OrderRow[]): string {
        if (data.length === 0) {
            return '';
        }

        // CSV Headers
        const headers = [
            'Order ID',
            'Order Date',
            'Store Name',
            'Customer First Name',
            'Customer Last Name',
            'Customer Email',
            'Customer Phone',
            'Address Line 1',
            'Address Line 2',
            'City',
            'State',
            'Zipcode',
            'Country',
            'Item Name',
            'Item Type',
            'Item SKU',
            'Category',
            'Product Type',
            'Item Quantity',
            'Item Unit Price',
            'Item Add-ons Amount',
            'Item Shipping Amount',
            'Item Total Amount',
            'Order Sub Total',
            'Order Shipping Amount',
            'Order Discount',
            'Order Total Amount',
            'Payment Method',
            'Payment Status',
            'Order Status',
            'Is Super Rush',
            'Coupon Code'
        ];

        // Build CSV rows
        const rows: string[][] = [headers];

        data.forEach(row => {
            rows.push([
                row.order_id,
                row.order_date,
                row.store_name,
                row.customer_first_name,
                row.customer_last_name,
                row.customer_email,
                row.customer_phone,
                row.address_line_1,
                row.address_line_2,
                row.city,
                row.state,
                row.zipcode,
                row.country,
                row.item_name,
                row.item_type,
                row.item_sku,
                row.category,
                row.productType,
                String(row.item_quantity),
                this.formatNumber(row.item_unit_price),
                this.formatNumber(row.item_addons_amount),
                this.formatNumber(row.item_shipping_amount),
                this.formatNumber(row.item_total_amount),
                this.formatNumber(row.order_sub_total),
                this.formatNumber(row.order_shipping_amount),
                this.formatNumber(row.order_discount),
                this.formatNumber(row.order_total_amount),
                row.payment_method,
                row.payment_status,
                row.order_status,
                row.is_super_rush,
                row.coupon_code
            ]);
        });

        // Convert to CSV string
        return rows.map(row =>
            row.map(cell => this.escapeCSV(cell)).join(',')
        ).join('\n');
    }

    escapeCSV(cell: string | number): string {
        if (cell === null || cell === undefined) {
            return '';
        }

        const str = String(cell);

        if (str.includes(',') || str.includes('"') || str.includes('\n')) {
            return `"${str.replace(/"/g, '""')}"`;
        }

        return str;
    }

    formatNumber(value: number | string | null | undefined): string {
        if (value === null || value === undefined || value === '') {
            return '0.00';
        }
        return parseFloat(String(value)).toFixed(2);
    }

    downloadCSV(csvContent: string, filename: string): void {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');

        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    // UI Helper Methods
    showLoader(): void {
        this.loaderTarget.classList.add('active');
        this.updateProgress(0);
    }

    hideLoader(): void {
        this.loaderTarget.classList.remove('active');
    }

    updateLoaderText(text: string): void {
        this.loaderTextTarget.textContent = text;
    }

    updateProgress(percent: number): void {
        this.progressFillTarget.style.width = `${percent}%`;
        if (this.hasProgressPercentTarget) {
            this.progressPercentTarget.textContent = `${Math.round(percent)}%`;
        }
    }

    disableButton(): void {
        this.exportBtnTarget.disabled = true;
        this.exportBtnTarget.textContent = '⏳ Exporting...';
    }

    enableButton(): void {
        this.exportBtnTarget.disabled = false;
        this.exportBtnTarget.textContent = '🚀 Export Orders to CSV';
    }

    hideAlerts(): void {
        this.successAlertTarget.classList.remove('active');
        this.errorAlertTarget.classList.remove('active');
    }

    showSuccess(message: string): void {
        this.successAlertTarget.textContent = `✅ ${message}`;
        this.successAlertTarget.classList.add('active');
    }

    showError(message: string): void {
        this.errorMessageTarget.textContent = message;
        this.errorAlertTarget.classList.add('active');

        setTimeout(() => {
            this.errorAlertTarget.classList.remove('active');
        }, 5000);
    }

    showStats(totalOrders: number, totalRows: number): void {
        if (this.hasStatsTarget) {
            this.statsTarget.innerHTML = `
                <div class="stat-card">
                    <div class="stat-value">${totalOrders.toLocaleString()}</div>
                    <div class="stat-label">Orders Exported</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${totalRows.toLocaleString()}</div>
                    <div class="stat-label">CSV Rows</div>
                </div>
            `;
            this.statsTarget.style.display = 'grid';
        }
    }

    sleep(ms: number): Promise<void> {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // Quick date selection
    selectDateRange(event: Event): void {
        const target = event.target as HTMLElement;
        const days = parseInt(target.dataset.days || '0');
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(endDate.getDate() - days);

        this.startDateTarget.value = this.formatDate(startDate);
        this.endDateTarget.value = this.formatDate(endDate);
    }

    formatDate(date: Date): string {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
}