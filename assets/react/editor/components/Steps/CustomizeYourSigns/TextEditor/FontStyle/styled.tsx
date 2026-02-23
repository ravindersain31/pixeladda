import styled from "styled-components";
import {CheckboxButton} from "@react/editor/components/Button";

export const FontStyleContainer = styled.div`
  @media screen and (max-width: 768px) {
    button {
      margin: 0 5px 5px 5px;
    }
  }
  @media screen and (max-width: 370px) {
    button {
      margin: 0 2px;
    }
  }
`;

const BaseCheckboxButton = styled(CheckboxButton)`
`;

export const Bold = styled(BaseCheckboxButton)`
    
`;

export const Italic = styled(BaseCheckboxButton)`
    
`;

export const Underline = styled(BaseCheckboxButton)`
    
`;

export const Overline = styled(BaseCheckboxButton)`
    
`;

export const LineThrough = styled(BaseCheckboxButton)`
    
`;