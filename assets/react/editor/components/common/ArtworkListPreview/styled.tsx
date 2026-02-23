import styled from "styled-components";

export const ArtworkList = styled.ul`
  overflow-y: auto;
  max-height: 200px;
  padding: 0;

   ::-webkit-scrollbar {
    width: 8px;
  }

  ::-webkit-scrollbar-thumb {
    background-color: #ccc;
    border-radius: 4px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background-color: #aaa;
  }

  ::-webkit-scrollbar-track {
    background-color: #f0f0f0;
    border-radius: 4px;
  }

  scrollbar-width: thin;
  scrollbar-color: #ccc #f0f0f0;
`;

export const ArtworkItem = styled.li`
  display: flex;
  position: relative;
  margin-top: 10px;
  width: 100%;
  border: 1px solid #ddd;
  padding: 5px;
`;

export const ArtworkImageContainer = styled.div`
  width: 90%;
  text-align: center;

  img {
    height: 40px;
  }
`;

export const DeleteButtonContainer = styled.div`
  width: 10%;
  position: relative;
`;