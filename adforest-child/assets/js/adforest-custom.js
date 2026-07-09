jQuery(document).ready(function ($) {
    const ajaxurl = get_strings.ajax_url;
    const sb_options = get_strings;
    const loading = $("#sb_loading");
    const uploadImagesNonce = $("#sb_upload_ad_images_nonce").val() || "";
    let uploadedImages = 0;
    let isImageRequired = sb_options.images_required_on_ad_post;
    var is_rtl_check = sb_options.is_rtl;
    is_rtl = false;

    // Global variable to store selected package data
    var selectedPackageData = null;
    let latestCategoryRequestSequence = 0;
    let activeCategoryFieldsRequest = null;
    let activeChildCategoriesRequest = null;
    let activeCategoryPackageRequest = null;

    function isPaidCategoryPackageFlow() {
        return typeof sb_options !== 'undefined'
            && sb_options.ad_posting_mode === 'paid_post'
            && !(sb_options.is_current_user_admin === 'admin' && sb_options.admin_allow_unlimited_ads === '1');
    }

    function isRequestAbortError(error) {
        return !!(error && (error.statusText === 'abort' || error.readyState === 0));
    }

    function isActiveCategoryRequest(requestSequence) {
        return typeof requestSequence !== 'number' || requestSequence === latestCategoryRequestSequence;
    }

    function abortPendingCategoryRequest(request) {
        if (request && typeof request.abort === 'function' && request.readyState !== 4) {
            request.abort();
        }
    }

    function beginCategoryRequestCycle() {
        latestCategoryRequestSequence += 1;
        abortPendingCategoryRequest(activeCategoryFieldsRequest);
        abortPendingCategoryRequest(activeChildCategoriesRequest);
        abortPendingCategoryRequest(activeCategoryPackageRequest);
        activeCategoryFieldsRequest = null;
        activeChildCategoriesRequest = null;
        activeCategoryPackageRequest = null;
        return latestCategoryRequestSequence;
    }

    function resetCategoryDependentFields() {
        $('#cat_temp_meta, #selected-category-name').val('');
        $('#cat_template_html, #ad_condition_and_warranty_box, #tags_and_video_link_box, #custom_field_container').empty();
        $('#ad_intent_type_container, #ad_intent_condition_warranty_container').empty();
        $('#ai-intent-fields-container').hide();
    }

    async function syncCategoryPackages(parentId, isUpdate, requestSequence) {
        let response;
        let request;

        if (!parentId || !isPaidCategoryPackageFlow()) {
            return null;
        }

        request = $.post(ajaxurl, {
            action: "sb_get_sub_cat",
            cat_id: parentId,
            is_update: isUpdate,
            security: $('#sb_get_sub_cat_nonce').val(),
        });

        if (typeof requestSequence === 'number') {
            activeCategoryPackageRequest = request;
        }

        try {
            response = await request;
            if (!isActiveCategoryRequest(requestSequence)) {
                return response;
            }

            if (response.success === false) {
                toastr.error(response?.data?.message, response?.data?.title, {
                    timeOut: 5000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });

                if (response?.data?.redirect) {
                    window.location.href = response.data.redirect;
                }

                return response;
            }

            let selected_packages = response?.data?.selected_packages || "";
            if (selected_packages !== "" && selected_packages !== null) {
                $(".package-label").show();
                $("#ad_post_packages_container").html(selected_packages);
            }

            return response;
        } catch (error) {
            if (isRequestAbortError(error)) {
                return null;
            }

            console.error('Error fetching selected packages:', error);
            throw error;
        } finally {
            if (typeof requestSequence === 'number' && activeCategoryPackageRequest === request) {
                activeCategoryPackageRequest = null;
            }
        }
    }

    // Function to check if Google Maps is ready
    function isGoogleMapsReady() {
        return typeof google !== 'undefined' && google.maps && google.maps.LatLng;
    }

    // Function to wait for Google Maps to be ready
    function waitForGoogleMaps(callback, maxAttempts = 50) {
        if (isGoogleMapsReady()) {
            callback();
            return;
        }

        if (maxAttempts <= 0) {
            console.warn('Google Maps API failed to load after maximum attempts');
            return;
        }

        setTimeout(() => {
            waitForGoogleMaps(callback, maxAttempts - 1);
        }, 100);
    }

    if (is_rtl_check === "1") {
        is_rtl = true;
    }

    $('.tax-show-more').on('click', function () {
        $(this).hide();
        var $showIt = $('.show_it');
        if ($showIt.length) {
            $showIt.removeClass('no-display');
        }
    });

    setTimeout(function () {
        $("body").addClass("loaded");
    }, 3000);

    $(document).on('click', '.notification-item-dash', function (event) {
        const isModifiedClick = event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.which === 2;
        if (isModifiedClick) {
            return;
        }

        const $item = $(this);
        const destination = $item.attr('href');
        if (!destination) {
            return;
        }

        const notificationType = $item.data('notification-type');
        const messageId = $item.data('message-id');
        const notificationId = $item.data('notification-id');
        // Pull the nonce localized for the dashboard hidden input.
        // On pages where it's absent the AJAX would 403, but the
        // 1.5s safety timeout below still guarantees navigation.
        const notifNonce = $('#mark_all_notifications_read_nonce').val() || '';
        const payload = {
            action: 'adforest_mark_notification_message_as_read',
            nonce: notifNonce
        };

        if (notificationType === 'theme' && notificationId) {
            payload.notification_type = 'theme';
            payload.notification_id = notificationId;
        } else if (messageId) {
            payload.message_id = messageId;
        } else {
            window.location.href = destination;
            return;
        }

        // Wait for the mark-as-read AJAX so the destination page renders
        // the freshly decremented count. Two redundant guards prevent
        // the original bug (clicks silently doing nothing) from coming
        // back: explicit 800ms jQuery timeout + 1000ms safety setTimeout.
        event.preventDefault();
        let navigated = false;
        const doNavigate = function () {
            if (navigated) return;
            navigated = true;
            window.location.assign(destination);
        };
        try {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: payload,
                timeout: 800
            }).always(doNavigate);
        } catch (e) {
            doNavigate();
        }
        setTimeout(doNavigate, 1000);
    });

    // brand carousel
    $('.brand-carousel').each(function () {
        var $slider = $(this);

        var sliderLoop = $slider.data('loop') === true;
        var sliderAutoplay = $slider.data('autoplay') === true;
        var sliderSpeed = $slider.data('autoplay-timeout') || 3000;
        var pause_on_hover = $slider.data('pause-on-hover') === true;

        $slider.owlCarousel({
            loop: sliderLoop,
            rtl: is_rtl,
            margin: 10,
            nav: false,
            dots: false,
            autoplay: sliderAutoplay,
            autoplayTimeout: sliderSpeed,
            autoplayHoverPause: pause_on_hover,
            responsive: {
                0: {
                    items: 1
                }, 576: {
                    items: 3
                }, 992: {
                    items: 5
                }, 1200: {
                    items: 6
                }
            }
        });
    });

    $('.owl-carousel-main-hero-4').each(function () {
        const slider = $(this);

        var sliderLoop = slider.data('loop') === true;
        var sliderAutoplay = slider.data('autoplay') === true;
        var sliderSpeed = slider.data('autoplay-timeout') || 3000;
        var pause_on_hover = slider.data('pause-on-hover') === true;


        slider.owlCarousel({
            loop: sliderLoop,
            rtl: is_rtl,
            margin: 10,
            nav: false,
            dots: false,
            autoplay: sliderAutoplay,
            autoplayTimeout: sliderSpeed,
            autoplayHoverPause: pause_on_hover,
            responsive: {
                0: {
                    items: 1
                },
                576: {
                    items: 1
                },
                992: {
                    items: 1
                },
                1200: {
                    items: 1
                }
            }
        });
    });

    $('.ajax-taxonomy-select').select2({
        placeholder: function () {
            console.log($(this).data('placeholder'));
            return $(this).data('placeholder') || (typeof adforestCustomI18n !== 'undefined' ? adforestCustomI18n.searchMinChars : "Type at least 3 letters to search...");
        },
        minimumInputLength: 3,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    action: 'search_taxonomy_terms',
                    taxonomy: $(this).data('taxonomy'),
                    q: params.term,
                    security: $('#adforest_search_taxonomy_terms_nonce').val(),
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            }
        }
    });

    if ($("#car-dealer-hero-form").length > 0) {
        var dataContainer = $('#taxonomy-data');
        var taxonomy = dataContainer.data('taxonomy');
        var ajaxUrl = dataContainer.data('ajax-url');
        var selectModelText = dataContainer.data('select-model-text');
        var selectVariantText = dataContainer.data('select-variant-text');

        $('#make').on('change.select2', function () {
            loading.show();
            var parent_id = $(this).val();
            if (parent_id) {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_child_terms',
                        parent: parent_id,
                        taxonomy: taxonomy,
                        security: $('#adforest_get_child_terms_nonce').val(),
                    },
                    success: function (response) {
                        loading.hide();
                        if (response.success) {
                            var options = '<option value="">' + selectModelText + '</option>';
                            $.each(response.data, function (index, term) {
                                options += '<option value="' + term.term_id + '">' + term.name + '</option>';
                            });
                            $('#model').html(options).prop('disabled', false);
                            $('#variant').html('<option value="">' + selectVariantText + '</option>').prop('disabled', true);
                        }
                    }
                });
            } else {
                $('#model').html('<option value="">' + selectModelText + '</option>').prop('disabled', true);
                $('#variant').html('<option value="">' + selectVariantText + '</option>').prop('disabled', true);
            }
        });

        // When a Model is selected, load its child terms into the Variant select
        $('#model').on('change.select2', function () {
            loading.show();
            var parent_id = $(this).val();
            if (parent_id) {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_child_terms',
                        parent: parent_id,
                        taxonomy: taxonomy,
                        security: $('#adforest_get_child_terms_nonce').val(),
                    },
                    success: function (response) {
                        loading.hide();
                        if (response.success) {
                            var options = '<option value="">' + selectVariantText + '</option>';
                            $.each(response.data, function (index, term) {
                                options += '<option value="' + term.term_id + '">' + term.name + '</option>';
                            });
                            $('#variant').html(options).prop('disabled', false);
                        }
                    }
                });
            } else {
                $('#variant').html('<option value="">' + selectVariantText + '</option>').prop('disabled', true);
            }
        });

        $('#car-dealer-hero-form').on('submit', function (e) {
            var selectedCatId = '';
            if ($('#variant').val()) {
                selectedCatId = $('#variant').val();
            } else if ($('#model').val()) {
                selectedCatId = $('#model').val();
            } else if ($('#make').val()) {
                selectedCatId = $('#make').val();
            }
            $('#cat_id_hidden').val(selectedCatId);
        });
    }

    $('.carousel-team-style-1').owlCarousel({
        loop: false,
        rtl: is_rtl,
        margin: 10,
        nav: false,
        dots: false,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: false,
        responsive: {
            0: {
                items: 1
            }, 576: {
                items: 3
            }, 992: {
                items: 4
            }, 1200: {
                items: 4
            }
        }
    });

    // Pets carousel
    $('.pet-category-carousel').owlCarousel({
        loop: false,
        rtl: is_rtl,
        margin: 30,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        autoplayTimeout: 5000,
        autoplayHoverPause: false,
        responsive: {
            0: {
                items: 2,
                margin: 10,
            }, 420: {
                items: 2,
                margin: 10,
            }, 576: {
                items: 3,
                margin: 10,
            }, 992: {
                items: 5,
                margin: 20,
            }, 1200: {
                items: 6,
                margin: 20,
            }, 1400: {
                items: 6
            }
        }
    });

    // Ads carousel
    $('.adt-ads-carousel').each(function () {
        var $carousel = $(this);
        var columns = parseInt($carousel.data('columns')) || 4;

        $carousel.owlCarousel({
            loop: false,
            rtl: is_rtl,
            margin: 30,
            nav: true,
            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
            dots: false,
            autoplayTimeout: 5000,
            autoplayHoverPause: false,
            responsive: {
                0: {
                    items: 2,
                    margin: 10,
                },
                576: {
                    items: Math.min(2, columns),
                    margin: 20,
                },
                768: {
                    items: Math.min(3, columns),
                    margin: 20,
                },
                992: {
                    items: Math.min(3, columns),
                    margin: 20,
                },
                1200: {
                    items: Math.min(3, columns),
                },
                1400: {
                    items: columns
                }
            }
        });
    });

    $('.adt-ads-carousel-widgets').each(function () {
        var $carousel = $(this);
        var columns = parseInt($carousel.data('columns')) || 4;
        var mobileColumns = parseInt($carousel.data('mobile-columns')) || 1;
        var sliderLoop = $carousel.data('loop') === true;
        var sliderAutoplay = $carousel.data('autoplay') === true;
        var sliderSpeed = $carousel.data('autoplay-timeout') || 3000;
        var pause_on_hover = $carousel.data('pause-on-hover') === true;

        $carousel.owlCarousel({
            loop: sliderLoop,
            rtl: is_rtl,
            margin: 36,
            nav: true,
            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
            dots: false,
            autoplay: sliderAutoplay,
            autoplayTimeout: sliderSpeed,
            autoplayHoverPause: pause_on_hover,
            responsive: {
                0: {
                    items: mobileColumns,
                    margin: 10,
                },
                500: {
                    items: mobileColumns,
                    margin: 10,
                },
                576: {
                    items: Math.min(2, columns),
                    margin: 20,
                },
                768: {
                    items: Math.min(3, columns),
                    margin: 20,
                },
                992: {
                    items: Math.min(3, columns)
                },
                1200: {
                    items: Math.min(4, columns)
                },
                1400: {
                    items: columns
                }
            }
        });
    });

    // Ads carousel
    $('.adt-ads-sub-carousel').each(function () {
        const $carousel = $(this);
        const columnCount = parseInt($carousel.data('columns')) || 4;
        const mobileColumns = parseInt($carousel.data('mobile-columns')) || 4;

        const sliderLoop = $carousel.data('loop') === true;
        const sliderAutoplay = $carousel.data('autoplay') === true;
        const sliderSpeed = $carousel.data('autoplay-timeout') || 3000;
        const pause_on_hover = $carousel.data('pause-on-hover') === true;

        $carousel.owlCarousel({
            loop: sliderLoop,
            rtl: is_rtl,
            margin: 20,
            nav: true,
            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
            dots: false,
            autoplay: sliderAutoplay,
            autoplayTimeout: sliderSpeed,
            autoplayHoverPause: pause_on_hover,
            responsive: {
                0: {
                    items: mobileColumns,
                    margin: 10
                },
                500: {
                    items: mobileColumns,
                    margin: 10
                },
                576: {
                    items: Math.min(columnCount, 2)
                },
                768: {
                    items: Math.min(columnCount, 3)
                },
                992: {
                    items: Math.min(columnCount, 4)
                },
                1200: {
                    items: Math.min(columnCount, 2)
                },
                1400: {
                    items: Math.min(columnCount, 3)
                }
            }
        });
    });

    // Category Mini carousel
    $('.category-mini-carousel').each(function () {
        var $slider = $(this);

        var sliderLoop = $slider.data('loop') === true;
        var sliderAutoplay = $slider.data('autoplay') === true;
        var sliderSpeed = $slider.data('autoplay-timeout') || 3000;
        var pause_on_hover = $slider.data('pause-on-hover') === true;


        $slider.owlCarousel({
            loop: sliderLoop,
            rtl: is_rtl,
            margin: 10,
            nav: false,
            dots: false,
            autoplay: sliderAutoplay,
            autoplayTimeout: sliderSpeed,
            autoplayHoverPause: pause_on_hover,
            responsive: {
                0: {
                    items: 2
                }, 576: {
                    items: 3
                }, 992: {
                    items: 5
                }, 1200: {
                    items: 7
                }, 1400: {
                    items: 9
                }
            }
        })
    });

    // Mini Ads carousel
    $('.adt-mini-ads-carousel').each(function () {
        const $carousel = $(this);
        const mobileColumns = parseInt($carousel.data('mobile-columns')) || 4;
        var sliderLoop = $carousel.data('loop') === true;
        var sliderAutoplay = $carousel.data('autoplay') === true;
        var sliderSpeed = $carousel.data('autoplay-timeout') || 3000;
        var pause_on_hover = $carousel.data('pause-on-hover') === true;

        $carousel.owlCarousel({
            loop: sliderLoop,
            rtl: is_rtl,
            margin: 20,
            nav: true,
            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
            dots: false,
            autoplay: sliderAutoplay,
            autoplayTimeout: sliderSpeed,
            autoplayHoverPause: pause_on_hover,
            responsive: {
                0: {
                    items: mobileColumns,
                    margin: 10,
                }, 576: {
                    items: mobileColumns
                }, 768: {
                    items: 3
                }, 992: {
                    items: 3
                }, 1200: {
                    items: 4
                }, 1400: {
                    items: 5
                }
            }
        });
    });

    // Vendor Mini Ads carousel
    $('.adt-vendor-mini-ads-carousel').owlCarousel({
        loop: false,
        rtl: is_rtl,
        margin: 20,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        autoplayTimeout: 5000,
        autoplayHoverPause: false,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 3
            },
            768: {
                items: 4
            },
            992: {
                items: 3
            },
            1200: {
                items: 3
            },
            1400: {
                items: 3
            }
        }
    });

    // Find categories Carousel
    $('.adt-find-by-categories-carousel').owlCarousel({
        loop: true,
        rtl: is_rtl,
        margin: 36,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: false,
        responsive: {
            0: {
                items: 2,
                margin: 10
            },
            420: {
                items: 2,
                margin: 10
            },
            576: {
                items: 3,
                margin: 20
            },
            768: {
                items: 4,
                margin: 20
            },
            992: {
                items: 5
            },
            1200: {
                items: 6
            }
        }
    });

    // Property image Carousel
    $('.adt-property-ads-carousel').owlCarousel({
        loop: false,
        rtl: is_rtl,
        margin: 36,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 2,
                margin: 20,
            },
            768: {
                items: 3,
                margin: 20,
            },
            992: {
                items: 3,
            },
            1200: {
                items: 4
            }
        }
    })

    $('.adt-property-ads-carousel-widgets').each(function () {
        var $carousel = $(this);
        var columns = parseInt($carousel.data('columns')) || 4;
        var mobileColumns = parseInt($carousel.data('mobile-columns')) || 1;
        var sliderLoop = $carousel.data('loop') === true;
        var sliderAutoplay = $carousel.data('autoplay') === true;
        var sliderSpeed = $carousel.data('autoplay-timeout') || 3000;
        var pause_on_hover = $carousel.data('pause-on-hover') === true;
        var itemsCount = $carousel.find('.item').length;
        var maxVisibleItems = Math.max(columns, mobileColumns);

        if (itemsCount <= maxVisibleItems) {
            sliderLoop = false;
        }

        $carousel.owlCarousel({
            loop: sliderLoop,
            rtl: is_rtl,
            margin: 36,
            nav: true,
            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
            autoplay: sliderAutoplay,
            autoplayTimeout: sliderSpeed,
            autoplayHoverPause: pause_on_hover,
            dots: false,
            responsive: {
                0: {
                    items: mobileColumns,
                    margin: 10,
                },
                500: {
                    items: mobileColumns,
                    margin: 10,
                },
                576: {
                    items: Math.min(2, columns),
                    margin: 20,
                },
                768: {
                    items: Math.min(3, columns),
                    margin: 20,
                },
                992: {
                    items: Math.min(3, columns)
                },
                1200: {
                    items: Math.min(4, columns)
                },
                1400: {
                    items: columns
                }
            }
        });
    });

    // Property image Carousel
    $('.adt-property-img-carousel').owlCarousel({
        loop: false,
        rtl: is_rtl,
        margin: 0,
        nav: true,
        dots: false,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        responsive: {
            0: {
                items: 1
            },
            600: {
                items: 1
            },
            1000: {
                items: 1
            }
        }
    });

    // Car Category Carousel
    $('.adt-car-category-carousel').each(function () {
        var $carousel = $(this);
        var mobileColumns = parseInt($carousel.data('mobile-columns')) || 1;
        $carousel.owlCarousel({
            loop: true,
            rtl: is_rtl,
            margin: 36,
            nav: true,
            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
            dots: false,
            responsive: {
                0: {
                    items: mobileColumns,
                    margin: 10
                },
                576: {
                    items: mobileColumns
                },
                768: {
                    items: 4
                },
                992: {
                    items: 5
                },
                1200: {
                    items: 6
                }
            }
        });
    });

    $('#showMoreCategories').on('click', function () {
        $('.hidden-category').slideDown();
        $(this).hide();
        $('#showLessCategories').show();
    });
    $('#showLessCategories').on('click', function () {
        $('.hidden-category').slideUp();
        $(this).hide();
        $('#showMoreCategories').show();
    });

    // Car Ad Carousel
    $('.adt-car-ad-carousel').owlCarousel({
        loop: true,
        rtl: is_rtl,
        margin: 0,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        responsive: {
            0: {
                items: 1
            },
            600: {
                items: 1
            },
            1000: {
                items: 1
            }
        }
    });

    // Category type Carousel
    $('.adt-category-types-carousel').owlCarousel({
        loop: true,
        rtl: is_rtl,
        margin: 0,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        responsive: {
            0: {
                items: 2
            },
            576: {
                items: 2
            },
            768: {
                items: 4
            },
            992: {
                items: 7
            },
            1200: {
                items: 9
            },
            1400: {
                items: 12
            }
        }
    });

    // Place Detail Carousel
    $('.adt-place-detail-carousel').owlCarousel({
        loop: false,
        rtl: is_rtl,
        margin: 0,
        items: 1,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
    });

    // Popular Place Carousel
    $('.adt-popular-place-carousel').owlCarousel({
        loop: false,
        rtl: is_rtl,
        margin: 36,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 2
            },
            768: {
                margin: 20,
                items: 3
            },
            992: {
                items: 3
            },
            1200: {
                margin: 20,
                items: 5
            },
            1400: {
                items: 5
            }
        }
    });

    // Popular Place Carousel
    $('.adt-event-list-carousel').owlCarousel({
        loop: false,
        rtl: is_rtl,
        margin: 36,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 2
            },
            768: {
                margin: 20,
                items: 3
            },
            992: {
                items: 3
            },
            1200: {
                margin: 20,
                items: 4
            },
            1400: {
                items: 4
            }
        }
    });

    // Category type Carousel
    $('.adt-category-types-carousel-2').owlCarousel({
        loop: true,
        rtl: is_rtl,
        margin: 0,
        center: true,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 2,
                nav: true
            },
            400: {
                items: 3,
                nav: true
            },
            576: {
                items: 2,
                nav: true
            },
            768: {
                items: 3,
                nav: true
            },
            992: {
                items: 6,
                nav: true
            },
            1200: {
                items: 7,
                nav: true
            },
            1400: {
                items: 8,
                nav: true
            }
        }
    });

    // Cyber Product Mini Carousel
    $('.cyber-products-mini-carousel').owlCarousel({
        loop: true,
        rtl: is_rtl,
        margin: 10,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1
            },
            400: {
                items: 2
            },
            576: {
                items: 2
            },
            768: {
                items: 3
            },
            992: {
                items: 2
            },
            1200: {
                items: 2,
                margin: 20
            },
            1400: {
                items: 2.9
            }
        }
    });

    // Cyber Sale Product Carousel
    $('.cyber-sale-products-carousel').owlCarousel({
        rtl: is_rtl,
        loop: false,
        margin: 5,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        responsive: {
            0: {
                items: 1
            },
            400: {
                items: 2
            },
            576: {
                items: 2
            },
            768: {
                items: 3
            },
            992: {
                items: 4
            },
            1200: {
                items: 4
            },
            1400: {
                items: 5
            }
        }
    });

    // Masonry grid js
    if ($('#masonry-grid').length > 0) {
        var $grid = $('#masonry-grid');
        $grid.masonry({
            itemSelector: '.masonry-item',
            columnWidth: '.masonry-item',
            percentPosition: true
        });
    }

    $('.default-select').select2();

    // Single controlled tooltip initializer.
    // - For favourite hearts: syncs state-aware attributes first via window.adfFixFavTooltip,
    //   then disposes any pre-existing tooltip instance and re-inits cleanly.
    // - For all other [data-toggle="tooltip"] elements: standard init (or re-init if already attached).
    // This is the ONLY tooltip initializer in the theme — removes race conditions from multiple init calls.
    window.adfInitTooltips = function () {
        // Heart buttons — sync state first, then re-init
        $('.favourite.ad_to_fav, .ad_to_fav, .save-ad').each(function () {
            var $btn = $(this);
            if (typeof window.adfFixFavTooltip === 'function') {
                // adfFixFavTooltip already disposes + re-inits the tooltip instance
                window.adfFixFavTooltip($btn);
                return;
            }
            // Fallback if the helper isn't available yet: plain dispose + init
            if (typeof $btn.tooltip === 'function') {
                try { $btn.tooltip('dispose'); } catch (e) {}
                $btn.tooltip({ html: true, trigger: 'hover' });
            }
        });

        // All OTHER [data-toggle="tooltip"] elements — standard Bootstrap init
        // (excludes hearts, which were handled above)
        $('[data-toggle="tooltip"]').not('.ad_to_fav, .save-ad').each(function () {
            var $el = $(this);
            if (typeof $el.tooltip === 'function') {
                try { $el.tooltip('dispose'); } catch (e) {}
                $el.tooltip({ html: true });
            }
        });
    };

    // Initial call
    window.adfInitTooltips();

    // Second pass after Bootstrap settles — guards against any third-party code
    // re-initializing tooltips in between.
    setTimeout(function () {
        window.adfInitTooltips();
    }, 300);

    $(".search-all-filters, .close-sidebar").on("click", function (e) {
        e.preventDefault();
        $('.search-all-filters').toggleClass('active');
        $('.all-filters-sidebar.adt-ads-filter-sidebar').toggleClass('open');
    });

    $(".mobile-filters-btn a, a.filter-close-btn").on("click", function () {
        $(".mobile-filters").toggleClass("active");
    });

    function adforestInitRangeSliders($scope) {
        if (typeof $.fn.ionRangeSlider !== 'function') {
            return;
        }

        var $context = ($scope && $scope.length) ? $scope : $(document);
        var $sliders = $context.find(".adt-ads-range-slider");
        if ($context.is(".adt-ads-range-slider")) {
            $sliders = $sliders.add($context);
        }

        $sliders.each(function () {
            var $slider = $(this);
            if ($slider.data('adforestRangeInitialized')) {
                return;
            }
            $slider.data('adforestRangeInitialized', true);

            var hasExplicitConfig = $slider.data('min') !== undefined && $slider.data('max') !== undefined;

            if (hasExplicitConfig) {
                var min = parseFloat($slider.data('min'));
                var max = parseFloat($slider.data('max'));
                if (isNaN(min) || isNaN(max)) {
                    return;
                }

                var step = parseFloat($slider.data('step'));
                if (isNaN(step) || step <= 0) {
                    step = 1;
                }

                var from = parseFloat($slider.data('from'));
                if (isNaN(from)) {
                    from = min;
                }

                var to = parseFloat($slider.data('to'));
                if (isNaN(to)) {
                    to = max;
                }

                var $minDisplay = $($slider.data('displayMin'));
                var $maxDisplay = $($slider.data('displayMax'));
                var $minInput = $($slider.data('inputMin'));
                var $maxInput = $($slider.data('inputMax'));

                function sync(data) {
                    if ($minDisplay.length) {
                        $minDisplay.text(data.from);
                    }
                    if ($maxDisplay.length) {
                        $maxDisplay.text(data.to);
                    }
                    if ($minInput.length) {
                        $minInput.val(data.from);
                    }
                    if ($maxInput.length) {
                        $maxInput.val(data.to);
                    }
                    $slider.data('from', data.from);
                    $slider.data('to', data.to);
                }

                $slider.ionRangeSlider({
                    skin: "round",
                    type: "double",
                    min: min,
                    max: max,
                    step: step,
                    from: from,
                    to: to,
                    hide_from_to: true,
                    hide_min_max: true,
                    onStart: sync,
                    onChange: sync
                });

                var sliderInstance = $slider.data("ionRangeSlider");

                if ($minInput.length) {
                    $minInput.on("input.adforestRange", function () {
                        var val = parseFloat($(this).val());
                        if (isNaN(val)) {
                            return;
                        }

                        var currentTo = sliderInstance.result ? sliderInstance.result.to : to;
                        if (val < min) {
                            val = min;
                        }
                        if (val > currentTo) {
                            val = currentTo;
                        }

                        sliderInstance.update({ from: val });
                    });
                }

                if ($maxInput.length) {
                    $maxInput.on("input.adforestRange", function () {
                        var val = parseFloat($(this).val());
                        if (isNaN(val)) {
                            return;
                        }

                        var currentFrom = sliderInstance.result ? sliderInstance.result.from : from;
                        if (val > max) {
                            val = max;
                        }
                        if (val < currentFrom) {
                            val = currentFrom;
                        }

                        sliderInstance.update({ to: val });
                    });
                }

                return;
            }

            var $container = $slider.closest('.adt-range-slider, .adt-price-slider, .accordion-body');
            var $servicesInputFrom = $container.find(".adt-ads-input-from");
            var $servicesInputTo = $container.find(".adt-ads-input-to");
            var $minValueSpan = $container.find('#min_price');
            var $maxValueSpan = $container.find('#max_price');

            var minVal = (typeof price_widget !== 'undefined' && price_widget !== null && price_widget.min_price !== undefined)
                ? parseInt(price_widget.min_price, 10)
                : 0;

            var maxVal = (typeof price_widget !== 'undefined' && price_widget !== null && price_widget.max_price !== undefined)
                ? parseInt(price_widget.max_price, 10)
                : 1000000;

            var fromVal = $servicesInputFrom.length && $servicesInputFrom.val() ? parseInt($servicesInputFrom.val(), 10) : minVal;
            var toVal = $servicesInputTo.length && $servicesInputTo.val() ? parseInt($servicesInputTo.val(), 10) : maxVal;

            function updateLegacyInputs(data) {
                if ($servicesInputFrom.length) {
                    $servicesInputFrom.prop("value", data.from);
                }
                if ($servicesInputTo.length) {
                    $servicesInputTo.prop("value", data.to);
                }
                if ($minValueSpan.length) {
                    $minValueSpan.text(data.from);
                }
                if ($maxValueSpan.length) {
                    $maxValueSpan.text(data.to);
                }
            }

            $slider.ionRangeSlider({
                skin: "round",
                type: "double",
                min: minVal,
                max: maxVal,
                from: fromVal,
                to: toVal,
                hide_from_to: true,
                hide_min_max: true,
                onStart: updateLegacyInputs,
                onChange: updateLegacyInputs
            });

            var legacyInstance = $slider.data("ionRangeSlider");

            if ($servicesInputFrom.length) {
                $servicesInputFrom.on("input.adforestRange", function () {
                    var val = parseInt($(this).prop("value"), 10);
                    if (isNaN(val)) {
                        return;
                    }

                    if (val < minVal) {
                        val = minVal;
                    } else if (val > legacyInstance.result.to) {
                        val = legacyInstance.result.to;
                    }

                    legacyInstance.update({
                        from: val
                    });
                });
            }

            if ($servicesInputTo.length) {
                $servicesInputTo.on("input.adforestRange", function () {
                    var val = parseInt($(this).prop("value"), 10);
                    if (isNaN(val)) {
                        return;
                    }

                    if (val < legacyInstance.result.from) {
                        val = legacyInstance.result.from;
                    } else if (val > maxVal) {
                        val = maxVal;
                    }

                    legacyInstance.update({
                        to: val
                    });
                });
            }
        });
    }

    adforestInitRangeSliders();


    // price slider for top bar search
    var $priceSlider = $("#price-slider-topbar-search");

    if ($priceSlider.length > 0 && $.fn.ionRangeSlider) {
        $priceSlider.ionRangeSlider({
            skin: "round",
            type: "double",
            grid: false,
            min: 0,
            max: 10000,
            from: 0,
            to: 5400,
            prefix: "$",
            hide_min_max: true,
        });
    }

    // Ad Carousel
    let sync1 = $("#sync1");
    let sync2 = $("#sync2");
    let slidesPerPage1 = 4;
    let syncedSecondary = true;

    sync1.owlCarousel({
        items: 1,
        slideSpeed: 2000,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        autoplay: true,
        autoplayTimeout: sb_options.auto_slide_time,
        dots: false,
        loop: true,
        rtl: is_rtl,
        responsiveRefreshRate: 200,
    }).on('changed.owl.carousel', syncPosition1);

    if ($("#sync2").length > 0) {
        sync2
            .on('initialized.owl.carousel', function () {
                sync2.find(".owl-item").eq(0).addClass("current");
            })
            .owlCarousel({
                rtl: is_rtl,
                items: 6,
                margin: 14,
                dots: false,
                nav: false,
                smartSpeed: 200,
                slideSpeed: 500,
                slideBy: slidesPerPage1,
                responsiveRefreshRate: 100
            }).on('click', '.owl-item', function (e) {
                let number = $(this).index();
                sync1.data('owl.carousel').to(number, 300, true);
            });
    }

    $(document).ready(function () {
        if ($('[data-fancybox="gallery"]').length !== 0) {
            $('[data-fancybox="gallery"]').fancybox({
                loop: true,
                buttons: [
                    "zoom",
                    "close"
                ]
            });
        }
    });

    function syncPosition1(el) {
        let count = el.item.count - 1;
        let current = Math.round(el.item.index - (el.item.count / 2) - .5);

        if (current < 0) {
            current = count;
        }
        if (current > count) {
            current = 0;
        }

        if ($("#sync2").length > 0) {
            sync2
                .find(".owl-item")
                .removeClass("current")
                .eq(current)
                .addClass("current");
            let onscreen = sync2.find('.owl-item.active').length - 1;
            let start = sync2.find('.owl-item.active').first().index();
            let end = sync2.find('.owl-item.active').last().index();

            if (current > end) {
                sync2.data('owl.carousel').to(current, 100, true);
            }
            if (current < start) {
                sync2.data('owl.carousel').to(current - onscreen, 100, true);
            }
        }
    }

    // Multivendor product detail carousel
    let sync3 = $("#sync3");
    let sync4 = $("#sync4");
    let slidesPerPage2 = 4;

    sync3.owlCarousel({
        items: 1, slideSpeed: 2000, nav: false, autoplay: false, dots: false, loop: true, responsiveRefreshRate: 200,
    }).on('changed.owl.carousel', syncPosition3);

    sync4
        .on('initialized.owl.carousel', function () {
            sync4.find(".owl-item").eq(0).addClass("current");
        })
        .owlCarousel({
            items: 4,
            rtl: is_rtl,
            margin: 25,
            dots: false,
            nav: false,
            smartSpeed: 200,
            slideSpeed: 500,
            slideBy: slidesPerPage2,
            responsiveRefreshRate: 100
        }).on('changed.owl.carousel', syncPosition4);

    function syncPosition3(el) {
        let count = el.item.count - 1;
        let current = Math.round(el.item.index - (el.item.count / 2) - .5);

        if (current < 0) {
            current = count;
        }
        if (current > count) {
            current = 0;
        }

        sync4
            .find(".owl-item")
            .removeClass("current")
            .eq(current)
            .addClass("current");
        let onscreen = sync4.find('.owl-item.active').length - 1;
        let start = sync4.find('.owl-item.active').first().index();
        let end = sync4.find('.owl-item.active').last().index();

        if (current > end) {
            sync4.data('owl.carousel').to(current, 100, true);
        }
        if (current < start) {
            sync4.data('owl.carousel').to(current - onscreen, 100, true);
        }
    }

    function syncPosition4(el) {
        if (syncedSecondary) {
            let number = el.item.index;
            sync3.data('owl.carousel').to(number, 100, true);
        }
    }

    sync4.on("click", ".owl-item", function (e) {
        e.preventDefault();
        let number = $(this).index();
        sync3.data('owl.carousel').to(number, 300, true);
    });


    $('.ad-content-item').click(function (event) {
        $('.ad-content-item').removeClass('active');
        $(this).addClass('active');
        $('html, body').animate({
            scrollTop: $($.attr(this, 'href')).offset().top
        }, 500);
        event.preventDefault();
    });

    // Multivendors Ads carousel
    $('.adt-multivendor-ads-carousel').each(function () {
        var $owl = $(this);
        var cols = parseInt($owl.data('columns')) || 4;
        $owl.owlCarousel({
            loop: false,
            rtl: $('html').attr('dir') === 'rtl',
            margin: 5,
            nav: true,
            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
            dots: false,
            responsive: {
                0: { items: 1 },
                576: { items: Math.min(2, cols) },
                768: { items: Math.min(3, cols) },
                992: { items: Math.min(4, cols) },
                1200: { items: cols }
            }
        });
    });

    // Multivendors Ads carousel
    $('.adt-multivendor-mini-ctg-carousel').owlCarousel({
        loop: true,
        rtl: is_rtl,
        margin: 20,
        nav: true,
        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
        dots: false,
        autoplayTimeout: 5000,
        autoplayHoverPause: false,
        responsive: {
            0: {
                items: 2
            }, 450: {
                items: 3
            }, 576: {
                items: 3
            }, 768: {
                items: 5
            }, 992: {
                items: 6
            }, 1200: {
                items: 8
            }, 1400: {
                items: 9
            }
        }
    });

    // Nice Scroll for Bids list
    if ($(".review-list-wrapper").length > 0) {
        $(".review-list-wrapper").niceScroll();
    }

    if ($(".bids-list-wrapper").length > 0) {
        $(".bids-list-wrapper").niceScroll();
    }


    let isInitializing = false;

    function initializeSelectedCategories() {
        if (typeof preselectedCategories !== 'undefined') {
            loading.show();
            isInitializing = true;
            let selectedCategoryIds = [...preselectedCategories.categories];
            let level = 1;

            async function handleCategoryLevels(parentId, level) {
                await fetchPreSelectedChildCategories(parentId, level);
                let selectId = 'ad_post_child_category_select_' + level;
                let select = $('#' + selectId);

                if (select.length && selectedCategoryIds.length > 0) {
                    let selectedId = selectedCategoryIds.shift();
                    select.val(selectedId);

                    if (selectedCategoryIds.length > 0) {
                        let nextLevel = level + 1;
                        let nextParentId = select.val();
                        await handleCategoryLevels(nextParentId, nextLevel);
                    }
                }
            }

            let topSelect = $('#ad_post_category_select');

            if (selectedCategoryIds.length > 0) {
                let firstSelected = selectedCategoryIds.shift();
                topSelect.val(firstSelected);
                checkCategorySelection();
                $('#child-category-container').empty();

                let isUpdate = "";
                if ($("#is_update").length > 0) {
                    isUpdate = $("#is_update").val();
                }
                selectedPackageData = null;

                if (typeof sb_options !== 'undefined' && sb_options.ad_posting_mode === 'paid_post' &&
                    !(sb_options.is_current_user_admin === 'admin' && sb_options.admin_allow_unlimited_ads === '1')) {
                    var dropzoneElement = $("#img_dropzone").get(0);
                    if (dropzoneElement && dropzoneElement.dropzone) {
                        updateDropzoneMaxFiles(null);
                    }
                }

                $.post(ajaxurl, {
                    action: "sb_get_sub_cat",
                    cat_id: firstSelected,
                    is_update: isUpdate,
                    security: $('#sb_get_sub_cat_nonce').val(),
                }).done(function (response) {
                    if (response.success !== false) {
                        let selected_packages = response?.data?.selected_packages || "";
                        if (selected_packages !== "" && selected_packages !== null) {
                            $(".package-label").show();
                            $("#ad_post_packages_container").html(selected_packages);
                        }
                    } else {
                        toastr.error(response?.data?.message, "", {
                            timeOut: 5000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        });
                    }
                }).fail(function (error) {
                    console.error('Error fetching selected packages:', error);
                });

                if (typeof sb_options !== 'undefined' && sb_options.ad_posting_mode === 'paid_post' &&
                    !(sb_options.is_current_user_admin === 'admin' && sb_options.admin_allow_unlimited_ads === '1') && sb_options.pay_per_post_option != 1) {
                    resetPackageSelectionUI();
                }

                (async () => {
                    // await fetchChildCategories(firstSelected, level);
                    await handleCategoryLevels(firstSelected, level + 1);

                    let lastSelectedCategory = getLastSelectedCategory();
                    await saveSelectedCategory(lastSelectedCategory, { manageLoading: false });
                    updateSelectedCategoriesDisplay();

                    loading.hide();
                    isInitializing = false;
                })();

            } else {
                loading.hide();
                isInitializing = false;
            }
        }
    }

    function fetchPreSelectedChildCategories(parentId, level) {
        let selectId = 'ad_post_child_category_select_' + level;
        let select = $('#' + selectId);

        if (select.length === 0) {
            $('#child-category-container').append('<div id="child-category-div-' + level + '">' +
                '<select name="' + selectId + '" id="' + selectId + '" class="default-select child-category-select" data-level="' + level + '">' +
                '<option value="">' + sb_options.select_option + '</option>' +
                '</select>' +
                '</div>');
            select = $('#' + selectId);
        } else {
            select.empty();
            select.append('<option value="">' + sb_options.select_option + '</option>');
        }

        return $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_child_categories',
                parent_id: parentId
            }
        }).then(response => {
            if (response.success) {
                let categories = response.data;
                $.each(categories, function (index, category) {
                    select.append('<option value="' + category.term_id + '">' + category.name + '</option>');
                });

                select.parent().show();
                select.select2();
                return Promise.resolve();
            } else {
                $('#child-category-div-' + level).remove();
                return Promise.resolve();
            }
        }).catch(error => {
            console.error('Error fetching child categories:', error);
            return Promise.reject(error);
        });
    }

    initializeSelectedCategories();

    function applyRadioButtonStyling() {
        $("input[type=radio][name=ad_warranty]").each(function () {
            $("label[for='" + $(this).attr("id") + "']").css({
                "color": "", "background-color": "", "border": ""
            });
        });

        $("input[type=radio][name=ad_warranty]:checked").each(function () {
            $("label[for='" + $(this).attr("id") + "']").css({
                "color": "#fff", "background-color": sb_options.main_btn_color, "border": sb_options.main_btn_color
            });
        });

        $("input[type=radio][name=ad_condition]").each(function () {
            $("label[for='" + $(this).attr("id") + "']").css({
                "color": "", "background-color": "", "border": ""
            });
        });

        $("input[type=radio][name=ad_condition]:checked").each(function () {
            $("label[for='" + $(this).attr("id") + "']").css({
                "color": "#fff", "background-color": sb_options.main_btn_color, "border": sb_options.main_btn_color
            });
        });
    }

    function initializeAfterResponse(options) {
        let initOptions = options || {};
        let $scope = initOptions.scope && initOptions.scope.length ? initOptions.scope : $(document);
        let $scopedSelects = $scope.find('select').filter(function () {
            return !$(this).hasClass('child-category-select') && !$(this).hasClass('select2-hidden-accessible');
        });
        let $priceType = $scope.find("#ad_post_price_type");
        let $adTypeContainer = $scope.find("#ad_type");
        let $warrantyContainer = $scope.find("#warranty");
        let $conditionContainer = $scope.find("#condition");

        $scopedSelects.select2();
        if ($priceType.length > 0) {
            if (!$priceType.hasClass('select2-hidden-accessible')) {
                $priceType.select2();
            }

            $priceType.off('change.adforestCategoryPriceType').on('change.adforestCategoryPriceType', function () {
                let priceType = $priceType.val();

                if (priceType == 'free' || priceType == "no_price" || priceType == "on_call") {
                    $(".ad_price_container").hide();
                    $(".price_range_container").hide();
                    $("#ad_price").removeAttr('required data-parsley-required');
                } else if (priceType == 'range') {
                    $(".ad_price_container").hide();
                    $(".price_range_container").show();
                    $("#ad_price_from, #ad_price_to").attr('required', 'required').attr('data-parsley-required', 'true');
                    $("#ad_price").removeAttr('required data-parsley-required');
                } else {
                    $(".ad_price_container").show();
                    $(".price_range_container").hide();
                    $("#ad_price").attr('required', 'required').attr('data-parsley-required', 'true');
                    $("#ad_price_from, #ad_price_to").removeAttr('required data-parsley-required');
                }
            });

            $priceType.trigger('change.adforestCategoryPriceType');
        }

        if ($("#ad_price").length > 0) {
        }

        function updateRadioStyles() {
            $("input[type=radio][name=ad_type]").each(function () {
                $("label[for='" + $(this).attr("id") + "']").css({
                    "color": "", "background-color": "", "border": ""
                });
            });

            $("input[type=radio][name=ad_type]:checked").each(function () {
                $("label[for='" + $(this).attr("id") + "']").css({
                    "color": "#fff", "background-color": sb_options.main_btn_color, "border": sb_options.main_btn_color
                });
            });
        }

        updateRadioStyles();

        // Apply styling to pre-selected radio buttons
        applyRadioButtonStyling();

        $adTypeContainer.off("change.adforestCategoryUi", "input[type=radio][name=ad_type]").on("change.adforestCategoryUi", "input[type=radio][name=ad_type]", function () {
            updateRadioStyles();
        });

        $warrantyContainer.off("change.adforestCategoryUi", "input[type=radio][name=ad_warranty]").on("change.adforestCategoryUi", "input[type=radio][name=ad_warranty]", function () {
            $("input[type=radio][name=ad_warranty]").each(function () {
                $("label[for='" + $(this).attr("id") + "']").css({
                    "color": "", "background-color": "", "border": ""
                });
            });

            $("input[type=radio][name=ad_warranty]:checked").each(function () {
                $("label[for='" + $(this).attr("id") + "']").css({
                    "color": "#fff", "background-color": sb_options.main_btn_color, "border": sb_options.main_btn_color
                });
            });
        });


        $conditionContainer.off("change.adforestCategoryUi", "input[type=radio][name=ad_condition]").on("change.adforestCategoryUi", "input[type=radio][name=ad_condition]", function () {
            $("input[type=radio][name=ad_condition]").each(function () {
                $("label[for='" + $(this).attr("id") + "']").css({
                    "color": "", "background-color": "", "border": ""
                });
            });

            $("input[type=radio][name=ad_condition]:checked").each(function () {
                $("label[for='" + $(this).attr("id") + "']").css({
                    "color": "#fff", "background-color": sb_options.main_btn_color, "border": sb_options.main_btn_color
                });
            });
        });

        adforestInitRangeSliders($scope);
    }

    $("input[type=radio][name=ad_warrenty]").change(function () {

        $("input[type=radio][name=ad_warrenty]").each(function () {
            $("label[for=\'" + $(this).attr("id") + "\']").css({
                "color": "", "background-color": "", "border": ""
            });
        });

        if ($(this).is(":checked")) {
            $("label[for=\'" + $(this).attr("id") + "\']").css({
                "color": "#fff", "background-color": sb_options.main_btn_color, "border": sb_options.main_btn_color
            });
        }
    });

    $("input[type=radio][name=s-size]").change(function () {

        $("input[type=radio][name=s-size]").each(function () {
            $("label[for=\'" + $(this).attr("id") + "\']").css({
                "color": "", "background-color": "", "border": ""
            });
        });

        // Apply styles to the checked label
        if ($(this).is(":checked")) {
            $("label[for=\'" + $(this).attr("id") + "\']").css({
                "color": "#fff", "background-color": sb_options.main_btn_color, "border": sb_options.main_btn_color
            });
        }
    });

    $("input[type=radio][name=ad_condition]").change(function () {

        $("input[type=radio][name=ad_condition]").each(function () {
            $("label[for=\'" + $(this).attr("id") + "\']").css({
                "color": "", "background-color": "", "border": ""
            });
        });


        if ($(this).is(":checked")) {
            $("label[for=\'" + $(this).attr("id") + "\']").css({
                "color": "#fff", "background-color": "#ff002e", "border": "1px solid #ff002e"
            });
        }
    });

    const classifiedButtons = document.querySelectorAll('.classified-nav-link');

    if (classifiedButtons.length > 0) {
        classifiedButtons.forEach(button => {
            button.addEventListener('click', function () {
                classifiedButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.style.backgroundColor = '';
                });

                this.classList.add('active');
                this.style.backgroundColor = 'black';
            });
        });
    }

    $("#fav_product_btn").on('click', function () {
        $(".product-favourite-sb").slideToggle("slow");
    });

    async function saveSelectedCategory(lastSelectedCategory, options) {
        const adID = $("#ad_post_id_on_update").val();
        const requestOptions = options || {};
        const requestSequence = typeof requestOptions.requestSequence === 'number' ? requestOptions.requestSequence : null;
        const shouldManageLoading = requestOptions.manageLoading !== false;
        let responseScope = $()
            .add($("#cat_template_html"))
            .add($("#custom_field_container"))
            .add($("#ad_condition_and_warranty_box"))
            .add($("#ad_intent_type_container"))
            .add($("#ad_intent_condition_warranty_container"))
            .add($("#tags_and_video_link_box"));
        let request;

        if (!lastSelectedCategory || !lastSelectedCategory.id) {
            resetCategoryDependentFields();
            return null;
        }

        if (shouldManageLoading) {
            loading.show();
        }

        request = $.ajax({
            url: ajaxurl, type: 'POST', data: {
                action: 'save_selected_category',
                category_id: lastSelectedCategory.id,
                category_name: lastSelectedCategory.name.trim(),
                post_id: adID,
                security: $('#save_selected_category_nonce').val()
            }
        });

        if (typeof requestSequence === 'number') {
            activeCategoryFieldsRequest = request;
        }

        try {
            let response = await request;

            if (!isActiveCategoryRequest(requestSequence)) {
                return response;
            }

            if (!response.success) {
                toastr.error('Error saving category.');
                return response;
            }

            $('#cat_temp_meta').val(response.data.id);
            $('#selected-category-name').val(response.data.name);
            $('#cat_template_html').html(response.data.category_template_html);
            if (response.data.condition_and_value_fields) {
                $("#ad_condition_and_warranty_box").html(response.data.condition_and_value_fields);

                setTimeout(function () {
                    applyRadioButtonStyling();
                }, 100);
            } else if (response.data.condition_and_value_fields == "") {
                $("#ad_condition_and_warranty_box").html("");
            }

            if (response.data.ai_intent_in_category) {
                var hasIntentFields = false;

                if (response.data.intent_ad_type_html) {
                    $("#ad_intent_type_container").html(response.data.intent_ad_type_html);
                    hasIntentFields = true;
                } else {
                    $("#ad_intent_type_container").html("");
                }

                if (response.data.intent_condition_warranty_html) {
                    $("#ad_intent_condition_warranty_container").html(response.data.intent_condition_warranty_html);
                    hasIntentFields = true;
                } else {
                    $("#ad_intent_condition_warranty_container").html("");
                }

                if (hasIntentFields) {
                    $("#ai-intent-fields-container").show();
                } else {
                    $("#ai-intent-fields-container").hide();
                }

                setTimeout(function () {
                    applyRadioButtonStyling();
                }, 100);
            } else {
                $("#ai-intent-fields-container").hide();
            }

            if (response.data.tags_and_video_fields) {
                $("#tags_and_video_link_box").html(response.data.tags_and_video_fields);
            } else if (response.data.tags_and_video_fields == "") {
                $("#tags_and_video_link_box").html("");
            }
            if (response.data.image_field) {
                $(".ad_post_image_container").html(response.data.image_field);
            }
            if (response.data.custom_fields_html && response.data.custom_fields_html.length > 0) {
                $("#custom_field_container").html(
                    `<h3>${sb_options.additional_fields_text}</h3>` + response.data.custom_fields_html
                );
            }

            if (response.data.no_template_selected && response.data.default_template_on) {
                if (response.data.default_template_html) {
                    $("#cat_template_html").html(response.data.default_template_html);
                }

                if (response.data.more_options) {
                    if (response.data.more_options.ad_images_on === 0) {
                        $(".ad_post_image_container").remove();
                    }
                    if (response.data.more_options.default_template_warranty_and_condition != "" && !response.data.ai_intent_in_category) {
                        $("#ad_condition_and_warranty_box").html(response.data.more_options.default_template_warranty_and_condition);
                        setTimeout(function () {
                            applyRadioButtonStyling();
                        }, 100);
                    }
                    if (response.data.more_options.default_template_image_container != "") {
                        $(".ad_post_image_container").html(response.data.more_options.default_template_image_container);
                    }
                }
            }

            if (response.data.default_template_on === false || sb_options.sb_default_adpost_template_on != "1") {
                if (response.data.image_field !== "") {
                    adforest_dropzone_image();
                }
            }

            if (sb_options.sb_default_adpost_template_on == "1" && response.data.default_template_on && sb_options.sb_default_adpost_template_images == "1") {
                adforest_dropzone_image();
            }
            initializeAfterResponse({ scope: responseScope });
            if (responseScope.find("#dropzone_video").length > 0) {
                adforest_dropzone_video();
            }
            if ($("#tags_and_video_link_box").length > 0) {
                adforest_inputTags();
            }

            return response;
        } catch (error) {
            if (isRequestAbortError(error)) {
                return null;
            }

            toastr.error('Error saving category.');
            throw error;
        } finally {
            if (typeof requestSequence === 'number' && activeCategoryFieldsRequest === request) {
                activeCategoryFieldsRequest = null;
            }

            if (shouldManageLoading) {
                loading.hide();
            }
        }
    }

    function updateSelectedCategoriesDisplay() {
        let selectedCategories = [];

        let mainCategorySelect = $('#ad_post_category_select');
        if (mainCategorySelect.val()) {
            selectedCategories.push(mainCategorySelect.find('option:selected').text().trim());
        }

        $('.child-category-select').each(function () {
            let selectedText = $(this).find('option:selected').text().trim();
            if ($(this).val() && selectedText !== "Select Option") {
                selectedCategories.push(selectedText);
            }
        });

        let displayText = selectedCategories.join(' > ');

        let container = $('.ad-post-selected-categories');
        container.empty();

        if (displayText) {
            $('<div class="selected-category-path">' + displayText + '</div>').hide().appendTo(container).fadeIn(300);
        }

        let mobileContainer = $('.ad-post-selected-categories-mobile');
        mobileContainer.empty();

        if (displayText) {
            $('<div class="selected-category-path">' + displayText + '</div>').hide().appendTo(mobileContainer).fadeIn(300);
        }
    }

    $('#ad_post_category_select').on('change', async function () {
        if (isInitializing) return;

        checkCategorySelection();
        loading.show();
        let requestSequence = beginCategoryRequestCycle();
        let parentId = $(this).val();
        let level = 1;

        $('#child-category-container').empty();
        let isUpdate = "";
        if ($("#is_update").length > 0) {
            isUpdate = $("#is_update").val();
        }
        // Reset selected package data when category changes
        selectedPackageData = null;

        if (isPaidCategoryPackageFlow()) {
            // Only try to update if dropzone is already initialized
            var dropzoneElement = $("#img_dropzone").get(0);
            if (dropzoneElement && dropzoneElement.dropzone) {
                updateDropzoneMaxFiles(null);
            }
        }

        if (typeof sb_options !== 'undefined' && sb_options.ad_posting_mode === 'paid_post' &&
            !(sb_options.is_current_user_admin === 'admin' && sb_options.admin_allow_unlimited_ads === '1') && sb_options.pay_per_post_option != 1) {
            resetPackageSelectionUI();
        }

        if (!parentId) {
            resetCategoryDependentFields();
            updateSelectedCategoriesDisplay();
            loading.hide();
            return;
        }

        try {
            await Promise.all([
                fetchChildCategories(parentId, level, requestSequence),
                saveSelectedCategory(getLastSelectedCategory(), { manageLoading: false, requestSequence: requestSequence }),
                syncCategoryPackages(parentId, isUpdate, requestSequence)
            ]);
            if (isActiveCategoryRequest(requestSequence)) {
                updateSelectedCategoriesDisplay();
            }
        } finally {
            if (isActiveCategoryRequest(requestSequence)) {
                loading.hide();
            }
        }
    });

    $(document).on('change', '.child-category-select', async function () {
        if (isInitializing) return;
        loading.show();
        let requestSequence = beginCategoryRequestCycle();

        // Reset selected package data when child category changes
        selectedPackageData = null;

        // Reset dropzone to default image limit (only if dropzone is initialized)
        if (isPaidCategoryPackageFlow()) {
            // Only try to update if dropzone is already initialized
            var dropzoneElement = $("#img_dropzone").get(0);
            if (dropzoneElement && dropzoneElement.dropzone) {
                updateDropzoneMaxFiles(null);
            }
        }

        let parentId = $(this).val();
        let level = $(this).data('level') + 1;

        if ($("#alert_category").length > 0) {
            $("#alert_category").val(parentId);
        }

        $('#child-category-container').find('div').filter(function () {
            return $(this).attr('id').split('-')[3] >= level;
        }).remove();

        if (typeof sb_options !== 'undefined' && sb_options.ad_posting_mode === 'paid_post' &&
            !(sb_options.is_current_user_admin === 'admin' && sb_options.admin_allow_unlimited_ads === '1') && sb_options.pay_per_post_option != 1) {
            resetPackageSelectionUI();
        }

        try {
            await Promise.all([
                parentId ? fetchChildCategories(parentId, level, requestSequence) : Promise.resolve(),
                saveSelectedCategory(getLastSelectedCategory(), { manageLoading: false, requestSequence: requestSequence }),
                syncCategoryPackages(parentId, "", requestSequence)
            ]);
            if (isActiveCategoryRequest(requestSequence)) {
                updateSelectedCategoriesDisplay();
            }
        } finally {
            if (isActiveCategoryRequest(requestSequence)) {
                loading.hide();
            }
        }
    });

    if ($("#adforest_login_user").length > 0) {
        $("#adforest_login_user").parsley().on("field:validated", function () {
            let ok = $(".parsley-error").length === 0;
        })
    }

    $("#adforest_login_user").on("submit", function (e) {
        const form = $(this);
        const button = $("#adforest_login_button");
        const is_captcha = $("#is_captcha").val();
        const spinner = button.find('#login-spinner-icon');

        e.preventDefault();

        if (form.parsley().isValid()) {
            button.prop('disabled', true);
            loading.show();

            let google_recaptcha_type = sb_options.google_recaptcha_type;

            google_recaptcha_type = typeof google_recaptcha_type !== "undefined" ? google_recaptcha_type : "v2";
            let google_recaptcha_site_key = sb_options.google_recaptcha_site_key;

            if (google_recaptcha_type === "v3" && google_recaptcha_site_key !== "undefined" && google_recaptcha_site_key !== "" && is_captcha === 'yes') {
                grecaptcha.ready(function () {
                    try {
                        grecaptcha
                            .execute(google_recaptcha_site_key, { action: "contact_form" })
                            .then(function (token) {
                                jQuery("#adforest_login_user").prepend('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');

                                jQuery.post(ajaxurl, {
                                    action: "sb_google_captcha3_verification", token: token, security: $('#sb_google_captcha3_verification_nonce').val()
                                }, function (result) {
                                    result = JSON.parse(result);
                                    if (result.success) {
                                        $.post(ajaxurl, {
                                            action: "sb_login_user",
                                            security: $("#sb-login-token").val(),
                                            sb_data: form.serialize(),
                                        }).done(function (response) {
                                            button.prop('disabled', false);
                                            loading.hide();
                                            if (response.trim() == "1") {
                                                $("#sb_login_redirect").show();
                                                toastr.success(sb_options.redirecting + "...", "", {
                                                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                                })
                                                window.location = sb_options.sb_after_login_page;
                                            } else {
                                                $("#sb_login_submit").show();
                                                button.prop('disabled', false);
                                                loading.hide();
                                                toastr.error(response, "", {
                                                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                                });
                                            }
                                        }).fail(function () {
                                            button.prop('disabled', false);
                                            loading.hide();
                                        });
                                    } else {
                                        loading.hide();
                                        $("#sb_register_submit").show();
                                        toastr.error(result.msg, "", {
                                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                        });
                                    }
                                });
                            });
                    } catch (err) {
                        let google_recaptcha_error_text = jQuery("#google_recaptcha_error_text").val();
                        google_recaptcha_error_text = typeof google_recaptcha_error_text !== "undefined" ? google_recaptcha_error_text : err;
                        jQuery("#sb_loading").hide();
                        toastr.error(google_recaptcha_error_text, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    }
                })
            } else {
                $.post(ajaxurl, {
                    action: "sb_login_user", security: $("#sb-login-token").val(), sb_data: form.serialize(),
                }).done(function (response) {
                    button.prop('disabled', false);
                    loading.hide();
                    if (response.trim() == "1") {
                        $("#sb_login_redirect").show();
                        toastr.success(sb_options.redirecting + "...", "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        })
                        window.location = sb_options.sb_after_login_page;
                    } else {
                        $("#sb_login_submit").show();
                        button.prop('disabled', false);
                        loading.hide();
                        toastr.error(response, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    }
                }).fail(function () {
                    button.prop('disabled', false);
                    loading.hide();
                });
            }
        }
    });

    if ($("#sb-forgot-form").length > 0) {
        $("#sb_forgot_msg").hide();
        $("#sb-forgot-form")
            .parsley()
            .on("field:validated", function () {
                var ok = $(".parsley-error").length === 0;
            })

        $("#sb-forgot-form").on('submit', function (e) {
            const form = $(this);
            $("#sb_forgot_submit").hide();
            $("#sb_forgot_msg").show();
            loading.show();
            $.post(ajaxurl, {
                action: "sb_forgot_password", security: $("#sb-forgot-pass-token").val(), sb_data: form.serialize(),
            }).done(function (response) {
                loading.hide();
                $("#sb_forgot_msg").hide();
                if (response.trim() == "1") {
                    $("#sb_forgot_submit").show();
                    $("#sb_forgot_email").val("");
                    toastr.success($("#adforest_forgot_msg").val(), "", {
                        timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                    });
                    $("#forgot_password_modal").modal("hide");
                } else {
                    $("#sb_forgot_submit").show();
                    toastr.error(response, "", {
                        timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                    });
                }
            }).fail(function () {
                $("#sb_forgot_submit").show();
                $("#sb_forgot_email").val("");
                toastr.error("NONCE ERROR", "", {
                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                });
            });
            return false;
        })
    }

    $("#show_more").on("click", function () {
        $("#cat-half-text").hide();
        $("#cat-full-text").show();
    });
    /*Register user*/
    if ($("#adforest-signup-form").length > 0) {
        $("#sb_register_msg").hide();
        $("#sb_register_redirect").hide();
        $("#adforest-signup-form")
            .parsley()
            .on("field:validated", function () {
                let ok = $(".parsley-error").length === 0;
            })
        $("#adforest-signup-form")
            .on("submit", function (e) {
                e.preventDefault();
                let form = $(this);
                loading.show();
                let google_recaptcha_type = sb_options.google_recaptcha_type;
                google_recaptcha_type = typeof google_recaptcha_type !== "undefined" ? google_recaptcha_type : "v2";
                let google_recaptcha_site_key = sb_options.google_recaptcha_site_key;
                if (google_recaptcha_type === "v3" && google_recaptcha_site_key !== "undefined" && google_recaptcha_site_key !== "") {
                    grecaptcha.ready(function () {
                        try {
                            grecaptcha
                                .execute(google_recaptcha_site_key, { action: "register_form" })
                                .then(function (token) {
                                    form.prepend('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
                                    jQuery.post(ajaxurl, {
                                        action: "sb_google_captcha3_verification",
                                        security: $('#sb_google_captcha3_verification_nonce').val(),
                                        token: token
                                    }, function (result) {
                                        result = JSON.parse(result);
                                        if (result.success) {
                                            $("#sb_register_submit").hide();
                                            $("#sb_register_msg").show();
                                            $.post(ajaxurl, {
                                                action: "sb_register_user",
                                                security: $("#sb-register-token").val(),
                                                sb_data: form.serialize(),
                                            })
                                                .done(function (response) {
                                                    loading.hide();
                                                    $("#sb_register_msg").hide();
                                                    if (response.trim() == "1") {
                                                        $("#sb_register_redirect").show();
                                                        window.location = sb_options.sb_after_login_page;
                                                    } else if (response.trim() === "2" || response.trim() === 2) {
                                                        $(".resend_email").show();
                                                        toastr.success(sb_options.verify_account_msg, "", {
                                                            timeOut: 4000,
                                                            closeButton: true,
                                                            positionClass: "toast-top-right",
                                                        });
                                                    } else if (response.trim() == "3") {
                                                        toastr.success($("#admin_verify_account").val(), "", {
                                                            timeOut: 4000,
                                                            closeButton: true,
                                                            positionClass: "toast-top-right",
                                                        });
                                                    } else {
                                                        $("#sb_register_submit").show();
                                                        toastr.error(response, "", {
                                                            timeOut: 4000,
                                                            closeButton: true,
                                                            positionClass: "toast-top-right",
                                                        });
                                                    }
                                                })
                                                .fail(function () {
                                                    loading.hide();
                                                    $("#sb_register_msg").hide();
                                                    $("#sb_register_submit").show();
                                                    toastr.error($("#_nonce_error").val(), "", {
                                                        timeOut: 4000,
                                                        closeButton: true,
                                                        positionClass: "toast-top-right",
                                                    });
                                                });
                                        } else {
                                            loading.hide();
                                            $("#sb_register_submit").show();
                                            toastr.error(result.msg, "", {
                                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                            });
                                        }
                                    });
                                });
                        } catch (err) {
                            var google_recaptcha_error_text = jQuery("#google_recaptcha_error_text").val();
                            google_recaptcha_error_text = typeof google_recaptcha_error_text !== "undefined" ? google_recaptcha_error_text : err;
                            jQuery("#sb_loading").hide();
                            toastr.error(google_recaptcha_error_text, "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        }
                    });
                } else {
                    $("#sb_register_submit").hide();
                    $("#sb_register_msg").show();
                    $.post(ajaxurl, {
                        action: "sb_register_user", security: $("#sb-register-token").val(), sb_data: form.serialize(),
                    })
                        .done(function (response) {
                            loading.hide();
                            $("#sb_register_msg").hide();

                            if (response.trim() == "1") {
                                $("#sb_register_redirect").show();
                                window.location = sb_options.sb_after_login_page;
                            } else if (response.trim() === "2" || response.trim() === 2) {
                                $(".resend_email").show();
                                toastr.success(sb_options.verify_account_msg, "", {
                                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                });
                            } else if (response.trim() == "3") {
                                toastr.success($("#admin_verify_account").val(), "", {
                                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                });
                            } else {
                                $("#sb_register_submit").show();
                                toastr.error(response, "", {
                                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                });
                            }
                        })
                        .fail(function () {
                            loading.hide();
                            $("#sb_register_msg").hide();
                            $("#sb_register_submit").show();
                            toastr.error($("#_nonce_error").val(), "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        });
                }
                return false;
            });
    }
    /*Register user*/

    /*Resend Email*/
    $("#resend_email").on("click", function () {
        var usr_email = $("#sb_reg_email").val();
        $.post(ajaxurl, {
            action: "sb_resend_email",
            usr_email: usr_email,
            security: jQuery('#sb_resend_email_nonce').val()
        }).done(function (response) {
            toastr.success($("#verify_account_msg").val(), "", {
                timeOut: 4000,
                closeButton: true,
                positionClass: "toast-top-right",
            });
            $(".resend_email").hide();
            $(".contact_admin").show();
        });
    });

    if (sb_options.facebook_key != "" && sb_options.google_key != "") {
        hello.init(
            { facebook: sb_options.facebook_key, google: sb_options.google_key },
            { redirect_uri: sb_options.redirect_uri }
        );
    } else if (sb_options.facebook_key != "" && sb_options.google_key == "") {
        hello.init(
            { facebook: sb_options.facebook_key },
            { redirect_uri: sb_options.redirect_uri }
        );
    } else if (sb_options.google_key != "" && sb_options.facebook_key == "") {
        hello.init(
            { google: sb_options.google_key },
            { redirect_uri: sb_options.redirect_uri }
        );
    }
    $(".social-item a.btn-social").on("click", function () {
        hello.on("auth.login", function (auth) {
            $("#sb_loading").show();
            hello(auth.network)
                .api("me")
                .then(function (r) {
                    if (
                        $("#get_action").val() == "login" ||
                        $("#get_action").val() == "register"
                    ) {
                        var access_token = hello(auth.network).getAuthResponse()
                            .access_token;
                        var sb_network = hello(auth.network).getAuthResponse().network;
                        $.post(ajaxurl, {
                            action: "sb_social_login",
                            access_token: access_token,
                            sb_network: sb_network,
                            security: $("#sb-social-login-nonce").val(),
                            email: r.email,
                            key_code: $("#nonce").val(),
                        })
                            .done(function (response) {
                                var get_r = response.split("|");
                                if (get_r[0].trim() == "1") {
                                    $("#nonce").val(get_r[1]);
                                    if (get_r[2].trim() == "1") {
                                        toastr.success(get_r[3], "", {
                                            timeOut: 4000,
                                            closeButton: true,
                                            positionClass: "toast-top-right",
                                        });
                                        window.location = sb_options.sb_after_login_page;
                                    } else {
                                        toastr.error(get_r[3], "", {
                                            timeOut: 4000,
                                            closeButton: true,
                                            positionClass: "toast-top-right",
                                        });
                                    }
                                } else {
                                    toastr.error(get_r[3], "", {
                                        timeOut: 4000,
                                        closeButton: true,
                                        positionClass: "toast-top-right",
                                    });
                                }
                            })
                            .fail(function () {
                                $("#sb_loading").hide();
                                toastr.error($("#_nonce_error").val(), "", {
                                    timeOut: 4000,
                                    closeButton: true,
                                    positionClass: "toast-top-right",
                                });
                            });
                    } else {
                        $("#sb_reg_name").val(r.name);
                        $("#sb_reg_email").val(r.email);
                    }
                    $("#sb_loading").hide();
                });
        });
    });

    $(document).ready(function () {
        $('#search_hero_3_type_list input[type="radio"]').on('change', function () {
            $('#search_hero_3_type_list label').css({
                'background-color': '#f6f6f6',
                'color': '#6d6d6d'
            });

            if ($(this).is(':checked')) {
                $(this).next('label').css({
                    'background-color': sb_options.main_btn_color,
                    'color': sb_options.main_btn_hover_color_text
                });
            }
        });
    });

    // AD POST //
    $('.next-btn').on('click', function (event) {
        event.preventDefault();
        if (!$(this).hasClass('btn-adpost-start')) {
            $('html, body').scrollTop(0);
        }
        let isValid = true;

        if ($("#ad_post_category_select").length > 0 && $("#ad_post_category_select").val() === "") {
            isValid = false;
            toastr.error($("#select_cat_first").val(), "", {
                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
            });
        }
        const isUpdate = new URLSearchParams(window.location.search).has('id');

        if (!isUpdate && sb_options.pay_per_post_option != 1) {
            const isAdminAllowed = sb_options.admin_allow_unlimited_ads != 1;
            const isNotAdmin = sb_options.is_current_user_admin == "not_admin";

            if (sb_options.ad_posting_mode == 'paid_post') {
                if ((isNotAdmin || isAdminAllowed) && $('input.ads-package-radio:checked').length === 0) {
                    isValid = false;
                    // Silent rejection here used to leave users stuck — the
                    // Next button is only visually faded via .package-required.
                    // Surface the requirement explicitly so the user knows to
                    // pick a package (parity with the previous classic flow).
                    toastr.error(sb_options.select_package, "", {
                        timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                    });
                }
            }
        }

        $("[id^='ad_post_child_category_select_']").each(function () {
            const $childCategory = $(this);
            if ($childCategory.val() === "" && sb_options.sub_cat_option_select == 1) {
                isValid = false;
                toastr.error(sb_options.not_sub_cat_text, "", {
                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                });
                return false;
                disableNavLinks();
            } else {
                enableNavLinks();
            }
        });

        if (isValid) {
            let $active = $('.nav-pills .nav-link.active');
            let next = $active.closest('button').next('button');

            if (next.length > 0) {
                next.tab('show');
            }
        }
    });

    $('.prev-btn').on('click', function (event) {
        event.preventDefault();
        $('html, body').scrollTop(0);
        let $active = $('.nav-pills .nav-link.active');
        let $prev = $active.closest('button').prev('button');

        if ($prev.length > 0) {
            $prev.tab('show');
        }
    });

    // AD POST //

    async function fetchChildCategories(parentId, level, requestSequence) {
        let request = $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_child_categories',
                parent_id: parentId
            }
        });

        if (typeof requestSequence === 'number') {
            activeChildCategoriesRequest = request;
        }

        try {
            let response = await request;

            if (!isActiveCategoryRequest(requestSequence)) {
                return response;
            }

            if (response.success) {
                let categories = response.data;
                let selectId = 'ad_post_child_category_select_' + level;
                let select = $('#' + selectId);

                if (select.length === 0) {
                    $('#child-category-container').append('<div id="child-category-div-' + level + '">' +
                        '<select name="' + selectId + '" id="' + selectId + '" class="default-select child-category-select" data-level="' + level + '">' +
                        '<option value="">' + sb_options.select_option + '</option>' +
                        '</select>' +
                        '</div>');
                    select = $('#' + selectId);
                } else {
                    if (select.hasClass('select2-hidden-accessible')) {
                        select.select2('destroy');
                    }
                    select.empty();
                    select.append('<option value="">' + sb_options.select_option + '</option>');
                }

                $.each(categories, function (index, category) {
                    select.append('<option value="' + category.term_id + '">' + category.name + '</option>');
                });

                select.parent().show();
                select.select2();
            } else {
                $('#child-category-div-' + level).remove();
            }

            return response;
        } catch (error) {
            if (isRequestAbortError(error)) {
                return null;
            }

            console.log(error);
            throw error;
        } finally {
            if (typeof requestSequence === 'number' && activeChildCategoriesRequest === request) {
                activeChildCategoriesRequest = null;
            }
        }
    }

    // Function to get the last selected category
    function getLastSelectedCategory() {
        let lastSelectedCategory = { id: null, name: '' };

        let mainCategorySelect = $('#ad_post_category_select');
        if (mainCategorySelect.val()) {
            lastSelectedCategory.id = mainCategorySelect.val();
            lastSelectedCategory.name = mainCategorySelect.find('option:selected').text();
        }

        $('.child-category-select').each(function () {
            if ($(this).val()) {
                lastSelectedCategory.id = $(this).val();
                lastSelectedCategory.name = $(this).find('option:selected').text();
            }
        });

        return lastSelectedCategory;
    }

    function disableNavLinks() {
        $('.ad-post-tabs button').each(function () {
            if (!$(this).hasClass('active')) {
                $(this).prop('disabled', true);
                $(this).css('color', '#c4c4c4');
            }
        });
    }

    function enableNavLinks() {
        $('.ad-post-tabs button').prop('disabled', false);
        $('.ad-post-tabs button').css('color', '#555555');
    }

    function checkCategorySelection() {
        if ($('#ad_post_category_select').val() === '') {
            disableNavLinks();
            return false;
        } else {
            enableNavLinks();
            return true;
        }
    }

    checkCategorySelection();

    $('.nav-link[data-tab-target]').on('click', function (event) {
        if (!checkCategorySelection()) {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    if ($("#adforest-ad-post-form").length > 0) {
        $("#adforest-ad-post-form")
            .parsley()
            .on("field:validated", function () {
                const $field = $(this.$element);
                const $tab = $field.closest('.tab-pane');
                const tabId = $tab.attr('id');
                const $navLink = $('[data-bs-target="#' + tabId + '"]');

                if (this.validationResult !== true) {
                    $navLink.addClass('has-warning');
                } else {
                    $navLink.removeClass('has-warning');
                }
            })
            .on("form:error", function () {
                $(".ad_errors").show();
                $(".parsley-errors-list").show();

                const errorMessages = [];
                const tabsWithErrors = [];

                $(".tab-pane").each(function () {
                    const $tab = $(this);
                    const tabId = $tab.attr('id');
                    const $navLink = $('[data-bs-target="#' + tabId + '"]');
                    const tabTitle = $navLink.text().trim() || 'Tab';

                    if ($tab.find('.parsley-error').length > 0) {
                        $navLink.addClass('has-warning');
                        tabsWithErrors.push(tabTitle);

                        $tab.find('.parsley-error').each(function () {
                            const $errorField = $(this);
                            const fieldLabel = $errorField.closest('.form-group, .label-box').find('label').text().trim();

                            $errorField.find('.parsley-errors-list li').each(function () {
                                const errorText = $(this).text().trim();
                                if (errorText && fieldLabel) {
                                    errorMessages.push(`${fieldLabel}: ${errorText}`);
                                } else if (errorText) {
                                    errorMessages.push(errorText);
                                }
                            });
                        });
                    }
                });

                if (tabsWithErrors.length > 0) {
                    let toastrTitle = sb_options.form_validation_error;
                    let toastrMessage = "";

                    if (tabsWithErrors.length === 1) {
                        toastrTitle = `${sb_options.error_in} ${tabsWithErrors[0]}`;

                        if (errorMessages.length > 0) {
                            if (errorMessages.length === 1) {
                                toastrMessage = errorMessages[0];
                            } else if (errorMessages.length <= 3) {
                                toastrMessage = errorMessages.join('<br>');
                            } else {
                                toastrMessage = `${errorMessages.length} ${sb_options.fields_need_attention}`;
                            }
                        } else {
                            toastrMessage = sb_options.fill_all_fields;
                        }
                    } else {
                        toastrTitle = sb_options.multiple_validation_errors;
                        toastrMessage = `${sb_options.check_following_tabs}: ${tabsWithErrors.join(', ')}`;
                    }

                    toastr.error(
                        toastrMessage,
                        toastrTitle,
                        {
                            timeOut: 8000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                            escapeHtml: false,
                            progressBar: true
                        }
                    );

                    if (tabsWithErrors.length > 0) {
                        const firstErrorTabId = $(".tab-pane").filter(function () {
                            return $(this).find('.parsley-error').length > 0;
                        }).first().attr('id');

                        if (firstErrorTabId) {
                            const $firstErrorNavLink = $('[data-bs-target="#' + firstErrorTabId + '"]');
                            if ($firstErrorNavLink.length > 0) {
                                setTimeout(() => {
                                    $firstErrorNavLink.tab('show');

                                    const $firstErrorField = $('#' + firstErrorTabId).find('.parsley-error').first();
                                    if ($firstErrorField.length > 0) {
                                        $('html, body').animate({
                                            scrollTop: $firstErrorField.offset().top - 100
                                        }, 500);
                                    }
                                }, 300);
                            }
                        }
                    }
                } else {
                    toastr.error(
                        "Please fill in all required fields correctly",
                        "Form Validation Error",
                        {
                            timeOut: 5000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        }
                    );
                }
            })

        function getFormValidationErrors() {
            const errors = [];
            const tabErrors = {};

            $("#adforest-ad-post-form").find('.parsley-error').each(function () {
                const $field = $(this);
                const $tab = $field.closest('.tab-pane');
                const tabId = $tab.attr('id');
                const tabTitle = $('[data-bs-target="#' + tabId + '"]').text().trim() || 'Unknown Tab';
                const fieldName = $field.attr('name') || $field.attr('id') || 'Unknown Field';
                const fieldLabel = $field.closest('.form-group, .label-box').find('label').text().trim() || fieldName;

                if (!tabErrors[tabTitle]) {
                    tabErrors[tabTitle] = [];
                }

                $field.find('.parsley-errors-list li').each(function () {
                    const errorText = $(this).text().trim();
                    if (errorText) {
                        tabErrors[tabTitle].push({
                            field: fieldLabel,
                            error: errorText
                        });
                    }
                });
            });

            return tabErrors;
        }

        function showCustomValidationToastr() {
            const errors = getFormValidationErrors();
            const tabNames = Object.keys(errors);

            if (tabNames.length === 0) {
                return;
            }

            if (tabNames.length === 1) {
                const tabName = tabNames[0];
                const tabErrors = errors[tabName];

                let message = '';
                if (tabErrors.length === 1) {
                    message = `${tabErrors[0].field}: ${tabErrors[0].error}`;
                } else if (tabErrors.length <= 3) {
                    message = tabErrors.map(err => `${err.field}: ${err.error}`).join('<br>');
                } else {
                    message = `${tabErrors.length} fields in ${tabName} need attention`;
                }

                toastr.error(message, `Error in ${tabName}`, {
                    timeOut: 8000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                    escapeHtml: false,
                    progressBar: true
                });
            } else {
                toastr.error(
                    `Please check the following sections: ${tabNames.join(', ')}`,
                    "Multiple Validation Errors",
                    {
                        timeOut: 8000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                        progressBar: true
                    }
                );
            }
        }

        $('#ad_country').on('change', function () {
            $(this).parsley().validate();
        });

        $("#adforest-ad-post-form").on('submit', function (event) {
            event.preventDefault();
            if (isImageRequired == 1 && uploadedImages === 0) {
                toastr.error(sb_options.images_required_error, "", {
                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                });
                return;
            }
            if (!checkCategorySelection()) {
                return;
            } else {
                loading.show();
                $.post(ajaxurl, {
                    action: "sb_ad_posting",
                    security: $("#adforest-post-token").val(),
                    sb_data: $("form#adforest-ad-post-form").serialize(),
                    is_update: $("#is_update").val(),
                }).done(function (response) {
                    loading.hide();
                    if (response.trim() == "0") {
                        toastr.error(sb_options.not_logged_in, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    } else if (response.trim() == "1") {
                        toastr.error($("#ad_limit_msg").val(), "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                        // window.location = $("#adforest_packages_page").val();
                    } else if (response.trim() == "img_req") {
                        toastr.error(sb_options.required_images, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    } else if (response.trim() == "2") {
                        toastr.error(sb_options.demo_mode, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    } else if (response.trim() == "10") {
                        toastr.error(sb_options.select_package, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    } else if (response.trim() == "11") {
                        toastr.error(sb_options.val_not_found, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    } else if (response.trim() == "no_product_pay_per_post") {
                        toastr.error(sb_options.pay_per_post_option_no_products, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    } else {
                        if ($("#is_update").val() != "undefined" && $("#is_update").val() != "") {
                            toastr.success(sb_options.ad_update_success, "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        } else {
                            toastr.success(sb_options.ad_posted, "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        }
                        window.location = response;
                    }
                })
                    .fail(function () {
                        loading.hide();
                        // toastr.error($("#_nonce_error").val(), "", {
                        toastr.error(sb_options._nonce_error, "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    });
                return false;
            }
        })
    }

    $('.toggle-contact-number').on('click', function () {
        loading.show();
        let $this = $(this);
        let adId = $this.data('ad-id');
        let $phoneNumberSpan = $this.siblings('.phone-number');
        let $phoneNumberSpan2 = $this.siblings('.style_2_ph');

        $.post(ajaxurl, {
            action: "sb_display_phone_num_user", ad_id: adId, security: sb_options.sb_display_phone_num_secure,
        }).done(function (response) {
            response = response.trim();
            let res = response.split("|");
            if (res[0] === '1') {
                $phoneNumberSpan.html(res[1]);
                $phoneNumberSpan2.html(res[1]);
                $this.css('pointer-events', 'none').css('opacity', '0.5');
            } else {
                toastr.error(res[1], "", {
                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                });
            }
            loading.hide();
        });
    });

    $('.click-to-view_ph').on('click', function () {
        loading.show();
        let user_id = jQuery(this).data("user_id");
        let nonce = jQuery(this).data("nonce");
        let $phoneNumber = $(this).siblings('.phone-number');

        $.post(ajaxurl, {
            action: "sb_display_phone_num_profile",
            user_id: user_id,
            nonce: nonce
        }).done(function (response) {
            loading.hide();
            let get_r = response.split("|");
            if (get_r[0].trim() == "1") {
                $phoneNumber.show();
                $phoneNumber.html(get_r[1]);
                $phoneNumber.is(':visible') ? $('.click-to-view_ph').hide() : $('.click-to-view_ph').show();
            } else {
                toastr.error(get_r[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
            }
        }).fail(function (response) {
            loading.hide();
            toastr.error("You need to login first.", "Request failed", {
                timeOut: 4000,
                closeButton: true,
                positionClass: "toast-top-right",
            });
        });
    });

    /* ============================
     * Pay Per Post ad Post adforest
     ==============================*/
    if ($("#pay_per_post_ad_form").length > 0) {
        $("#pay_per_post_ad_form").on("submit", function (e) {
            e.preventDefault();
            var package_id = $('input[name="package"]:checked').val();
            var pid = $("#pid").val();

            $("#sb_loading").show();
            $.post(ajaxurl, {
                action: "pay_per_post_ad_posting",
                security: $("#sb-post-ppp-token").val(),
                package_id: package_id,
                pay_per_post_id: pid,
            }).done(function (response) {

                if (true === response.success) {
                    $("#sb_loading").show();
                    toastr.success(response.data.message, "", {
                        timeOut: 8000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                        showMethod: "slideDown",
                        hideMethod: "slideUp",
                    });
                    if (response.data.url) {
                        location.replace(response.data.url);
                    } else {
                        setTimeout(function () {
                            location.reload(true);
                            $("#sb_loading").show();
                        }, 600);
                    }
                } else {
                    $(".loader-outer").hide();
                    toastr.error(response.data.message, "", {
                        timeOut: 8000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                        showMethod: "slideDown",
                        hideMethod: "slideUp",
                    });
                }
            });
        });
    }

    /* ============================
     * Feature ad Pad adforest
     ==============================*/
    if ($("#feature_ad_form").length > 0) {
        $("#feature_ad_form").on("submit", function (e) {
            event.preventDefault();
            var package = $('input[name="package"]:checked').val();
            var pid = $("#pid").val();

            $("#sb_loading").show();
            $.post(ajaxurl, {
                action: "feature_ad_posting",
                security: $("#sb-post-feature-token").val(),
                package_id: package,
                featured_id: pid,
            }).done(function (response) {
                if (true === response.success) {
                    $("#sb_loading").show();
                    toastr.success(response.data.message, "", {
                        timeOut: 8000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                        showMethod: "slideDown",
                        hideMethod: "slideUp",
                    });
                    if (response.data.url) {
                        location.replace(response.data.url);
                    } else {
                        setTimeout(function () {
                            location.reload(true);
                            $("#sb_loading").show();
                        }, 600);
                    }
                } else {
                    $(".loader-outer").hide();
                    toastr.error(response.data.message, "", {
                        timeOut: 8000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                        showMethod: "slideDown",
                        hideMethod: "slideUp",
                    });
                }
            });
        });
    }

    /* ============================
     * Bump up ad Pade adforest
     ==============================*/
    if ($("#bumup_ad_form").length > 0) {
        $("#bumup_ad_form").on("submit", function (e) {
            event.preventDefault();
            var package = $('input[name="package"]:checked').val();
            var pid = $("#pid").val();

            $("#sb_loading").show();
            $.post(ajaxurl, {
                action: "bumup_ad_posting",
                security: $("#sb-post-bumpup-token").val(),
                package_id: package,
                bump_up_id: pid,
            }).done(function (response) {

                if (true === response.success) {
                    $("#sb_loading").show();
                    toastr.success(response.data.message, "", {
                        timeOut: 8000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                        showMethod: "slideDown",
                        hideMethod: "slideUp",
                    });
                    if (response.data.url) {
                        location.replace(response.data.url);
                    } else {
                        setTimeout(function () {
                            location.reload(true);
                            $("#sb_loading").show();
                        }, 600);
                    }
                } else {
                    $(".loader-outer").hide();
                    toastr.error(response.data.message, "", {
                        timeOut: 8000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                        showMethod: "slideDown",
                        hideMethod: "slideUp",
                    });
                }
            });
        });
    }

    function adforest_dropzone_image() {
        if ($("#img_dropzone").get(0).dropzone) return;

        if ($("#img_dropzone").length) {
            Dropzone.autoDiscover = false;
            let is_update_val = $("#is_update").val();
            const appendUploadNonce = (url) => uploadImagesNonce ? `${url}&nonce=${uploadImagesNonce}` : url;
            let images_ajax_callback = appendUploadNonce(`${ajaxurl}?action=upload_ad_images&is_update=${is_update_val}`);

            if (ajaxurl.indexOf("?lang=") !== -1) {
                images_ajax_callback = appendUploadNonce(`${ajaxurl}?action=upload_ad_images&is_update=${is_update_val}`);
            }

            let acceptedFileTypes = "image/jpeg,image/png,image/jpg";
            let i = 0;
            let sb_max_files = typeof $("#sb_upload_limit").val() !== "undefined" && $("#sb_upload_limit").val() === "null" ? null : $("#sb_upload_limit").val();

            // Check if we're in paid posting mode and have selected package data
            if (typeof sb_options !== 'undefined' && sb_options.ad_posting_mode === 'paid_post' && selectedPackageData && selectedPackageData.num_of_images) {
                console.log("Applying package image limit during dropzone initialization:", selectedPackageData.num_of_images);
                if (selectedPackageData.num_of_images === '-1' || selectedPackageData.num_of_images === -1) {
                    sb_max_files = null; // Unlimited
                } else {
                    sb_max_files = parseInt(selectedPackageData.num_of_images);
                }
            }

            $("#img_dropzone").dropzone({
                timeout: 5000000,
                maxFilesize: 50000000,
                addRemoveLinks: true,
                paramName: "my_file_upload",
                maxFiles: sb_max_files,
                acceptedFiles: acceptedFileTypes,
                dictMaxFilesExceeded: $("#adforest_max_upload_reach").val(),
                url: images_ajax_callback,
                parallelUploads: 1,
                dictDefaultMessage: $("#dictDefaultMessage").val(),
                dictFallbackMessage: $("#dictFallbackMessage").val(),
                dictFallbackText: $("#dictFallbackText").val(),
                dictFileTooBig: $("#dictFileTooBig").val(),
                dictInvalidFileType: $("#dictInvalidFileType").val(),
                dictResponseError: $("#dictResponseError").val(),
                dictCancelUpload: $("#dictCancelUpload").val(),
                dictCancelUploadConfirmation: $("#dictCancelUploadConfirmation").val(),
                dictRemoveFile: $("#dictRemoveFile").val(),
                dictRemoveFileConfirmation: null,
                init: function () {
                    let thisDropzone = this;


                    $.post(ajaxurl, {
                        action: "get_uploaded_ad_images", is_update: is_update_val, security: $('#adforest_get_uploaded_ad_images_nonce').val(),
                    }).done(function (data) {
                        if (data && typeof data === "object" && Object.keys(data).length > 0) {
                            $.each(data, function (key, value) {
                                let mockFile = { name: value.dispaly_name, size: value.size };
                                thisDropzone.options.addedfile.call(thisDropzone, mockFile);
                                thisDropzone.options.thumbnail.call(thisDropzone, mockFile, value.name);
                                $("a.dz-remove:eq(" + i + ")").attr("data-dz-remove", value.id);
                                i++;
                                uploadedImages++;
                                $(".dz-progress").remove();
                            });
                        }
                        if (i > 0) $(".dz-message").hide(); else $(".dz-message").show();
                    });

                    this.on("addedfile", function () {
                        uploadedImages++;
                        $(".dz-message").hide();
                    });

                    this.on("removedfile", function (file) {
                        let img_id = file._removeLink.attributes[2].value;
                        if (img_id !== "") {
                            uploadedImages--; // Decrease the image count
                            if (i === 0) $(".dz-message").show();
                            $.post(ajaxurl, {
                                action: "delete_ad_image", img: img_id, is_update: is_update_val, security: $('#adforest_delete_ad_image_nonce').val(),
                            }).done(function (response) {
                                if (response.trim() === "1") {
                                    toastr.success(sb_options.image_removed_successfully, "", {
                                        timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                    });
                                }
                            });
                        }
                    });

                    this.on("success", function (file, responseText) {
                        let res_arr = responseText.split("|");
                        if (res_arr[0].trim() !== "0") {
                            $("a.dz-remove:eq(" + i + ")").attr("data-dz-remove", responseText);
                            i++;
                            $(".dz-message").hide();
                        } else {
                            if (i === 0) $(".dz-message").show();
                            this.removeFile(file);
                            toastr.error(res_arr[1], "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        }
                    });

                    this.on("maxfilesexceeded", function (file) {
                        if (typeof sb_options !== 'undefined' && sb_options.ad_posting_mode === 'paid_post' && selectedPackageData && selectedPackageData.num_of_images) {
                            toastr.error(sb_options.max_upload_images_allowed_pkg + " " + selectedPackageData.num_of_images, "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        } else {
                            toastr.error($("#max_upload_images").val(), "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        }
                        this.removeFile(file);
                    });
                }
            });

            // Apply package image limit after dropzone initialization if needed
            if (typeof sb_options !== 'undefined' && sb_options.ad_posting_mode === 'paid_post' && selectedPackageData && selectedPackageData.num_of_images) {
                console.log("Applying package image limit after dropzone initialization");
                updateDropzoneMaxFiles(selectedPackageData.num_of_images);
            }
        }
    }

    function adforest_dropzone_video() {
        if ($("#dropzone_video").get(0).dropzone) return;

        if ($("#dropzone_video").length) {
            let videoLogoUrl = $("#video_logo_url").val();
            Dropzone.autoDiscover = false;
            let video_ajax_callback =
                ajaxurl +
                "?action=upload_ad_videos&is_update=" +
                $("#is_update").val() +
                "&security=" +
                $("#sb_upload_ad_videos_nonce").val();
            if (ajaxurl.indexOf("?lang=") !== -1) {
                video_ajax_callback =
                    ajaxurl +
                    "&action=upload_ad_videos&is_update=" +
                    $("#is_update").val() +
                    "&security=" +
                    $("#sb_upload_ad_videos_nonce").val();
            }
            let acceptedFileTypes = "video/mp4,video/ogg,video/webm";
            let i = 0;
            let sb_max_files =
                typeof $("#sb_upload_video_limit").val() !== "undefined" &&
                    $("#sb_upload_video_limit").val() === "null"
                    ? null
                    : $("#sb_upload_video_limit").val();

            $("#dropzone_video").dropzone({
                timeout: 5000000,
                maxFilesize: 50000000,
                addRemoveLinks: true,
                paramName: "my_single_video_upload",
                maxFiles: sb_max_files,
                acceptedFiles: acceptedFileTypes,
                dictMaxFilesExceeded: $("#adforest_max_upload_reach").val(),
                url: video_ajax_callback,
                parallelUploads: 1,
                dictDefaultMessage: $("#dictDefaultMessage").val(),
                dictFallbackMessage: $("#dictFallbackMessage").val(),
                dictFallbackText: $("#dictFallbackText").val(),
                dictFileTooBig: $("#dictFileTooBig").val(),
                dictInvalidFileType: $("#dictInvalidFileType").val(),
                dictResponseError: $("#dictResponseError").val(),
                dictCancelUpload: $("#dictCancelUpload").val(),
                dictCancelUploadConfirmation: $("#dictCancelUploadConfirmation").val(),
                dictRemoveFile: $("#dictRemoveFile").val(),
                dictRemoveFileConfirmation: null,
                init: function () {
                    let thisDropzone = this;

                    // Fetch previously uploaded Videos if any
                    $.post(ajaxurl, {
                        action: "get_uploaded_video",
                        is_update: $("#is_update").val(),
                        security: $("#sb_get_uploaded_video_nonce").val(),
                    }).done(function (data) {
                        if (typeof data !== "undefined" && data !== 0) {
                            $.each(data, function (key, value) {
                                let mockFile = {
                                    name: value.video_name,
                                    size: value.video_size,
                                };
                                thisDropzone.options.addedfile.call(thisDropzone, mockFile);
                                thisDropzone.options.thumbnail.call(
                                    thisDropzone,
                                    mockFile,
                                    videoLogoUrl
                                );
                                $("a.dz-remove:eq(" + i + ")").attr(
                                    "data-dz-remove",
                                    value.video_id
                                );
                                i++;
                                $(".dz-progress").remove();
                            });
                        }
                        if (i > 0) $(".dz-message").hide();
                        else $(".dz-message").show();
                    });

                    this.on("addedfile", function (file) {
                        $(".dz-message").hide();
                    });
                    this.on("success", function (file, responseText) {
                        let res_arr = responseText.split("|");
                        if (res_arr[0].trim() !== "0") {
                            $("a.dz-remove:eq(" + i + ")").attr(
                                "data-dz-remove",
                                responseText
                            );
                            i++;
                            $(".dz-message").hide();
                        } else {
                            if (i === 0) $(".dz-message").show();
                            this.removeFile(file);
                            toastr.error(res_arr[1], "", {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: "toast-top-right",
                            });
                        }
                    });
                    this.on("removedfile", function (file) {
                        let img_id = file._removeLink.attributes[2].value;
                        if (img_id !== "") {
                            i--;
                            if (i === 0) $(".dz-message").show();
                            $.post(ajaxurl, {
                                action: "delete_upload_video",
                                video: img_id,
                                is_update: $("#is_update").val(),
                                security: $("#sb_delete_upload_video_nonce").val(),
                            }).done(function (response) {
                                if (response.trim() === "1") {
                                    /*this.removeFile(file);*/
                                }
                            });
                        }
                    });
                    this.on("maxfilesexceeded", function (file) {
                        toastr.error($("#adforest_max_upload_reach").val(), "", {
                            timeOut: 4000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        });
                        this.removeFile(file);
                    });
                },
            });
        }
    }

    /* ad post on create ad ID for ad title */
    $("#ad_title").on("blur", function () {
        if ($("#is_update").val() === "") {
            const createAdIdTitle = $("#sb_create_ad_id_for_title_nonce").val() || "";
            console.log("Nonce: ", createAdIdTitle)
            $.post(ajaxurl, {
                action: "create_ad_id_for_title",
                title: $("#ad_title").val(),
                is_update: $("#is_update").val(),
                nonce: createAdIdTitle
            }).done(function (response) {
            });
        }
    });

    $(".make_feature_admin_unlimited").on("click", function () {
        let adId = $(this).attr('data-ad-id');
        loading.show();
        $.post(ajaxurl, {
            action: "make_ad_featured_admin",
            ad_id: adId,
            security: $('#adforest_make_ad_featured_admin_nonce').val(),
        }).done(function (response) {
            loading.hide();
            if (response.success) {
                toastr.success(response.data.message || "Ad marked as featured successfully!");
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                toastr.error(response.data.message || "Something went wrong.");
            }
        }).fail(function () {
            loading.hide();
            toastr.error("AJAX request failed.");
        });
    });

    /* ad post on create ad ID for ad title */

    function adforest_inputTags() {
        let total_tags = $("#tags_count").val();
        if (typeof total_tags === "undefined" || total_tags == "") {
            total_tags = 0;
        }
        if ($("#tags").length !== "undefined" && $("#tags").length > 0) {
            $("#tags").tagsInput({
                width: "100%", height: "5px;", defaultText: "", onAddTag: function (elem, elem_tags) {
                    total_tags = parseInt(total_tags) + 1;
                    if (total_tags > sb_options.adforest_tags_limit_val) {
                        alert(sb_options.adforest_tags_limit);
                        $(this).removeTag(elem);
                    }
                }, onRemoveTag: function () {
                    total_tags = parseInt(total_tags) - 1;
                },
            });
        }
        if ($("#is_update").val() == "" && $(".dynamic-form-date-fields").length > 0) {
            if ($(".dynamic-form-date-fields").length > 0) {
                $(".dynamic-form-date-fields").datepicker({
                    timepicker: false, dateFormat: "yyyy-mm-dd", language: {
                        days: [get_strings.Sunday, get_strings.Monday, get_strings.Tuesday, get_strings.Wednesday, get_strings.Thursday, get_strings.Friday, get_strings.Saturday,],
                        daysShort: [get_strings.Sun, get_strings.Mon, get_strings.Tue, get_strings.Wed, get_strings.Thu, get_strings.Fri, get_strings.Sat,],
                        daysMin: [get_strings.Su, get_strings.Mo, get_strings.Tu, get_strings.We, get_strings.Th, get_strings.Fr, get_strings.Sa,],
                        months: [get_strings.January, get_strings.February, get_strings.March, get_strings.April, get_strings.May, get_strings.June, get_strings.July, get_strings.August, get_strings.September, get_strings.October, get_strings.November, get_strings.December,],
                        monthsShort: [get_strings.Jan, get_strings.Feb, get_strings.Mar, get_strings.Apr, get_strings.May, get_strings.Jun, get_strings.Jul, get_strings.Aug, get_strings.Sep, get_strings.Oct, get_strings.Nov, get_strings.Dec,],
                        today: get_strings.Today,
                        clear: get_strings.Clear,
                        dateFormat: "mm/dd/yyyy",
                    },
                });
            }
        }
    }

    $("#ad_bidding").on("change", function () {
        if ($("#ad_bidding").val() == "1") {
            $(".biddind_div").show();
        } else {
            $(".biddind_div").hide();
        }
    });
    $("#ad_bidding_date").datepicker({
        timepicker: true,
        dateFormat: "yy-mm-dd",
        timeFormat: "hh:ii:00",
        language: {
            days: [
                sb_options.Sunday,
                sb_options.Monday,
                sb_options.Tuesday,
                sb_options.Wednesday,
                sb_options.Thursday,
                sb_options.Friday,
                sb_options.Saturday,
            ],
            daysShort: [
                sb_options.Sun,
                sb_options.Mon,
                sb_options.Tue,
                sb_options.Wed,
                sb_options.Thu,
                sb_options.Fri,
                sb_options.Sat,
            ],
            daysMin: [
                sb_options.Su,
                sb_options.Mo,
                sb_options.Tu,
                sb_options.We,
                sb_options.Th,
                sb_options.Fr,
                sb_options.Sa,
            ],
            months: [
                sb_options.January,
                sb_options.February,
                sb_options.March,
                sb_options.April,
                sb_options.May,
                sb_options.June,
                sb_options.July,
                sb_options.August,
                sb_options.September,
                sb_options.October,
                sb_options.November,
                sb_options.December,
            ],
            monthsShort: [
                sb_options.Jan,
                sb_options.Feb,
                sb_options.Mar,
                sb_options.Apr,
                sb_options.May,
                sb_options.Jun,
                sb_options.Jul,
                sb_options.Aug,
                sb_options.Sep,
                sb_options.Oct,
                sb_options.Nov,
                sb_options.Dec,
            ],
            today: sb_options.Today,
            clear: sb_options.Clear,
            firstDay: 0,
        },
        minDate: new Date(),
    });

    /* Ad Location */
    if ($("#ad_lat").length > 0) {
        let lat = $("#ad_lat").val();
        let lon = $("#ad_lon").val();
        let map_type = sb_options.adforest_map_type;
        if (map_type === "leafletjs_map") {
            /*For leafletjs map*/
            let map = L.map("itemMap", {zoomSnap: 0.25, zoomDelta: 0.5, wheelDebounceTime: 80}).setView([lat, lon], 7);
            // Two-layer CARTO basemap: terrain WITHOUT labels at the bottom,
            // transparent labels-only tiles on a separate pane above markers.
            // (Previously stacked OSM-standard on top of voyager-with-labels —
            // two full basemaps overlapped, producing washed-out tiles and
            // doubled text. Both layers must be transparent-compatible.)
            L.tileLayer("https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png", {
                maxZoom: 19,
                detectRetina: true,
                updateWhenZooming: false,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>'
            }).addTo(map);
            map.createPane('labels');
            map.getPane('labels').style.zIndex = 450;
            map.getPane('labels').style.pointerEvents = 'none';
            L.tileLayer("https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png", {
                maxZoom: 19, detectRetina: true, updateWhenZooming: false, pane: 'labels', minZoom: 5
            }).addTo(map);
            var modernIcon = L.divIcon({className: 'leaflet-modern-marker', iconSize: [32, 42], iconAnchor: [16, 42], popupAnchor: [0, -42]});
            L.marker([lat, lon], {icon: modernIcon}).addTo(map);
        } else if (map_type === "google_map") {
            /*For Google Map*/
            waitForGoogleMaps(function () {
                let map = "";
                let latlng = new google.maps.LatLng(lat, lon);
                let myOptions = {
                    zoom: 13,
                    center: latlng,
                    scrollwheel: false,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    size: new google.maps.Size(480, 240),
                };
                map = new google.maps.Map(document.getElementById("itemMap"), myOptions);
                let marker = new google.maps.Marker({
                    map: map, position: latlng,
                });
            });
        } else if (map_type === "map_box") {
            mapboxgl.accessToken = sb_options.map_box_token;
            let map = new mapboxgl.Map({
                container: "itemMap",
                style: "mapbox://styles/mapbox/streets-v11",
                center: [lon, lat],
                zoom: 9,
            });
            new mapboxgl.Marker().setLngLat([lon, lat]).addTo(map);
        }
    }

    $(".sb_show_pass").on("click", function (event) {
        event.preventDefault();

        var passwordField = $("input[name='sb_reg_password']");
        var iconField = $(".sb_show_pass i");
        if (passwordField.attr("type") == "text") {
            passwordField.attr("type", "password");
            iconField.addClass("fa-eye");
            iconField.removeClass("fa-eye-slash");
        } else if (passwordField.attr("type") == "password") {
            passwordField.attr("type", "text");
            iconField.removeClass("fa-eye");
            iconField.addClass("fa-eye-slash");
        }
    });

    $(".sb_show_pass2").on("click", function (event) {
        event.preventDefault();

        let passwordField = $("input[name='sb_reg_password_confirm']");
        let iconField = $(".sb_show_pass2 i");
        if (passwordField.attr("type") == "text") {
            passwordField.attr("type", "password");
            iconField.addClass("fa-eye");
            iconField.removeClass("fa-eye-slash");
        } else if (passwordField.attr("type") == "password") {
            passwordField.attr("type", "text");
            iconField.removeClass("fa-eye");
            iconField.addClass("fa-eye-slash");
        }
    });

    if ($("#file_attacher").length > 0) {
        let attachmentsDropzone = new Dropzone(document.getElementById("file_attacher"), {
            url: ajaxurl,
            autoProcessQueue: true,
            previewsContainer: "#attachment-wrapper", // Define the container to display the previews
            previewTemplate: '<span class="dz-preview dz-file-preview"><span class="dz-details"><span class="dz-filename"><i class="fa fa-link"></i>   <span data-dz-name></span></span>   <span class="dz-size" data-dz-size></span>   <i class="fa fa-times" style="cursor:pointer;font-size:15px;" data-dz-remove></i></span><span class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></span><i class="ti ti-refresh ti-spin"></i></span>',
            clickable: "a.msgAttachFile",
            acceptedFiles: $("#provided_format").val(),
            maxFilesize: 15,
            maxFiles: 4,
        });

        attachmentsDropzone.on("sending", function () {
            $("#send_msg ,#send_ad_message").attr("disabled", true);
        });
        attachmentsDropzone.on("queuecomplete", function () {
            $("#send_msg, #send_ad_message").attr("disabled", false);
        });
    }

    /* Bidding System  */
    if ($("#sb_bid_ad").length > 0) {
        $("#sb_bid_ad")
            .parsley()
            .on("field:validated", function () {
                let ok = $(".parsley-error").length === 0;
            });

        $("#sb_bid_ad").on("submit", function (event) {
            event.preventDefault();
            let form = $(this);
            loading.show();
            $.post(ajaxurl, {
                action: "sb_submit_bid", security: $("#sb-bidding-token").val(), sb_data: form.serialize(),
            })
                .done(function (response) {
                    loading.hide();
                    let res_arr = response.split("|");
                    if (res_arr[0].trim() != "0") {
                        toastr.success(res_arr[1], "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                        location.reload();
                    } else {
                        toastr.error(res_arr[1], "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    }
                })
                .fail(function () {
                    loading.hide();
                    toastr.error($("#_nonce_error").val(), "", {
                        timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                    });
                });
            return false;
        });
    }

    if ($("#input-21b").length > 0) {
        let star_rtl = false;
        if ($("#is_rtl").val() != "" && $("#is_rtl").val() == "1") {
            star_rtl = true;
        }
        $("#input-21b").rating({
            filledStar: '<i class="fill fas fa-star"></i>', emptyStar: '<i class="far fa-star"></i>', starCaptions: {
                1: sb_options.one, 2: sb_options.two, 3: sb_options.three, 4: sb_options.four, 5: sb_options.five,
            },
        });
    }

    $('.chat_toggler_popup').on('click', () => {
        let message = $('.chat_toggler_popup').attr('data-pt-title');
        if (typeof message !== "undefined") toastr.error(message, "", {
            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
        });
    })
    /*Send message to ad owner*/
    if ($("#send_message_pop").length > 0) {
        // Custom validation for message field - allows empty message if files are attached
        $("#send_message_pop").parsley().addValidator("messageOrFile", {
            requirementType: "boolean",
            validate: function (value, requirement) {
                var hasFiles = false;
                if ($("#file_attacher").length > 0) {
                    var fileUpload = $("#file_attacher").get(0).dropzone;
                    if (fileUpload !== undefined && fileUpload.files.length > 0) {
                        hasFiles = true;
                    }
                }
                return value.trim() !== "" || hasFiles;
            },
            messages: {
                en: "Message content or file attachment is required."
            }
        });

        $("#send_message_pop")
            .parsley()
            .on("field:validated", function () {
            })

        $("#send_message_pop").on("submit", function (event) {
            event.preventDefault();
            let form = $(this);
            loading.show();
            let fd = new FormData();
            if ($("#file_attacher").length > 0) {
                let fileUpload = $("#file_attacher").get(0).dropzone;
                let files = fileUpload.files;
                for (let i = 0; i < files.length; i++) {
                    fd.append("message_file[]", files[i]);
                }
            }
            let sb_data = form.serialize();
            let security = $("#sb-msg-token").val();
            fd.append("action", "sb_send_message");
            fd.append("sb_data", sb_data);
            fd.append("security", security);
            loading.show();
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: fd,
                contentType: false,
                processData: false,
                success: function (response) {
                    loading.hide();
                    let get_r = response.split("|");
                    if (get_r[0].trim() == "1") {
                        toastr.success(get_r[1], "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                        $("#sb_forest_message").val("");

                        if ($(".dz-preview").length > 0) {
                            Dropzone.forElement("#file_attacher").removeAllFiles(true);
                            $(".dz-preview").remove();
                            $(".dz-success").fadeOut("slow");
                        }
                        $(".close").trigger("click");
                    } else {
                        toastr.error(get_r[1], "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                        $(".close").trigger("click");
                        []
                    }
                },
                error: function () {
                    loading.hide();
                    toastr.error($("#_nonce_error").val(), "", {
                        timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                    });
                },
            });

            return false;
        });
    }

    /* Ad rating Logic */
    if ($("#ad_rating_form").length > 0) {
        $("#ad_rating_form").parsley().on("field:validated", function () {
            // Validation logic
        });
        $("#ad_rating_form").on("submit", function (event) {
            event.preventDefault();
            loading.show();
            let form = $(this);
            let fileInput = document.getElementById("files");
            let formSerialized = form.serialize();
            let formData = new FormData();
            let security = $("#sb_ad_rating_nonce").val();
            formData.append("formdata", formSerialized);
            formData.append("action", "sb_ad_rating");
            formData.append("security", security);
            if (fileInput !== null && fileInput.files.length > 0) {
                fileInput = $("#files")[0].files[0];
                formData.append("file", fileInput);
            }
            // Use Parsley.js to prevent form submission
            let parsleyForm = form.parsley();
            if (parsleyForm.isValid()) {
                $.post({
                    url: ajaxurl, type: "POST", data: formData, processData: false, contentType: false,
                })
                    .done(function (response) {
                        loading.hide();
                        let get_r = response.split("|");
                        if (get_r[0].trim() == "1") {
                            toastr.success(get_r[1], "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                            location.reload();
                        } else {
                            toastr.error(get_r[1], "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        }
                    })
                    .fail(function () {
                        loading.hide();
                        toastr.error($("#_nonce_error").val(), "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    });
            }
            return false; // Prevent default behavior
        });
    }

    $(".reply_ad_rating").on("click", function () {
        var p_comment_id = $(this).attr("data-comment_id");
        $("#reply_to_rating").html($(this).attr("data-commenter-name"));
        $("#parent_comment_id").val(p_comment_id);
    });

    /*Send message to ad owner*/
    if ($("#rating_reply_form").length > 0) {
        $("#rating_reply_form").parsley().on("field:validated", function () {
        });
        $("#rating_reply_form").on("submit", function () {
            loading.show();
            let form = $(this);
            $.post(ajaxurl, {
                action: "sb_ad_rating_reply", security: $("#sb-review-reply-token").val(), sb_data: form.serialize(),
            })
                .done(function (response) {
                    loading.hide();
                    var get_r = response.split("|");
                    if (get_r[0].trim() == "1") {
                        toastr.success(get_r[1], "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                        location.reload();
                    } else {
                        toastr.error(get_r[1], "", {
                            timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                        });
                    }
                })
                .fail(function () {
                    loading.hide();
                    toastr.error($("#_nonce_error").val(), "", {
                        timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                    });
                });
            return false;
        });
    }

    /* Emoji Reaction Against Reviews  */
    $(".Emoji").on("click", function () {
        let reaction_id = $(this).data("reaction");
        let c_id = $(this).data("cid");
        $("#reaction-loader-" + c_id).show();
        $.post(ajaxurl, {
            action: "adforest_ads_rating_reaction", r_id: reaction_id, c_id: c_id,
        }).done(function (response) {
            $("#reaction-loader-" + c_id).hide();

            let get_r = response.split("|");
            if (get_r[0].trim() == "0") {
                toastr.error(get_r[1].trim(), "", {
                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                });
                return false;
            } else {
                if (reaction_id === 1) {
                    $(".emoji-count.likes-" + c_id).text(response);
                }
                if (reaction_id === 2) {
                    $(".emoji-count.loves-" + c_id).text(response);
                }
                if (reaction_id === 3) {
                    $(".emoji-count.wows-" + c_id).text(response);
                }
                if (reaction_id === 4) {
                    $(".emoji-count.angrys-" + c_id).text(response);
                }
            }
        });
        return false;
    });

    /*ad ads to favourite (toggle add/remove)*/
    // Tooltip strings (localized via wp_localize_script)
    var adfFavTitles = (typeof adforestFav !== 'undefined') ? adforestFav : {
        titleAdd: 'Click to make it favourite',
        titleRemove: 'Click to remove from favourite'
    };

    // Fix favourite tooltip — updates ALL conflicting attributes (title, data-original-title, aria-label)
    // and force-refreshes Bootstrap 4 / Bootstrap 5 tooltip instances so the cached tooltip text
    // doesn't shadow the new value.
    // Attached to window so it's callable from any other DOM-ready scope in this file.
    window.adfFixFavTooltip = function ($btn) {
        var isFav = $btn.hasClass('ad-favourited') || $btn.find('i').hasClass('fas');
        var localTitles = (typeof adforestFav !== 'undefined') ? adforestFav : (typeof adfFavTitles !== 'undefined' ? adfFavTitles : {
            titleAdd: 'Click to make it favourite',
            titleRemove: 'Click to remove from favourite'
        });
        var text  = isFav ? localTitles.titleRemove : localTitles.titleAdd;

        // Normalize icon class to match detected state (fixes server-rendered mismatches).
        // Also applies/removes text-danger so the red color is set via the Bootstrap utility class
        // (matches the PHP render pattern and the inline click handler contract).
        var $icon = $btn.find('i');
        if ($icon.length) {
            if (isFav) {
                $icon.removeClass('far fa-heart').addClass('fas fa-heart text-danger');
            } else {
                $icon.removeClass('fas fa-heart text-danger').addClass('far fa-heart');
            }
        }

        // Update user-visible / accessibility attributes.
        // IMPORTANT: do NOT set data-original-title ourselves — Bootstrap manages that attribute
        // internally via fixTitle(); setting it manually causes Bootstrap to treat OUR stale value
        // as the "original" and ignore later updates. Clear any stale value so Bootstrap regenerates it.
        $btn.attr('title', text);
        $btn.attr('aria-label', text);
        $btn.removeAttr('data-original-title');
        $btn.removeAttr('data-bs-original-title'); // Bootstrap 5 cache — must clear, otherwise tooltip shows stale text

        // Force Bootstrap tooltip content update (direct instance mutation — the critical fix).
        // Bootstrap caches title in its internal config; just updating DOM attributes isn't enough.
        try {
            // Bootstrap 4 (jQuery data API)
            var bs4 = (typeof $btn.data === 'function') ? $btn.data('bs.tooltip') : null;
            if (bs4) {
                if (bs4.config)  { bs4.config.title  = text; }
                if (bs4.options) { bs4.options.title = text; }
                if (typeof bs4.fixTitle === 'function') { bs4.fixTitle(); }
            }

            // Bootstrap 5 (native API)
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                var bs5 = bootstrap.Tooltip.getInstance($btn.get(0));
                if (bs5 && typeof bs5.setContent === 'function') {
                    bs5.setContent({ '.tooltip-inner': text });
                }
            }
        } catch (e) {}

        // Final safety net: dispose + reinit so the next hover gets a freshly-constructed tooltip.
        // Runs after the in-place update above, guaranteeing correctness even if the instance
        // didn't exist or mutation failed silently.
        if (typeof $btn.tooltip === 'function') {
            try {
                $btn.tooltip('dispose');
                $btn.tooltip({ html: true, trigger: 'hover' });
            } catch (e) {}
        }

        // Bootstrap 5 dispose + reinit
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            try {
                var instance = bootstrap.Tooltip.getInstance($btn.get(0));
                if (instance) instance.dispose();
                new bootstrap.Tooltip($btn.get(0));
            } catch (e) {}
        }
    };

    // Backward-compat alias — callers elsewhere still use the old name
    function adfSetFavTitle($el) { window.adfFixFavTooltip($el); }

    // Initial state: run on every heart in the DOM
    // Covers both '.favourite.ad_to_fav' (grid/list pages) AND plain '.ad_to_fav' (ad detail page)
    $(".ad_to_fav, .save-ad").each(function () { adfFixFavTooltip($(this)); });

    // Safety-net delegated click handler: re-syncs tooltip after AJAX completes.
    // Uses setTimeout so it runs AFTER the direct click handler + AJAX response update the class/icon.
    // .closest() ensures we target the .ad_to_fav anchor even when the click originates on the <i> child.
    $(document).on('click', '.ad_to_fav, .save-ad, .ad_to_fav i, .save-ad i', function () {
        var $btn = $(this).closest('.ad_to_fav, .save-ad');
        if (!$btn.length) { return; }
        setTimeout(function () {
            if (typeof window.adfFixFavTooltip === 'function') {
                window.adfFixFavTooltip($btn);
            }
        }, 120);
    });

    $(".ad_to_fav,.save-ad").on("click", function () {
        // .closest() guarantees $btn is the .ad_to_fav anchor even if the click target was the <i> icon
        let $btn = $(this).closest('.ad_to_fav, .save-ad');
        if (!$btn.length) { $btn = $(this); }
        loading.show();
        $.post(ajaxurl, {
            action: "sb_fav_ad", ad_id: $btn.attr("data-adid"), security: $("#sb_fav_ad_nonce").val(),
        }).done(function (response) {
            loading.hide();
            let get_p = response.split("|");
            let code = get_p[0].trim();
            let message = get_p[1];
            let toastOpts = { timeOut: 4000, closeButton: true, positionClass: "toast-top-right" };

            // Success codes: "1" = added, "2" = removed. Everything else is an error.
            if (code !== "1" && code !== "2") {
                toastr.error(message, "", toastOpts);
                return;
            }

            // STEP 1: Toggle class + icon based on response code (mutate DOM first, synchronously)
            let $icon = $btn.find("i");
            if (code === "1") {
                $btn.addClass("ad-favourited");
                $icon.removeClass("far fa-heart")
                     .addClass("fas fa-heart text-danger");
            } else {
                $btn.removeClass("ad-favourited");
                $icon.removeClass("fas fa-heart text-danger")
                     .addClass("far fa-heart");
            }

            // Toast fires immediately for snappy UX
            toastr.success(message, "", toastOpts);

            // Authoritative tooltip override — runs 120ms after AJAX success to let
            // every other theme / plugin handler finish its DOM mutations. Uses .bind(this)
            // so the clicked element is preserved; .closest() re-resolves to the .ad_to_fav
            // anchor even if the click landed on the <i> icon.
            setTimeout(function () {

                var $btn = $(this).closest('.ad_to_fav, .save-ad');
                if (!$btn.length) return;

                var $icon = $btn.find('i');

                // STEP 1: Determine FINAL DOM state (single source of truth)
                var isFav = $btn.hasClass("ad-favourited") || $icon.hasClass("fas");

                var newText = isFav
                    ? "Click to remove from favourite"
                    : "Click to make it favourite";

                if (typeof adforestFav !== "undefined") {
                    newText = isFav ? adforestFav.titleRemove : adforestFav.titleAdd;
                }

                // STEP 2: HARD RESET attributes (prevent Bootstrap cache issues)
                $btn.removeAttr("data-original-title");
                $btn.removeAttr("data-bs-original-title"); // Bootstrap 5 cache

                $btn.attr("title", newText);
                $btn.attr("aria-label", newText);

                // STEP 3: DOUBLE OVERRIDE (handles late overwrites by other scripts)
                setTimeout(function () {
                    $btn.removeAttr("data-original-title");
                    $btn.removeAttr("data-bs-original-title"); // Bootstrap 5 cache
                    $btn.attr("title", newText);
                    $btn.attr("aria-label", newText);
                }, 50);

                // STEP 4: Refresh Bootstrap tooltip (BS4 + BS5 safe)
                if (typeof $btn.tooltip === "function") {
                    try {
                        $btn.tooltip("dispose");
                        $btn.tooltip({ html: true });
                    } catch (e) {}
                }

                if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
                    try {
                        var instance = bootstrap.Tooltip.getInstance($btn.get(0));
                        if (instance) instance.dispose();
                        new bootstrap.Tooltip($btn.get(0));
                    } catch (e) {}
                }

                // STEP 5: If user is hovering, force tooltip to show updated text
                if ($btn.is(":hover")) {
                    if (typeof $btn.tooltip === "function") {
                        try { $btn.tooltip("show"); } catch (e) {}
                    }

                    if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
                        try {
                            var inst = bootstrap.Tooltip.getInstance($btn.get(0));
                            if (inst) inst.show();
                        } catch (e) {}
                    }
                }

            }.bind(this), 120);
        });
    });

    /*Report Ad*/
    $("#sb_mark_it").on("click", function () {
        loading.show();
        $.post(ajaxurl, {
            action: "sb_report_ad",
            option: $("#report_option").val(),
            comments: $("#report_comments").val(),
            ad_id: $("#ad_id").val(),
            security: $("#sb_report_ad_nonce").val(),
        }).done(function (response) {
            loading.hide();
            let get_r = response.split("|");
            if (get_r[0].trim() == "1") {
                toastr.success(get_r[1], "", {
                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                });
                $(".report-quote").modal("hide");
            } else {
                toastr.error(get_r[1], "", {
                    timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                });
                $(".report-quote").modal("hide");
            }
        });
    });

    /* Ad Custom Locations */
    $("#ad_country").on("change", function () {
        loading.show();
        $.post(ajaxurl, {
            action: "sb_get_sub_states", country_id: $("#ad_country").val(), security: $("#sb_get_sub_states_nonce").val(),
        }).done(function (response) {
            loading.hide();
            $("#ad_country_states").val("");
            $("#ad_country_cities").val("");
            $("#ad_country_towns").val("");
            if (response.trim() != "") {
                $("#ad_country_id").val($("#ad_cat").val());
                $("#ad_country_sub_div").show();
                $("#ad_country_states").html(response);
                $("#ad_country_sub_sub_sub_div").hide();
                $("#ad_country_sub_sub_div").hide();
            } else {
                $("#ad_country_sub_div").hide();
                $("#ad_cat_sub_sub_div").hide();
                $("#ad_country_sub_sub_div").hide();
                $("#ad_country_sub_sub_sub_div").hide();
            }
        });
    });
    /* Level 2 */
    $("#ad_country_states").on("change", function () {
        loading.show();
        $.post(ajaxurl, {
            action: "sb_get_sub_states", country_id: $("#ad_country_states").val(), security: $("#sb_get_sub_states_nonce").val(),
        }).done(function (response) {
            loading.hide();
            $("#ad_country_cities").val("");
            $("#ad_country_towns").val("");
            if (response.trim() != "") {
                $("#ad_country_id").val($("#ad_country_states").val());
                $("#ad_country_sub_sub_div").show();
                $("#ad_country_cities").html(response);
                $("#ad_country_sub_sub_sub_div").hide();
            } else {
                $("#ad_country_sub_sub_div").hide();
                $("#ad_country_sub_sub_sub_div").hide();
            }
        });
    });
    /* Level 3 */
    $("#ad_country_cities").on("change", function () {
        loading.show();
        $.post(ajaxurl, {
            action: "sb_get_sub_states", country_id: $("#ad_country_cities").val(), security: $("#sb_get_sub_states_nonce").val(),
        }).done(function (response) {
            loading.hide();
            $("#ad_country_towns").val("");
            if (response.trim() != "") {
                $("#ad_country_id").val($("#ad_country_cities").val());
                $("#ad_country_sub_sub_sub_div").show();
                $("#ad_country_towns").html(response);
            } else {
                $("#ad_country_sub_sub_sub_div").hide();
            }
        });
    });
    /* Ad Custom Locations */

    /* Contact from profile  */
    if ($("#user_contact_form").length > 0) {
        var userContactNonce = $("#sb_user_contact_form_nonce").val() || "";
        $("#user_contact_form")
            .parsley()
            .on("field:validated", function () {
                let ok = $(".parsley-error").length === 0;
            })
            .on("form:submit", function () {
                let adforest_ajax_url = ajaxurl;
                loading.show();

                let google_recaptcha_type = sb_options.google_recaptcha_type;

                google_recaptcha_type = typeof google_recaptcha_type !== "undefined" ? google_recaptcha_type : "v2";
                let google_recaptcha_site_key = sb_options.google_recaptcha_site_key;

                if (google_recaptcha_type === "v3" && google_recaptcha_site_key !== "undefined" && google_recaptcha_site_key !== "") {
                    grecaptcha.ready(function () {
                        try {
                            grecaptcha
                                .execute(google_recaptcha_site_key, { action: "contact_form" })
                                .then(function (token) {
                                    jQuery("#user_contact_form").prepend('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');

                                    jQuery.post(adforest_ajax_url, {
                                        action: "sb_google_captcha3_verification", token: token, security: $('#sb_google_captcha3_verification_nonce').val(),
                                    }, function (result) {
                                        result = JSON.parse(result);
                                        if (result.success) {
                                            $.post(adforest_ajax_url, {
                                                action: "sb_user_contact_form",
                                                receiver_id: $("#receiver_id").val(),
                                                sb_data: $("form#user_contact_form").serialize(),
                                                security: userContactNonce
                                            }).done(function (response) {
                                                loading.hide();
                                                let res_arr = response.split("|");
                                                if (res_arr[0].trim() != "0") {
                                                    toastr.success(res_arr[1], "", {
                                                        timeOut: 4000,
                                                        closeButton: true,
                                                        positionClass: "toast-top-right",
                                                    });
                                                    location.reload();
                                                } else {
                                                    toastr.error(res_arr[1], "", {
                                                        timeOut: 4000,
                                                        closeButton: true,
                                                        positionClass: "toast-top-right",
                                                    });
                                                }
                                            });
                                        } else {
                                            loading.hide();
                                            $("#sb_register_submit").show();
                                            toastr.error(result.msg, "", {
                                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                                            });
                                        }
                                    });
                                });
                        } catch (err) {
                            let google_recaptcha_error_text = jQuery("#google_recaptcha_error_text").val();
                            google_recaptcha_error_text = typeof google_recaptcha_error_text !== "undefined" ? google_recaptcha_error_text : err;
                            jQuery("#sb_loading").hide();
                            toastr.error(google_recaptcha_error_text, "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        }
                    });
                } else {
                    $.post(adforest_ajax_url, {
                        action: "sb_user_contact_form",
                        receiver_id: $("#receiver_id").val(),
                        sb_data: $("form#user_contact_form").serialize(),
                        security: userContactNonce
                    }).done(function (response) {
                        loading.hide();
                        let res_arr = response.split("|");
                        if (res_arr[0].trim() != "0") {
                            toastr.success(res_arr[1], "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                            // location.reload();
                            $("form").trigger("reset");
                        } else {
                            toastr.error(res_arr[1], "", {
                                timeOut: 4000, closeButton: true, positionClass: "toast-top-right",
                            });
                        }
                    });
                }
                return false;
            });
    }

    $('#sort_sellers').on('change', function () {
        const selectedSort = $(this).val();
        const currentUrl = new URL(window.location.href);

        currentUrl.searchParams.set('sort', selectedSort);

        window.location.href = currentUrl.toString();
    });

    $('#sort_vendors').on('change', function () {
        const selectedSort = $(this).val();
        const currentUrl = new URL(window.location.href);

        currentUrl.searchParams.set('sort', selectedSort);

        window.location.href = currentUrl.toString();
    });

    $('#search_seller_form').on('submit', function (e) {
        e.preventDefault();
        loading.show();
        $('.adt-seller-cards-grid').html('');
        $('.sellers_found_heading').html('');
        $('.adt-custom-pagination').html('');

        let searchQuery = $('#search').val();
        let rating = $('#input-21b').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_sellers',
                search: searchQuery,
                rating,
                paged: 1,
                security: $('#sb_fetch_sellers_nonce').val(),
            },
            success: function (response) {
                if (response.data.total_users > 0) {
                    $('.adt-seller-cards-grid').removeClass('nothing-found-search');
                    $('.adt-seller-cards-grid').html(response.data.html);
                    $('.sellers_found_heading').text(response.data.total_users + " Sellers Found:");
                    $('.adt-custom-pagination').html(response.data.pagination);
                } else {
                    $('.adt-seller-cards-grid').addClass('nothing-found-search');
                    $('.adt-seller-cards-grid').html(response.data.html);
                    $('.sellers_found_heading').text(response.data.total_users + " Sellers Found:");
                    $('.adt-custom-pagination').html(response.data.pagination);
                }
                loading.hide();
            }
        });
    });

    /* ======================
     * vendor favourites/un-favourites
     ========================*/
    $(".vendor_to_fav").on("click", function () {
        $(this).toggleClass("favourited_v");
        var status_class = $(this).hasClass("favourited_v");
        var status_code;
        if (status_class == true) {
            status_code = "true";
        } else {
            status_code = "false";
        }
        $("#sb_loading").show();
        $.post(ajaxurl, {
            action: "vendor_fav_ad",
            vendor_id: $(this).attr("data-vendorid"),
            status_code: status_code,
            security: adforest_vendor_security.vendor_fav_nonce,
        }).done(function (response) {
            $("#sb_loading").hide();
            var get_p = response.split("|");
            if (get_p[0].trim() == 1) {
                toastr.success(get_p[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
            } else if (get_p[0].trim() == 0) {
                toastr.error(get_p[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
            }
        });
    });

    if ($("#vendro-owner-contact").length > 0) {
        $("#vendro-owner-contact")
            .parsley()
            .on("field:validated", function () {
                var ok = $(".parsley-error").length === 0;
            })
            .on("form:submit", function () {
                $("#sb_loading").show();
                $.post(ajaxurl, {
                    action: "sb_send_email_to_store_vendor",
                    sb_data: $("form.vendro-owner-contact").serialize(),
                    vendor_id: $("#vendor_id").val(),
                    security: adforest_vendor_security.vendor_email_nonce,
                }).done(function (response) {
                    $("#sb_loading").hide();
                    var res_arr = response.split("|");

                    if (res_arr[0].trim() != "0") {
                        toastr.success(res_arr[1], "", {
                            timeOut: 4000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        });
                    } else {
                        toastr.error(res_arr[1], "", {
                            timeOut: 4000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        });
                    }
                });
                return false;
            });
    }

    $(document).on('click', '.seller_pagination a.page-link', function (e) {
        e.preventDefault();
        let data_paged = $(this).attr('data-page');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_sellers',
                search: $('#search').val(),
                paged: data_paged,
                rating: $('#input-21b').val(),
                security: $('#sb_fetch_sellers_nonce').val(),
            },
            success: function (response) {
                $('.adt-seller-cards-grid').html(response?.data.html);
                var sellersTpl = (typeof adforestCustomI18n !== 'undefined' && adforestCustomI18n.sellersFound) ? adforestCustomI18n.sellersFound : '%s Sellers Found:';
                $('.adt-ads-sort-box h3').text(sellersTpl.replace('%s', response.data.total_users));
                $('.adt-custom-pagination').html(response?.data.pagination);
            }
        });
    });

    $('#get-location').on('click', function () {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(showPosition, showError);
        } else {
            alert(adforestCustomI18n.geoNotSupported);
        }
    });

    function showPosition(position) {
        loading.show();
        let lat = position.coords.latitude;
        let lng = position.coords.longitude;

        waitForGoogleMaps(function () {
            let pos = new google.maps.LatLng(lat, lng);
            let geocoder = new google.maps.Geocoder();
            geocoder
                .geocode({
                    location: {
                        lat: lat,
                        lng: lng
                    }
                })
                .then((response) => {
                    loading.hide();
                    $("#radius-search").val(response.results[0].formatted_address);
                });
        });
    }

    function showError(error) {
        switch (error.code) {
            case error.PERMISSION_DENIED:
                alert(adforestCustomI18n.geoDenied);
                break;
            case error.POSITION_UNAVAILABLE:
                alert(adforestCustomI18n.geoUnavailable);
                break;
            case error.TIMEOUT:
                alert(adforestCustomI18n.geoTimeout);
                break;
            case error.UNKNOWN_ERROR:
                alert(adforestCustomI18n.geoUnknown);
                break;
        }
    }

    $("#you_current_location_text").click(function () {
        loading.show();
        $.ajax({
            url: "https://geolocation-db.com/jsonp",
            jsonpCallback: "callback",
            dataType: "jsonp",
            success: function (location) {
                loading.hide();
                $("#sb-radius-form #sb_user_address").val(
                    location.city + ", " + location.state + ", " + location.country_name
                );
                $("#sb_user_address").val(
                    location.city + ", " + location.state + ", " + location.country_name
                );
                var map_type = sb_options.adforest_map_type;
                if (map_type == "leafletjs_map") {
                    $("#sb_user_address_lat").val(location.latitude);
                    $("#sb_user_address_long").val(location.longitude);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                loading.hide();
                console.log("Request failed: " + textStatus + ", " + errorThrown);
                toastr.error(adforestCustomI18n.geoFetchFailed);
            }
        });
    });

    /* Validating Registration process */
    if ($('#sb-radius-form').length > 0) {
        $('#sb-radius-form').parsley().on('field:validated', function () {
            let ok = $('.parsley-error').length === 0;
        }).on('form:submit', function () {
        });
    }

    $(document).on('click', '.category_click_link', function (e) {
        e.preventDefault();
        let cat_s_id = $(this).attr('data-cat-id');
        if (!cat_s_id) { return; }
        var directSelectEnabled = $('#sb_show_sub_with_parent').val && $('#sb_show_sub_with_parent').val() === '1';
        if (directSelectEnabled) {
            $('#cat_id').val(cat_s_id);
            $('#search_cats_w').submit();
            return;
        }
        $('#cat_id').val(cat_s_id);
        if (typeof loading !== 'undefined' && loading.show) { loading.show(); }
        $('#cats_response').html('');
        $.post(ajaxurl, {
            action: 'sb_get_sub_cat_search',
            cat_id: cat_s_id,
            security: $('#sb_get_sub_cat_search_nonce').val(),
        }).done(function (response) {
            if (typeof loading !== 'undefined' && loading.hide) { loading.hide(); }
            if (response.trim() === 'submit') {
                if ($('#cat_modal').length) { $('#cat_modal').modal('hide'); }
                $('#search_cats_w').submit();
            } else {
                $('#cats_response').html(response);
                if ($('#cat_modal').length) { $('#cat_modal').modal('show'); }
            }
        });
    });

    $(document).on('click', '#ajax_cat', function () {
        loading.show();

        let cat_s_id = $(this).attr('data-cat-id');
        $('#cat_id').val(cat_s_id);
        $.post(ajaxurl, {
            action: 'sb_get_sub_cat_search',
            cat_id: cat_s_id,
            security: $('#sb_get_sub_cat_search_nonce').val(),
        }).done(function (response) {
            loading.hide();
            if (response.trim() === 'submit') {
                $('#cat_modal').modal('hide');
                $('#search_cats_w').submit();
            } else {
                $('#cats_response').html(response)
            }
        });
    });

    $(document).on('click', '#ad-search-btn', function () {
        $('#search_cats_w').submit();
    });

    $(document).ready(function () {
        const urlParams = new URLSearchParams(window.location.search);
        const adTypeParam = urlParams.get('ad_type');
        if (adTypeParam) {
            $('input[type="radio"][value="' + adTypeParam + '"]').prop('checked', true);
        }
        $('input[type="radio"].submit-on-change').on('change', function () {
            $('#ad_type_form').submit();
        });
    });

    $(document).ready(function () {
        const urlParams = new URLSearchParams(window.location.search);
        const adConditionParam = urlParams.get('condition');
        if (adConditionParam) {
            $('input[type="radio"][value="' + adConditionParam + '"]').prop('checked', true);
        }
        $('input[type="radio"].submit-on-condition-change').on('change', function () {
            $('#ad_condition_form').submit();
        });
    });

    $(document).ready(function () {
        const urlParams = new URLSearchParams(window.location.search);
        const adWarrantyParam = urlParams.get('warranty');
        if (adWarrantyParam) {
            $('input[type="radio"][value="' + adWarrantyParam + '"]').prop('checked', true);
        }
        $('input[type="radio"].submit-on-warranty-change').on('change', function () {
            $('#ad_warranty_form').submit();
        });
    });

    $(document).ready(function () {
        const urlParams = new URLSearchParams(window.location.search);
        const adCurrencyParam = urlParams.get('ad_currency');
        if (adCurrencyParam) {
            $('input[type="radio"][value="' + adCurrencyParam + '"]').prop('checked', true);
        }
        $('input[type="radio"].submit-on-currency-change').on('change', function () {
            $('#ad_currency_form').submit();
        });
    });

    jQuery(document).ready(function ($) {
        $('#select-sort').on('select2:select', function () {
            $('#sort-form').submit();
        });

        let urlParams = new URLSearchParams(window.location.search);
        let selectedSort = urlParams.get('sort');

        if (selectedSort) {
            $('#select-sort').val(selectedSort);
        }
    });


    $(document).on('click', '.sb_make_feature_ad_detail_page', async function () {
        loading.show();
        adID = $(this).attr('data-aaa-id');
        let formId = "make_ad_featured";
        let ad_packages = await fetchAdPackages(ajaxurl, adID, formId);
        loading.hide();

        $.dialog({
            title: sb_options.select_package,
            content: ad_packages,
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'blue',
        });
    });

    $(document).on('submit', '#make_ad_featured', function (e) {
        e.preventDefault();
        loading.show();

        let modalBtn = $(".feature_modal_btn");
        modalBtn.attr('disabled', true).text('Processing...');

        let ad_id = $(this).find('input[name="ad_id"]').val();
        let ads_package = $(this).find('input[name="ads_package"]:checked').val();

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'sb_make_featured_detail',
                ad_id: ad_id,
                ads_package: ads_package,
                security: $('#sb_make_featured_detail_nonce').val(),
            },
            success: function (response) {
                modalBtn.attr('disabled', false).text('Submit');
                loading.hide();

                if (response.success) {
                    toastr.success(response.data.message, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                    if (response.data.url) {
                        location.replace(response.data.url);
                    } else {
                        window.location.reload();
                    }
                } else {
                    toastr.error(response.data.message, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                }
            },
            error: function (error) {
                modalBtn.attr('disabled', false).text('Submit');
                loading.hide();
                console.log("Error submitting form", error);
            }
        });
    });

    async function fetchAdPackages(adforest_ajax_url, adID, formId) {
        let result;
        try {
            result = await $.ajax({
                url: adforest_ajax_url,
                type: 'POST',
                data: {
                    action: 'load_feature_ad_modal',
                    adID,
                    formId,
                    security: $('#adforest_load_feature_ad_modal_nonce').val(),
                }
            });
            return result;
        } catch (error) {
            console.error(error);
        }
    }

    function adforestSubmitOnSelect($element) {
        if (typeof loading !== 'undefined' && loading && typeof loading.show === 'function') {
            loading.show();
        }
        $element.closest("form").trigger("submit");
    }

    function adforestNormalizeValue(value) {
        if (Array.isArray(value)) {
            return value.slice().sort().join(',');
        }
        if (value === undefined || value === null) {
            return '';
        }
        return String(value);
    }

    function adforestShouldSubmit($element) {
        var current = adforestNormalizeValue($element.val());
        var previous = $element.data('adforestPreviousValue');
        var hasOpened = $element.data('adforestOpened') === true;

        $element.data('adforestPreviousValue', current);

        if (!hasOpened) {
            return false;
        }

        if (previous === undefined) {
            return false;
        }

        if (current === previous) {
            return false;
        }

        return true;
    }

    $("select.submit_on_select").each(function () {
        var $select = $(this);
        $select.data('adforestPreviousValue', adforestNormalizeValue($select.val()));
        $select.data('adforestOpened', false);
    });

    $("select.submit_on_select").on("select2:open", function () {
        $(this).data('adforestOpened', true);
    });

    $("select.submit_on_select").on("focus click", function () {
        $(this).data('adforestOpened', true);
    });

    $("select.submit_on_select").on("select2:select change", function () {
        var $select = $(this);
        if (adforestShouldSubmit($select)) {
            adforestSubmitOnSelect($select);
        }
    });

    $(".submit_on_select").on("click", function (e) {
        var $element = $(this);
        if ($element.is("a")) {
            e.preventDefault();
        }
        adforestSubmitOnSelect($element);
    });

    jQuery(document).ready(function ($) {
        let loading = false;
        let loadingMode = $('#load-more-ads-btn').data('loading-mode');
        let searchQuery = $('#load-more-ads-btn').data('search-query');
        let adCount = $('#load-more-ads-btn').data('ad-count');
        let postsPerPage = $('#load-more-ads-btn').data('posts-per-page');
        let searchPageType = $('#load-more-ads-btn').data('search-page');
        let viewType = $('#load-more-ads-btn').data('view-type');
        let paged = 2;

        // Function to reinitialize JavaScript components for newly loaded content
        function reinitializeComponents() {
            // Reinitialize carousels for newly loaded content
            $('.adt-car-ad-carousel').each(function () {
                if (!$(this).hasClass('owl-loaded')) {
                    $(this).owlCarousel({
                        loop: true,
                        rtl: is_rtl,
                        margin: 0,
                        nav: true,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        dots: false,
                        responsive: {
                            0: {
                                items: 1
                            },
                            600: {
                                items: 1
                            },
                            1000: {
                                items: 1
                            }
                        }
                    });
                }
            });

            // Reinitialize property image carousels for grid 2 and 3
            $('.adt-property-img-carousel').each(function () {
                if (!$(this).hasClass('owl-loaded')) {
                    $(this).owlCarousel({
                        loop: false,
                        rtl: is_rtl,
                        margin: 0,
                        nav: true,
                        dots: false,
                        navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                        responsive: {
                            0: {
                                items: 1
                            },
                            600: {
                                items: 1
                            },
                            1000: {
                                items: 1
                            }
                        }
                    });
                }
            });

            // Reinitialize all tooltips (including favourite hearts with state sync) for newly loaded content.
            // Uses the single controlled initializer defined on DOM ready — no direct .tooltip() calls here
            // to avoid conflicting Bootstrap initializations.
            if (typeof window.adfInitTooltips === 'function') {
                window.adfInitTooltips();
            }

            // Bootstrap 5 tooltips (data-bs-toggle) are a separate API surface — init them if not already
            $('[data-bs-toggle="tooltip"]').not('.ad_to_fav, .save-ad').each(function () {
                if (!$(this).data('bs.tooltip') && typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    new bootstrap.Tooltip(this, { html: true });
                }
            });

            // Reinitialize confirmation dialogs for newly loaded content
            $('[data-bs-toggle="confirmation"]').each(function () {
                if (!$(this).data('bs.confirmation')) {
                    new bootstrap.Popover(this, {
                        html: true,
                        trigger: 'click',
                        placement: 'top',
                        content: function () {
                            return '<div class="confirmation-dialog">' +
                                '<p>' + $(this).attr('data-bs-content') + '</p>' +
                                '<div class="btn-group">' +
                                '<button type="button" class="btn btn-sm btn-primary confirm-yes">' +
                                ($(this).attr('data-btn-confirm-label') || 'Yes') + '</button>' +
                                '<button type="button" class="btn btn-sm btn-secondary confirm-no">' +
                                ($(this).attr('data-btn-cancel-label') || 'No') + '</button>' +
                                '</div></div>';
                        }
                    });
                }
            });

            // Reinitialize favorite buttons for newly loaded content (toggle add/remove)
            $('.ad_to_fav').each(function () {
                // Set initial tooltip for newly loaded hearts
                if (typeof adfSetFavTitle === 'function') { adfSetFavTitle($(this)); }
                if (!$(this).hasClass('event-bound')) {
                    $(this).addClass('event-bound');
                    $(this).on("click", function () {
                        let $btn = $(this).closest('.ad_to_fav, .save-ad');
                        if (!$btn.length) { $btn = $(this); }
                        loading.show();
                        $.post(ajaxurl, {
                            action: "sb_fav_ad", ad_id: $btn.attr("data-adid"), security: $("#sb_fav_ad_nonce").val(),
                        }).done(function (response) {
                            loading.hide();
                            let get_p = response.split("|");
                            let code = get_p[0].trim();
                            let message = get_p[1];
                            let toastOpts = { timeOut: 4000, closeButton: true, positionClass: "toast-top-right" };

                            if (code !== "1" && code !== "2") {
                                toastr.error(message, "", toastOpts);
                                return;
                            }

                            // STEP 1: Toggle class + icon based on response code (synchronous)
                            let $icon = $btn.find("i");
                            if (code === "1") {
                                $btn.addClass("ad-favourited");
                                $icon.removeClass("far fa-heart")
                                     .addClass("fas fa-heart text-danger");
                            } else {
                                $btn.removeClass("ad-favourited");
                                $icon.removeClass("fas fa-heart text-danger")
                                     .addClass("far fa-heart");
                            }

                            // Immediate toast for snappy UX
                            toastr.success(message, "", toastOpts);

                            // Authoritative tooltip override — identical logic to the primary handler.
                            setTimeout(function () {

                                var $btn = $(this).closest('.ad_to_fav, .save-ad');
                                if (!$btn.length) return;

                                var $icon = $btn.find('i');

                                // STEP 1: Determine FINAL DOM state (single source of truth)
                                var isFav = $btn.hasClass("ad-favourited") || $icon.hasClass("fas");

                                var newText = isFav
                                    ? "Click to remove from favourite"
                                    : "Click to make it favourite";

                                if (typeof adforestFav !== "undefined") {
                                    newText = isFav ? adforestFav.titleRemove : adforestFav.titleAdd;
                                }

                                // STEP 2: HARD RESET attributes (prevent Bootstrap cache issues)
                                $btn.removeAttr("data-original-title");
                                $btn.removeAttr("data-bs-original-title"); // Bootstrap 5 cache

                                $btn.attr("title", newText);
                                $btn.attr("aria-label", newText);

                                // STEP 3: DOUBLE OVERRIDE (handles late overwrites by other scripts)
                                setTimeout(function () {
                                    $btn.removeAttr("data-original-title");
                                    $btn.removeAttr("data-bs-original-title"); // Bootstrap 5 cache
                                    $btn.attr("title", newText);
                                    $btn.attr("aria-label", newText);
                                }, 50);

                                // STEP 4: Refresh Bootstrap tooltip (BS4 + BS5 safe)
                                if (typeof $btn.tooltip === "function") {
                                    try {
                                        $btn.tooltip("dispose");
                                        $btn.tooltip({ html: true });
                                    } catch (e) {}
                                }

                                if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
                                    try {
                                        var instance = bootstrap.Tooltip.getInstance($btn.get(0));
                                        if (instance) instance.dispose();
                                        new bootstrap.Tooltip($btn.get(0));
                                    } catch (e) {}
                                }

                                // STEP 5: If user is hovering, force tooltip to show updated text
                                if ($btn.is(":hover")) {
                                    if (typeof $btn.tooltip === "function") {
                                        try { $btn.tooltip("show"); } catch (e) {}
                                    }

                                    if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
                                        try {
                                            var inst = bootstrap.Tooltip.getInstance($btn.get(0));
                                            if (inst) inst.show();
                                        } catch (e) {}
                                    }
                                }

                            }.bind(this), 120);
                        });
                    });
                }
            });

            // Reinitialize any other interactive elements that might need attention
            // This includes dropdowns, modals, and other Bootstrap components
            $('[data-bs-toggle="dropdown"]').each(function () {
                if (!$(this).hasClass('dropdown-initialized')) {
                    $(this).addClass('dropdown-initialized');
                }
            });

            // Reinitialize any custom event handlers that might be needed
            // Trigger a custom event to let other scripts know new content was loaded
            $(document).trigger('ajax_content_loaded');

            // Reinitialize any other carousels that might be in the newly loaded content
            $('.owl-carousel').each(function () {
                if (!$(this).hasClass('owl-loaded')) {
                    // Check if it's a specific carousel type and initialize accordingly
                    if ($(this).hasClass('adt-category-types-carousel')) {
                        $(this).owlCarousel({
                            loop: true,
                            rtl: is_rtl,
                            margin: 0,
                            nav: true,
                            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                            dots: false,
                            responsive: {
                                0: {
                                    items: 2
                                },
                                576: {
                                    items: 2
                                },
                                768: {
                                    items: 4
                                },
                                992: {
                                    items: 7
                                },
                                1200: {
                                    items: 9
                                },
                                1400: {
                                    items: 12
                                }
                            }
                        });
                    } else if ($(this).hasClass('adt-place-detail-carousel')) {
                        $(this).owlCarousel({
                            loop: false,
                            rtl: is_rtl,
                            margin: 0,
                            items: 1,
                            nav: true,
                            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                            dots: false,
                        });
                    } else if ($(this).hasClass('adt-popular-place-carousel')) {
                        $(this).owlCarousel({
                            loop: false,
                            rtl: is_rtl,
                            margin: 36,
                            nav: true,
                            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                            dots: false,
                            responsive: {
                                0: {
                                    items: 1
                                },
                                576: {
                                    items: 2
                                },
                                768: {
                                    margin: 20,
                                    items: 3
                                },
                                992: {
                                    items: 3
                                },
                                1200: {
                                    margin: 20,
                                    items: 5
                                },
                                1400: {
                                    items: 5
                                }
                            }
                        });
                    } else if ($(this).hasClass('adt-event-list-carousel')) {
                        $(this).owlCarousel({
                            loop: false,
                            rtl: is_rtl,
                            margin: 36,
                            nav: true,
                            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
                            dots: false,
                            responsive: {
                                0: {
                                    items: 1
                                },
                                576: {
                                    items: 2
                                },
                                768: {
                                    margin: 20,
                                    items: 3
                                },
                                992: {
                                    items: 3
                                },
                                1200: {
                                    margin: 20,
                                    items: 5
                                },
                                1400: {
                                    items: 5
                                }
                            }
                        });
                    }
                }
            });
        }

        function loadMoreAds() {
            if (loading) return;
            loading = true;
            $("#sb_loading").show();

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'load_more_ads',
                    search_query: JSON.stringify(searchQuery),
                    paged: paged,
                    view_type: viewType,
                    security: $('#sb_load_more_ads_nonce').val(),
                },
                beforeSend: function () {
                    if (loadingMode === 'show_more') {
                        $('#load-more-ads-btn').text('Loading...');
                    }
                },
                success: function (response) {
                    $("#sb_loading").hide();
                    if (response.trim() == '0') {
                        $('#no_more_ads_p').html(
                            '<div role="alert" class="alert alert-info alert-dismissible">' +
                            '<i class="fa fa-info-circle"></i>' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            sb_options.no_more_ads +
                            '</div>'
                        );
                        if (loadingMode === 'show_more') {
                            $('#load-more-ads-btn').text(sb_options.no_more_ads).prop('disabled', true).hide();
                        }
                    } else {
                        if (searchPageType === 'map') {
                            $('.search-ads-result-box').append(response);
                        }
                        if (viewType === 'list') {
                            $('.adt-search-ads-list').append(response);
                        } else {
                            $('.adt-search-ads-grid').append(response);
                        }

                        // Reinitialize JavaScript components for newly loaded content
                        reinitializeComponents();

                        if (loadingMode === 'show_more') {
                            $('#load-more-ads-btn').text(sb_options.show_more_btn_text).show();
                        }
                        paged++;
                        loading = false;
                    }
                }
            });
        }

        if (loadingMode === 'show_more') {
            if (adCount < postsPerPage) {
                $('#load-more-ads-btn').hide();
            } else {
                $('#load-more-ads-btn').show();
            }
            $('#load-more-ads-btn').on('click', function () {
                loadMoreAds();
            });
        } else if (loadingMode === 'infinity_scroll') {
            $('#load-more-ads-btn').hide();
            $('.adt-search-ads-grid').on('scroll', function () {
                if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 100) {
                    loadMoreAds();
                }
            });
            $('.adt-search-ads-list').on('scroll', function () {
                if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 100) {
                    loadMoreAds();
                }
            });
            $('.search-content-side').on('scroll', function () {
                if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 100) {
                    loadMoreAds();
                }
            });
        }
    });

    $('.reset-search').on('click', function () {
        $(this).closest('form')[0].reset();

        $('select[name="cat_id"]').val(null).trigger('change');
    });

    $('#topbar_categories').on('select2:select', (e) => {
        let selectedData = e.params.data;
        let cat_s_id = selectedData.element.getAttribute('data-cat-id');

        if (cat_s_id !== "" && typeof (cat_s_id) !== 'undefined' && cat_s_id !== null) {
            $('#cat_id').val(cat_s_id);
            loading.show();
            $('#cats_response').html('');
            $.post(ajaxurl, {
                action: 'sb_get_sub_cat_search',
                cat_id: cat_s_id,
                security: $('#sb_get_sub_cat_search_nonce').val(),
            }).done(function (response) {
                loading.hide();
                if (response.trim() === 'submit') {
                    $('#cat_modal').modal('hide');
                    $('#search_cats_w').submit();
                } else {
                    $('#cats_response').html(response);
                    $('#cat_modal').modal('show');
                }
            });
        }
    })

    $('#topbar_countries').on('select2:select', (e) => {
        let selectedData = e.params.data;
        let country_s_id = selectedData.element.getAttribute('data-country-id');

        if (country_s_id !== "" && typeof (country_s_id) !== 'undefined' && country_s_id !== null) {
            $('#country_id').val(country_s_id);
            loading.show();
            $('#countries_response').html('');
            $.post(ajaxurl,
                {
                    action: 'get_related_cities',
                    country_id: country_s_id,
                    security: $('#adforest_get_countries_nonce').val(),
                }
            ).done(function (response) {
                loading.hide();
                if (response.trim() === 'submit') {
                    $('#search_countries').submit();
                } else {
                    $('#states_model').modal('show');
                    $('#countries_response').html(response);
                }
            });
        }
    })

    $(document).on('click', '#ajax_states', function () {
        loading.show();
        let cat_s_id = $(this).attr('data-country-id');
        $('#country_id').val(cat_s_id);
        $.post(ajaxurl, {
            action: 'get_related_cities',
            country_id: cat_s_id,
            security: $('#adforest_get_countries_nonce').val(),
        }).done(function (response) {
            loading.hide();
            if (response.trim() === 'submit') {
                $('#search_countries').submit();
            } else {
                $('#countries_response').html(response);
            }
        });
    });
    $(document).on('click', '#country-btn', function () {
        $('#search_countries').submit();
    });

    $("#topbar_search_currency").on('select2:select', () => {
        $("#ad_currency_form").submit();
    })
    $("#topbar_ad_type").on('select2:select', () => {
        $("#ad_type_form").submit();
    })

    $("#ad-condition-topbar").on('select2:select', () => {
        $("#ad_condition_form").submit();
    })

    $("#ad-warranty-topbar").on('select2:select', () => {
        $("#ad_warranty_form").submit();
    })

    $('.adv-srch').on('click', function () {
        $('.hide_adv_search').slideToggle();

        if ($(this).find('i').hasClass('fa-times')) {
            $(this).html('Advance Search');
        } else {
            $(this).html('Close <i class="fa fa-times"></i>');
        }
    });

    /*initilize jquery text editor */
    let ad_html_switch = $("#adforest_ad_html").val();
    ad_html_switch = typeof ad_html_switch !== "undefined" && ad_html_switch == "1" ? true : false;
    if ($("#ad_description").length > 0) {
        if (ad_html_switch) {
            $("#ad_description").jqte({ color: false });
        } else {
            $("#ad_description").jqte({
                link: false,
                unlink: false,
                formats: false,
                format: false,
                funit: false,
                fsize: false,
                fsizes: false,
                color: false,
                strike: false,
                source: false,
                sub: false,
                sup: false,
                indent: false,
                outdent: false,
                right: true,
                left: true,
                center: true,
                remove: false,
                rule: false,
                title: false,
            });
        }
    }

    /* ======================
     * Product favourites/un-favourites shop layout 5
     ========================*/
    $(".product_to_fav").on("click", function () {
        $(this).toggleClass("favourited");
        var status_class = $(this).hasClass("favourited");
        var status_code;
        if (status_class == true) {
            status_code = "true";
        } else {
            status_code = "false";
        }
        $("#sb_loading").show();
        $.post(ajaxurl, {
            action: "product_fav_add",
            product_id: $(this).attr("data-productId"),
            status_code: status_code,
        }).done(function (response) {
            $("#sb_loading").hide();
            var get_p = response.split("|");
            if (get_p[0].trim() == 1) {
                toastr.success(get_p[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
            } else if (get_p[0].trim() == 0) {
                toastr.error(get_p[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
            }
        });
    });

    $('#increase-quantity').on('click', function () {
        let quantity = $('#product-quantity');
        quantity.innerText = parseInt(quantity.innerText) + 1;
    });

    $('#decrease-quantity').on('click', function () {
        let quantity = $('#product-quantity');
        let currentQuantity = parseInt(quantity.innerText);
        if (currentQuantity > 1) {
            quantity.innerText = currentQuantity - 1;
        }
    });

    $('#product-review-form').on('submit', function (e) {
        loading.show();
        e.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: formData,
            success: function (response) {
                loading.hide();
                if (response.success) {
                    toastr.success(response.data.message, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                    $('#product-review-form')[0].reset();
                } else {
                    toastr.error(response.data.message, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                }
            }
        });
    });

    let $quantity = $('#product-quantity');
    let defaultQty = parseInt($quantity.data('default-qty'), 10);
    let currentQty = defaultQty;

    $('#increase-quantity').on('click', function () {
        currentQty++;
        $quantity.text(currentQty);
    });

    $('#decrease-quantity').on('click', function () {
        if (currentQty > 1) {
            currentQty--;
            $quantity.text(currentQty);
        }
    });

    $('.custom_add_to_cart_button').on('click', function (e) {
        e.preventDefault();
        let productId = $(this).data('product-id');
        let qty = currentQty;
        $("#sb_loading").show();
        $.post(ajaxurl, {
            action: "sb_add_cart",
            product_id: productId,
            qty: qty,
            security: $('#sb_add_cart_nonce').val(),
        }).done(function (response) {
            $("#sb_loading").hide();
            let get_r = response.split("|");

            if (get_r[0].trim() === "1") {
                toastr.success(get_r[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
                window.location = get_r[2];
            } else {
                toastr.error(get_r[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
                window.location = get_r[2];
            }
        });
    });

    $('.custom_add_to_cart_button_grid').on('click', function (e) {
        e.preventDefault();
        let productId = $(this).data('product-id');
        let qty = 1;
        $("#sb_loading").show();
        $.post(ajaxurl, {
            action: "sb_add_cart",
            product_id: productId,
            qty: qty,
            security: $('#sb_add_cart_nonce').val(),
        }).done(function (response) {
            $("#sb_loading").hide();
            let get_r = response.split("|");

            if (get_r[0].trim() === "1") {
                toastr.success(get_r[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
                window.location = get_r[2];
            } else {
                toastr.error(get_r[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
                window.location = get_r[2];
            }
        });
    });

    $('.category-link').on('click', function (event) {
        event.preventDefault();

        let categoryId = $(this).data('category-id');
        let form = $('#shop-categories-select');
        let currentUrl = form.attr('action') || window.location.href;
        let newUrl = currentUrl.split('?')[0] + '?category_id=' + categoryId;

        window.location.href = newUrl;
    });

    if ($("#user_rating_form").length > 0) {
        $("#user_rating_form")
            .parsley()
            .on("field:validated", function () {
                let ok = $(".parsley-error").length === 0;
            });

        $("#user_rating_form").submit(function () {
            let form = $(this);
            // Ajax for Registration
            loading.show();
            $.post(ajaxurl, {
                action: "sb_post_user_rating",
                security: $("#sb-user-rating-token").val(),
                sb_data: form.serialize(),
            })
                .done(function (response) {
                    loading.hide();
                    let res_arr = response.split("|");
                    if (res_arr[0].trim() != "0") {
                        toastr.success(res_arr[1], "", {
                            timeOut: 4000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        });
                        location.reload();
                    } else {
                        toastr.error(res_arr[1], "", {
                            timeOut: 4000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        });
                    }
                })
                .fail(function () {
                    loading.hide();
                    toastr.error($("#_nonce_error").val(), "", {
                        timeOut: 4000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                    });
                });
            return false;
        });
    }

    /* Back To Top */
    $(window).scroll(function () {
        var offset = 300,
            offset_opacity = 1200,
            scroll_top_duration = 700,
            $back_to_top = $(".cd-top");
        var ad_post_btn = $(".sticky-post-button");
        $(this).scrollTop() > offset
            ? ad_post_btn.addClass("sticky-post-button-visible")
            : ad_post_btn
                .removeClass("sticky-post-button-visible")
                .removeClass("sticky-post-button-fadeout");
        $(this).scrollTop() > offset
            ? $back_to_top.addClass("cd-is-visible")
            : $back_to_top.removeClass("cd-is-visible cd-fade-out");
        if ($(this).scrollTop() > offset_opacity) {
            $back_to_top.addClass("cd-fade-out");
            ad_post_btn.addClass("sticky-post-button-fadeout");
        }
    });
    $back_to_top = $(".cd-top");
    $(document).on("click", ".cd-top", function (event) {
        event.preventDefault();
        $("body,html").animate({ scrollTop: 0 }, 700);
    });
    /* Back To Top */

    $(".ad_alerts").click(function () {
        $("#sb_loading").show();
        var security = $('#sb_job_alert_subscription_check_nonce').val();
        $.post(ajaxurl, {
            action: "job_alert_subscription_check",
            security: security,
        }).done(function (response) {
            $("#sb_loading").hide();
            var res_arr = response.split("|");
            if (res_arr[0].trim() != "0") {
                $("#ad_cat_sub_div").hide();
                $("#ad_cat_sub_sub_div").hide();
                $("#ad_cat_sub_sub_sub_div").hide();
                $("select").select2({
                    placeholder: sb_options.select_place_holder,
                    allowClear: true,
                    width: "100%",
                });

                $("#sb_loading").show();
                $("#ad-alert-subscribtion").modal("show");
                $("#sb_loading").hide();
            } else {
                toastr.error(res_arr[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
            }
        });
    });

    $(document).on("click", "#submit_alert", function () {
        $("#alert_job_form")
            .parsley()
            .on("field:validated", function () {
                let ok = $(".parsley-error").length === 0;
            })
            .on("form:submit", function () {
                $("#sb_loading").show();
                let formData = new FormData(document.querySelector("#alert_job_form"));
                formData.append('action', 'job_alert_subscription');
                formData.append('security', $('#sb_job_alert_subscription_nonce').val());

                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function () {
                        $("#sb_loading").show();
                    },
                    success: function (response) {
                        $("#sb_loading").hide();
                        var res_arr = response.split("|");

                        if (res_arr[0].trim() != "0") {
                            toastr.success(res_arr[1], "", {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: "toast-top-right",
                            });
                            $("#ad-alert-subscribtion").modal("hide");
                        } else {
                            toastr.error(res_arr[1], "", {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: "toast-top-right",
                            });
                        }
                    },
                    error: function () {
                        $("#sb_loading").hide();
                        toastr.error("An error occurred. Please try again.", "", {
                            timeOut: 4000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        });
                    }
                });
                return false;
            });
    });

    $('#ad_alert_category_select').on('change', async function () {
        // checkCategorySelection();
        loading.show();
        let parentId = $(this).val();
        let level = 1;
        if ($("#alert_category").length > 0) {
            $("#alert_category").val($("#ad_alert_category_select").val());
        }

        $('#child-category-container').empty();

        if (parentId) {
            await fetchChildCategories(parentId, level);
        }
        loading.hide();
        // let lastSelectedCategory = getLastSelectedCategory();
        // saveSelectedCategory(lastSelectedCategory);

    });

    if ($(".send-message-to-author").length > 0) {
        $(".send-message-to-author")
            .parsley()
            .on("field:validated", function () {
                var ok = $(".parsley-error").length === 0;
            })
            .on("form:submit", function () {
                $("#sb_loading").show();
                var google_recaptcha_type = get_strings.google_recaptcha_type;
                google_recaptcha_type =
                    typeof google_recaptcha_type !== "undefined"
                        ? google_recaptcha_type
                        : "v2";
                var google_recaptcha_site_key = get_strings.google_recaptcha_site_key;

                if (
                    google_recaptcha_type == "v3" &&
                    google_recaptcha_site_key !== "undefined" &&
                    google_recaptcha_site_key != ""
                ) {
                    grecaptcha.ready(function () {
                        try {
                            var adforest_ajax_url = jQuery("#adforest_ajax_url").val();
                            grecaptcha
                                .execute(google_recaptcha_site_key, { action: "contact_form" })
                                .then(function (token) {
                                    jQuery("#user_contact_form").prepend(
                                        '<input type="hidden" name="g-recaptcha-response" value="' +
                                        token +
                                        '">'
                                    );
                                    jQuery.post(
                                        adforest_ajax_url,
                                        {
                                            action: "sb_google_captcha3_verification",
                                            security: $('#sb_google_captcha3_verification_nonce').val(),
                                            token: token,
                                        },
                                        function (result) {
                                            result = JSON.parse(result);
                                            if (result.success) {
                                                $.post(adforest_ajax_url, {
                                                    action: "sb_send_message_to_author",
                                                    ad_id: $("#ad_id").val(),
                                                    sb_data: $("form.send-message-to-author").serialize(),
                                                    security: $('#adforest_send_message_to_author_nonce').val(),
                                                }).done(function (response) {
                                                    $("#sb_loading").hide();
                                                    var res_arr = response.split("|");
                                                    if (res_arr[0].trim() != "0") {
                                                        toastr.success(res_arr[1], "", {
                                                            timeOut: 4000,
                                                            closeButton: true,
                                                            positionClass: "toast-top-right",
                                                        });
                                                    } else {
                                                        toastr.error(res_arr[1], "", {
                                                            timeOut: 4000,
                                                            closeButton: true,
                                                            positionClass: "toast-top-right",
                                                        });
                                                    }
                                                });
                                            } else {
                                                $("#sb_loading").hide();
                                                $("#sb_register_submit").show();
                                                toastr.error(result.msg, "", {
                                                    timeOut: 4000,
                                                    closeButton: true,
                                                    positionClass: "toast-top-right",
                                                });
                                            }
                                        }
                                    );
                                });
                        } catch (err) {
                            var google_recaptcha_error_text = jQuery(
                                "#google_recaptcha_error_text"
                            ).val();
                            google_recaptcha_error_text =
                                typeof google_recaptcha_error_text !== "undefined"
                                    ? google_recaptcha_error_text
                                    : err;
                            jQuery("#sb_loading").hide();
                            toastr.error(google_recaptcha_error_text, "", {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: "toast-top-right",
                            });
                        }
                    });
                } else {
                    $.post(adforest_ajax_url, {
                        action: "sb_send_message_to_author",
                        ad_id: $("#ad_id").val(),
                        sb_data: $("form.send-message-to-author").serialize(),
                        security: $('#adforest_send_message_to_author_nonce').val(),
                    }).done(function (response) {
                        $("#sb_loading").hide();
                        var res_arr = response.split("|");
                        if (res_arr[0].trim() != "0") {
                            toastr.success(res_arr[1], "", {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: "toast-top-right",
                            });
                        } else {
                            toastr.error(res_arr[1], "", {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: "toast-top-right",
                            });
                        }
                    });
                }
                return false;
            });
    }

    if ($("#ad_post_page_get_packages_cat_based").length > 0) {
        let cat_id = $("#ad_post_page_get_packages_cat_based").val();
        let isUpdate = "";
        if ($("#is_update").length > 0) {
            isUpdate = $("#is_update").val();
        }
        $.post(ajaxurl, {
            action: "sb_get_sub_cat",
            cat_id: cat_id,
            is_update: isUpdate,
            security: $('#sb_get_sub_cat_nonce').val(),
        }).done((response) => {
            let selected_packages = response?.data?.selected_packages || "";
            if (selected_packages !== "" && selected_packages !== null) {
                $("#purchase-package").html(selected_packages);
            }
        })
    }

    jQuery(document).ready(function ($) {
        // Get the sale end date from the data attribute
        const $countdownElement = $('#countdown-timer');
        const saleEndDate = $countdownElement.data('sale-end-date');

        // Parse the sale end date
        const endDate = new Date(saleEndDate).getTime();

        // Update countdown every second
        const updateCountdown = setInterval(function () {
            const now = new Date().getTime();
            const timeLeft = endDate - now;

            if (timeLeft > 0) {
                // Calculate days, hours, minutes, and seconds
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                // Update the UI
                $('#days').text(days);
                $('#hours').text(hours);
                $('#minutes').text(minutes);
                $('#seconds').text(seconds);
            } else {
                // If the countdown ends, stop the interval and show a message
                clearInterval(updateCountdown);
                $countdownElement.html("<li><strong>The sale has ended!</strong></li>");
            }
        }, 1000);
    });

    /* ============================
     * Delete ADS Rating adforest
     ==============================*/
    $(".ads_rating_dlt").on("click", function () {
        let confirmed = get_strings.confirm;
        let this_elem = $(this);
        $("#sb_loading").show();
        if (confirmed) {
            $.post(ajaxurl, {
                action: "ads_rating_delete",
                comment_id: $(this).attr("data-comment_id"),
                ad_id: $(this).attr("data-ad_id"),
                security: $('#adforest_ads_rating_delete_nonce').val(),
            }).done(function (response) {
                $("#sb_loading").hide();
                let get_p = response.split("|");
                if (get_p[0].trim() == 1) {
                    toastr.success(get_p[1], "", {
                        timeOut: 4000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                    });

                    this_elem.parents(".review-content").remove();
                } else if (get_p[0].trim() == 0) {
                    toastr.error(get_p[1], "", {
                        timeOut: 4000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                    });
                }
            });
        }
    });

    jQuery(document).ready(function ($) {
        /* Reset Password*/
        if ($("#sb-reset-password-form").length > 0) {
            $("#sb_reset_password_modal").modal("show");
            $("#sb_reset_password_msg").hide();
            $("#sb-reset-password-form")
                .parsley()
                .on("field:validated", function () {
                    var ok = $(".parsley-error").length === 0;
                })
                .on("form:submit", function () {
                    if (
                        $("#sb_new_password").val() != $("#sb_confirm_new_password").val()
                    ) {
                        toastr.error($("#adforest_password_mismatch_msg").val(), "", {
                            timeOut: 4000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        });
                        return false;
                    }
                    $("#sb_reset_password_submit").hide();
                    $("#sb_reset_password_msg").show();
                    $("#sb_loading").show();
                    $.post(ajaxurl, {
                        action: "sb_reset_password",
                        security: $("#sb-reset-pass-token").val(),
                        sb_data: $("form#sb-reset-password-form").serialize(),
                    })
                        .done(function (response) {
                            $("#sb_loading").hide();
                            $("#sb_reset_password_msg").hide();

                            var get_r = response.split("|");
                            if (get_r[0].trim() == "1") {
                                toastr.success(get_r[1], "", {
                                    timeOut: 4000,
                                    closeButton: true,
                                    positionClass: "toast-top-right",
                                });
                                $("#sb_reset_password_modal").modal("hide");
                                $("#sb_reset_password_submit").show();
                                window.location = $("#login_page").val();
                            } else {
                                $("#sb_reset_password_submit").show();
                                toastr.error(get_r[1], "", {
                                    timeOut: 4000,
                                    closeButton: true,
                                    positionClass: "toast-top-right",
                                });
                            }
                        })
                        .fail(function () {
                            $("#sb_loading").hide();
                            $("#sb_reset_password_msg").hide();
                            $("#sb_reset_password_submit").show();
                            toastr.error($("#_nonce_error").val(), "", {
                                timeOut: 4000,
                                closeButton: true,
                                positionClass: "toast-top-right",
                            });
                        });
                    return false;
                });
        }

        if (sb_options.msg_notification_on != "" && sb_options.msg_notification_on != 0 && sb_options.msg_notification_time != "") {
            if (sb_options.is_logged_in == "1") {
                setInterval(function () {
                    $.post(ajaxurl, {
                        action: "sb_check_messages",
                        new_msgs: $("#is_unread_msgs").val(),
                        security: $('#adforest_check_messages_nonce').val(),
                    }).done(function (response) {
                        var get_r = response.split("|");
                        if (get_r[0].trim() == "1") {
                            toastr.success(get_r[1], "", {
                                timeOut: 5000,
                                closeButton: true,
                                positionClass: "toast-top-right",
                            });
                        }
                    });
                }, sb_options.msg_notification_time);
            }
        }
    });

    const blockUI = ($element) => {
        $element.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    };

    const updateCartQuantity = (key, quantity, $counter) => {
        const $form = $('form.woocommerce-cart-form');
        loading.show();

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'update_cart_item_quantity',
                security: $('#update_cart_item_quantity_nonce').val(),
                cart_item_key: key,
                quantity: quantity
            },
            success: function (response) {
                if (response.success) {
                    if (response.data.cart_total) {
                        $('.order-summary-list .values').last().html(response.data.cart_total);
                    }
                    if (response.data.subtotal) {
                        $('.order-summary-list .values').first().html(response.data.subtotal);
                    }

                    $counter.find('.adt-count').text(String(quantity).padStart(2, '0'));

                    $(document.body).trigger('updated_cart_totals');
                } else {
                    alert('Error updating cart');
                    location.reload();
                }
            },
            error: function () {
                alert('Error updating cart');
                location.reload();
            },
            complete: function () {
                loading.hide();
            }
        });
    };

    $('.adt-counter button').on('click', function (e) {
        e.preventDefault();

        const $counter = $(this).closest('.adt-counter');
        const $input = $counter.find('.qty');
        const currentVal = parseInt($input.val());
        const cartItemKey = $input.attr('name').match(/cart\[(.*?)\]/)[1];
        let newVal = currentVal;

        if ($(this).find('.fa-angle-left').length) {
            if (currentVal > 1) {
                newVal = currentVal - 1;
            }
        } else {
            newVal = currentVal + 1;
        }

        if (newVal !== currentVal) {
            $input.val(newVal);
            updateCartQuantity(cartItemKey, newVal, $counter);
        }
    });

    $('.adt-remove').on('click', function (e) {
        e.preventDefault();
        const $item = $(this).closest('.pro-list-item');
        const $form = $('form.woocommerce-cart-form');

        loading.show()

        const $removeLink = $item.find('.remove-item-link');
        if ($removeLink.length) {
            const removeUrl = $removeLink.attr('href');

            $.ajax({
                type: 'GET',
                url: removeUrl,
                success: function (response) {
                    $item.next('.adt-line').remove();
                    $item.remove();

                    $(document.body).trigger('updated_cart_totals');

                    location.reload();
                },
                error: function () {
                    alert('Error removing item');
                    location.reload();
                },
                complete: function () {
                    loading.hide();
                }
            });
        }
    });

    function initAutocomplete() {
        waitForGoogleMaps(function () {
            let input = $('#sb_user_address').get(0);
            if (!input) { return; }
            // Phase A compatibility shim — bail cleanly when the legacy
            // Places Autocomplete constructor is missing (new Google Cloud
            // projects after March 1, 2025). The address input still
            // accepts manual typing; only the suggestions dropdown is lost.
            if (!(window.google
                && google.maps
                && google.maps.places
                && typeof google.maps.places.Autocomplete === 'function')) {
                if (!window.__adfGPlacesWarned) {
                    window.__adfGPlacesWarned = true;
                    if (window.console && console.warn) {
                        console.warn('AdForest: Google Places Autocomplete unavailable. Falling back to manual address input.');
                    }
                }
                return;
            }
            let autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['geocode']
            });

            autocomplete.addListener('place_changed', function () {
                let place = autocomplete.getPlace();
                if (place.geometry) {
                    let latInput = $('#sb_user_address_lat');
                    let lngInput = $('#sb_user_address_long');
                    if (latInput.length && lngInput.length) {
                        latInput.val(place.geometry.location.lat());
                        lngInput.val(place.geometry.location.lng());
                    }
                }
            });
        });
    }

    if ($('#sb_user_address').length > 0) {
        // google.maps.event.addDomListener(window, 'load', initAutocomplete)
    }

    if ($("#sb_user_address_leaflet").length > 0) {
        document.getElementById('sb_user_address_leaflet').addEventListener('input', function () {
            console.log("This")
            let query = this.value;
            let suggestionsBox = document.getElementById('suggestions-box');

            if (query.length < 3) {
                suggestionsBox.innerHTML = "";
                return;
            }

            var _nomCC = (typeof adforestNominatim !== 'undefined' && adforestNominatim.countrycodes) ? '&countrycodes=' + adforestNominatim.countrycodes : '';
            var _nomFT = (typeof adforestNominatim !== 'undefined' && adforestNominatim.featuretype) ? '&featuretype=' + adforestNominatim.featuretype : '';
            var _nomVB = (typeof adforestNominatim !== 'undefined' && (adforestNominatim.defaultLat || adforestNominatim.defaultLng)) ? '&viewbox=' + (adforestNominatim.defaultLng-1) + ',' + (adforestNominatim.defaultLat-1) + ',' + (adforestNominatim.defaultLng+1) + ',' + (adforestNominatim.defaultLat+1) + '&bounded=0' : '';
            fetch("https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&q=" + encodeURIComponent(query) + _nomCC + _nomFT + _nomVB)
                .then(response => response.json())
                .then(data => {
                    if (typeof adforestNominatim !== 'undefined' && adforestNominatim.featuretype) {
                        var _cityTypes = ['city', 'town', 'village', 'municipality', 'administrative'];
                        data = data.filter(function(p) { return _cityTypes.indexOf(p.type) !== -1; });
                    }
                    suggestionsBox.innerHTML = "";
                    data.forEach(place => {
                        let suggestion = document.createElement('div');
                        suggestion.className = 'suggestion-item';
                        suggestion.textContent = place.display_name;
                        suggestion.addEventListener('click', function () {
                            document.getElementById('sb_user_address_leaflet').value = place.display_name;
                            let latInput = $('#sb_user_address_lat');
                            let lngInput = $('#sb_user_address_long');
                            if (latInput.length && lngInput.length) {
                                latInput.val(place.lat);
                                lngInput.val(place.lon);
                            }
                            suggestionsBox.innerHTML = "";
                        });
                        suggestionsBox.appendChild(suggestion);
                    });
                })
                .catch(error => {
                    //console.error("Error fetching location suggestions:", error);
                });
        });
    }

    $('#collapseOnez').on('show.bs.collapse', function () {
        $('a[href="#collapseOnez"]').find('.more-less').removeClass('fa-plus').addClass('fa-minus');
    }).on('hide.bs.collapse', function () {
        $('a[href="#collapseOnez"]').find('.more-less').removeClass('fa-minus').addClass('fa-plus');
    });

    /*image sortable for ad detail page*/
    if ($("#sortable").length > 0) {
        $("#sortable").sortable({
            stop: function (event, ui) {
                $("#post_img_ids").val("");
                var current_img = "";
                $(".ui-state-default img").each(function (index) {
                    current_img = current_img + $(this).attr("data-img-id") + ",";
                });
                $("#post_img_ids").val(current_img.replace(/,\s*$/, ""));
            },
        });
        $("#sortable").disableSelection();
    }

    $("#sb_sort_images").on("click", function () {
        $("#sb_loading").show();
        $.post(ajaxurl, {
            action: "sb_sort_images",
            ids: $("#post_img_ids").val(),
            ad_id: $("#current_pid").val(),
            security: $('#adforest_sort_images_nonce').val(),
        }).done(function (response) {
            toastr.success($("#re-arrange-msg").val(), "", {
                timeOut: 4000,
                closeButton: true,
                positionClass: "toast-top-right",
            });
            location.reload();
            $("#sb_loading").hide();
        });
    });

    (function () {
        const countdownContainer = document.querySelector('.adt-deal-countdown-desc-clock');
        const countdownContainerMobile = document.querySelector('.adt-deal-countdown-desc-clock-mobile');

        if (!countdownContainer && !countdownContainerMobile) return;

        const bidDateString = (countdownContainer || countdownContainerMobile).getAttribute('data-biddate');
        if (!bidDateString) return;

        function parseCustomDate(dateStr) {
            const [shortYear, month, dayAndTime] = dateStr.split("-");
            const [day, time] = dayAndTime.split(" ");
            const fullYear = "20" + shortYear;
            return new Date(`${fullYear}-${month}-${day}T${time}`);
        }

        const targetDate = parseCustomDate(bidDateString);

        function updateCountdownAdBidding() {
            const now = new Date();
            const timeRemaining = targetDate - now;

            if (timeRemaining <= 0) {
                clearInterval(countdownInterval);
                if (countdownContainer) countdownContainer.innerHTML = "<p>Countdown ended!</p>";
                if (countdownContainerMobile) countdownContainerMobile.innerHTML = "<p>Countdown ended!</p>";
                return;
            }

            const days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);

            [countdownContainer, countdownContainerMobile].forEach(container => {
                if (!container) return;
                container.querySelector("#days").textContent = days;
                container.querySelector("#hours").textContent = hours;
                container.querySelector("#minutes").textContent = minutes;
                container.querySelector("#seconds").textContent = seconds;
            });
        }

        const countdownInterval = setInterval(updateCountdownAdBidding, 1000);
        updateCountdownAdBidding();
    })();

    $('#scrollToMessageBox').on('click', function () {
        const $target = $('#message_box');
        if ($target.length) {
            $('html, body').animate({
                scrollTop: $target.offset().top
            }, 600);
        }
    });

    $(document).on('click', '.btn-adpost-start', function (e) {
        const isUpdate = new URLSearchParams(window.location.search).has('id');

        if (!isUpdate && sb_options.pay_per_post_option != 1) {
            const isAdminAllowed = sb_options.admin_allow_unlimited_ads != 1;
            const isNotAdmin = sb_options.is_current_user_admin == "not_admin";

            if ((isNotAdmin || isAdminAllowed) && $('input.ads-package-radio:checked').length === 0) {
                e.preventDefault();
                e.stopPropagation();

                if (sb_options.ad_posting_mode == 'paid_post') {
                    toastr.error(
                        sb_options.pkg_error,
                        sb_options.pkg_required,
                        {
                            timeOut: 5000,
                            closeButton: true,
                            positionClass: "toast-top-right",
                        }
                    );
                }

                // if ($('#ad_post_packages_container').length) {
                //     $('html, body').animate({
                //         scrollTop: $('#ad_post_packages_container').offset().top - 100
                //     }, 500);
                // }

                return false;
            }
        }
    });

    const isUpdate = new URLSearchParams(window.location.search).has('id');

    if (!isUpdate && sb_options.pay_per_post_option != 1) {
        if (sb_options.is_current_user_admin == "not_admin") {
            if ($('input.ads-package-radio:checked').length === 0) {
                // $('.btn-adpost-start').attr('disabled', 'disabled');

                if (sb_options.ad_posting_mode == 'paid_post') {
                    $('.btn-adpost-start').addClass('package-required');
                }
            }
        }

        if (sb_options.admin_allow_unlimited_ads != 1) {
            if ($('input.ads-package-radio:checked').length === 0) {
                // $('.btn-adpost-start').attr('disabled', 'disabled');

                if (sb_options.ad_posting_mode == 'paid_post') {
                    $('.btn-adpost-start').addClass('package-required');
                }
            }
        }
    }

    $(document).on('change', 'input.ads-package-radio', function () {
        var jsonString = $(this).data('package');
        console.log("jsonString: " + JSON.stringify(jsonString));
        loading.show();

        try {
            var pkgData = typeof jsonString === 'object'
                ? jsonString
                : JSON.parse(jsonString);

            // Store the package data globally
            selectedPackageData = pkgData;

            // Update dropzone maxFiles if in paid posting mode
            if (typeof sb_options !== 'undefined' && sb_options.ad_posting_mode === 'paid_post') {
                console.log("Updating dropzone with package num_of_images:", pkgData.num_of_images);
                // The dropzone might not be initialized yet, but updateDropzoneMaxFiles will handle this gracefully
                updateDropzoneMaxFiles(pkgData.num_of_images);

                // Save package data to user meta for server-side validation
                savePackageDataToUserMeta(pkgData);
            }

            $("#v-pills-info-tab").show();
            $("#v-pills-images-tab").show();
            $("#v-pills-contact-tab").show();
            $('.ad_yvideo_container').show();
            $('.ad_tags_container').show();
            $('.bidding-content').show();

            if (pkgData.video_links !== "yes") {
                $('.ad_yvideo_container').html('');
            }

            if (pkgData.allow_tags !== "yes") {
                $('.ad_tags_container').html('');
            }

            if (pkgData.allow_bidding == 0 || pkgData.allow_bidding == "") {
                $('.bidding-content').hide();
                $('#biddingSection').html('');
            } else {
                // Package allows bidding — but the per-category "Selective"
                // bidding rule (sb_make_bid_categorised + bid_categorised_type)
                // may still forbid it for the chosen category. Without this
                // re-check the Bidding accordion header stays visible while
                // its body stays display:none, producing the "visible but
                // not clickable" state.
                //
                // Call the bidding check directly via the global hook —
                // previously this fired `$catSel.trigger('change')`, which
                // also re-ran the FULL category-change handler and re-fetched
                // packages, blowing away the radio button the user had just
                // selected. The user would then be unable to submit because
                // the "selected" package was wiped between step 1 and step 2.
                if (typeof window.adfCheckBiddingCat === 'function') {
                    var $catSelBid = $('#ad_post_category_select');
                    if ($catSelBid.length) {
                        window.adfCheckBiddingCat($catSelBid.val());
                    }
                }
            }

            updateFeaturedAdsCount(pkgData.featured_ads);

            $('.btn-adpost-start').removeClass('package-required');

            toastr.success(
                sb_options.pkg_success,
                sb_options.success,
                {
                    timeOut: 3000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                }
            );

            loading.hide();
        } catch (e) {
            console.error('Failed to parse package JSON:', e, jsonString);

            toastr.error(
                "Failed to load package details. Please try again.",
                "Error",
                {
                    timeOut: 5000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                }
            );

            loading.hide();
        }
    });

    function updateDropzoneMaxFiles(numOfImages) {
        console.log("updateDropzoneMaxFiles called with:", numOfImages);

        var dropzoneElement = $("#img_dropzone").get(0);
        if (dropzoneElement && dropzoneElement.dropzone) {
            var dropzone = dropzoneElement.dropzone;

            var maxFiles = null;
            if (numOfImages && numOfImages !== '' && numOfImages !== 'null') {
                if (numOfImages === '-1' || numOfImages === -1) {
                    maxFiles = null; // Unlimited
                } else {
                    maxFiles = parseInt(numOfImages);
                }
            }

            dropzone.options.maxFiles = maxFiles;

            $("#sb_upload_limit").val(maxFiles);

            if (maxFiles !== null) {
                $("#max_upload_images").val(maxFiles);
            } else {
                $("#max_upload_images").val(sb_options.sb_upload_limit || 2);
            }

            console.log("Updated dropzone maxFiles to: " + maxFiles);
        } else {
            console.log("Dropzone not found or not initialized yet, cannot update maxFiles");
        }
    }

    function resetPackageSelectionUI() {
        console.log("Resetting package selection UI");
        $("#v-pills-info-tab").hide();
        $("#v-pills-images-tab").hide();
        $("#v-pills-contact-tab").hide();
        $('.ad_yvideo_container').hide();
        $('.ad_tags_container').hide();
        $('.bidding-content').hide();

        $('.btn-adpost-start').addClass('package-required');

        $('.make-feature h3 small').text('');
        $('.make-feature').hide();
        $('.make_featured_box').hide();

        clearPackageDataFromUserMeta();
    }

    function savePackageDataToUserMeta(pkgData) {
        console.log("Saving package data to user meta:", pkgData);

        $.post(ajaxurl, {
            action: "save_package_data_to_user_meta",
            package_data: pkgData,
            security: sb_options.nonce || ""
        }).done(function (response) {
            if (response.success) {
                console.log("Package data saved successfully to user meta");
            } else {
                console.error("Failed to save package data to user meta:", response.data);
            }
        }).fail(function (error) {
            console.error("Error saving package data to user meta:", error);
        });
    }

    function clearPackageDataFromUserMeta() {
        console.log("Clearing package data from user meta");

        $.post(ajaxurl, {
            action: "clear_package_data_from_user_meta",
            security: sb_options.nonce || ""
        }).done(function (response) {
            if (response.success) {
                console.log("Package data cleared successfully from user meta");
            } else {
                console.error("Failed to clear package data from user meta:", response.data);
            }
        }).fail(function (error) {
            console.error("Error clearing package data from user meta:", error);
        });
    }

    jQuery(document).ready(function ($) {
        $('.like-btn, .dislike-btn').on('click', function (e) {
            e.preventDefault();
            $('#sb_loading').show();

            let comment_id = $(this).data('comment_id');
            let type = $(this).hasClass('like-btn') ? 'like' : 'dislike';
            let $likeButton = $('.like-btn[data-comment_id="' + comment_id + '"]');
            let $dislikeButton = $('.dislike-btn[data-comment_id="' + comment_id + '"]');
            let $likeCount = $likeButton.find('.like-count');
            let $dislikeCount = $dislikeButton.find('.dislike-count');

            $.ajax({
                url: ajaxurl, type: 'POST', data: {
                    action: 'handle_like_dislike',
                    comment_id: comment_id,
                    type: type,
                    security: $('#handle_like_dislike_nonce').val(),
                }, success: function (response) {
                    if (response.success) {
                        var likes = response.data.likes;
                        var dislikes = response.data.dislikes;

                        $likeCount.text(likes);
                        $dislikeCount.text(dislikes);

                        if (type == 'like') {
                            $likeButton.find('i')
                                .removeClass('far fa-thumbs-up')
                                .addClass('fas fa-thumbs-up text-primary');

                            if ($dislikeButton.find('i').hasClass('fas')) {
                                $dislikeButton.find('i')
                                    .removeClass('fas fa-thumbs-down text-danger')
                                    .addClass('far fa-thumbs-down');
                            }
                        } else {
                            $dislikeButton.find('i')
                                .removeClass('far fa-thumbs-down')
                                .addClass('fas fa-thumbs-down text-danger');

                            if ($likeButton.find('i').hasClass('fas')) {
                                $likeButton.find('i')
                                    .removeClass('fas fa-thumbs-up text-primary')
                                    .addClass('far fa-thumbs-up');
                            }
                        }

                    } else {
                        $('#sb_loading').hide();
                        toastr.error(response.data.message, '', {
                            timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right"
                        });
                    }
                    $('#sb_loading').hide();
                }, error: function () {
                    $('#sb_loading').hide();
                    console.log('Error occurred while processing the request');
                }
            });
        });
    });

    // $(document).ready(function() {
    //     $(document).on('mouseenter', '.btn-adpost-start.package-required', function() {
    //         if ($(this).is(':disabled')) {
    //             toastr.error(
    //                 sb_options.pkg_error,
    //                 sb_options.pkg_required,
    //                 {
    //                     timeOut: 2000,
    //                     closeButton: false,
    //                     positionClass: "toast-top-right",
    //                 }
    //             );
    //         }
    //     });
    // });

    function updateFeaturedAdsCount(featuredAdsCount) {
        var countText = '';

        if (featuredAdsCount === '-1') {
            countText = sb_options.remaining_featured_ads + ": " + sb_options.unlimited_string;
        } else if (featuredAdsCount > 0) {
            countText = sb_options.remaining_featured_ads + ': ' + featuredAdsCount;
        } else {
            countText = sb_options.no_featured_ads + ` <a href="${sb_options.packages_page_link}">${sb_options.here}</a>`;
        }

        if (featuredAdsCount > 0 || featuredAdsCount === '-1') {
            $('.make-feature h3 small').text(countText);
            $('.make-feature').show();
            $('.make_featured_box').show();
        } else {
            $('.make-feature h3 small').html(countText);
            $('.make-feature').show();
            $('.make_featured_box').hide();
        }
    }

    $('#mark-all-read').on('click', function (e) {
        e.preventDefault();

        var $btn = $(this), nonce = $btn.data('nonce');
        $btn.prop('disabled', true).text(sb_options.marking_all_read + "...");
        $.post(
            ajaxurl,
            {
                action: 'adforest_mark_all_read',
                nonce: nonce
            },
            function (response) {
                if (response.success) {
                    $('.notification-item.unread').removeClass('unread').addClass('read');
                    $('.msgs_count').text(0);
                    $btn.text(sb_options.all_read);
                } else {
                    alert(response.data || 'Could not mark all as read.');
                    $btn.prop('disabled', false).text('Mark all as read');
                }
            }
        );
    });

    jQuery(document).ready(function ($) {
        let selects = [
            '#ad_country_states',
            '#ad_country_cities',
            '#ad_country_towns'
        ];

        selects.forEach(function (sel) {
            let $select = $(sel);

            let $realOptions = $select.find('option').filter(function () {
                return $(this).val().trim() !== '';
            });
            let realCount = $realOptions.length;

            if (realCount === 0) {
                $select.hide();
                $select.nextAll('.select2-container').first().hide();
                $select.closest('.col-md-6').hide();
            }
        });
    });


    // ================================================
    // Main Hero 4 - Address Autocomplete (Standalone)
    // ================================================
    (function ($, window, document) {
        'use strict';

        function initializeGooglePlacesAutocomplete(inputEl, latEl, lngEl) {
            // Phase A compatibility shim — feature-detect the legacy
            // constructor explicitly instead of swallowing a TypeError in
            // try/catch. Same one-time warning behavior as the other
            // call sites. Manual address entry continues to work.
            if (!(window.google
                && google.maps
                && google.maps.places
                && typeof google.maps.places.Autocomplete === 'function')) {
                if (!window.__adfGPlacesWarned) {
                    window.__adfGPlacesWarned = true;
                    if (window.console && console.warn) {
                        console.warn('AdForest: Google Places Autocomplete unavailable. Falling back to manual address input.');
                    }
                }
                return;
            }
            try {
                var autocomplete = new google.maps.places.Autocomplete(inputEl, { types: ['geocode'] });
                autocomplete.addListener('place_changed', function () {
                    var place = autocomplete.getPlace();
                    if (place && place.geometry && place.geometry.location) {
                        if (latEl) latEl.value = place.geometry.location.lat();
                        if (lngEl) lngEl.value = place.geometry.location.lng();
                    }
                    if (place && place.formatted_address) {
                        inputEl.value = place.formatted_address;
                    }
                });
            } catch (e) { }
        }

        function debounce(fn, wait) {
            var timeout;
            return function () {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function () { fn.apply(context, args); }, wait);
            };
        }

        function createSuggestionList($input) {
            var $parent = $input.parent();
            if ($parent.css('position') === 'static') {
                $parent.css('position', 'relative');
            }
            var $list = $('<ul/>', { class: 'adt-address-suggestions' });
            $list.css({
                position: 'absolute',
                zIndex: 9999,
                left: 0,
                top: ($input.outerHeight() || 40) + 4,
                width: $input.outerWidth() || '100%',
                maxHeight: 240,
                overflowY: 'auto',
                border: '1px solid #ddd',
                borderRadius: '4px',
                background: '#fff',
                boxShadow: '0 2px 8px rgba(0,0,0,0.08)'
            });
            $input.after($list);
            return $list;
        }

        function clearSuggestions($list) {
            if ($list) { $list.empty().hide(); }
        }

        function initializeLeafletNominatimAutocomplete($input, $lat, $lng) {
            var $wrapper = $input.parent();
            var $list = $wrapper.find('ul.adt-address-suggestions');
            if ($list.length === 0) {
                $list = createSuggestionList($input);
            }

            var search = debounce(function (query) {
                if (!query || query.length < 3) { clearSuggestions($list); return; }
                var _nomCC = (typeof adforestNominatim !== 'undefined' && adforestNominatim.countrycodes) ? '&countrycodes=' + adforestNominatim.countrycodes : '';
                var _nomFT = (typeof adforestNominatim !== 'undefined' && adforestNominatim.featuretype) ? '&featuretype=' + adforestNominatim.featuretype : '';
                var _nomVB = (typeof adforestNominatim !== 'undefined' && (adforestNominatim.defaultLat || adforestNominatim.defaultLng)) ? '&viewbox=' + (adforestNominatim.defaultLng-1) + ',' + (adforestNominatim.defaultLat-1) + ',' + (adforestNominatim.defaultLng+1) + ',' + (adforestNominatim.defaultLat+1) + '&bounded=0' : '';
                var url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=5&q=' + encodeURIComponent(query) + _nomCC + _nomFT + _nomVB;
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (res) { return res.json(); })
                    .then(function (results) {
                        if (typeof adforestNominatim !== 'undefined' && adforestNominatim.featuretype) {
                            var _cityTypes = ['city', 'town', 'village', 'municipality', 'administrative'];
                            results = results.filter(function(item) { return _cityTypes.indexOf(item.type) !== -1; });
                        }
                        $list.empty();
                        if (!Array.isArray(results) || results.length === 0) { $list.hide(); return; }
                        results.forEach(function (item) {
                            var label = item.display_name || '';
                            var $li = $('<li/>', { text: label });
                            $li.css({ cursor: 'pointer', padding: '8px 10px', background: '#fff' });
                            $li.on('mouseenter', function () { $(this).css('background', '#f5f5f5'); });
                            $li.on('mouseleave', function () { $(this).css('background', '#fff'); });
                            $li.on('mousedown', function (e) { // mousedown to fire before input blur
                                e.preventDefault();
                                $input.val(label);
                                if ($lat.length) { $lat.val(item.lat); }
                                if ($lng.length) { $lng.val(item.lon); }
                                clearSuggestions($list);
                            });
                            $list.append($li);
                        });
                        $list.show();
                    })
                    .catch(function () { clearSuggestions($list); });
            }, 300);

            $input.on('input', function () { search($input.val()); });
            $input.on('blur', function () { setTimeout(function () { clearSuggestions($list); }, 150); });
            $input.on('focus', function () { if ($list.children().length > 0) { $list.show(); } });
        }

        jQuery(document).ready(function ($) {
            var $section = $('.adt-smart-ads-section');
            if ($section.length === 0) { return; }

            var $input = $section.find('#sb_user_address');
            if ($input.length === 0) { return; }

            // Avoid double init
            if ($input.data('adt-address-init')) { return; }

            var inputEl = $input.get(0);
            var $lat = $section.find('#sb_user_address_lat');
            var $lng = $section.find('#sb_user_address_long');

            var attempts = 0;
            var maxAttempts = 40;
            var interval = setInterval(function () {
                attempts++;
                var hasGoogle = (typeof window.google !== 'undefined' &&
                    google && google.maps && google.maps.places);
                var hasLeaflet = (typeof window.L !== 'undefined');

                if (hasGoogle) {
                    clearInterval(interval);
                    $input.data('adt-address-init', true);
                    initializeGooglePlacesAutocomplete(inputEl, $lat.get(0), $lng.get(0));
                } else if (hasLeaflet) {
                    clearInterval(interval);
                    $input.data('adt-address-init', true);
                    initializeLeafletNominatimAutocomplete($input, $lat, $lng);
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                }
            }, 200);
        });
    })(jQuery, window, document);

    $(".user_rating_dlt").on("click", function () {
        var confirmed = confirm($(this).attr("data-confirmation"));
        $("#sb_loading").show();
        if (confirmed) {
            $.post(ajaxurl, {
                action: "sb_delete_user_rating_frontend",
                user_id: $(this).attr("data-userid"),
                security: $('#adforest_delete_user_rating_frontend_nonce').val(),
            }).done(function (response) {
                $("#sb_loading").hide();
                var get_p = response.split("|");
                if (get_p[0].trim() == 1) {
                    toastr.success(get_p[1], "", {
                        timeOut: 4000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                    });
                    window.location.reload();
                } else if (get_p[0].trim() == 0) {
                    toastr.error(get_p[1], "", {
                        timeOut: 4000,
                        closeButton: true,
                        positionClass: "toast-top-right",
                    });
                }
            });
        }
    });

    // ===============================================
    // GLOBAL POST-AJAX TOOLTIP SYNC (FINAL FIX)
    // Runs after EVERY AJAX request completes. Syncs every favourite-heart
    // tooltip to its actual DOM state — catches cases where handlers finish
    // in unexpected order or third-party scripts overwrite attributes.
    // ===============================================
    $(document).ajaxComplete(function () {

        $('.ad_to_fav, .save-ad').each(function () {

            var $btn = $(this);
            var $icon = $btn.find('i');

            // Determine actual DOM state
            var isFav = $btn.hasClass('ad-favourited') || $icon.hasClass('fas');

            var newText = isFav
                ? "Click to remove from favourite"
                : "Click to make it favourite";

            if (typeof adforestFav !== "undefined") {
                newText = isFav ? adforestFav.titleRemove : adforestFav.titleAdd;
            }

            // Reset attributes (avoid Bootstrap cache issue)
            $btn.removeAttr("data-original-title");
            $btn.removeAttr("data-bs-original-title"); // Bootstrap 5 cache
            $btn.attr("title", newText);
            $btn.attr("aria-label", newText);

            // Refresh tooltip safely — BS4 (jQuery) and BS5 (native)
            if (typeof $btn.tooltip === "function") {
                try {
                    $btn.tooltip('dispose');
                    $btn.tooltip({ html: true });
                } catch (e) {}
            }

            if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
                try {
                    var instance = bootstrap.Tooltip.getInstance($btn.get(0));
                    if (instance) instance.dispose();
                    new bootstrap.Tooltip($btn.get(0));
                } catch (e) {}
            }

        });

    });

    // ===============================================
    // BIDDING CATEGORY VISIBILITY
    // When the ad-posting category dropdown changes, query the server to check
    // whether the selected category has bidding enabled under selective mode.
    // Show / hide the bidding section in response.
    // ===============================================
    (function () {
        var $catSelect = $('#ad_post_category_select');
        if (!$catSelect.length) { return; }

        function adfCheckBiddingCat(catId) {
            if (typeof ajaxurl === 'undefined') { return; }
            // Bidding trigger + panel. Both live in ad_post_shortcode.php
            // (.bidding-content + #biddingSection).
            //
            // Hide BOTH synchronously before firing the AJAX. Without this
            // pre-emptive hide, two race windows can leave the accordion
            // header visible while its panel stays display:none — the
            // "visible but not clickable" state the user reports on the
            // Modern page when in Paid Posting + Selective Bidding mode:
            //
            //   1. The package-selection handler shows `.bidding-content`
            //      synchronously (so the user sees it during the AJAX wait)
            //      then only async-triggers this category re-check.
            //   2. The initial PHP render sets `style="display:none"` on
            //      the panel — once the trigger is shown by anything else,
            //      clicking the trigger fires Bootstrap collapse but the
            //      inline style wins and the panel never expands.
            //
            // Hiding both up-front, then reveal only after a positive
            // response, closes both windows. Classic and Modern both
            // benefit because both pages share this handler.
            var $trigger = $('.bidding-content');
            var $panel   = $('#biddingSection');
            $trigger.hide();
            $panel.hide();
            $.post(ajaxurl, {
                action: 'adforest_check_bidding_cat',
                cat_id: catId
            }).done(function (response) {
                if ((response + '').trim() === '1') {
                    $trigger.show();
                    $panel.show();
                }
                // else: keep both hidden (already hidden synchronously above)
            });
        }

        // Expose the per-category bidding check to other handlers so they
        // can re-validate bidding visibility WITHOUT triggering a full
        // category-change cascade (which would re-fetch packages and wipe
        // the user's package selection — the cause of the "package gets
        // deselected when moving from step 1 to step 2" bug).
        window.adfCheckBiddingCat = adfCheckBiddingCat;

        // Run once on page load for the initial category, then on every change
        var initial = $catSelect.val();
        if (initial) { adfCheckBiddingCat(initial); }

        $catSelect.on('change', function () {
            adfCheckBiddingCat($(this).val());
        });
    })();

    // ===============================================
    // FINAL LOCK: MutationObserver
    // Enforcement layer — if any script (theme, plugin, Bootstrap's fixTitle, etc.)
    // overwrites the `title` attribute on a favourite heart, immediately restore
    // the correct state-derived value. Also handles newly-added hearts via infinity-scroll.
    //
    // Guarded against infinite loops via the `$btn.attr("title") !== newText` check —
    // if the title already matches the desired value, no mutation is triggered.
    // ===============================================
    (function () {

        if (typeof MutationObserver === 'undefined' || !document.body) {
            return; // Older browser or very early init — skip silently
        }

        function syncTooltip($btn) {

            var $icon = $btn.find('i');

            var isFav = $btn.hasClass('ad-favourited') || $icon.hasClass('fas');

            var newText = isFav
                ? "Click to remove from favourite"
                : "Click to make it favourite";

            if (typeof adforestFav !== "undefined") {
                newText = isFav ? adforestFav.titleRemove : adforestFav.titleAdd;
            }

            // Read both BS4 and BS5 cache attrs to detect stale values
            var currentTitle    = $btn.attr("title");
            var currentBs4Cache = $btn.attr("data-original-title");
            var currentBs5Cache = $btn.attr("data-bs-original-title");

            // Rewrite only if any attribute diverges from desired value (loop guard)
            if (currentTitle !== newText ||
                (currentBs4Cache && currentBs4Cache !== newText) ||
                (currentBs5Cache && currentBs5Cache !== newText)) {

                $btn.removeAttr("data-original-title");
                $btn.removeAttr("data-bs-original-title"); // Bootstrap 5 cache — MUST clear
                $btn.attr("title", newText);
                $btn.attr("aria-label", newText);
            }
        }

        // Observe DOM changes (covers both BS4 and BS5 cache attribute names)
        var observer = new MutationObserver(function (mutations) {

            mutations.forEach(function (mutation) {

                if (mutation.type === "attributes" &&
                    (mutation.attributeName === "title" ||
                     mutation.attributeName === "data-original-title" ||
                     mutation.attributeName === "data-bs-original-title")) {

                    var $btn = $(mutation.target);

                    if ($btn.is('.ad_to_fav, .save-ad')) {
                        syncTooltip($btn);
                    }
                }

                if (mutation.type === "childList") {

                    $(mutation.addedNodes).each(function () {

                        // mutation.addedNodes can include text nodes; only process elements
                        if (this.nodeType !== 1) { return; }

                        var $node = $(this);

                        if ($node.is('.ad_to_fav, .save-ad')) {
                            syncTooltip($node);
                        }

                        $node.find('.ad_to_fav, .save-ad').each(function () {
                            syncTooltip($(this));
                        });
                    });
                }

            });

        });

        // Start observing whole document body.
        // Watch both Bootstrap 4 (`data-original-title`) and Bootstrap 5 (`data-bs-original-title`)
        // cache attributes in addition to the raw `title` — any stale value in any of these
        // three locations can cause the visible tooltip to show wrong text.
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['title', 'data-original-title', 'data-bs-original-title'],
            childList: true,
            subtree: true
        });

    })();
});
