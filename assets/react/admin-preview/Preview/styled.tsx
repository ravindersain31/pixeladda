import styled from "styled-components";
import { Col, Row } from "antd";

export const PreviewContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  margin-top: 10px;

  .custom-product {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;

    a {
      padding: 5px 20px;
      background: #fff;
      border-radius: 5px;
      box-shadow: 1px 1px 1px #ddd;
      color: #4e4e4e;
      margin-bottom: 15px;
    }
  }
`;

export const StyledCol = styled(Col)`
  button {
    margin-bottom: 10px;
  }
`