import styled from "styled-components";
import {Tabs} from 'antd';

export const StyledTabs = styled(Tabs)`
  background: #f3f3f3;
  .option-tabs-nav {
    margin: 0 !important;
  }
  @media (max-width: 860px) {
    .option-tabs-nav-wrap {
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
  .option-tabs-nav-list {
    .option-tabs-tab {
      justify-content: center;
      background-color: #fff;
    }
    .option-tabs-tab-active {
      background: #f3f3f3;
      border-bottom-color: #f3f3f3 !important;
    }
  }
  @media (min-width: 768px) {
    .option-tabs-nav-list {
      width: 100%;
      .option-tabs-tab {
        width: 100%;
      }
    }
  }
  @media (max-width: 480px) {
    .option-tabs-nav-list {
      .option-tabs-tab {
        padding: 5px 10px;
        font-size: 13px;
      }
    }
  }
`;