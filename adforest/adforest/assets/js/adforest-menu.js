jQuery(document).ready(function ($) {
    if ($('.sb-header').length) {
        let sbMenuListDropdown = $('.sb-menu ul li:has(ul)');
        let headerShadow = $('.sb-header.header-shadow');

        let burgerMenu = $('.burger-menu');
        let sbMenuListWrapper = $('.sb-menu > ul');
        let sbMenuContainer = $('.sb-menu');
        const body = $('body');
        let mobileMenuOverlay = $('.sb-mobile-menu-overlay');

        if (!mobileMenuOverlay.length) {
            mobileMenuOverlay = $('<div class="sb-mobile-menu-overlay"></div>');
            body.append(mobileMenuOverlay);
        }

        const closeMobileMenu = () => {
            burgerMenu.removeClass('menu-open');
            sbMenuContainer.removeClass('is-open');
            body.removeClass('sb-mobile-menu-active');
            sbMenuListWrapper.css('display', '');
        };

        const openMobileMenu = () => {
            burgerMenu.addClass('menu-open');
            sbMenuContainer.addClass('is-open');
            body.addClass('sb-mobile-menu-active');
            sbMenuListWrapper.css('display', 'block');
        };

        /* ========== Dropdown Menu Toggle ========== */
        burgerMenu.on('click', function () {
            if (sbMenuContainer.hasClass('is-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });

        mobileMenuOverlay.on('click', function () {
            closeMobileMenu();
        });

        sbMenuListWrapper.find('a').on('click', function () {
            const link = $(this);
            const href = link.attr('href');

            if (!href || href === '#' || href === 'javascript:void(0)' || href === 'javascript:void(0);') {
                return;
            }

            closeMobileMenu();
        });

        $(window).on('resize', function () {
            if (window.innerWidth >= 1200) {
                closeMobileMenu();
            }
        });

        sbMenuListDropdown.each(function () {
            $(this).append('<span class="dropdown-plus"></span>');
            $(this).addClass('dropdown_menu');
        });

        $('.dropdown-plus').on("click", function () {
            $(this).prev('ul').slideToggle(300);
            $(this).toggleClass('dropdown-open');
        });

        $('.dropdown_menu a').append('<span></span>');

        /* ========== Added header shadow ========== */
        headerShadow.append('<div class="header-shadow-wrapper"></div>');

        /* ========== Sticky on scroll ========== */
        $(window).on("scroll", function () {
            // stickyNav();
        }).scroll();
    }

    if ($(".login-user").length > 0) {
        $(".login-user").click(function () {
            $(".dropdown-user-login").slideToggle("slow");
        });
    }

})
