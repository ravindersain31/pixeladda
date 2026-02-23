import styled from "styled-components";

export const SavingDesign = styled.div`
  .backdrop {
    background: rgb(0 0 0 / 80%);
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 99;
  }

  .text {
    z-index: 999;
    text-align: center;
  }

  position: absolute;
  z-index: 999;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  color: #FFF;
  font-size: 30px;
`;
