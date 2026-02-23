import { Controller } from "@hotwired/stimulus";
import { Modal } from "bootstrap";
import * as XLSX from "xlsx";

export default class extends Controller {
  static targets = ["fileInput", "progressContainer", "uploadButton", "carrierSelect"];

  declare fileInputTarget: HTMLInputElement;
  declare progressContainerTarget: HTMLDivElement;
  declare uploadButtonTarget: HTMLButtonElement;
  declare carrierSelectTarget: HTMLSelectElement;
  private fileIdToDelete: string | null = null;

  connect(): void {

  }

  async uploadCSV(event: Event): Promise<void> {
    event.preventDefault();

    const files = Array.from(this.fileInputTarget.files || []);
    const selectedCarrier = this.carrierSelectTarget.value.trim();

    if (!selectedCarrier && files.length > 0) {
      this.showGlobalError("Please select a carrier before uploading files. Carrier is required.");
      return;
    }

    if (files.length === 0) {
      this.showGlobalError("Select at least one file!");
      this.uploadButtonTarget.disabled = false;
      return;
    }

    this.progressContainerTarget.innerHTML = "";

    for (const file of files) {
      await this.processFile(file, selectedCarrier);
    }

    this.fileInputTarget.value = "";
    this.uploadButtonTarget.innerText = "Refreshing...";

    setTimeout(() => {
      location.reload();
    }, 2500);
  }

  private async processFile(file: File, selectedCarrier: string): Promise<void> {
    const progressWrapper = document.createElement("div");
    progressWrapper.classList.add("small", "mb-2");

    progressWrapper.innerHTML = `
      <p class="mb-1 text-truncate"><strong>${file.name}</strong></p>
      <div class="progress" style="height: 5px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
             role="progressbar" style="width: 0%;" data-progress-bar></div>
      </div>
      <p class="small text-muted m-0" data-progress-text>0/0 uploaded (0%)</p>
      <p class="small text-danger m-0 d-none" data-error-message></p>
    `;

    this.progressContainerTarget.appendChild(progressWrapper);

    const progressBar = progressWrapper.querySelector("[data-progress-bar]") as HTMLDivElement;
    const progressText = progressWrapper.querySelector("[data-progress-text]") as HTMLParagraphElement;
    const errorMessage = progressWrapper.querySelector("[data-error-message]") as HTMLParagraphElement;

    const CHUNK_SIZE = 50;
    const data = await this.readSpreadsheet(file, selectedCarrier);

    const totalRecords = data.length;
    let uploadedCount = 0;

    for (let i = 0; i < totalRecords; i += CHUNK_SIZE) {
      const chunk = data.slice(i, i + CHUNK_SIZE);
      const response = await this.uploadBatch(chunk, file);

      if (response.status !== "success") {
        errorMessage.innerText = `Failed: ${response.message}`;
        errorMessage.classList.remove("d-none");
        progressBar.classList.replace("bg-primary", "bg-danger");
        progressText.innerText = `Upload Failed! (${uploadedCount}/${totalRecords})`;
        return;
      }

      uploadedCount += chunk.length;
      this.updateProgress(uploadedCount, totalRecords, progressBar, progressText);
    }

    progressText.classList.add("text-success");
    progressText.innerText = `${uploadedCount}/${totalRecords} uploaded (100%)`;
    progressBar.classList.replace("bg-primary", "bg-success");
  }

  private async readSpreadsheet(file: File, selectedCarrier: string): Promise<any[]> {

    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      const isExcel = file.name.toLowerCase().endsWith(".xlsx") || file.name.toLowerCase().endsWith(".xls");
      const fallbackCarrier = this.extractCarrierName(file.name);
      const finalCarrier = selectedCarrier || fallbackCarrier;

      reader.onload = (event: ProgressEvent<FileReader>) => {
        try {
          const result = event.target?.result;
          if (!result) {
            console.error("No result from FileReader.");
            return reject("Failed to read file.");
          }

          if (isExcel) {
            // Read Excel data
            const data = new Uint8Array(result as ArrayBuffer);
            const workbook = XLSX.read(data, { type: "array" });
            const sheetName = workbook.SheetNames[0];

            if (!sheetName) {
              console.error("No sheet found in Excel file.");
              return reject("No sheets found in Excel file.");
            }

            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "", raw: true });

            if (!Array.isArray(jsonData) || jsonData.length === 0) {
              console.warn("Parsed Excel data is empty.");
              return reject("No data found in Excel sheet.");
            }

            const enrichedData = jsonData.map((row: any) => ({
              ...row,
              Carrier: finalCarrier
            }));

            resolve(enrichedData);
          } else {
            // Read CSV data
            const text = result as string;
            const lines = text.split("\n").filter(line => line.trim() !== "");
            if (lines.length < 2) {
              console.warn("CSV has no content rows.");
              return reject("CSV file has no data rows.");
            }

            const header = this.parseCSVLine(lines[0]);
            const data = lines.slice(1).map(line => {
              const columns = this.parseCSVLine(line);
              const rowObject: { [key: string]: string } = {};
              header.forEach((column, index) => {
                rowObject[column.trim()] = columns[index]?.trim() || "";
              });
              return rowObject;
            });

            const enrichedData = data.map(row => ({
              ...row,
              Carrier: finalCarrier
            }));

            resolve(enrichedData);
          }
        } catch (error) {
          console.error("Error parsing spreadsheet:", error);
          reject("Error parsing file contents.");
        }
      };

      reader.onerror = () => {
        console.error("FileReader error:", reader.error);
        reject("Error reading the file.");
      };

      if (isExcel) {
        reader.readAsArrayBuffer(file);
      } else {
        reader.readAsText(file);
      }
    });
  }

  private extractCarrierName(fileName: string): string {
    const lower = fileName.toLowerCase();
    if (lower.includes("fedex")) return "FedEx";
    if (lower.includes("ups")) return "UPS";
    if (lower.includes("dhl")) return "DHL";
    if (lower.includes("usps")) return "USPS";
    return "Unknown";
  }

  private async uploadBatch(batch: any[], file: File): Promise<any> {
    try {
      const formData = new FormData();
      formData.append("file", file);
      formData.append("carrier", batch[0].Carrier);
      formData.append("records", JSON.stringify(batch));

      const response = await fetch(`/cogs/shipping/invoice-upload`, {
        method: "POST",
        body: formData,
      });

      return await response.json();
    } catch (error) {
      console.error("Upload failed", error);
      return { status: "error", message: "Network error" };
    }
  }

  private updateProgress(uploaded: number, total: number, progressBar: HTMLDivElement, progressText: HTMLParagraphElement): void {
    const percentage = ((uploaded / total) * 100).toFixed(2);
    progressBar.style.width = `${percentage}%`;
    progressText.innerText = `${uploaded}/${total} uploaded (${percentage}%)`;
  }

  private showGlobalError(message: string): void {
    const errorWrapper = document.createElement("p");
    errorWrapper.classList.add("text-danger", "small", "mt-1");
    errorWrapper.innerText = message;
    this.progressContainerTarget.appendChild(errorWrapper);
  }

  private parseCSVLine(line: string): string[] {
    const values: string[] = [];
    let currentValue = '';
    let inQuotes = false;

    for (let char of line) {
      if (char === '"') {
        inQuotes = !inQuotes;
      } else if (char === ',' && !inQuotes) {
        values.push(currentValue.trim());
        currentValue = '';
      } else {
        currentValue += char;
      }
    }
    values.push(currentValue.trim());
    return values.map(value => value.replace(/^"|"$/g, ''));
  }

  async confirmInvoicesDelete(event: Event): Promise<void> {
    event.preventDefault();

    const target = event.currentTarget as HTMLButtonElement;
    const fileId = target.dataset.fileId;
    if (!fileId) {
      console.error("Error: No file ID found.");
      return;
    }

    this.fileIdToDelete = fileId;
    const modalElement = document.getElementById("deleteInvoiceModal");
    const deleteInvoiceMessage = document.getElementById("deleteInvoiceMessage");

    if (deleteInvoiceMessage) {
      deleteInvoiceMessage.textContent = "";
      deleteInvoiceMessage.classList.remove("text-success", "text-danger");
    }

    if (modalElement) {
      const modal = new Modal(modalElement);
      modal.show();
    }
  }

  async deleteInvoices(): Promise<void> {
    if (!this.fileIdToDelete) {
      console.error("Error: No file ID to delete.");
      return;
    }

    const deleteInvoiceMessage = document.getElementById("deleteInvoiceMessage");

    try {
      const response = await fetch(`/cogs/shipping/delete-invoices/${this.fileIdToDelete}`, {
        method: "DELETE"
      });

      const { success, error } = await response.json();
      if (!success) throw new Error(error || "Unknown error");

      if (deleteInvoiceMessage) {
        deleteInvoiceMessage.textContent = "Invoice deleted successfully!";
        deleteInvoiceMessage.classList.remove("text-danger");
        deleteInvoiceMessage.classList.add("text-success");
      }

      setTimeout(() => location.reload(), 2500);
    } catch (error) {
      console.error("Error deleting invoice:", error);
      if (deleteInvoiceMessage) {
        deleteInvoiceMessage.textContent = "Error deleting invoice. Please try again.";
        deleteInvoiceMessage.classList.remove("text-success");
        deleteInvoiceMessage.classList.add("text-danger");
      }
    }
  }
}
