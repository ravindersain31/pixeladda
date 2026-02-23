import Swiper from 'swiper';
import {Navigation, Autoplay, Mousewheel} from 'swiper/modules';
import lightGallery from 'lightgallery';
import 'lightgallery/css/lightgallery.css';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import Viewer from "viewerjs";

const peopleAlsoPurchase = new Swiper('.people-also-purchased-swiper', {
    slidesPerView: 4,
    freeMode: true,
    loop: true,
    modules: [Navigation, Autoplay],
    autoplay: {
        delay: 1500,
        disableOnInteraction: true,
    },
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
    on: {
        reachEnd: function () {
            if (peopleAlsoPurchase.params.loop) {
                peopleAlsoPurchase.update();
            }
        },
        slideChange: function() {
            if (peopleAlsoPurchase.activeIndex >= peopleAlsoPurchase.slides.length - 2) {
                peopleAlsoPurchase.slideTo(0, 0);
            }
        }
    },
    breakpoints: {
        320: {
            slidesPerView: 1,
        },
        480: {
            slidesPerView: 1,
        },
        640: {
            slidesPerView: 2,
        },
        1080: {
            slidesPerView: 3,
        },
        1440: {
            slidesPerView: 4,
        }
    }
});
const customerPhotosSlider = document.getElementById('customer-photos-swiper-wrapper') as HTMLDivElement;
if (customerPhotosSlider) customerPhotosSlider.classList.remove("d-none");
const customerPhotosSwiperWrapper = document.getElementById('customer-photos-swiper-wrapper') as HTMLElement;
const customerPhotosSwiper = new Swiper('.customer-photos-swiper', {
    slidesPerView: 7,
    freeMode: true,
    loop: true,
    rewind: true,
    centeredSlides: true,
    spaceBetween: 10,
    modules: [Autoplay, Mousewheel],
    mousewheel: true,
    autoplay: {
        delay: 1500,
        disableOnInteraction: false,
    },
    on: {
        init: function () {
            const customerPhotosSwiperImages = document.querySelectorAll('.customer-photos-swiper img, .customer-photos-swiper video') as NodeListOf<HTMLImageElement>;

            customerPhotosSwiperImages.forEach((img) => {
                img.style.width = '100%';
            });

            const lg = lightGallery(customerPhotosSwiperWrapper, {
                licenseKey: 'FBF36E6B-7FFA-4D31-B357-92A22A095A7F',
                mobileSettings: {
                    controls: true,
                    showCloseIcon: true,
                    download: false,
                },
                selector: '.customer-photos-swiper a',
            });

            customerPhotosSwiperWrapper.addEventListener('lgBeforeOpen', () => {
                customerPhotosSwiper.autoplay.stop(); 
            });

            customerPhotosSwiperWrapper.addEventListener('lgBeforeClose', () => {
                customerPhotosSwiper.autoplay.start(); 
            });
        },
        slideChange: function() {
            if(customerPhotosSwiper){
                if (customerPhotosSwiper.activeIndex >= customerPhotosSwiper.slides.length - 2) {
                    customerPhotosSwiper.slideTo(0, 0);
                }
            }            
        },
        reachEnd: function () {
            if (customerPhotosSwiper.params.loop) {
                customerPhotosSwiper.update();
            }
        },
    },
    breakpoints: {
        320: {
            slidesPerView: 3,
        },
        480: {
            slidesPerView: 3,
        },
        640: {
            slidesPerView: 3,
        },
        991: {
            slidesPerView: 4,
        },
        1080: {
            slidesPerView: 5,
        },
        1440: {
            slidesPerView: 7,
        }
    }
});

const customerPhotos = document.getElementById('customer-photos-viewer') as HTMLDivElement;
if(customerPhotos){
    new Viewer(customerPhotos, {
        title: false,
        movable: false,
        navbar: true,
        toolbar: {
        zoomIn: 4,
        zoomOut: 4,
        oneToOne: 4,
        reset: 4,
        prev: 4,
        play: {
            show: 4,
            size: "large",
        },
        next: 4,
        rotateLeft: 4,
        rotateRight: 4,
        flipHorizontal: 4,
        flipVertical: 4,
        },
    });
}
