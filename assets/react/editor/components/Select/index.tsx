import {
    StyledSelect
} from './styled.tsx';

const Select = (props: any) => {
    return <StyledSelect
        classNames={{
            control: (state: any) => state.isFocused ? 'rs-control-focused rs-control' : 'rs-control',
            option: () => 'rs-option',
            valueContainer: () => 'rs-option-focused',
            menuList: () => 'rs-menu-list',
        }}
        {...props}
    />
}

export default Select;