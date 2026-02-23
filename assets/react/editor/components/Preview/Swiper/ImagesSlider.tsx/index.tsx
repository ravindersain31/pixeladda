import React, { useCallback, useState } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Mousewheel, FreeMode, Navigation, Autoplay } from "swiper/modules";
import { StyledImage, StyledSwiper } from "../Slider/styled";
import { isMobile } from "react-device-detect";
import LightGallery from "lightgallery/react";
import "lightgallery/css/lightgallery.css";
import lgThumbnail from "lightgallery/plugins/thumbnail";
import lgZoom from "lightgallery/plugins/zoom";

const ImagesSlider = ({ images }: { images: string[] }) => {

    const handleOpen = useCallback(() => {
        document.body.style.overflow = "hidden";
    }, []);

    const handleClose = useCallback(() => {
        document.body.style.overflow = "";
    }, []);

    return (
        <>
            {images.length > 0 && (
                <LightGallery
                    plugins={[lgThumbnail, lgZoom]}
                    speed={500}
                    mode="lg-fade"
                    licenseKey="FBF36E6B-7FFA-4D31-B357-92A22A095A7F"
                    elementClassNames="custom-lightgallery"
                    selector=".lightgallery-item"
                    closable
                    mobileSettings={{
                        closable: true,
                    }}
                    onAfterOpen={handleOpen}
                    onAfterClose={handleClose}
                >
                    <StyledSwiper
                        spaceBetween={10}
                        slidesPerView={isMobile ? 4 : 6}
                        freeMode
                        mousewheel
                        centeredSlides
                        centeredSlidesBounds
                        loop
                        watchSlidesProgress
                        modules={[Mousewheel, FreeMode, Navigation, Autoplay]}
                        allowSlideNext
                        allowSlidePrev
                        allowTouchMove
                        className="mySwiper preview-swiper-thumbnails"
                        style={{
                            width: "100%",
                            margin: isMobile ? "15px 0px 15px" : "0px 0px 15px 0px",
                        }}
                        autoplay={{
                            delay: 2500,
                            disableOnInteraction: false,
                        }}
                    >
                        {images.map((image: string, index: number) => (
                            <SwiperSlide
                                key={index}
                                style={{
                                    background: isMobile ? "#fff" : "#f9fafc",
                                    border: "1px solid #d9d9d9",
                                    borderRadius: "2px",
                                    cursor: "pointer",
                                }}
                            >
                                <a 
                                    tabIndex={-1}
                                    className="lightgallery-item"
                                    href={image}
                                    data-src={image}
                                >
                                    <StyledImage
                                        width={100}
                                        height={100}
                                        loading="lazy"
                                        src={image}
                                        alt={"image" + index}
                                        onError={(e: any) => (e.target.src = image)}
                                    />
                                </a>
                            </SwiperSlide>
                        ))}
                    </StyledSwiper>
                </LightGallery>
            )}
        </>
    );
};

export default ImagesSlider;
