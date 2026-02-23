import { EditorItems } from "../redux/reducer/editor/interface";

export const getClosestProportionalVariantFromDefaultSizes = (variantName: string): string => {
    const defaultSizes: string[] = [
        "6x18",
        "6x24",
        "9x12",
        "9x24",
        "12x12",
        "12x18",
        "18x12",
        "24x18",
        "18x24",
        "24x24",
        "20x30",
        "24x6",
        "24x30",
        "24x36",
        "24x48",
        "30x24",
        "36x18",
        "36x24",
        "48x24",
        "48x48",
        "48x72",
        "48x96",
        "96x48"
    ];
    const closestProportionalSize = getClosestProportionalVariant(variantName, defaultSizes);
    if (!closestProportionalSize) {
        let [width, height] = variantName.split('x').map(Number);
        if (width <= 9) {
            return '9x12';
        }
        return '24x18';
    }

    return closestProportionalSize;
}

export const getClosestProportionalVariant = (variantName: string, variants: string[]): null | string => {
    let [width, height] = variantName.split('x').map(Number);
    let targetAspectRatio = width / height;

    let closestProportionalSize = null;
    let closestProportionalDiff = Number.MAX_SAFE_INTEGER;
    let maxArea = 0;

    variants.forEach(v => {
        let [vw, vh] = v.split('x').map(Number);
        let aspectRatio = vw / vh;
        let area = vw * vh;

        // Calculate proportional difference
        let proportionalDiff = Math.abs(targetAspectRatio - aspectRatio);
        if (proportionalDiff < closestProportionalDiff || (proportionalDiff === closestProportionalDiff && area > maxArea)) {
            closestProportionalDiff = proportionalDiff;
            closestProportionalSize = v;
            maxArea = area;
        }
    });

    return closestProportionalSize;
}

export const getClosestVariantFromPricing = (variant: string | { width: number; height: number; }, pricing: any) => {
    if (typeof variant === 'object') {
        variant = `${variant.width}x${variant.height}`;
    }
    const variants = Object.keys(pricing.variants).map((item: string) => item.split('_')[1])
        .filter((item: string) => item !== undefined);
    return getClosestVariant(variant, variants);
}

export const getClosestVariant = (variant: string, variants: string[]) => {
    // Direct match
    if (variants.includes(variant) || variants.length <= 0) {
        return variant;
    }

    let [w, h] = variant.split('x').map(Number);
    let swappedVariantName = `${h}x${w}`;

    // Swapped match
    if (variants.includes(swappedVariantName)) {
        return swappedVariantName;
    }

    // Finding closest by area, ensuring the area is greater than or equal to the target
    let target = w * h;
    let closestSize = null;
    let closestDiff = Number.MAX_SAFE_INTEGER;

    variants.forEach(v => {
        let [vw, vh] = v.split('x');
        let sizeArea = parseInt(vw) * parseInt(vh);

        // Only consider sizes with an area greater than or equal to the target
        if (sizeArea >= target) {
            let diff = Math.abs(target - sizeArea);
            if (diff < closestDiff) {
                closestDiff = diff;
                closestSize = v;
            }
        }
    });
    if (!closestSize) {
        let [width, height] = variant.split('x').map(Number);
        if (width <= 9) {
            return '9x12';
        }
        return '24x18';
    }
    return closestSize;
}

export const sortItemsByQuantity = (items: EditorItems[]) => {
    return (items).sort((a, b) => a.quantity - b.quantity);
};
