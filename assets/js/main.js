$('.alumni-section__slider').not('.slick-initialized').slick({
    dots: false,
    infinite: true,
    speed: 1200,
    arrows: false,
    autoplay: true,
    autoplaySpeed: 4000,
    slidesToShow: 3,
    slidesToScroll: 1,

    responsive: [{
            breakpoint: 1400,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
            }
        },
        {
            breakpoint: 992,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }
    ]
});



$('.testimoni-section__slider').not('.slick-initialized').slick({
    dots: true,
    infinite: true,
    speed: 1200,
    autoplay: true,
    autoplaySpeed: 4000,
    slidesToShow: 1,
    slidesToScroll: 1,
    nextArrow: '<div class="fa fa-arrow-circle-right slick-next"></div>',
    prevArrow: '<div class="fa fa-arrow-circle-left slick-prev"></div>',

    responsive: [{
        breakpoint: 768,
        settings: {
            slidesToShow: 1,
            slidesToScroll: 1
        }
    }]
});


$('.gallery-section__slider').not('.slick-initialized').slick({
    dots: false,
    infinite: true,
    speed: 1200,
    autoplay: true,
    autoplaySpeed: 4000,
    slidesToShow: 2,
    slidesToScroll: 1,
    arrows: false,
    nextArrow: '<div class="fa fa-arrow-circle-right slick-next"></div>',
    prevArrow: '<div class="fa fa-arrow-circle-left slick-prev"></div>',

    responsive: [{
        breakpoint: 768,
        settings: {
            slidesToShow: 1,
            slidesToScroll: 1
        }
    }]
});


$('.placementLogos-section__slider').not('.slick-initialized').slick({
    dots: false,
    arrows: false,
    infinite: true,
    speed: 300,
    autoplay: true,
    autoplaySpeed: 2000,
    slidesToShow: 1,
    slidesToScroll: 1,
    variableWidth: true,
    nextArrow: '<div class="fa fa-arrow-circle-right slick-next"></div>',
    prevArrow: '<div class="fa fa-arrow-circle-left slick-prev"></div>',
});

$('.testimoni-videos__slider').not('.slick-initialized').slick({
    dots: false,
    arrows: false,
    infinite: true,
    speed: 300,
    autoplay: true,
    autoplaySpeed: 2000,
    slidesToShow: 1,
    slidesToScroll: 1,
});


$('.collapse').on('shown.bs.collapse', function(e) {
    var $card = $(this).closest('.accordion-item');
    var $open = $($(this).data('parent')).find('.collapse.show');
    var additionalOffset = 180;
    if ($card.prevAll().filter($open.closest('.accordion-item')).length !== 0) {
        additionalOffset = $open.height();
    }
    $('html,body').animate({
        scrollTop: $card.offset().top - additionalOffset
    }, 500);
});





$('.breadcrumb li a').text(function() {
    return $(this).text().replace(/.php/g, '');
});


$('.allNotification__list').addClass(window.localStorage.toggled);
$('.allNotification__icon').on('click', function() {
    if (window.localStorage.toggled != "show") {
        $('.allNotification__list').addClass("show", true);
        window.localStorage.toggled = "show";
    } else {
        $('.allNotification__list').removeClass("show", false);
        window.localStorage.toggled = "";
    }
});



$(window).scroll(function() {
    if ($(this).scrollTop() > 1) {
        //$('.header').addClass("sticky");
        $('.top-notification-slider').addClass("stickyBottom").slick('slickGoTo', 0);
        $('.allNotification').addClass("moveUp");
    } else {
        //$('header').removeClass("sticky");
        $('.top-notification-slider').removeClass("stickyBottom");
        $('.allNotification').removeClass("moveUp");
    }
    if ($(window).scrollTop() >= 500) {
        $('.gotoTop').addClass('moveUp');
    } else {
        $('.gotoTop').removeClass('moveUp');
    }
});


$('.gotoTop__icon').click(function() {
    $("html, body").animate({ scrollTop: '0' }, 600);
});


var new_scroll_position = 0;
var last_scroll_position;
var header = document.getElementById("header");

window.addEventListener('scroll', function(e) {
    last_scroll_position = window.scrollY;

    if (new_scroll_position < last_scroll_position && last_scroll_position > 300) {
        header.classList.remove("slideDown");
        header.classList.add("slideUp");

    } else if (new_scroll_position > last_scroll_position) {
        header.classList.remove("slideUp");
        header.classList.add("slideDown");
    }

    new_scroll_position = last_scroll_position;
});


$(document).on("scroll", function() {
    var pixels = $(document).scrollTop();
    var pageHeight = $(document).height() - $(window).height();
    var progress = 100 * pixels / pageHeight;

    $("div.progress").css("width", progress + "%");
})