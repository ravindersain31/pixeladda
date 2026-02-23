import { Controller } from "@hotwired/stimulus";
import { calculateCanvasDimensions } from "@react/admin-preview/utils";
import { fitObjectsToCanvas } from "@react/admin/editor/canvas";
import { fabric } from "fabric";
import { preloadFonts } from "@react/editor/canvas/utils";
import { toJpeg } from "html-to-image";
import { isArray } from "lodash";

interface OrderItemSideCanvas {
    [side: string]: object | null;
}

interface OrderItem {
    id: number;
    canvasData: OrderItemSideCanvas;
    metaData: any;
    addOns: any;
}

export default class extends Controller {
    static values = {
        orderId: Number,
        proofUrl: String,
        items: Array,
        grommetTemplates: Object,
        wireStakeTemplates: Object
    };

    declare orderIdValue: number;
    declare proofUrlValue: string;
    declare itemsValue: OrderItem[];
    declare grommetTemplatesValue: { [color: string]: string };
    declare wireStakeTemplatesValue: { [type: string]: string };

    private working = false;
    private buttonEl: HTMLButtonElement | null = null;

    connect() {
        this.buttonEl = this.element as HTMLButtonElement;
        this.updateButtonUI();
    }

    private updateButtonUI(converted: boolean | null = null): void {
        const isConverted = converted ?? this.hasAnyConverted();

        if (!this.buttonEl) return;

        if (isConverted) {
            this.buttonEl.classList.remove("bg-warning");
            this.buttonEl.classList.add("bg-primary");

            this.buttonEl.innerHTML = `
                <i class="fa fa-check me-2"></i>
                Converted (Click again to re-generate)
            `;
        } else {
            this.buttonEl.classList.remove("bg-primary");
            this.buttonEl.classList.add("bg-warning");

            this.buttonEl.innerHTML = `
                <i class="fa fa-palette me-2"></i>
                Convert Canvas To Image (JPEG)
            `;
        }
    }

    private hasAnyConverted(): boolean {
        return this.itemsValue.some(item => {
            const design = item.metaData?.customerDesign ?? {};
            return Object.values(design).some((file: any) =>
                typeof file === "string" && (file.toLowerCase().endsWith(".png") || file.toLowerCase().endsWith(".jpg") || file.toLowerCase().endsWith(".jpeg"))
            );
        });
    }

    async click(): Promise<void> {
        if (this.working) return;
        this.working = true;

        this.showLoader();

        try {
            let uploadCount = 0;

            const totalUploads = this.itemsValue.reduce((acc, item) => {
                const realSides = Object.entries(item.canvasData ?? {}).filter(([, json]) => !!json);
                return acc + realSides.length;
            }, 0);

            if (totalUploads === 0) {
                this.updateProgress(1);
                this.hideLoader();
                this.working = false;
                return;
            }

            for (const item of this.itemsValue) {
                for (const [side, json] of Object.entries(item.canvasData ?? {})) {
                    if (!json || (isArray(json) && json.length <= 0)) {
                        console.warn(`⚠ Skipping missing canvas: item ${item.id} -> ${side}`);
                        continue;
                    }
                    const templateSize = item.metaData?.customSize?.templateSize ?? { width: 24, height: 18 };

                    try {
                        // 1. CLEAN DESIGN - Export canvas without overlays
                        const base64Clean = await this.canvasToBase64(json, templateSize);
                        const cleanImageUrl = await this.uploadCanvas(item.id, side, base64Clean, 'customerDesign');

                        // 2. PROOF DESIGN - Create HTML with overlays
                        const base64Proof = await this.createProofImageWithOverlays(base64Clean, item);
                        await this.uploadCanvas(item.id, side, base64Proof, 'customerDesignProof');
                    } catch (err) {
                        console.error(`❌ Upload failed for item ${item.id}, side ${side}`, err);
                    } finally {
                        uploadCount++;
                        const fraction = uploadCount / totalUploads;
                        this.updateProgress(fraction);
                    }
                }
            }

            this.updateProgress(1);
            await new Promise(r => setTimeout(r, 300));

            this.hideLoader();
            this.updateButtonUI(true);
            this.working = false;
        } catch (e) {
            console.error("❌ Conversion failed:", e);
            alert("Failed to save customer designs.");
            this.hideLoader();
            this.working = false;
        }
    }

    private redirect(): void {
        window.location.href = this.proofUrlValue;
    }

    /**
     * Create HTML element with clean image + overlays, then convert to JPEG
     */
    private async createProofImageWithOverlays(cleanImageBase64: string, item: OrderItem): Promise<string> {
        // Find the preview container in the modal
        const previewContainer = document.getElementById('image-preview-container');
        
        // Main container (overlay-container)
        const container = document.createElement('div');
        container.style.border = 'none';
        container.style.zIndex = '10';
        container.style.transform = 'scale(0.2)';
        
        if (previewContainer) {
            previewContainer.innerHTML = ''; // Clear previous
            previewContainer.appendChild(container);
        } else {
             // Fallback if modal not found (shouldn't happen in this flow)
             container.style.left = '-9999px';
             document.body.appendChild(container);
        }

        try {
            // 1. Sign Wrapper (Image + Grommets)
            const signWrapper = document.createElement('div');
            signWrapper.style.position = 'relative'; 
            signWrapper.style.display = 'inline-block'; 
            signWrapper.style.zIndex = '10'; 
            signWrapper.style.marginBottom = '-120px';
            container.appendChild(signWrapper);

            // Add main image to sign wrapper
            const img = document.createElement('img');
            img.src = cleanImageBase64;
            img.style.display = 'block';
            img.style.position = 'relative';
            img.style.zIndex = '5';
            img.crossOrigin = 'anonymous';
            signWrapper.appendChild(img);

            // Wait for image to load
            await new Promise((resolve, reject) => {
                if (img.complete && img.naturalWidth > 0) {
                    resolve(null);
                } else {
                    img.onload = () => resolve(null);
                    img.onerror = () => reject(new Error('Failed to load main image'));
                }
            });

            const addOns = item.addOns ?? {};
            const grommetType = addOns.grommets?.key ?? 'NONE';
            const grommetColor = (addOns.grommetColor?.key ?? '').toUpperCase();
            const frameType = (addOns.frame?.key ?? '').toUpperCase();

            const grommetImgUrl = this.grommetTemplatesValue[grommetColor];
            const wireStakeImgUrl = this.wireStakeTemplatesValue[frameType];

            // 2. Add grommets (inside sign wrapper)
            if (grommetImgUrl && grommetType !== 'NONE') {
                await this.addGrommetsToHtml(signWrapper, grommetImgUrl, grommetType);
            }

            // 3. Add Wire Stake (Sibling to sign wrapper)
            if (wireStakeImgUrl && frameType !== 'NONE') {
                // Wire Stake Wrapper
                const wireStakeWrapper = document.createElement('div');
                wireStakeWrapper.style.position = 'relative';
                wireStakeWrapper.style.display = 'flex';
                wireStakeWrapper.style.justifyContent = 'center';
                wireStakeWrapper.style.zIndex = '1';
                wireStakeWrapper.style.marginTop = '0';
                wireStakeWrapper.style.pointerEvents = 'none';

                const stake = document.createElement('img');
                stake.src = wireStakeImgUrl;
                if (!wireStakeImgUrl.startsWith('data:')) {
                    stake.crossOrigin = 'anonymous';
                }
                stake.style.width = '40%';
                stake.style.maxWidth = '150px';
                stake.style.height = 'auto';

                wireStakeWrapper.appendChild(stake);
                container.appendChild(wireStakeWrapper);

                await new Promise((resolve, reject) => {
                    if (stake.complete && stake.naturalWidth > 0) {
                        resolve(null);
                    } else {
                        stake.onload = () => resolve(null);
                        stake.onerror = () => reject(new Error('Failed to load wire stake'));
                    }
                });
            }

            // Wait a bit for layout to settle
            await new Promise(resolve => setTimeout(resolve, 50));

            // Convert HTML to JPEG
            const result = await this.convertHtmlToJpeg(container);

            return result;
        } finally {
            // Clean up
            if (container) {
                container.remove();
            }
        }
    }

    /**
     * Add grommet overlays to HTML container
     */
    private async addGrommetsToHtml(
        container: HTMLElement,
        grommetImgUrl: string,
        grommetType: string,
    ): Promise<void> {
        const offset = 10; // Distance from edge
        const promises: Promise<void>[] = [];

        const addGrommet = (left?: string, top?: string, right?: string, bottom?: string) => {
            const grommet = document.createElement('img');
            grommet.src = grommetImgUrl;
            // Don't set crossOrigin for data URLs
            if (!grommetImgUrl.startsWith('data:')) {
                grommet.crossOrigin = 'anonymous';
            }
            grommet.style.position = 'absolute';
            grommet.style.setProperty('width', '20px', 'important');
            grommet.style.setProperty('height', '20px', 'important');
            if (left) grommet.style.left = left;
            if (top) grommet.style.top = top;
            if (right) grommet.style.right = right;
            if (bottom) grommet.style.bottom = bottom;
            grommet.style.zIndex = '10';

            container.appendChild(grommet);
            
            // Track loading
            if (grommetImgUrl.startsWith('data:')) {
                // Data URLs are instant, but consistency helps
                promises.push(Promise.resolve());
            } else {
                promises.push(new Promise<void>(resolve => {
                    if (grommet.complete) resolve();
                    else {
                        grommet.onload = () => resolve();
                        grommet.onerror = () => resolve(); // Proceed even if fails
                    }
                }));
            }
        };

        // TOP_CENTER
        if (['TOP_CENTER', 'SIX_CORNERS'].includes(grommetType)) {
            addGrommet('50%', `${offset}px`);
            // Adjust for centering
            const lastGrommet = container.lastChild as HTMLElement;
            lastGrommet.style.transform = 'translateX(-50%)';
        }

        // TOP_CORNERS
        if (['TOP_CORNERS', 'ALL_FOUR_CORNERS', 'SIX_CORNERS'].includes(grommetType)) {
            addGrommet(`${offset}px`, `${offset}px`);
            addGrommet(undefined, `${offset}px`, `${offset}px`);
        }

        // BOTTOM_CORNERS
        if (['ALL_FOUR_CORNERS', 'SIX_CORNERS'].includes(grommetType)) {
            addGrommet(`${offset}px`, undefined, undefined, `${offset}px`);
            addGrommet(undefined, undefined, `${offset}px`, `${offset}px`);
        }

        // BOTTOM_CENTER
        if (grommetType === 'SIX_CORNERS') {
            addGrommet('50%', undefined, undefined, `${offset}px`);
            // Adjust for centering
            const lastGrommet = container.lastChild as HTMLElement;
            lastGrommet.style.transform = 'translateX(-50%)';
        }
        
        // Wait for all images to load
        if (promises.length > 0) {
            await Promise.all(promises);
        }
    }

    /**
     * Convert HTML element to JPEG using html-to-image
     */
    private async convertHtmlToJpeg(element: HTMLElement): Promise<string> {
        try {
            const width = element.scrollWidth;
            const height = element.scrollHeight;

            const dataUrl = await toJpeg(element, {
                quality: 0.85,
                backgroundColor: '#e4e4e3',
                pixelRatio: 2,
                cacheBust: true,
                width: width,
                height: height,
                style: {
                    transform: 'none'
                }
            });

            return dataUrl;
        } catch (error) {
            console.error('HTML to JPEG conversion failed:', error);
            throw error;
        }
    }

    private canvasToBase64(json: any, templateSize: { width: number; height: number }): Promise<string> {
        return new Promise((resolve, reject) => {
            try {
                const container = document.createElement("div");
                container.style.position = "absolute";
                container.style.left = "-9999px";
                container.style.top = "-9999px";
                container.style.width = "1000px";
                container.style.height = "1000px";
                document.body.appendChild(container);

                const canvasEl = document.createElement("canvas");
                canvasEl.id = `preview-canvas-${Date.now()}`;
                container.appendChild(canvasEl);

                const canvas = new fabric.Canvas(canvasEl, {
                    width: 500,
                    height: 500,
                    perPixelTargetFind: true,
                    backgroundColor: "#ffffff"
                });

                // Use requestAnimationFrame to ensure DOM is ready
                requestAnimationFrame(async () => {
                    // Calculate dimensions from aspect ratio
                    this.autoResizeCanvas(canvas, templateSize, true);

                    if (json.objects) {
                        await preloadFonts(json.objects);
                    }

                    canvas.loadFromJSON(
                        json,
                        () => {
                            const objects = canvas.getObjects();

                            objects.forEach((obj: any) => {
                                obj.selection = false;
                                obj.selectable = false;
                            });

                            canvas.renderAll();
                            fitObjectsToCanvas(canvas);
                            canvas.requestRenderAll();
                            this.exportCanvasToBase64(canvas, container, resolve, reject);
                        },
                        (o: any) => {
                            o.selection = false;
                            o.selectable = false;

                            if (o.type === "text") {
                                o = {
                                    ...o,
                                    text: o.text.trim(),
                                };
                            }

                            return o;
                        }
                    );
                });
            } catch (err) {
                reject(err);
            }
        });
    }

    private getTextWidth(text: string, fontSize: string, fontFamily: string): number {
        const canvasEl = document.createElement('canvas');
        const context = canvasEl.getContext('2d');
        if (context) {
            context.font = fontSize + 'px ' + fontFamily;
            return context.measureText(text).width;
        }
        return 0;
    }

    private autoResizeCanvas(canvas: fabric.Canvas, templateSize: { width: number; height: number }, fitContents: boolean = false): void {
        const dimensions = calculateCanvasDimensions(canvas.getElement(), templateSize);
        canvas.setDimensions(dimensions);
        if (fitContents) {
            fitObjectsToCanvas(canvas);
        }
    }

    private exportCanvasToBase64(canvas: any, container: HTMLDivElement, resolve: any, reject: any): void {
        try {
            // MAXIMUM COMPRESSION FOR PDF COMPATIBILITY
            // Target: <500KB per image, ~600x600 max resolution
            const MAX_EXPORT_WIDTH = 600;
            const MAX_EXPORT_HEIGHT = 600;

            const currentWidth = canvas.getWidth();
            const currentHeight = canvas.getHeight();

            // Always scale down to max 600x600
            if (currentWidth > MAX_EXPORT_WIDTH || currentHeight > MAX_EXPORT_HEIGHT) {
                const scaleX = MAX_EXPORT_WIDTH / currentWidth;
                const scaleY = MAX_EXPORT_HEIGHT / currentHeight;
                const scale = Math.min(scaleX, scaleY);

                canvas.setZoom(scale);
                canvas.setWidth(currentWidth * scale);
                canvas.setHeight(currentHeight * scale);
            }

            // Very aggressive quality for small file size
            const quality = 0.5;
            const base64 = canvas.toDataURL({
                format: "jpeg",
                quality: quality,
            });

            document.body.removeChild(container);
            resolve(base64);
        } catch (e) {
            document.body.removeChild(container);
            reject(e);
        }
    }

    private async uploadCanvas(itemId: number, side: string, base64: string, type: string = 'customerDesign'): Promise<string> {
        const res = await fetch("/save-customer-canvas", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ itemId, side, base64, type })
        });

        if (!res.ok) {
            throw new Error(`Upload failed for ${itemId}/${side}: ${res.status}`);
        }

        const data = await res.json();
        return data.url || '';
    }

    /* Loader UI */
    private showLoader(): void {
        const modal = document.getElementById("image-progress-modal");
        const bar = document.getElementById("image-progress-bar");
        const percent = document.getElementById("image-progress-percent");

        if (modal) modal.style.display = "flex";
        if (bar) bar.style.width = "0%";
        if (percent) percent.textContent = "0%";
    }

    private updateProgress(fraction: number): void {
        const v = Math.floor(fraction * 100);
        const bar = document.getElementById("image-progress-bar");
        const percent = document.getElementById("image-progress-percent");

        if (bar) bar.style.width = `${v}%`;
        if (percent) percent.textContent = `${v}%`;
    }

    private hideLoader(): void {
        const modal = document.getElementById("image-progress-modal");
        if (modal) modal.style.display = "none";
    }
}
