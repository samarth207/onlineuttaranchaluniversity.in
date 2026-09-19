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


$('.allNotification, .allNotification__icon, .allNotification__list, .doon_link').remove();

// Keep contact actions consistent across legacy pages.
(function() {
    var phone = '9266530366';
    var whatsapp = '919266530366';
    document.querySelectorAll('a[href^="tel:"]').forEach(function(link) {
        link.href = 'tel:' + phone;
        link.textContent = link.textContent.replace(/\+?91?\s*18002124454|\+?91?\s*7617774454|\+?91?\s*7617774486|18002124454/g, phone);
    });
    document.querySelectorAll('a[href*="whatsapp.com"], a[href*="wa.me"]').forEach(function(link) {
        link.href = 'https://wa.me/' + whatsapp;
        link.textContent = link.textContent.replace(/\+?91?\s*7617774454|Whatsapp|WhatsApp/gi, phone);
    });
    document.querySelectorAll('a[href*="mba-executive.php"]').forEach(function(link) {
        link.href = 'executive-mba.html';
        link.removeAttribute('target');
        link.removeAttribute('rel');
    });
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    var node;
    while (node = walker.nextNode()) {
        node.nodeValue = node.nodeValue.replace(/18002124454|\+91\s*7617774454|\+91\s*7617774486/g, phone);
    }
})();



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

(function() {
    function createApplyPopup() {
        if (document.getElementById('uuApplyPopup')) return;

        var overlay = document.createElement('div');
        overlay.id = 'uuApplyPopup';
        overlay.className = 'uu-apply-popup-overlay';
        overlay.innerHTML = [
            '<div class="uu-apply-popup" role="dialog" aria-modal="true" aria-labelledby="uuApplyPopupTitle">',
            '  <button type="button" class="uu-apply-close" aria-label="Close">&times;</button>',
            '  <div class="uu-apply-header">',
            '    <span class="uu-apply-badge">Admissions Open</span>',
            '    <h3 id="uuApplyPopupTitle">Apply for your preferred program</h3>',
            '    <p>Share your details and our academic advisor will contact you shortly.</p>',
            '  </div>',
            '  <form class="uu-apply-form" novalidate>',
            '    <div class="uu-form-grid">',
            '      <label>',
            '        <span>Full Name</span>',
            '        <input type="text" name="name" placeholder="Your name" required>',
            '      </label>',
            '      <label>',
            '        <span>Phone Number</span>',
            '        <input type="tel" name="phone" placeholder="10-digit mobile number" required>',
            '      </label>',
            '      <label>',
            '        <span>Email Address</span>',
            '        <input type="email" name="email" placeholder="you@example.com" required>',
            '      </label>',
            '      <label>',
            '        <span>Interested Program</span>',
            '        <select name="program" required>',
            '          <option value="">Select program</option>',
            '          <option value="MBA">MBA</option>',
            '          <option value="MCA">MCA</option>',
            '          <option value="BBA">BBA</option>',
            '          <option value="BCA">BCA</option>',
            '          <option value="BA">BA</option>',
            '          <option value="Executive MBA">Executive MBA</option>',
            '        </select>',
            '      </label>',
            '    </div>',
            '    <button type="submit" class="uu-apply-submit">Submit Enquiry</button>',
            '  </form>',
            '</div>'
        ].join('');

        document.body.appendChild(overlay);

        overlay.addEventListener('click', function(event) {
            if (event.target === overlay) {
                closeApplyPopup();
            }
        });

        overlay.querySelector('.uu-apply-close').addEventListener('click', closeApplyPopup);

        overlay.querySelector('.uu-apply-form').addEventListener('submit', function(event) {
            event.preventDefault();
            var form = event.currentTarget;
            var required = form.querySelectorAll('[required]');
            var valid = true;

            required.forEach(function(field) {
                if (!field.value.trim()) {
                    valid = false;
                    field.focus();
                    field.style.borderColor = '#d92929';
                } else {
                    field.style.borderColor = '#dfe7f3';
                }
            });

            if (!valid) return;

            form.innerHTML = [
                '<div class="uu-apply-success">',
                '  <span class="uu-apply-badge">Thank You</span>',
                '  <h4>Your enquiry has been received.</h4>',
                '  <p>Our admissions team will contact you soon.</p>',
                '</div>'
            ].join('');

            setTimeout(function() {
                window.location.href = 'apply.html';
            }, 1500);
        });
    }

    function openApplyPopup() {
        createApplyPopup();
        var overlay = document.getElementById('uuApplyPopup');
        if (!overlay) return;
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeApplyPopup() {
        var overlay = document.getElementById('uuApplyPopup');
        if (!overlay) return;
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(function() {
            overlay.remove();
        }, 200);
    }

    document.addEventListener('click', function(event) {
        var link = event.target.closest('a[href="apply.html"], a[href="/apply.html"], a[href="https://www.onlineuttaranchaluniversity.com/apply.html"]');
        if (!link) return;

        var text = (link.textContent || '').trim().toLowerCase();
        if (link.getAttribute('href') === 'apply.html' || link.getAttribute('href') === '/apply.html' || text.indexOf('apply now') !== -1 || text.indexOf('apply') !== -1) {
            event.preventDefault();
            openApplyPopup();
        }
    });
})();

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

// ---- Mobile Nav Toggle (course/site pages header) ----
(function() {
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('primaryNav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function() {
        var isOpen = nav.classList.toggle('is-open');
        toggle.classList.toggle('is-active', isOpen);
        toggle.setAttribute('aria-expanded', isOpen);
    });

    document.addEventListener('click', function(e) {
        if (window.matchMedia('(max-width: 768px)').matches && nav.classList.contains('is-open')) {
            if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('is-open');
                toggle.classList.remove('is-active');
                toggle.setAttribute('aria-expanded', 'false');
            }
        }
    });

    window.addEventListener('resize', function() {
        if (!window.matchMedia('(max-width: 768px)').matches) {
            nav.classList.remove('is-open');
            toggle.classList.remove('is-active');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
})();