import { useState } from 'react';
import { AddQrButton } from './styled';
import AddQrModal from './AddQrModal';

const AddQrCode = () => {
    const [qrModalVisible, setQrModalVisible] = useState<boolean>(false);

    return (
        <>
            <AddQrButton onClick={() => setQrModalVisible(true)}>
                Add QR Code
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fillRule="evenodd" clipRule="evenodd" d="M5 0H1.5H0V1.5V5H1.5V1.5H5V0ZM5 16.5H1.5V13H0V16.5V18H1.5H5V16.5ZM13 18V16.5H16.5V13H18V16.5V18H16.5H13ZM13 1.5V0H16.5H18V1.5V5H16.5V1.5H13Z" fill="#757575" />
                    <path fillRule="evenodd" clipRule="evenodd" d="M3.00049 3H8.33382V8.33333H3.00049V3ZM4.33382 4.33333V7H7.00049V4.33333H4.33382Z" fill="#757575" />
                    <path fillRule="evenodd" clipRule="evenodd" d="M3.00049 9.66667H8.33382V15H3.00049V9.66667ZM4.33382 11V13.6667H7.00049V11H4.33382Z" fill="#757575" />
                    <path fillRule="evenodd" clipRule="evenodd" d="M9.66716 15V9.66667H15.0005V15H9.66716ZM11.0005 11V13.6667H13.6672V11H11.0005Z" fill="#757575" />
                    <path d="M12.3338 4.33333H13.6672V5.66667H12.3338V4.33333Z" fill="#757575" />
                    <path d="M13.6672 5.66667H15.0005V8.33333H12.3338V7H13.6672V5.66667Z" fill="#757575" />
                    <path d="M13.6672 4.33333V3H15.0005V4.33333H13.6672Z" fill="#757575" />
                    <path d="M10.3338 3H11.6672V6.33333H10.3338V3Z" fill="#757575" />
                    <path d="M10.3338 7H11.6672V8.33333H10.3338V7Z" fill="#757575" />
                </svg>
            </AddQrButton>
            <AddQrModal visible={qrModalVisible} onClose={() => setQrModalVisible(false)} />
        </>
    )
}

export default AddQrCode