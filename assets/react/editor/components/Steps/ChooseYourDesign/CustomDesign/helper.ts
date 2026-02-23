import type { UploadFile as AntdUploadFile } from "antd/es/upload/interface";

export function processUploadSuccess(
    files: AntdUploadFile[],
    onUploadSuccess?: (file: AntdUploadFile) => void
) {

    if (!onUploadSuccess) return;

    const processedUids = new Set<string>();

    files.forEach((file) => {
        if (file.status === "done" && !processedUids.has(file.uid)) {
            onUploadSuccess(file);
            processedUids.add(file.uid);
        }
    });
}
