import { Frame } from "../redux/interface";
import { frameImages } from "./addonFrameImages";

export interface StakeOption {
    key: Frame;
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
export const frameRibbons: Partial<Record<Frame, string[]>> = {
    [Frame.NONE]: ["FREE"],
    [Frame.WIRE_STAKE_10X24]: ["Best Seller", "Standard"],
    [Frame.WIRE_STAKE_10X24_PREMIUM]: [],
    [Frame.WIRE_STAKE_10X30_SINGLE]: [],
};

export const frameRibbonColors: Partial<Record<Frame, string[]>> = {
    [Frame.NONE]: ["#1B8A1B"],
    [Frame.WIRE_STAKE_10X24]: ["#1d4e9b", "#3398d9", "#66b94d"],
    [Frame.WIRE_STAKE_10X24_PREMIUM]: ["#1d4e9b"],
    [Frame.WIRE_STAKE_10X30_SINGLE]: ["#1d4e9b"],
};

/**
 * Descriptions
 */
export const frameDescriptions: Partial<Record<Frame, JSX.Element>> = {
    [Frame.WIRE_STAKE_10X24]: (
        <p className="text-start mb-0">
            <b>10"W x 24"H Wire Stake:</b><br />
            Increase exposure with our durable wire H-stakes.
            All signs include corrugated holes or flutes along the
            top and bottom edges, allowing for easy and instant
            installation of wire stakes. Simply insert the wire
            stake directly into the corrugated holes. Then place
            the wire stake in any soft ground (e.g. grass or dirt)
            for support. 3.4mm thick, 10 gauge (wire diameter),
            and 0.14kg weight. Recommended for all standard
            and custom sizes with a minimum of 10" width.
        </p>
    ),
    [Frame.WIRE_STAKE_10X24_PREMIUM]: (
        <p className="text-start mb-0">
            <b>Premium 10"W x 24"H Wire Stake:</b><br />
            Increase exposure with our premium, thicker,
            and greater durability wire U-stakes. All signs
            include corrugated holes along the top and
            bottom edges, allowing for easy and instant
            installation of wire stakes. Simply insert
            the wire stake directly into the corrugated
            holes. Then place the wire stake in any soft
            ground (e.g. grass or dirt) for support.
            3.4mm thickness near top, 5.0mm thickness at base,
            1/4” galvanized steel, 10 gauge (wire diameter)
            near top, 6 gauge near base, and 0.2kg weight.
            Recommended for all standard and custom
            sizes with a minimum of 10" width.
        </p>
    ),
    [Frame.WIRE_STAKE_10X30_SINGLE]: (
        <p className="text-start mb-0">
            <b>Single 30"H Wire Stake:</b><br />
            Increase exposure with our durable single
            wire stakes. All signs include corrugated
            holes along the top and bottom edges,
            allowing for easy and instant installation.
            Simply insert the single wire stake directly
            into the corrugated holes. Then place it
            in any soft ground (e.g. grass or dirt) for
            support. 3.4mm thick, 10 gauge (wire diameter).
            Recommended for all standard and custom sizes
            requiring only one single stake for support.
        </p>
    ),
};

/**
 * Title Helper
 */
const getFrameTitle = (frame: Frame): string => {
    switch (frame) {
        case Frame.WIRE_STAKE_10X24:
            return 'Standard 10"W x 24"H Wire Stake';
        case Frame.WIRE_STAKE_10X24_PREMIUM:
            return 'Premium 10"W x 24"H Wire Stake';
        case Frame.WIRE_STAKE_10X30_SINGLE:
            return 'Single 30"H Wire Stake';
        default:
            return "No Wire Stake";
    }
};

export const getStakeOptionByVariant = (
    product: any,
    currentItem: any,
    framePrices: Partial<Record<Frame, number>>,
    frame: Frame
): StakeOption => {
    const price = framePrices[frame] ?? 0;
    const ribbons = frameRibbons[frame] ?? [];
    const ribbonColors = frameRibbonColors[frame] ?? ["#1d4e9b"];
    const isFree = frame === Frame.NONE;
    const images = frameImages(currentItem);

    return {
        key: frame,
        title: getFrameTitle(frame),
        image: images[frame]?.(product) ?? "",
        price,
        ribbonText: isFree ? ribbons : [`$${price.toFixed(2)}`, ...ribbons],
        ribbonColor: ribbonColors,
        helpText: frameDescriptions[frame] ?? undefined,
    };
};

export const getStakeOptions = (
    product: any,
    currentItem: any,
    framePrices: Partial<Record<Frame, number>>,
    includeNone?: boolean
): StakeOption[] => {
    const frames: Frame[] = [
        ...(includeNone ? [Frame.NONE] : []),
        Frame.WIRE_STAKE_10X24,
        Frame.WIRE_STAKE_10X24_PREMIUM,
        Frame.WIRE_STAKE_10X30_SINGLE,
    ];

    return frames.map((frame) =>
        getStakeOptionByVariant(product, currentItem, framePrices, frame)
    );
};

export const FrameTypes = [
    Frame.NONE,
    Frame.WIRE_STAKE_10X30,
    Frame.WIRE_STAKE_10X24,
    Frame.WIRE_STAKE_10X30_PREMIUM,
    Frame.WIRE_STAKE_10X24_PREMIUM,
    Frame.WIRE_STAKE_10X30_SINGLE,
];