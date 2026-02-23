import React from 'react';
import { NumericFormat } from 'react-number-format';
import { StyledTable, SubAddonWrapper } from './styled';
import { AddonDisplayText } from '@react/editor/redux/reducer/config/interface';

interface SubAddonCollapseProps {
    addonName: any;
    addon: any;
    quantity: number;
}
type AddonName = keyof typeof AddonDisplayText;


const SubAddonCollapse = ({ addonName, addon, quantity }: SubAddonCollapseProps) => {
    
    return (
        <SubAddonWrapper key={`addon_${addon.key}`}>
            <td colSpan={2} className="bg-white text-muted small p-0">
                <StyledTable className="table table-sm">
                    <tbody>
                        {Object.values(addon).map((subAddon: any) => {
                            if (subAddon.amount <= 0) {
                                return null;
                            }
                            return (
                                <tr key={`subAddon_${subAddon.key}`}>
                                    <td className="bg-white text-muted small">{subAddon.displayText}</td>
                                    <td className="bg-white text-muted small text-end">
                                        <NumericFormat
                                            value={subAddon.unitAmount}
                                            prefix={"$"}
                                            displayType="text"
                                            decimalScale={2}
                                            fixedDecimalScale
                                        />
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </StyledTable>
            </td>
        </SubAddonWrapper>
    );
};

export default SubAddonCollapse;
