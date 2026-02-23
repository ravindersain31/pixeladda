import React from 'react'
import FileUploader from './components/FileUploader'
import { ConfigProvider } from 'antd'

const AdminFileUpload = (props: any) => {
    return (
        <ConfigProvider
            theme={{
                token: {
                    borderRadius: 3,
                    colorPrimary: "#0061f2",
                    fontFamily: 'Montserrat, sans-serif',
                    fontWeightStrong: 500,
                    // colorBorderSecondary: '#d9d9d9'
                },
            }}
        >
            <FileUploader {...props} />
        </ConfigProvider>
    )
}

export default AdminFileUpload