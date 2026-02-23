import { useState } from 'react';
import StyledButton from '../Button';
import { message } from 'antd';
import { DownloadOutlined } from '@ant-design/icons';

interface DownloadArtworkProps {
    matchedArtwork: any;
    previewIndex: number;
    orderId: string;
    side: string;
}

const DownloadArtwork = ({ matchedArtwork, previewIndex, orderId, side }: DownloadArtworkProps) => {
    const [loadingOriginal, setLoadingOriginal] = useState(false);

    const downloadImage = async (url: string) => {
        try {
            const response = await fetch(url, { mode: 'cors' });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.download = `order-${orderId}-${side}-${previewIndex}.png`;
            link.href = objectUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(objectUrl);
        } catch (error: any) {
            message.error('Failed to download image: ' + error?.message || '');
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = "noopener noreferrer"
            link.download = `order-${orderId}-${side}-${previewIndex}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    const handleDownload = async () => {
        setLoadingOriginal(true);

        try {
            await downloadImage(matchedArtwork.originalFileUrl);
        } catch (error: any) {
            message.error(error.response?.data?.error || error.message || 'Failed to download image');
        } finally {
            setLoadingOriginal(false);
        }
    };

    return (
        <StyledButton
            onClick={handleDownload}
            type="default"
            loading={loadingOriginal}
            style={{
                textTransform: 'capitalize',
                whiteSpace: 'normal',
                wordWrap: 'break-word',
                overflowWrap: 'break-word',
                height: 'auto'
            }}
        >
            Download Original Artwork {previewIndex}
        </StyledButton>
    );
};

export default DownloadArtwork;
