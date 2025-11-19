document.addEventListener('DOMContentLoaded', function() {
    const carousels = document.querySelectorAll('.wp-events-carousel.swiper');
    
    carousels.forEach(function(carousel) {
        new Swiper(carousel, {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            pagination: {
                el: carousel.querySelector('.swiper-pagination'),
                clickable: true,
            },
            navigation: {
                nextEl: carousel.querySelector('.swiper-button-next'),
                prevEl: carousel.querySelector('.swiper-button-prev'),
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            }
        });
    });
});
