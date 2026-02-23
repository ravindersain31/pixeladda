import { memo, useContext, useEffect, useState } from "react";
import StepCard from '@react/editor/components/Cards/StepCard';
import { stepConfigProps, StepProps } from "@react/editor/components/Steps/interface.ts";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import CustomDesign from "./CustomDesign";
import Templates from "./Templates";
import { StyledTabs, StyledCard, NewFlag } from './styled';
import actions from "@react/editor/redux/actions";
import { fetchTemplate, fetchTemplateJson, isProductEditable } from "@react/editor/helper/template.ts";
import CanvasContext from "@react/editor/context/canvas.ts";
import fabric from "@react/editor/canvas/fabric";
import axios from "axios";
import { calculateCartTotalFrameQuantity, recalculateOnUpdateQuantity } from "@react/editor/helper/quantity.ts";
import ConfigState, { templateSizeProps } from "@react/editor/redux/reducer/config/interface";
import { CanvasProperties } from "@react/editor/canvas/utils";
import { EditorItems, Shape } from "@react/editor/redux/reducer/editor/interface";
import { sortItemsByQuantity } from "@react/editor/helper/size-calc";
import { Frame } from "@react/editor/redux/interface";

const ChooseYourDesign = ({ option }: stepConfigProps) => {

    const storage = useAppSelector(state => state.storage);

    const config = useAppSelector(state => state.config);

    const canvas = useAppSelector(state => state.canvas);

    const editor = useAppSelector(state => state.editor);

    const [lastEditableSku, setLastEditableSku] = useState<string | null>(null);
    const [lastEditableVariant, setLastEditableVariant] = useState<string | null>(null);
    const [lastEditableSlug, setLastEditableSlug] = useState<string | null>(null);

    const [categoryKey, setCategoryKey] = useState<string>(`category-tab-${config.product.category.slug}`);

    const canvasContext = useContext(CanvasContext);

    const dispatch = useAppDispatch();

    const onCategoryChange = async (key: string) => {        
        setCategoryKey(key);
        setLastEditableVariant(`${canvas.customSize.templateSize.width}x${canvas.customSize.templateSize.height}`);
        if (key === 'category-tab-custom-signs') {
            if (isProductEditable(config)) {
                setLastEditableSku(config.product.sku);
                setLastEditableSlug(config.product.category.slug);
            }

            if (config.product.isDieCut) {
                await onProductChange('DC-CUSTOM');
            } else if (config.product.isBigHeadCutouts) {
                await onProductChange('BHC-CUSTOM');
            } else if (config.product.isHandFans) {
                await onProductChange('HF-CUSTOM');
            } else {
                await onProductChange('CUSTOM');
            }
        } else if (key === 'category-tab-custom-signs') {
            if (lastEditableSku) {
                await onProductChange(lastEditableSku);
            }
        }
    }

    const onProductChange = async (sku: string) => {
        const prevProduct = config.product;
        const { isYardLetters, isDieCut, isBigHeadCutouts } = config.product;
        if (!(isYardLetters || isDieCut || isBigHeadCutouts)) {
            dispatch(actions.editor.updateShape(Shape.SQUARE));
        }
        let canvasData = canvas.data;
        const isEditable = isProductEditable(config);
        if (canvasContext.canvas && isEditable) {
            canvasData = {
                ...canvasData,
                [canvas.view]: canvasContext.canvas.toJSON(CanvasProperties),
            };
        }
        dispatch(actions.storage.saveProduct(prevProduct, canvas.item, canvasData));
        dispatch(actions.canvas.updateCanvasLoader(true));
        const data = await fetchTemplate(sku);
        if (data && data.product) {
            const oldItems: EditorItems[] = [];
            for (const item of Object.values(editor.items)) {
                oldItems[item.id] = {
                    name: item.name,
                    quantity: item.quantity,
                    isCustomSize: item.isCustomSize,
                    closestVariantSize: item.customSize.closestVariant,
                    templateSize: item.customSize.templateSize,
                    itemId: item.itemId,
                    id: item.id,
                }
            }
            if (isProductEditable(data)) {
                for (const variant of data.product.variants) {
                    if (!variant.templateJson) {
                        variant.templateJson = await fetchTemplateJson(variant.template);
                    }
                }
                for (const variant of data.product.customVariant) {
                    if (!variant.templateJson) {
                        variant.templateJson = await fetchTemplateJson(variant.template);
                    }
                }
            }
            dispatch(actions.config.updateProduct(data.product));

            let clearItemsDispatched = false;
            for (const [key, itemOld] of Object.entries(sortItemsByQuantity(oldItems))) {
                let variant;
                if (itemOld.isCustomSize && data.product.productType.allowCustomSize) {
                    variant = {
                        ...config.product.customVariant[0],
                        quantity: itemOld.quantity,
                        name: itemOld.closestVariantSize,
                        templateSize: itemOld.templateSize,
                        itemId: itemOld.itemId,
                        id: itemOld.id,
                    };
                    dispatch(actions.canvas.updateVariant(variant));
                    dispatch(actions.canvas.updateCustomVariant(variant));
                } else {
                    variant = data.product.variants.find((v: any) => v.name === `${itemOld.name}`);
                    if (!variant) {
                        variant = data.product.variants[0];
                    }
                    dispatch(actions.canvas.updateVariant(variant));
                }
                if (!clearItemsDispatched) {
                    dispatch(actions.editor.clearItems());
                    clearItemsDispatched = true;
                }
                const item = JSON.parse(JSON.stringify(variant));
                const quantity = itemOld.quantity || 0;
                const quantityData = recalculateOnUpdateQuantity(item, quantity);
                dispatch(actions.editor.updateQty(quantityData));
            }

            updateUrl(data);

            dispatch(actions.canvas.updateCanvasLoader(false));
            selectCanvasText();
            if (!data.product.isYardLetters) {
                dispatch(actions.editor.updateFrame(Frame.NONE));
                const totalFrameQuantity = calculateCartTotalFrameQuantity() ?? 1;
                dispatch(actions.editor.updateFramePrice(totalFrameQuantity));
            }
            dispatch(actions.editor.updateYSPLogoDiscount());
        }
    }

    const updateUrl = (data: ConfigState) => {
        const currentOrigin = window.location.origin;
        const defaultUrl = `${currentOrigin}/${data.product.category.slug}/shop/${data.product.productType.slug}/${data.product.sku}`;

        if (data.product.isCustom) {
            if (lastEditableVariant) {
                let customType = "yard-sign";

                if (data.product.isBigHeadCutouts) {
                    customType = "big-head-cutouts";
                } else if (data.product.isDieCut) {
                    customType = "die-cut";
                } else if (data.product.isHandFans) {
                    customType = "hand-fans";
                }

                const newUrl = `${currentOrigin}/shop/custom-${lastEditableVariant}-${customType}`;
                history.replaceState(null, "", newUrl);
                dispatch(actions.canvas.updateCanvasLoader(false));
            }
        } else {
            history.replaceState(null, "", defaultUrl);
            dispatch(actions.canvas.updateCanvasLoader(false));
        }
    };

    useEffect(() => {
        const firstCategory = config.categories.find((_, index) => index === 0);
        const specialOptions = ['custom-signs', 'help-artwork', 'email-artwork'];
        let categoryTab = specialOptions.includes(option) ? 'custom-signs' : lastEditableSlug || (config.product.isCustom ? firstCategory?.slug : config.product.category.slug);

        if (config.product.isCustom && (lastEditableSku === 'CUSTOM-SIGN' || lastEditableSku === 'CUSTOM')) {
            categoryTab = firstCategory?.slug ?? "contractor";
        }
        
        const newKey = `category-tab-${categoryTab}`;

        if (categoryKey !== newKey) {
            (async () => {
                await onCategoryChange(newKey);
                selectCanvasText();
            })();
        }
    }, [option]);

    const selectCanvasText = () => {
        if (!canvas.loading && canvasContext.canvas instanceof fabric.Canvas) {
            const textObject = canvasContext.canvas.getObjects().find(obj => obj.type === 'text');
            if (textObject) {
                canvasContext.canvas.setActiveObject(textObject);
                canvasContext.canvas.requestRenderAll();
            }
        }
    }

    return <>
        <StyledTabs
            type="card"
            activeKey={categoryKey}
            onChange={onCategoryChange}
            items={[...config.categories.map((category: any) => {
                const isNewCategories: any = [];
                const isNew = isNewCategories.includes(category.slug);
                return {
                    label: (
                        <>
                            {category.name}
                            {isNew && <NewFlag>New</NewFlag>}
                        </>
                    ),
                    key: `category-tab-${category.slug}`,
                    disabled: canvas.loading,
                    children: <Templates
                        name={category.name}
                        slug={category.slug}
                        onProductChange={onProductChange}
                        categoryId={category.id}
                    />
                }
            })]}
        />
    </>
}

export default memo(ChooseYourDesign);