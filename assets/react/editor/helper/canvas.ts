import { CustomArtwork, ImprintColor, ItemProps, Sides } from "@react/editor/redux/reducer/editor/interface.ts";
import { CanvasDataProps } from "@react/editor/redux/reducer/canvas/interface.ts";

export const syncArtworkWithCanvasData = (item: ItemProps, side: 'front' | 'back', canvasData: any) => {
    const objects = canvasData?.objects || [];
    const customDesignObjects = objects.filter((obj: any) => obj.custom?.type === "custom-design");

    if (canvasData) {
        item.canvasData = {
            ...item.canvasData,
            [side]: canvasData
        };
    }

    if (!item.customArtwork) {
        item.customArtwork = {};
    }

    const customDesignKey = CustomArtwork.CUSTOM_DESIGN;
    if (!item.customArtwork[customDesignKey]) {
        item.customArtwork = {
            ...item.customArtwork,
            [customDesignKey]: { front: [], back: [] }
        };
    }

    item.customArtwork = {
        ...item.customArtwork,
        [customDesignKey]: {
            ...item.customArtwork[customDesignKey],
            [side]: [...customDesignObjects]
        }
    };

    if (!item.customOriginalArtwork) {
        item.customOriginalArtwork = { front: [], back: [] };
    }

    const metadataPool = new Map<string, any>();
    [...(item.customOriginalArtwork?.front || []), ...(item.customOriginalArtwork?.back || [])].forEach(art => {
        if (art.id) metadataPool.set(art.id, art);
    });

    const processedUids = new Set<string>();
    const updatedOriginalArtwork: any[] = [];

    customDesignObjects.forEach((obj: any) => {
        const uid = obj.custom?.id;
        if (uid && !processedUids.has(uid)) {
            processedUids.add(uid);
            const existingMetadata = metadataPool.get(uid);
            if (existingMetadata) {
                updatedOriginalArtwork.push(existingMetadata);
            } else {
                updatedOriginalArtwork.push({
                    id: uid,
                    url: obj.src,
                    originalFileUrl: obj.src
                });
            }
        }
    });

    item.customOriginalArtwork = {
        ...item.customOriginalArtwork,
        [side]: updatedOriginalArtwork
    };

    return item;
}

export const getArtworksFromCanvasData = (canvasData: any) => {
    const data = typeof canvasData === 'string' ? JSON.parse(canvasData) : canvasData;
    const objects = data?.objects || [];
    return objects.filter((o: any) => o.custom?.type === 'artwork');
}

export const consolidateAllArtworks = (frontData: any, backData: any, currentCanvasObjects: any[] = []) => {
    const frontArtworks = getArtworksFromCanvasData(frontData);
    const backArtworks = getArtworksFromCanvasData(backData);

    const allArtworks = [...frontArtworks, ...backArtworks, ...currentCanvasObjects.filter((o: any) => o.custom?.type === 'artwork')];

    return allArtworks.filter((value, index, self) =>
        index === self.findIndex((t) => t.custom?.id === value.custom?.id)
    );
}

export const copyFrontToBackWhenEmpty = (items: {
    [key: string]: ItemProps
}, sides: Sides) => {
    const updateItems: { [key: string]: ItemProps } = {};
    for (const [key, item] of Object.entries(items)) {
        if (sides === Sides.DOUBLE) {
            if (item.canvasData.back === null) {
                item.canvasData.back = item.canvasData.front;
                syncArtworkWithCanvasData(item, 'back', item.canvasData.back);
            } else {
                const data: any = item.canvasData.back
                if (data.objects && data.objects.length <= 0) {
                    item.canvasData.back = item.canvasData.front;
                    syncArtworkWithCanvasData(item, 'back', item.canvasData.back);
                }
            }
        }

        updateItems[key] = item;
    }
    return updateItems;
}

export const updateItemsDesignOption = (items: {
    [key: string]: ItemProps
}, isHelpWithArtwork: boolean, isEmailArtworkLater: boolean) => {
    const updateItems: { [key: string]: ItemProps } = {};
    for (const [key, item] of Object.entries(items)) {
        updateItems[key] = {
            ...item,
            isHelpWithArtwork,
            isEmailArtworkLater
        };
    }
    return updateItems;
}

export const mapCustomUploadedFileWhenOtherHasQtyZero = (items: {
    [key: string]: ItemProps
}, currentItem: ItemProps, canvasData: CanvasDataProps) => {
    const updateItems: { [key: string]: ItemProps } = {};
    let numberOfSizesHaveQuantity = 0;
    for (const [key, item] of Object.entries(items)) {
        if (item.quantity > 0) {
            numberOfSizesHaveQuantity++;
        }
        updateItems[key] = item;
    }

    if (numberOfSizesHaveQuantity === 1) {
        updateItems[currentItem.id].canvasData = canvasData;
    }
    return updateItems;
}

export const identifyImprintColor = (data: {
    front: any,
    back: any,
}) => {
    let imprintColor = ImprintColor.ONE;
    const allObjects: fabric.Object[] = [
        ...(data.front?.objects || []),
        ...(data.back?.objects || [])
    ];
    let colors: string[] = [];
    for (const object of allObjects) {
        if (object.type === "image") {
            imprintColor = ImprintColor.UNLIMITED;
        } else {
            if (object.fill && !colors.includes(object.fill as string)) {
                colors.push(parseColor(object.fill as string));
            }
            if (object.backgroundColor && !colors.includes(object.backgroundColor as string)) {
                colors.push(parseColor(object.backgroundColor as string));
            }
            if (object.stroke && !colors.includes(object.stroke as string)) {
                colors.push(parseColor(object.stroke as string));
            }
        }
    }

    if (imprintColor !== ImprintColor.UNLIMITED) {
        if (colors.length <= 1) {
            imprintColor = ImprintColor.ONE;
        }
        if (colors.length === 2) {
            imprintColor = ImprintColor.TWO;
        }
        if (colors.length === 3) {
            imprintColor = ImprintColor.THREE;
        }
        if (colors.length > 3) {
            imprintColor = ImprintColor.UNLIMITED;
        }
    }
    return imprintColor;
}

const parseColor = (color: string) => {
    if (!color) {
        return color;
    }

    if (color.startsWith('#')) {
        return color;
    }
    if (!color.startsWith('rgb')) {
        return color;
    }
    // remove rgba and rgb from color string and get only the rgb color values as r,g,b object
    const rgb = color.replace(/rgba?\(|\)/g, '').split(',');
    return rgbToHex(parseFloat(rgb[0]), parseFloat(rgb[1]), parseFloat(rgb[2]));
}

const componentToHex = (c: number) => {
    const hex = c.toString(16);
    return hex.length == 1 ? "0" + hex : hex;
}

const rgbToHex = (r: number, g: number, b: number) => {
    return "#" + componentToHex(r) + componentToHex(g) + componentToHex(b);
}

export const lightOrDark = (color: any) => {

    let r, g, b;

    // Check the format of the color, HEX or RGB?
    if (color.match(/^rgb/)) {

        // If HEX --> store the red, green, blue values in separate variables
        color = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/);

        r = color[1];
        g = color[2];
        b = color[3];
    } else {

        // If RGB --> Convert it to HEX: http://gist.github.com/983661
        color = +("0x" + color.slice(1).replace(color.length < 5 && /./g, '$&$&'));

        r = color >> 16;
        g = color >> 8 & 255;
        b = color & 255;
    }

    // HSP equation from http://alienryderflex.com/hsp.html
    const hsp = Math.sqrt(
        0.299 * (r * r) +
        0.587 * (g * g) +
        0.114 * (b * b)
    );

    // Using the HSP value, determine whether the color is light or dark
    if (hsp > 127.5) {
        return 'light';
    } else {
        return 'dark';
    }
}