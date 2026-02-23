import { Flute } from "../redux/interface";
import { fluteImages } from "./addonFluteImages";

export interface FluteOption {
    key: Flute;
    title: string;
    image: string;
    price: number;
    ribbonText: string[];
    ribbonColor: string[];
    helpText: JSX.Element | undefined;
}

/**
 * Ribbons & Colors
 */
export const FluteRibbons: Partial<Record<Flute, string[]>> = {
    [Flute.VERTICAL]: ["Best Seller", "Most Popular"],
};

export const FluteRibbonColors: Partial<Record<Flute, string[]>> = {
    [Flute.VERTICAL]: [ "#3398d9", "#66b94d"],
};

/**
 * Descriptions
 */
export const FluteDescriptions: Partial<Record<Flute, JSX.Element>> = {
    [Flute.VERTICAL]: (
        <p className="text-start mb-0">
            <b>Vertical Flutes:</b><br />
            The position of the corrugated holes run up and down, at the top and bottom of the sign.
            This is the most common flutes direction. Wire stakes can be inserted from below the sign, as the holes are aligned vertically.
        </p>
    ),
    [Flute.HORIZONTAL]: (
        <p className="text-start mb-0">
            <b>Horizontal Flutes:</b><br />
            The position of the corrugated holes run side to side, at the left and right side of the sign.
            This is the least common flutes direction. This is not recommended for most customers.
            Wire stakes cannot be inserted from below the sign because the holes are positioned horizontally.
        </p>
    ),
};

/**
 * Title Helper
 */
const getFluteTitle = (flute: Flute): string => {
    switch (flute) {
        case Flute.HORIZONTAL:
            return 'Horizontal Flutes';
        case Flute.VERTICAL:
            return 'Vertical Flutes';
        default:
            return "No Flutes";
    }
};

export const getFluteOptionByVariant = (
    product: any,
    currentItem: any,
    FlutePrices: Partial<Record<Flute, number>>,
    flute: Flute
): FluteOption => {
    const price = FlutePrices[flute] ?? 0;
    const ribbons = FluteRibbons[flute] ?? [];
    const ribbonColors = FluteRibbonColors[flute] ?? ["#1d4e9b"];
    const images = fluteImages(currentItem);

    return {
        key: flute,
        title: getFluteTitle(flute),
        image: images[flute]?.(product) ?? "",
        price,
        ribbonText: ribbons,
        ribbonColor: ribbonColors,
        helpText: FluteDescriptions[flute] ?? undefined,
    };
};

export const getFluteOptions = (
    product: any,
    currentItem: any,
    flutePrices: Partial<Record<Flute, number>>,
): FluteOption[] => {
    const Flutes: Flute[] = [
        Flute.VERTICAL,
        Flute.HORIZONTAL,
    ];

    return Flutes.map((flute) =>
        getFluteOptionByVariant(product, currentItem, flutePrices, flute)
    );
};

export const FluteTypes = [
    Flute.VERTICAL,
    Flute.HORIZONTAL,
];