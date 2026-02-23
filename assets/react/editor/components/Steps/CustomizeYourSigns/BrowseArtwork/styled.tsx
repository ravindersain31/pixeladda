import styled from "styled-components";
import { Empty, Input } from "antd";
import Select from "@react/editor/components/Select";
import { isPromoStore } from "@react/editor/helper/editor";

export const ArtworkBrowser = styled.div`
  display: flex;
  justify-content: space-evenly;
  flex-wrap: wrap;
  position: relative;
  overflow: scroll;
  border: 1px solid #d9d9d9;
  border-radius: 5px;
  padding: 10px;
`;

export const ArtworkWrapper = styled.div`
  width: 80px;
  height: 80px;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 5px;
  border: 1px solid #cfcfcf;
  margin: 5px;
  border-radius: 5px;
  background: #fff;
  cursor: pointer;
  position: relative;
  z-index: 0;

  &:hover {
    .artwork-selector {
      display: flex;
    }

    &:before {
      content: "";
      position: absolute;
      background: rgb(0 0 0 / 44%);
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      border-radius: 5px;
    }
  }

  @media (max-width: 480px) {
    width: 70px;
    height: 70px;
  }
`;
export const Artwork = styled.img`
  height: auto;
  width: 100%;
  padding: 5px;
  max-height: 100%;
  max-width: max-content;
`;

export const ArtworkSelector = styled.span`
  position: absolute;
  z-index: 999;
  font-size: 12px;
  padding: 10px;
  text-align: center;
  height: 100%;
  width: 100%;
  color: #fff;
  justify-content: center;
  align-items: center;
  font-weight: 600;
  display: none;
`;

export const ArtworkSearchWrapper = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 10px;
  background: #ddd;
  border-radius: 5px;
  margin-bottom: 15px;

  @media (max-width: 480px) {
    .rs-control {
      font-size: 13px!important;
    }
  }
`;

export const ArtworkSearch = styled(Input.Search)`
  width: 100%;
  margin-left: 5px;
  @media (max-width: 480px) {
    .ant-input-affix-wrapper {
      padding: 5px 10px;

      input {
        font-size: 13px !important;
      }
    }

    .ant-btn {
      height: 34px;
    }
  }
`;

export const ArtworkCategory = styled(Select)`
  margin: 0;
`;

export const Loading = styled.div`
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  background: rgb(196 192 192 / 43%);
  z-index: 9;
  border-radius: 5px;
  display: flex;
  justify-content: center;
  align-items: center;
  color: ${() => (isPromoStore() ? "#25549b" : "#704e9f")};
  font-size: 21px;
  text-shadow: 1px 1px 1px #494949;
`;

export const NoteMessage = styled.div`
  width: 100%;
  text-align: center;
  font-size: 12px;
  border: dashed 1px #d8d8d8;
  background: #eff2f8;
  padding: 15px;
  color: rgba(0, 0, 0, 0.85);
  border-radius: 2px;
  margin: 0.5rem 0;
  @media (max-width: 480px) {
    padding: 10px;
  }
`;

export const EmptyNote = styled(Empty)`
  .ant-empty-image {
    height: 60px;
  }
`;