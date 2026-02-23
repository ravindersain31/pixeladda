import styled from "styled-components";
import {Card} from 'antd';
import {StepCardProps} from "./props.tsx";

export const StyledCard = styled(Card)<StepCardProps, { color: string }>`
  border-radius: 0;
  border-top: 0;

  .ant-card-head {
    padding: 0;
    min-height: auto;

    .ant-card-head-wrapper {
      background: #d8d8d8;

      .ant-card-head-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        .step-number {
          padding: 10px 20px;
          display: inline-flex;
          text-transform: uppercase;
          background: ${({ color }) => color};
          color: #fff;
        }

        .step-title {
          padding: 10px 20px;
          display: initial;
        }
        .step-total {
          font-size: 12px;
          @media (max-width: 480px) {
            font-size: 9px;
          }
          padding: 0 5px;
          display: block;
          @media screen and (max-width: 767px) {
            font-size: 10px;
            text-align: end;
          }
          .separator {
              display: inline;
          }
          /* Mobile view */
          @media (max-width: 767px) {
              .separator {
                  display: none; /* Hide separator on mobile */
              }
              .price {
                  display: block; /* Ensure the price is displayed as a block */
              }
          }

          /* Desktop view */
          @media (min-width: 768px) {
              .price {
                  display: inline; /* Ensure the price is inline with quantity */
              }
          }
        }
      }
    }
  }

  .ant-card-body {
    padding: 5px;
    position: relative;
  }

  .ant-radio-group {
    display: flex;

    ${({ scrollable, scrollHeight }) =>
      scrollable &&
      `
        max-height: ${scrollHeight || 250}px;
        overflow-y: hidden;
        overflow-x: auto;
        flex-direction: column;
        &::-webkit-scrollbar {
          height: 5px;
        }

        &::-webkit-scrollbar {
            width: 10px;
            background: #f1f1f1;
        }

        &::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 5px;
        }

        &::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
      `}
  }

  @media (max-width: 480px) {
    border: 0;
    .ant-card-head {
      .ant-card-head-wrapper {
        .ant-card-head-title {
          font-size: 14px;

          .step-number {
            padding: 10px 15px;
          }

          .step-title {
            padding: 10px 15px;
          }
        }
      }
    }
  }

  @media (max-width: 375px) {
    .ant-card-head {
      .ant-card-head-wrapper {
        .ant-card-head-title {
          font-size: 12px;

          .step-number {
            padding: 7px 10px;
          }

          .step-title {
            padding: 7px 10px;
          }
        }
      }
    }
  }
`;