import React, { KeyboardEvent } from "react";
import { ItemProps, YSP_LOGO_DISCOUNT } from "../redux/reducer/editor/interface";
import { isBiggerSize, hasYSPLogo } from "./template";
import actions from "../redux/actions";

export const updateItemsFreeFreight = (items: {
    [key: string]: ItemProps
}, isFreeFreight: boolean) => {
    const updateItems: { [key: string]: ItemProps } = {};
    for (const [key, item] of Object.entries(items)) {
        updateItems[key] = {
            ...item,
            isFreeFreight: isBiggerSize(item.name) && !item.isWireStake ? isFreeFreight : false
        };
    }
    return updateItems;
}

export const updateItemsYSPLogoDiscount = (items: { [key: string]: ItemProps }) => {
    const updateItems: { [key: string]: ItemProps } = {};

    for (const [key, item] of Object.entries(items)) {
        if (item.quantity <= 0) {
            updateItems[key] = item;
            continue;
        }

        const hasCanvasLogo =
            item.canvasData ? hasYSPLogo(item.canvasData) : false;

        const logoArtwork = item.customArtwork?.['YSP-LOGO'];
        const hasArtworkLogo =
            (logoArtwork?.front?.length ?? 0) > 0 ||
            (logoArtwork?.back?.length ?? 0) > 0;

        const hasLogo = hasCanvasLogo || hasArtworkLogo;

        const originalTotal = item.totalAmount;
        const discountAmount = hasLogo
            ? (originalTotal * YSP_LOGO_DISCOUNT) / 100
            : 0;

        updateItems[key] = {
            ...item,
            YSPLogoDiscount: {
                ...item.YSPLogoDiscount,
                hasLogo,
                discountAmount: Number(discountAmount.toFixed(2)),
            },
        };
    }

    return updateItems;
};

export const toggleFreeFreightBasedOnItems = (
    items: { [key: string]: ItemProps },
    isCurrentlyFreeFreight: boolean,
    dispatch: any
) => {
    const hasBiggerSizeItem = Object.values(items).some(
        item => item.quantity > 0 && isBiggerSize(item.name)
    );

    if (hasBiggerSizeItem !== isCurrentlyFreeFreight) {
        dispatch(actions.editor.updateFreeFreight(hasBiggerSizeItem));
    }
};

export const hasBiggerSizeItem = (items: { [key: string]: ItemProps }): boolean => {
    return Object.values(items).some(
        item =>
            item.quantity > 0 &&
            !item.isCustomSize &&
            isBiggerSize(item.name)
    );
};


export function getStoreInfo() {
    return (window as any).storeInfo || {};
}

export function isPromoStore(): boolean {
    return getStoreInfo().isPromoStore || false;
}

export function spinnerImage(): string {
    return isPromoStore()
        ? "https://static.yardsignplus.com/storage/promo-store/Promo-Spinner.svg"
        : "/app-images/spinner.svg";
}


export function getUserId(): number | null {
    return (window as any).userId ?? null;
}

export function getRoles(): string[] {
    return (window as any).roles ?? [];
}

export const getHost = (): string => window.location.hostname;

export const isPromoHost = (): boolean => getHost().includes("yardsignpromo.com");



export const formatPhone = (input: string): string => {
    let numbers = input.replace(/\D/g, "");
    numbers = numbers.slice(0, 10);

    if (numbers.length > 6) {
        return `(${numbers.slice(0, 3)})-${numbers.slice(3, 6)}-${numbers.slice(6)}`;
    } else if (numbers.length > 3) {
        return `(${numbers.slice(0, 3)})-${numbers.slice(3)}`;
    } else if (numbers.length > 0) {
        return `(${numbers}`;
    }

    return numbers;
};

export const getHandFanVariantShape = (name: string, label: string): string => {
  const shapeMap: Record<string, string> = {
    '12x12': 'Square',
    '24x24': 'Square',
    '8x8': 'Square',
    '18x12': 'Rectangle',
    '18x24': 'Rectangle',
    '24x18': 'Rectangle',
  };

  return shapeMap[name] ?? label ?? '';
};

export const getQueryParam = (key: string): string | null => {
    if (typeof window === "undefined") return null;

    const params = new URLSearchParams(window.location.search);
    return params.get(key);
};

export const handleNumericKeyDown =
  (onValueChangeSideEffect?: (value: string) => void) =>
  (e: KeyboardEvent<HTMLInputElement>) => {

    const input = e.target as HTMLInputElement;

    if (e.ctrlKey || e.metaKey) {
      return;
    }

    onValueChangeSideEffect?.(input.value);

    const allowedKeys = [
      "Backspace",
      "Delete",
      "ArrowLeft",
      "ArrowRight",
      "Tab",
      "Enter",
    ];

    const notAllowedKeys = [".", "e", "-"];

    if (notAllowedKeys.includes(e.key)) {
      e.preventDefault();
      return;
    }

    if (!allowedKeys.includes(e.key) && !/^\d$/.test(e.key)) {
      e.preventDefault();
    }
  };