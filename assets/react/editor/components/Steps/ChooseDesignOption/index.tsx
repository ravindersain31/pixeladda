import {lazy, Suspense, useEffect, useState} from "react";
import {StepProps} from "@react/editor/components/Steps/interface.ts";
import {useAppDispatch, useAppSelector} from "@react/editor/hook";
import StepCard from "@react/editor/components/Cards/StepCard";
import CustomDesign from "../ChooseYourDesign/CustomDesign";
import {StyledTabs} from "./styled";
import EmailArtworkLater from "../ChooseYourDesign/EmailArtworkLater";
import HelpWithArtwork from "../ChooseYourDesign/HelpWithArtwork";
import actions from "@react/editor/redux/actions";
import { isProductEditable } from "@react/editor/helper/template.ts";
import { getQueryParam } from "@react/editor/helper/editor";
const ChooseYourDesign = lazy(() => import("@react/editor/components/Steps/ChooseYourDesign"));

const ChooseDesignOption = ({stepNumber}: StepProps) => {
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);

    const defaultDesignOption = isProductEditable(config) && !config.product.isCustom ? "browse-template" : config.product.isCustom ? "custom-signs" : "browse-template";
    const [designOption, setDesignOption] = useState<string>(defaultDesignOption);

    const dispatch = useAppDispatch();
    const [initializedProductSku, setInitializedProductSku] = useState<string | null>(null);

    useEffect(() => {
        const data = config.product;
        const currentOrigin = window.origin;
        const breadcrumbItems = ["Home", data?.category?.name as string, "Shop", data.sku];

        const subCategory = getQueryParam("subCategory");
        const subCategorySlug = subCategory ? `&sc=${encodeURIComponent(subCategory)}` : "";

        const breadcrumbLinks = [currentOrigin, `${currentOrigin}/${data?.category?.slug}`, `${currentOrigin}/shop?c=${data?.category?.slug}${subCategorySlug}`, '#'];
        updateBreadcrumb(breadcrumbItems, breadcrumbLinks);
        
        if (!data?.sku || initializedProductSku === data.sku) return;
        const initial =
            data.isCustom && (data.isBigHeadCutouts || data.isDieCut)
                ? "custom-signs"
                : isProductEditable(config) && !data.isCustom
                    ? "browse-template"
                    : data.isCustom
                        ? "custom-signs"
                        : "browse-template";

        setDesignOption(initial);
        setInitializedProductSku(data.sku);
    }, [config.product]);

    const onDesignOptionChange = (key: string) => {
        setDesignOption(key);
        dispatch(
            actions.editor.updateDesignOption({
                isHelpWithArtwork: key === "help-artwork",
                isEmailArtworkLater: key === "email-artwork",
            })
        );
    };

    const updateBreadcrumb = (breadcrumbItems: any[] | null = null, breadcrumbLinks: any[] | null = null) => {
        const categories = '/shop?c=' + config.categories.map(category => category.slug).join(',');
        const breadcrumbElements = document.querySelectorAll(".breadcrumb-item");
        if(breadcrumbElements.length  <= 0) return;
        const category = breadcrumbElements[1].querySelector("a");
        const shop = breadcrumbElements[2].querySelector("a");

        breadcrumbElements.forEach((element, index) => {
            const item = breadcrumbItems ? breadcrumbItems[index] : null;
            const link = breadcrumbLinks ? breadcrumbLinks[index] : null;
            const anchorElement = element.querySelector("a");
            if (anchorElement) {
                anchorElement.textContent = item || anchorElement.textContent;
                anchorElement.href = link || anchorElement.href;
            } else {
                element.textContent = item || element.textContent;
            }
            if (anchorElement?.textContent === 'Custom Mockup') {
                anchorElement.href = categories;
            }
        });
        if (category?.textContent === "Custom Mockup") {
            if (shop) shop.href = categories;
        }
    };

    return (
        <>
            <StepCard
                id="choose-design-option"
                title="Choose Your Design"
                stepNumber={stepNumber}
            >
                <StyledTabs
                    type="card"
                    activeKey={designOption}
                    defaultActiveKey={"browse-template"}
                    onChange={onDesignOptionChange}
                    className="design-option-tabs"
                    size="large"
                    tabBarGutter={2}
                    prefixCls="option-tabs"
                    items={[
                        {
                            label: <strong>Upload My Artwork</strong>,
                            key: `custom-signs`,
                            children: <CustomDesign/>,
                            disabled: canvas.loading,
                        },
                        {
                            label: "Help With Artwork",
                            key: `help-artwork`,
                            children: <EmailArtworkLater/>,
                            disabled: canvas.loading,
                        },
                        {
                            label: "Email Artwork Later",
                            key: `email-artwork`,
                            children: <HelpWithArtwork/>,
                            disabled: canvas.loading,
                        },
                        {
                            label: "Browse Templates",
                            key: `browse-template`,
                            children: <Suspense fallback={<div>Loading...</div>}><ChooseYourDesign option={designOption} /></Suspense>,
                            disabled: canvas.loading,
                        },
                    ]}
                />
            </StepCard>
        </>
    );
};

export default ChooseDesignOption;
