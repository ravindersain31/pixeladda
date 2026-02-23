import styled from "styled-components";
import Select from "react-select";

export const StyledSelect = styled(Select)`
  width: 100%;
  margin: 0 10px;
  font-family: Montserrat, sans-serif;
  transition: all 0.2s;
  
  .rs-control {
    font-size: 16px;

    .rs-menu-list {
      z-index: 9999;
    }
    &.rs-control-focused {
      border-color: #8a6dab !important;
      box-shadow: 0 0 0 2px rgba(55, 13, 81, 0.19);
      border-inline-end-width: 1px;
      outline: 0;
    }
  }

  .rs-option {
    &.rs-control-focused {
      background: #8a6dab !important;
      color: #fff !important;
    }
  }
`;