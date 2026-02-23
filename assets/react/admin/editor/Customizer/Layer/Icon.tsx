import {
    FontSizeOutlined,
    FileImageOutlined,
    RadiusSettingOutlined,
    SmallDashOutlined
} from '@ant-design/icons';

const Icon = ({type}: any) => {
    switch (type) {
        case 'text':
            return <FontSizeOutlined/>
        case 'path':
        case 'rect':
            return <RadiusSettingOutlined/>
        case 'image':
            return <FileImageOutlined/>
        default:
            return <SmallDashOutlined/>
    }
}

export default Icon;