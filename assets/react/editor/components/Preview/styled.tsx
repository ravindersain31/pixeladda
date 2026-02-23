import styled from "styled-components";

export const PreviewWrapper = styled.div`
    position: sticky;
    top: 0;

    &.mobile-device {
        position: relative;
        margin-bottom: 10px;
    }
`;

export const PreviewContent = styled.div<{ $show?: boolean }>`
  display: ${({ $show }) => ($show ? 'block' : 'none')};
  text-align: center;
  padding: 5px 15px 15px 15px;
  @media screen and (max-width: 760px) {
    padding: 0;
  }
`;

export const CustomPreview = styled.div`
  padding: 15px;

  object {
    border: 1px solid #ddd;
    width: 100%;
    height: 70vh;
    display: block;
    
    p {
      margin: 0!important;
      padding: 10px 0;
    }

    @media (max-width: 760px) {
      height: auto;
    }
  }

  picture {
    padding: 10px;
    border: 1px solid #ddd;
    height: 100%;
    display: block;
  }
`;

export const SwiperPreview = styled.div`
    padding: 0 15px;

    .mySwiper.preview-swiper-thumbnails {
        .swiper-wrapper {
            .swiper-slide-thumb-active {
                border: 1px solid var(--primary-color) !important;
            }
        }
    }
`;

export const CanvasWrapper = styled.div`
  border: 2px dotted #a5a5a5;
  width: fit-content;
  padding: 2px;
  margin: auto;
  position: relative;
`;

export const CanvasLoader = styled.div`
  position: absolute;
  z-index: 999;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;

  &::before {
    background: rgb(0 0 0 / 32%);
    content: "";
    display: block;
    top: 0;
    left: 0;
    position: absolute;
    width: 100%;
    height: 100%;
  }
`;