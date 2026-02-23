import styled from "styled-components";

export const LightCartWrapper = styled.div`
  position: relative;

  .shopping-cart {
    padding: 0 0 5px 0 !important;
  }

  .dropdown-menu {
    margin-top: 0;
  }

  &:hover, &:active {
    .dropdown-menu {
      display: block;
      opacity: 1;
      visibility: visible;
    }
  }
`;

export const DropDownMenu = styled.div`
    padding-bottom: 10px;
`;

export const CartItemWrapper = styled.div`
    span{
        margin-left: 3px;
    }
`;
export const LightCartFooter = styled.div`
    display: flex;
    justify-content: center;
    gap: 10px;
    padding: 0 10px;

    .btn-cart-two{
        font-size: 16px !important;
        padding: 3px;
    }
`;