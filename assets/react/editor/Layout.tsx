import React, {useEffect, useState} from 'react';
import {Col, Row} from 'antd';
import {MobileView, isBrowser} from 'react-device-detect';
import {useAppDispatch, useAppSelector} from "@react/editor/hook.ts";
import {VerticalPreviewButton} from "@react/editor/styled.tsx";
import Initializing from "@react/editor/components/Initializing";
import Preview from "@react/editor/components/Preview";
import Steps from "@react/editor/components/Steps";
import Customize from "@react/editor/components/Steps/CustomizeYourSigns/Customize";
import actions from "@react/editor/redux/actions";
import BSDrawer from "@react/editor/components/BSDrawer";
import ReviewedByStamp from "./components/ReviewedByStamp";
import CustomDesign from "@react/editor/components/Steps/ChooseYourDesign/CustomDesign";
import PriceChart from "@react/editor/components/PriceChart";
import AdditionalNote from "@react/editor/components/AdditionalNote";
import {isProductEditable} from "@react/editor/helper/template.ts";
import BulkOrderModal from './components/common/BulkOrderModal';
import useShowCanvas from './hooks/useShowCanvas';

const Editor = (props: any) => {

    const initialized = useAppSelector((state) => state.config.initialized);
    const config = useAppSelector((state) => state.config);

    const [drawerVisible, setDrawerVisible] = useState(true);
    const [isBulkOrderModalOpen, setIsBulkOrderModalOpen] = useState(false);
    const showCanvas = useShowCanvas(); 

    const dispatch = useAppDispatch();

    useEffect(() => {
        dispatch(actions.config.initialize(props));
        dispatch(actions.editor.initialize(props));
    }, []);

    useEffect(() => {
        window.addEventListener("beforeunload", handleBeforeUnload);
        return () => {
            window.removeEventListener("beforeunload", handleBeforeUnload);
        };
    });

    const handleBeforeUnload = () => {
        window.location.reload();
    };


    if (!initialized) return <Initializing/>;

    return (
        <div>
            <PriceChart setIsBulkOrderModalOpen={setIsBulkOrderModalOpen}/>
            <MobileView>
                <VerticalPreviewButton
                    tabIndex={-1}
                    id='openEditorPreview'
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#editorPreviewOffcanvas"
                    aria-controls="editorPreviewOffcanvas"
                >
                    Open Preview
                </VerticalPreviewButton>
                <BSDrawer id="editorPreviewOffcanvas" heading="Preview">
                    <Preview/>
                    {config.product.isCustom && <CustomDesign/>}
                    {config.product.productType.slug === 'yard-letters' &&
                        <AdditionalNote showNeedAssistance={false} showNoteMessage={false}/>
                    }
                    {isProductEditable(config) && showCanvas && <Customize/>}
                    <ReviewedByStamp/>
                </BSDrawer>
            </MobileView>
            <Row>
                {isBrowser && <Col span={10} style={{background: '#f9fafc'}}>
                    <Preview/>
                </Col>}
                <Col span={isBrowser ? 14 : 24}>
                    <Steps/>
                </Col>
            </Row>
            <BulkOrderModal open={isBulkOrderModalOpen} onClose={() => setIsBulkOrderModalOpen(false)} />
        </div>
    );
}

export default Editor;