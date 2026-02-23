import styled from "styled-components";

const HeaderWrapper = styled.div<{ bg: string }>`
  padding: 10px;
  background: ${(props) => props.bg};
  color: #fff;
  font-size: 18px;
  text-align: center;

  h4 {
    margin: 0;
    color: #fff;
  }
`;

export {
    HeaderWrapper
};
