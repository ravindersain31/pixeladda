import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import Custom from "./Custom";
import Editable from "./Editable";
import { useEffect, useState } from "react";
import { fetchTemplateJson, isProductEditable } from "@react/editor/helper/template.ts";
import actions from "@react/editor/redux/actions";
import { isMobile } from "react-device-detect";
import { CanvasLoader, PreviewWrapper } from './styled';
import { Spin } from "antd";
import { LoadingOutlined } from "@ant-design/icons";
import Breadcrumb from "../common/Breadcrumb";
import { ShareDesignWrapper } from "../common/Breadcrumb/styled";
import ShareDesign from "../ShareDesign";
import useShowCanvas from "@react/editor/hooks/useShowCanvas";
import { enableStickyView } from "assets/web/js/sticky-view";

const Preview = () => {
    const canvas = useAppSelector(state => state.canvas);
    const config = useAppSelector(state => state.config);
    const showCanvas = useShowCanvas();

    const dispatch = useAppDispatch();

    useEffect(() => {
        if (isProductEditable(config)) {
            (async () => {
                await fetchTemplates();
            })();
        } else {
            dispatch(actions.canvas.updateCanvasLoader(false));
        }
    }, [config.product.isCustom]);

    const fetchTemplates = async () => {
        const variants = JSON.parse(JSON.stringify(config.product.variants));
        for (const variant of variants) {
            if (!variant.templateJson) {
                const ext = variant.template.split('.').pop();
                if (ext !== 'json') {
                    // console.log('Template file is not json for ', variant);
                }
                variant.templateJson = await fetchTemplateJson(variant.template);
            }
        }

        const cvariants = JSON.parse(JSON.stringify(config.product.customVariant));
        for (const variant of cvariants) {
            if (!variant.templateJson) {
                const ext = variant.template.split('.').pop();
                if (ext !== 'json') {
                    // console.log('Template file is not json for ', variant);
                }
                variant.templateJson = await fetchTemplateJson(variant.template);
            }
        }
        const newProduct = JSON.parse(JSON.stringify(config.product));
        newProduct.variants = variants;
        newProduct.customVariant = cvariants;

        dispatch(actions.config.updateProduct(newProduct));
        return newProduct;
    }

    const isCanvasReady = !canvas.loading && showCanvas && isProductEditable(config);

    useEffect(() => {
        if (!isCanvasReady) return;
        enableStickyView("header.sticky-top", ".editor-preview-wrapper");
    }, [isCanvasReady]);

    return <>
        {!isMobile && <Breadcrumb />}
        {showCanvas && isMobile && (
            <ShareDesignWrapper>
                <ShareDesign />
            </ShareDesignWrapper>
        )}
        <PreviewWrapper className={isMobile ? 'mobile-device' : 'editor-preview-wrapper'}>
            {canvas.loading && <CanvasLoader>
                <Spin indicator={<LoadingOutlined style={{ fontSize: 50 }} spin />} />
            </CanvasLoader>}
            {isProductEditable(config) ? <Editable /> : <Custom />}
        </PreviewWrapper>
    </>
}

export default Preview;