import {StyledDrawer} from './styled.tsx'

const Index = ({children, ...props}: any) => {
    return <StyledDrawer {...props}>
        {children}
    </StyledDrawer>
}


export default Index;