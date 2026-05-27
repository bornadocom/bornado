const swiper = new Swiper(".swiper-container", {
    slidesPerView: 'auto',
   spaceBetween: 30,
       loop:true,
        autoplay: {
          delay: 2500,
          disableOnInteraction: false,
        },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
      type: "fraction",
    }, navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
  });



