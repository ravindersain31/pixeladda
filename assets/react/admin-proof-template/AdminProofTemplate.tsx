import { ConfigProvider } from 'antd'
import ProofTemplateManagementPage from './components/ProofTemplateManagementPage'

const AdminProofTemplate = (props: any) => {
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
            <ProofTemplateManagementPage {...props} />
        </ConfigProvider>
    )
}

export default AdminProofTemplate