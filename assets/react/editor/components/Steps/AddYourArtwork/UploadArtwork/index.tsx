import React, { useState } from "react";
import { DownloadOutlined, LoadingOutlined, PlusOutlined, UploadOutlined } from '@ant-design/icons';
import { StyledButton } from "../styled.tsx";
import { StyledUpload } from "./styled.tsx";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import { Sides } from "@react/editor/redux/reducer/editor/interface.ts";
import { UploadFile } from 'antd/es/upload/interface';
import { Spin } from "antd";
import { generateUniqueId } from "@react/editor/helper/template.ts";

const CustomDesign = () => {
    const dispatch = useAppDispatch();
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);
    const editor = useAppSelector(state => state.editor);

    const type = "UPLOAD-ARTWORK";

    const [fileList, setFileList] = useState<{
        [key: string]: UploadFile[],
    }>({
        front: [],
        back: []
    });

    const [uploadingFiles, setUploadingFiles] = useState<{
        [key: string]: UploadFile[],
    }>({
        front: [],
        back: []
    });

    const addFile = (url: string, side: string) => {
        // @ts-ignore
        const prevData: any = editor.items?.[canvas.item.id]?.customArtwork?.[type]?.[side] || [];
        const newData = [
            ...prevData,
            { id: generateUniqueId(), url: url }
        ];
        dispatch(actions.editor.updateCustomArtwork(newData, side, type));
    };

    const onRemove = (file: UploadFile, side: string) => {
        // @ts-ignore
        const prevData: any = editor.items?.[canvas.item.id]?.customArtwork?.[type]?.[side] || [];
        const filesAfterRemove = prevData.filter((item: any) => item.url !== file.response.url);
        dispatch(actions.editor.updateCustomArtwork(filesAfterRemove, side, type));
    };

    const modifyImageUrl = (url?: string): string => {
        if (url?.includes("https://static.yardsignplus.com/fit-in/1000x1000/")) {
            return url;
        }
        return url && url.endsWith(".gif")
            ? url
            : url?.replace(
                "https://static.yardsignplus.com/",
                "https://static.yardsignplus.com/fit-in/1000x1000/"
            ) ?? "";
    };

    const handleChange = (info: any, side: string) => {
        for (const file of uploadingFiles[side]) {
            const match = info.fileList.find(({ uid }: any) => uid === file.uid);
            if (match && match.status === 'done') {
                addFile(match.response.url, side);
            }
        }

        setUploadingFiles((prevState) => ({
            ...prevState,
            [side]: info.fileList.filter((file: any) => file.status === 'uploading')
        }));

        const finalFiles = info.fileList.map((file: any) => {
            return {
                ...file,
                thumbUrl: file.response?.url && file.response.url.endsWith(".gif") ? file.response.url : modifyImageUrl(file.response?.url) || file.thumbUrl,
                error: file.status === "error" ? new Error(file.response.message) : null,
            }
        });
        setFileList((prevState) => ({
            ...prevState,
            [side]: finalFiles
        }));
    };

    const beforeUpload = (file: UploadFile) => {
        const ext = file.name.split('.').pop() || '';
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'ai', 'eps', 'ppt', 'pptx', 'psd', "tiff", "tif", "heic", 'svg', 'csv', 'xlsx', 'xls'];
        if (!allowedExtensions.includes(ext)) {
            const message = 'Please upload a valid file type.  Accepted files are PNG, JPEG, JPG, EPS, CSV, EXCEL, Ai & PDF. Files must be less than 50 MB in size.';
            alert(message);
            file.status = 'error';
            file.response.message = message;
            return false;
        }
        return true;
    }

    const isUploading = uploadingFiles[canvas.view || "front"].length > 0;

    return (
        <>
            <StyledUpload
                action={config.links.upload_custom_design}
                fileList={fileList[canvas.view || 'front']}
                onChange={(info: any) => handleChange(info, canvas.view || 'front')}
                onRemove={(file: UploadFile) => onRemove(file, canvas.view || 'front')}
                beforeUpload={beforeUpload}
                showUploadList={{
                    showRemoveIcon: true,
                    showPreviewIcon: false,
                    showDownloadIcon: true,
                    downloadIcon: <PlusOutlined style={{ color: '#fff' }} title="" />,
                }}
            >
                <StyledButton type="primary">
                    {isUploading ? (<Spin  style={{ marginRight: "5px" }} indicator={<LoadingOutlined spin />} size="small" />) : (<UploadOutlined />)} Upload Artwork
                </StyledButton>
            </StyledUpload>
        </>
    );
};

export default CustomDesign;