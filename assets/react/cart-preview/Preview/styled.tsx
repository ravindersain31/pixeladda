import styled from "styled-components";

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
      background: var(--primary-color);
      border-radius: 5px;
      box-shadow: 1px 1px 1px #ddd;
      color: #FFF;
      margin-bottom: 15px;
    }
  }
`;