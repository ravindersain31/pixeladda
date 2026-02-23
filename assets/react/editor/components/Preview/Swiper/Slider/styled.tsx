import styled from "styled-components";
import { Swiper } from "swiper/react";

export const StyledImage = styled.img`
  width: 100%;
  object-fit: contain;
  cursor: pointer;
  @media (min-width: 768px) {
    max-height: 100px;
    min-height: 100px;
  }
  @media (max-width: 768px) {
    height: 100px;
  }
  @media (max-width: 480px) {
    height: 80px;
  }
  @media (max-width: 380px) {
    height: 70px;
  }
`;

export const StyledImagePreview = styled.img`
  object-fit: contain;
  width: 100%;
  height: 100%;
`;

export const StyledSwiper = styled(Swiper)`
  @media (min-width: 768px) {
    .swiper-slide {
      width: 100px !important;
    }
  }
`;
