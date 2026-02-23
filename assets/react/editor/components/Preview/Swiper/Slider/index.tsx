import React, {useState} from "react";
import {Swiper, SwiperSlide} from "swiper/react";
import {
    FreeMode,
    Navigation,
    Thumbs,
    Pagination,
    Autoplay,
    Mousewheel,
    Scrollbar,
} from "swiper/modules";
import 'swiper/css/scrollbar';
import {StyledImage, StyledImagePreview, StyledSwiper} from "./styled";
import {isMobile} from "react-device-detect";

interface YSPSliderProps {
    onSwiperInit?: (swiper: any) => void;
    images: any[];
}

const YSPSlider = ({images, onSwiperInit}: YSPSliderProps) => {

    const [thumbsSwiper, setThumbsSwiper] = useState(null);
    const [mainSwiper, setMainSwiper] = useState(null);
    const [activeIndex, setActiveIndex] = useState(0);

    const onSwiper = (swiper: any) => {
        setMainSwiper(swiper);
        onSwiperInit && onSwiperInit(swiper);
    };

    const onThumbSwiper = (swiper: any) => {
        setThumbsSwiper(swiper);
        setActiveIndex(swiper.activeIndex);
    };

    return (
        <>
            {images.length > 0 && (
                <>
                    <Swiper
                        style={{
                            // @ts-ignore
                            "--swiper-navigation-color": "var(--primary-color)",
                            "--swiper-pagination-color": "var(--primary-color)",
                            "--swiper-navigation-size": "25px",
                            height: isMobile ? "45vh" : "70vh",
                        }}
                        onSwiper={onSwiper}
                        spaceBetween={10}
                        slidesPerView={1}
                        navigation
                        mousewheel={true}
                        pagination={isMobile ? false : {clickable: true}}
                        initialSlide={activeIndex}
                        loop
                        autoplay={{
                            delay: 2500,
                            disableOnInteraction: false,
                        }}
                        scrollbar={isMobile ? {
                            draggable: true,
                        } : false}
                        thumbs={{swiper: thumbsSwiper}}
                        modules={[
                            Mousewheel,
                            Autoplay,
                            FreeMode,
                            Navigation,
                            Thumbs,
                            Scrollbar,
                            Pagination,
                        ]}
                        className="mySwiper2 custom-preview-swiper"
                    >
                        {images.map((image: any, index: number) =>
                            (() => {
                                return (
                                    <SwiperSlide
                                        key={index}
                                        style={{height: "90%", cursor: "pointer"}}
                                    >
                                        <StyledImagePreview
                                            loading="lazy"
                                            src={image}
                                            alt={"image" + index}
                                            onError={(e: any) =>
                                                (e.target.src = image)
                                            }
                                        />
                                    </SwiperSlide>
                                );
                            })()
                        )}
                    </Swiper>
                    <StyledSwiper
                        onSwiper={onThumbSwiper}
                        spaceBetween={10}
                        slidesPerView={isMobile ? 4 : 6}
                        freeMode={true}
                        mousewheel={true}
                        centeredSlides={true}
                        centeredSlidesBounds={true}
                        loop={true}
                        watchSlidesProgress
                        modules={[Mousewheel, Autoplay, FreeMode, Navigation, Thumbs]}
                        allowSlideNext
                        allowSlidePrev
                        allowTouchMove
                        className="mySwiper preview-swiper-thumbnails"
                        style={{
                            width: "100%",
                            margin: "15px 0",
                        }}
                    >
                        {images.map((image: any, index: number) =>
                            (() => {
                                return (
                                    <SwiperSlide
                                        key={index}
                                        style={{
                                            background: isMobile ? "#fff" : "#f9fafc",
                                            border: "1px solid #d9d9d9",
                                            borderRadius: "2px",
                                            cursor: "pointer",
                                        }}
                                    >
                                        <StyledImage
                                            width={100}
                                            height={100}
                                            loading="lazy"
                                            src={image}
                                            alt={"image" + index}
                                            onError={(e: any) =>
                                                (e.target.src = image)
                                            }
                                        />
                                    </SwiperSlide>
                                );
                            })()
                        )}
                    </StyledSwiper>
                </>
            )}
        </>
    );
};

export default YSPSlider;
