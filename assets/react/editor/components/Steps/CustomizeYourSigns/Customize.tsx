import React, { useState } from 'react';
import { isDesktop } from "react-device-detect";
import { Tabs } from "@react/editor/components/Tabs";
import NeedAssistancePopover from "@react/editor/components/NeedAssistance";
import TextEditor from "./TextEditor";
import BrowseArtwork from "./BrowseArtwork";
import UploadArtwork from "./UploadArtwork";
import { ExtraActions } from './styled.tsx';
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import { NeedAssistanceContainer, StyledDivider, TemplateList } from '../ChooseYourDesign/CustomDesign/styled.tsx';
import { DownloadOutlined } from "@ant-design/icons";
import actions from '@react/editor/redux/actions/index.ts';
import { AlertMessage } from '../ChooseDeliveryDate/styled.tsx';
import HelpWithArtwork from './HelpWithArtwork/index.tsx';
import EmailArtworkLater from './EmailArtworkLater/index.tsx';
import { UploadFile } from 'antd';

const Customize = () => {
    const editor = useAppSelector((state) => state.editor);
    const config = useAppSelector((state) => state.config);
    const [uploadFileList, setUploadFileList] = useState<UploadFile<any>[]>([]);
    const dispatch = useAppDispatch();

    const NeedAssistance = () => {
        return !isDesktop && config.product.isCustom ? (
            <NeedAssistanceContainer>
                <NeedAssistancePopover />
            </NeedAssistanceContainer>
        ) : null;
    };

    const VariantsTemplates = () => {
        return !isDesktop && config.product.isCustom ? (
            <>
                <StyledDivider orientation="center">Download Optional Templates</StyledDivider>
                <TemplateList>
                    {config.product.variants &&
                        config.product.variants.map((variant) => (
                            <a href={variant.template} target="_blank" key={`custom_mockup_template_${variant.name}`}>
                                <DownloadOutlined /> {variant.name}
                            </a>
                        ))}
                </TemplateList>
            </>
        ) : null;
    };

    const onCustomizeTabChange = (key: string) => {
        dispatch(
            actions.editor.updateDesignOption({
                isHelpWithArtwork: key === "help-artwork",
                isEmailArtworkLater: key === "email-artwork",
            })
        );
    };

    return <>
        {editor.totalQuantity <= 0 ? (
            <AlertMessage>
                Please add 1 or more quantity to customize your signs.
            </AlertMessage>
        ) : (
            <Tabs
                type="card"
                defaultActiveKey="add-text"
                onChange={onCustomizeTabChange}
                items={[
                    {
                        key: 'add-text',
                        label: 'Add Text',
                        children: <TextEditor />,
                    },
                    {
                        key: 'browse-artwork',
                        label: 'Browse Artwork',
                        children: <BrowseArtwork />
                    },
                    {
                        key: 'upload-artwork',
                        label: 'Upload Artwork',
                        children: (
                            <UploadArtwork
                                title="Upload Artwork"
                                side="front"
                                fileList={uploadFileList}
                                onChange={({ fileList }) => setUploadFileList(fileList)}
                                onRemove={(file) => {
                                    setUploadFileList((prev) => prev.filter((f) => f.uid !== file.uid));
                                }}
                            />
                        ),
                    },
                    {
                        label: "Help With Artwork",
                        key: `help-artwork`,
                        className: "help-artwork-tab",
                        children: <HelpWithArtwork />,
                    },
                    {
                        label: "Email Artwork Later",
                        key: `email-artwork`,
                        className: "email-artwork-tab",
                        children: <EmailArtworkLater />,
                    },
                ]}
                tabBarExtraContent={isDesktop ? <ExtraActions>
                    <NeedAssistancePopover />
                </ExtraActions> : null}
            />
        )}
    </>
}

export default Customize;