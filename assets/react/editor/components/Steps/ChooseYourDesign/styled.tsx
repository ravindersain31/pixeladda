import styled from "styled-components";
import {Card, Input, Tabs} from 'antd';
import { isPromoStore } from "@react/editor/helper/editor";

export const StyledTabs = styled(Tabs)`
  background: #f3f3f3;
  padding: 5px 5px 0px 5px;
  .ant-tabs-nav {
    margin: 0 !important;
  }
  @media (max-width: 860px) {
    .ant-tabs-nav-wrap {
      &::before {
        content: "<" !important;
        color: #000;
        text-align: center;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 23px;
        background: #fff;
        box-shadow: 5px 0 10px 10px rgba(0, 0, 0, 0.08) !important;
      }
      &::after {
        content: ">" !important;
        color: #000;
        text-align: center;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 23px;
        background: #fff;
        box-shadow: -10px 0 10px 5px rgba(0, 0, 0, 0.08) !important;
      }
    }
  }
  .ant-tabs-nav-list {
    .ant-tabs-tab {
      background-color: #fff;
    }
    .ant-tabs-tab-active {
      background: #f3f3f3;
      border-bottom-color: #f3f3f3 !important;
    }
  }
  @media (max-width: 480px) {
    .ant-tabs-nav-list {
      .ant-tabs-tab {
        padding: 5px 10px;
        font-size: 13px;
      }
    }
  }
`;

export const TemplateContainer = styled.div`
  padding: 5px;
  background: #f3f3f3;
  border-radius: 5px;
`;

export const CardWrapper = styled.div`
  height: 300px;
  overflow-y: scroll;
  overflow-x: hidden;
  margin-bottom: 5px;
  margin-top: 5px;
  position: relative;

  @media (max-width: 480px) {
    height: 250px;
  }
`;

export const CardHeader = styled.div`
    display: flex;
    justify-content: space-between;
    align-items: center;
`;

export const ProductSearch = styled(Input.Search)`
  width: 100%;
  padding: 5px;
  @media (max-width: 480px) {
    .ant-input {
      font-size: 13px;
    }

    .ant-btn {
      height: 35px;
    }
  }
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

export const SearchWrapper = styled.div`
  position: absolute;
  right: 5px;
  height: 40px;
  z-index: 100;
`;

export const IconWrapper = styled.div<{ $isOpen: boolean }>`
  position: absolute;
  top: 260px;
  right: 20px;
  transform: translateY(-50%);
  cursor: pointer;
  z-index: 10;
  color: #fff; 
  font-size: 20px;
  background-color: ${({ $isOpen }) => ($isOpen ? "var(--background-color_1)" : "var(--primary-color)")};
  box-shadow: ${({ $isOpen }) => $isOpen ? "0 4px 8px rgba(0, 0, 0, 0.2)" : "none"};
  width: 43px;
  height: 43px;
  border-radius: 50%;

  @media (max-width: 480px) {
    top: 230px;
    right: 5px; 
    width: 36px;
    height: 36px;
    font-size: 14px;
  }

  &&:hover{
    background: var(--background-color_1);
  }

  .anticon-search{
    position: absolute;
    top: 12px;
    left: 12px;
  }
`;

export const InputWrapper = styled.div<{ $isSearchOpen: boolean }>`
  position: absolute;
  top: 260px;
  right: 20px; 
  padding-right: 43px;
  transform: translateY(-50%);
  width: ${({ $isSearchOpen }) => ($isSearchOpen ? '187px' : '0')}; 
  overflow: hidden;
  transition: width 0.3s ease-in-out, padding 0.3s ease-in-out; 
  display: flex;
  align-items: center;
  background-color: #fff;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  border-radius: 25px;

  @media (max-width: 480px) {
    top: 230px;
    right: 5px; 
    padding-right: 36px;
  }

  &.template-input-wrapper {
    
    .ant-input-wrapper{
      display: block;
    }

    .ant-input-outlined {
      border-color: transparent !important;
      box-shadow: none !important;
      padding-right: 2px;

      input {
        font-size: 15px;
      }

      @media (max-width: 480px) {
          padding-top: 4px;
          padding-bottom: 4px;
      }

      .anticon-close-circle {
          color: #bf1212;
      }

      &:hover {
        border-color: transparent !important;
      }

      &::focus-within {
        border-color: transparent !important;
      }
    }

    .ant-input {
      border-color: transparent !important;
    }
  }
`;

export const StyledInput = styled(Input.Search)`
  height: 40px;
  
  @media (max-width: 480px) {
    height: 34px;
  }
  
  button {
    display: none; 
  }
`;

export const NoTemplates = styled.div`
  min-height: 100px;  
  text-align: center;
  @media (max-width: 480px) {
    min-height: 50px;
  }
`;

export const NoTemplateBadge = styled.div`
  background-color: #767575;
  padding: 10px;
  border-radius: 5px;
  color: #fff;
  font-size: 16px;
  @media (max-width: 480px) {
    font-size: 13px;
    padding: 5px;
  }
`;

export const NoTemplateText = styled.div`
  width: 82%;
  margin: 0 auto;
    
  h4 {
    line-height: 1.5;
    color: #212529;
    font-weight: 800;
    font-size: 32px;
    margin: 10px 0;
  }

  p {
    font-size: 19px;
    font-weight: 500;
    color: #43484e;
    margin-bottom: 0;
  }

  a {
    font-weight: 600;
    color: var(--primary-color);
  }

  button {
    font-size: 19px;
    font-weight: 600;
    color: var(--primary-color);
    padding: 0;
    height: auto;

    &:hover {
      color: var(--primary-color) !important;
      opacity: 0.8;
    }
  }

  @media (max-width: 480px) {
    h4 {
      font-size: 20px;
    }

    p {
      font-size: 12px;
    }

    a, button {
      font-size: 12px;
    }
  }
`;

export const StyledCard = styled(Card)`
  text-align: center;
  margin: 10px 0px 0px 0;
  border: 1px solid #d9d9d9;
  cursor: pointer;

  .ant-card-body {
    padding: 10px;
  }
`;

export const NewFlag = styled.span`
  margin-left: 8px;
  background-color: #00c477;
  color: white;
  border-radius: 4px;
  padding: 0 6px;
  font-size: 12px;
  line-height: 18px;
  user-select: none;
`;