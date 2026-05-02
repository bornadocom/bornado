(function ($) {
    jQuery(document).ready(function () {
        var $ = jQuery;
        var sbchatNonces = (typeof localize_vars !== 'undefined' && localize_vars.nonces) ? localize_vars.nonces : {};
        var reloadMessagesNonce = sbchatNonces.reload_incoming_messages || '';
        var loadConversationsNonce = sbchatNonces.load_conversations_list || '';

        const $body = $('#sbModalBody');
        if ($body.length) {
            $body.scrollTop($body[0].scrollHeight);
        }

        const target = document.querySelector('#sbModalBody .messages-list');
        if (target) {
            const observer = new MutationObserver(() => {
                const $body = $('#sbModalBody');
                if ($body.length) {
                    $body.scrollTop($body[0].scrollHeight);
                }
            });

            observer.observe(target, { childList: true });
        }

        $('.sbchat-myBtn').on('click', function () {

            $("#sb_loading").show();
            $(this).prop('disabled', 'true');
            var xhr = localize_vars.sbAjaxurl;
            post_id = $(this).attr('data-post_id');
            user_id = $(this).attr('data-user_id');
            $.ajax({
                type: 'post',
                url: xhr,
                data: {action: 'sb_get_popup_data', post_id: post_id, user_id: user_id},
                success: function (response) {
                    $("#sb_loading").hide();
                    $(this).prop('disabled', 'false');

                    if (response.success) {
                        $('#sbchatModal').html(response.data.html);

                        modal.style.display = "block";
                    }
                },
                error: function (error) {
                    $("#sb_loading").hide();
                    $(this).prop('disabled', 'false');
                    if (error.status !== 200) {
                        console.log(error);
                    }
                }
            });

        });


        $(document).on('click', '.sb-chat-close', function () {
            modal.style.display = "none";
        })

        var btn = document.getElementById("sbchat-myBtn");
        var span = document.getElementsByClassName("sb-chat-close")[0];
        if (span !== undefined && span.innerHTML.length > 0) {
            span.onclick = function () {
                modal.style.display = "none";
                btn.style.display = "";
            }
        }

        var modal = document.getElementById("sbchatModal");
        if (modal !== null && modal.innerHTML.length > 0) {
            window.onclick = function (event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                    btn.style.display = "";
                }
            }
        }

        if (document.getElementById('sbchat-mu')) {

            var max_files_upload = $('#dz_max_files_upload').val();
            var max_file_size = $('#dz_max_file_size').val();
            var allowed_mime_types = $('#dz_allowed_mime_types').val();

            var inboxDropzone = new Dropzone('#sbchat-mu', {
                url: localize_vars.sbAjaxurl,
                paramName: 'sbchat_media_uploads',
                uploadMultiple: false,
                filesizeBase: 1024,
                parallelUploads: max_files_upload,
                maxFiles: max_files_upload,
                maxFilesize: 15,
                acceptedFiles: allowed_mime_types,
                addRemoveLinks: false,
                previewsContainer: '#attachment-wrapper',
                autoProcessQueue: true,
                dictCancelUpload: 'Cancel Upload',
                previewTemplate: '<span class="dz-preview dz-file-preview"><span data-dz-errormessage><img data-dz-thumbnail class="data-dz-thumbnail" src ="#" /><span class="dz-details"><span class="dz-filename"><span data-dz-name></span></span><span class="dz-size" data-dz-size></span></span><i class="fa fa-times" data-dz-remove></i><span class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></span></span>',
            });

            inboxDropzone.on('addedfile', function(file) {

                var ext = file.name.split('.').pop();

                if (ext == "pdf") {
                    $(file.previewElement).find(".data-dz-thumbnail").attr("src", `${localize_vars.image_dir}icons/pdf-icon.svg`);
                } else if (ext == "txt") {
                    $(file.previewElement).find(".data-dz-thumbnail").attr("src", `${localize_vars.image_dir}icons/txt-icon.svg`);
                } else if (ext == "doc") {
                    $(file.previewElement).find(".data-dz-thumbnail").attr("src", `${localize_vars.image_dir}icons/doc-icon.svg`);
                } else if (ext == "docx") {
                    $(file.previewElement).find(".data-dz-thumbnail").attr("src", `${localize_vars.image_dir}icons/docx-icon.svg`);
                } else if (ext == "xls") {
                    $(file.previewElement).find(".data-dz-thumbnail").attr("src", `${localize_vars.image_dir}icons/xls-icon.svg`);
                } else if (ext == "xlsx") {
                    $(file.previewElement).find(".data-dz-thumbnail").attr("src", `${localize_vars.image_dir}icons/xlsx-icon.svg`);
                } else if (ext == "ppt") {
                    $(file.previewElement).find(".data-dz-thumbnail").attr("src", `${localize_vars.image_dir}icons/ppt-icon.svg`);
                } else if (ext == "pptx") {
                    $(file.previewElement).find(".data-dz-thumbnail").attr("src", `${localize_vars.image_dir}icons/pptx-icon.svg`);
                }
            });

            inboxDropzone.on('uploadprogress', function (file, progress, bytesSent) {
                inboxDropzone.off('error');
                if (file.previewElement) {
                    var progressElement = file.previewElement.querySelector("[data-dz-uploadprogress]");
                    progressElement.style.width = progress + "%";
                }
            });

            inboxDropzone.on('success', function(file, response) {
                // File uploaded successfully
                console.log('File uploaded:', file.name);
            });

            inboxDropzone.on('error', function(file, errorMessage) {
                console.error('Upload error:', errorMessage);
                // Show error message to user
                if (file.previewElement) {
                    $(file.previewElement).append('<div class="dz-error-message">Upload failed: ' + errorMessage + '</div>');
                }
            });

            inboxDropzone.on('maxfilesexceeded', function () {
                inboxDropzone.removeAllFiles(true);
                var error = '<p class="maxfilesexceeded error" style="display: none; color: red;">Only ' + inboxDropzone.options.maxFiles + ' files can be uploaded at a time.</p>';
                $('.dropzone-errors').append(error);
                $('.maxfilesexceeded.error').show(1000);
                setTimeout(
                    function () {
                        $('.maxfilesexceeded.error').hide(1000);
                        $('.maxfilesexceeded.error').remove();
                    }, 5000);
            });
        }


        $(document).on('submit', '.send-message', function (e) {

            e.preventDefault();
            var conversation_id = $('#conversation_id').val();
            if (conversation_id == 0 || conversation_id == null || conversation_id == '')
                return false;

            var message = $('#message_box').val();
            var hasFiles = inboxDropzone && inboxDropzone.files && inboxDropzone.files.length > 0;

            console.log(message);
            // Check if both message and files are empty
            if ((!message || message.length == 0 || message.trim() == '') && !hasFiles) {
                $("#message_box").focus();
                return;
            }
            var recipient_id = $('#recipient_id').val();

            if (recipient_id == 0 || recipient_id == null || recipient_id == '')
                return;


            var unique_message_id = Math.floor(Math.random() * Math.floor(Math.random() * Date.now()));

            var sbchat_inbox = new FormData();
            var xml_http_request_url = localize_vars.sbAjaxurl;

            sbchat_inbox.append('action', 'sb_send_message_ajax');
            sbchat_inbox.append('conversation_id', conversation_id);
            sbchat_inbox.append('message', message);
            sbchat_inbox.append('unique_message_id', unique_message_id);
            sbchat_inbox.append('recipient_id', recipient_id);

            console.log(inboxDropzone);
            if (inboxDropzone) {
                var all_files = inboxDropzone.files;
                if (all_files.length > 0) {

                    for (var i = 0; i < all_files.length; i++) {
                        sbchat_inbox.append('sbchat_mu_' + i, all_files[i]);
                        sbchat_inbox.append('sbchat_mu_uuid_' + i, all_files[i].upload['uuid']);
                        sbchat_inbox.append('sbchat_mu_durl_' + i, all_files[i].dataURL);
                    }

                    inboxDropzone.removeAllFiles(true);
                }
            }

            $(this).find('button.send-btn').prop('disabled', true);
            
            // Add loading state to send button
            var $sendBtn = $(this).find('button.send-btn');
            var originalText = $sendBtn.html();
            $sendBtn.html('<i class="fa fa-spinner fa-spin"></i> Sending...');
            
            // Only append message bubble if there's actual text content
            if (message && message.trim() !== '') {
                $('div.msg-body ul').append('<li id="umid_' + unique_message_id + '" class="message-bubble new-message reply sending"><div class="message-text"><p>' + message + '</p></div></li>');
            }
            
            // Clear message box
            $('#message_box').val('');
            $.ajax({
                type: 'post',
                url: xml_http_request_url,
                data: sbchat_inbox,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response) {
                        if (response.success) {
                            // Remove temporary message bubble
                            if (message && message.trim() !== '') {
                                $('.new-message').remove();
                            }
                            
                            // Refresh the chat once to show the actual message from server
                            setTimeout(function() {
                                refreshChatMessages(conversation_id);
                            }, 500);
                            
                        } else {
                            toastr.error(response.data.message, '', {
                                timeOut: 8000, "closeButton":
                                    true, "positionClass": "toast-top-right", "showMethod": "slideDown",
                                "hideMethod": "slideUp"
                            });
                            // Only remove message bubble if it exists
                            if (message && message.trim() !== '') {
                                $('.new-message').remove();
                            }
                        }

                        if (response.data.upload_previews) {
                            $('div.msg-body ul').append(response.data.upload_previews);
                            $('div.msg-body ul li').show(500);
                            refreshFsLightbox();
                            
                            // Refresh chat once after file upload to ensure proper display
                            setTimeout(function() {
                                refreshChatMessages(conversation_id);
                            }, 1000);
                        }
                    }
                    $('.send-message')[0].reset();
                    $('button.send-btn').prop('disabled', false);
                    
                    // Restore send button text
                    $sendBtn.html(originalText);
                    
                    var scrollToTarget = $('#sbModalBody');
                    scrollToTarget.animate({scrollTop: 9999}, 1000);
                },
                error: function (error) {
                    if (error.status !== 200) {
                        // Remove temporary message on error
                        if (message && message.trim() !== '') {
                            $('.new-message').remove();
                        }
                    }
                    
                    // Restore send button text on error
                    $sendBtn.html(originalText);
                    $('button.send-btn').prop('disabled', false);
                }
            });
        });

        // Function to refresh chat messages
        function refreshChatMessages(conversation_id) {
            if (!conversation_id) return;
            
            $.ajax({
                type: 'POST',
                url: localize_vars.sbAjaxurl,
                data: {
                    action: 'sb_notification_ajax',
                    conv_id: conversation_id,
                    async: true
                },
                success: function(response) {
                    if (response.success) {
                        // Update messages list
                        if (response.data.result) {
                            $(".messages-list").html(response.data.result);
                        }
                        
                        // Refresh lightbox for new images
                        if (typeof refreshFsLightbox === 'function') {
                            refreshFsLightbox();
                        }
                        
                        // Scroll to bottom
                        var scrollToTarget = $('#sbModalBody');
                        if (scrollToTarget.length) {
                            scrollToTarget.animate({scrollTop: 9999}, 500);
                        }
                    }
                },
                error: function(error) {
                    console.log('Error refreshing chat:', error);
                }
            });
        }



        $('.load-conversations').on('click', function (event) {

            event.preventDefault();
            var load_conversations_btn = $(this);

            var limit = $(this).attr('data-limit');
            if (limit == '' || limit <= 0)
                return false;

            var offset = $(this).attr('data-offset');
            if (offset == '' || offset <= 0 || offset < limit)
                return false;

            var context = $('.messages-inbox').attr('data-context');
            if (context == '' || context == null)
                return false;

            var xhr = localize_vars.sbAjaxurl;

            $.ajax({
                type: 'post',
                url: xhr,
                data: {action: 'load_conversations_list', limit: limit, offset: offset, context: context, security: loadConversationsNonce},
                success: function (response) {

                    if (response.success) {

                        var new_offset = response.data.offset;
                        load_conversations_btn.attr('data-offset', new_offset);

                        var conversations_list = response.data.conversations_list;
                        var notify_text = 'Load more (' + new_offset + ')';
                        load_conversations_btn.text(notify_text);

                        $('.chat-list-detail').append(conversations_list);
                        $('.chat-list-detail li').show('slow');
                    }
                },
                error: function (error) {
                    if (error.status !== 200) {
                        console.log(error);
                    }
                }
            });
        });

        $(document).on('submit', '.sbchat-popup-message', function (event) {

            event.preventDefault();
            var conversation_id = $('#conversation_id').val();
            if (conversation_id == null || conversation_id == '') {
                return false;
            }

            var $form = $(this);

            var message = $.trim(
                $form.find('textarea[name="message_box"]').val() || ''
            );
            console.log('raw:', $form.find('textarea[name="message_box"]').val());
            console.log('trimmed:', message);

            if (!message) {
                $form.find('textarea[name="message_box"]').focus();
                return false;
            }
            var recipient_id = $('#recipient_id').val();
            if (recipient_id == 0 || recipient_id == null || recipient_id == '') {
                return false;
            }

            var post_id = $('#post_id').val();
            if (post_id == 0 || post_id == null || post_id == '') {
                return false;
            }

            var xml_http_request_url = localize_vars.sbAjaxurl;

            var sbchat_messsage = new FormData();
            sbchat_messsage.append('action', 'sb_send_message_ajax');
            sbchat_messsage.append('conversation_id', conversation_id);
            sbchat_messsage.append('message', message);
            sbchat_messsage.append('recipient_id', recipient_id);
            sbchat_messsage.append('post_id', post_id);

            $(this).find('sbchat-popup-send').prop('disabled', true);
            //  $( 'div.msg-body ul' ).append('<li class="message-bubble reply"><div class="message-text"><p>' + message + '</p></div></li>' );

            var this_value = $(this);
            this_value.find('div.bubbles').show();

            $.ajax({
                type: 'post',
                url: xml_http_request_url,
                data: sbchat_messsage,
                processData: false,
                contentType: false,
                success: function (response) {
                    this_value.find('div.bubbles').hide();
                    if (response) {

                        if (response.success) {
                            toastr.success(response.data.message, '', {
                                timeOut: 8000, "closeButton":
                                    true, "positionClass": "toast-top-right", "showMethod": "slideDown",
                                "hideMethod": "slideUp"
                            });
                        } else {
                            toastr.error(response.data.message, '', {
                                timeOut: 8000, "closeButton":
                                    true, "positionClass": "toast-top-right", "showMethod": "slideDown",
                                "hideMethod": "slideUp"
                            });

                        }

                        $('.sbchat-popup-message')[0].reset();
                        $('#sbchatModal').hide();

                        $('.sbchat-popup-send').prop('disabled', false);
                    }
                },
                error: function (error) {
                    if (error.status !== 200) {
                        console.log('error');
                    }
                }
            });
        });


        if (localize_vars.sb_notification == true) {
            var context = $('.messages-inbox').attr('data-context');

            if (context === 'user-dashboard' || context === 'inbox' || context === 'sbchat')
                setInterval(sb_automate_notification, localize_vars.notification_time);

            var title = document.title;
            var conversation_id = $('#conversation_id').val();

            var conversations_limit = $('.load-conversations').attr('data-limit');
            var conversations_offset = $('.load-conversations').attr('data-offset');

            function sb_automate_notification() {

                var conversations_limit = $('.load-conversations').attr('data-limit');
                var conversations_offset = $('.load-conversations').attr('data-offset');
                var conversation_id = $('#conversation_id').val();

                if (conversations_limit === '' || conversations_limit <= 0 || !$.isNumeric(conversations_limit))
                    conversations_limit = 7;

                if (conversations_offset === '' || conversations_offset === 0 || !$.isNumeric(conversations_offset))
                    conversations_offset = conversations_limit;

                $.post(localize_vars.sbAjaxurl, {
                    type: "POST",
                    action: 'inbox_reload_incoming_messages',
                    conversation_id: conversation_id,
                    conversations_offset: conversations_offset,
                    context: context,
                    security: reloadMessagesNonce
                }).done(function (response) {
                    if (true === response.success) {
                        var conversation_list_items = response.data.conversation_list_items;
                        var conversation_messages = response.data.conversation_messages;

                        if (conversation_list_items != '' || conversation_list_items != 0) {

                            var chat_list_detail = $('.chat-list-detail').length;
                            if (chat_list_detail > 0) {
                                $('.chat-list-detail').html(conversation_list_items);
                            } else {
                                $('.messages-inbox.chat-list').append('<ul class="chat-list-detail"></ul>');
                                $('.chat-list-detail').html(conversation_list_items);
                            }
                        }

                        if (conversation_messages != '' || conversation_messages != 0)
                            $(".messages-list").html(conversation_messages);

                        refreshFsLightbox();
                    }
                });
            }
        }


        $(document).on('click', '.delete-chat', function () {
            // var sb_nonce = $(".sb_nonce"). val();
            var conv_id = $('#conversation_id').val();
            var delete_text = $('.delete-chat').attr('data-delete');
            if (confirm(delete_text)) {
                $.post(localize_vars.sbAjaxurl, {action: 'sb_delete_chat', conv_id: conv_id}).done(function (response) {
                    if (true === response.success) {
                        $('.sb-notification.success').css("display", "block");
                        setTimeout(function () {
                            location.reload(true);
                        }, 10000);
                    } else {
                        console.log("something went wrong");
                    }
                }).fail(function () {
                    //nonce failed
                });
            }
        });


        $(document).on('click', '.delete-single-chat', function () {
            // var sb_nonce = $(".sb_nonce"). val();
            var conv_id = $('#conversation_id').val();
            var delete_text = $('.delete-single-chat').attr('data-delete');
            if (confirm(delete_text)) {
                $.post(localize_vars.sbAjaxurl, {
                    action: 'sb_delete_single_user_chat',
                    conv_id: conv_id
                }).done(function (response) {
                    if (true === response.success) {
                        $('.sb-notification.success').css("display", "block");

                        toastr.success($('.sb-notification p').html(), '', {
                            timeOut: 8000, "closeButton":
                                true, "positionClass": "toast-top-right", "showMethod": "slideDown",
                            "hideMethod": "slideUp"
                        });


                        setTimeout(function () {
                            window.location.reload();
                        }, 3000);
                    } else {
                        console.log("something went wrong");
                    }
                }).fail(function () {
                    //nonce failed
                });
            }
        });

//Load Remaining Services
// $(document).ready(function () {
//     $(document).on('click', '.load-page', function (e) {
//        e.preventDefault();
//        e.stopPropagation();
//        var freelanceAjaxURL = $("#freelance_ajax_url").val();
//        var currentPage = $(this).data('paged');
//        // AJAX request
//        $.ajax({
//           type: 'POST',
//           url: freelanceAjaxURL,
//           data: {
//              action: 'load_remaining_services',
//              page: currentPage,
//              search_title: $('#serviceSearchInput').val(),
//           },
//           success: function (response) {
//              // Update data attributes for the next click
//              $('#load_remaining_services').data('paged', currentPage + 1);
//              // Append the new services to the container within the modal
//              $('.er-gigs-list').empty().append(response);
//           },
//           error: function (errorThrown) {
//              console.log('AJAX error: ' + errorThrown);
//           }
//        });
//     });
//  });


        $(document).on('click', '.pagination-buttons .load-page', function (e) {
            e.preventDefault();
            var clickedPageLink = $(this);
            $('.loader-outer').show();
            var currentPage = $(this).data('paged');
            var freelanceAjaxURL = $("#freelance_ajax_url").val();
            // AJAX request
            $.ajax({
                type: 'POST',
                url: freelanceAjaxURL,
                data: {
                    action: 'load_remaining_services',
                    page: currentPage,
                    search_title: $('#serviceSearchInput').val(),
                },
                success: function (response) {
                    // Update data attributes for the next click
                    $('.loader-outer').hide();
                    $('#load_remaining_services').data('paged', currentPage + 1);
                    $('.er-gigs-list').empty().append(response);

                    clickedPageLink.closest('li').addClass('active');
                    // Remove active class from other li elements
                    $('.pagination-buttons li').not(clickedPageLink.closest('li')).removeClass('active');

                },
                error: function (errorThrown) {
                    $('.loader-outer').hide();
                    console.log('AJAX error: ' + errorThrown);
                }
            });
        });


//fetching all services//


        $(document).ready(function () {
            // Search functionality
            $('#serviceSearchInput').on('input', function () {
                var searchTerm = $(this).val().toLowerCase();
                $('.er-gigs-list li').each(function () {
                    var serviceName = $(this).find('.pro-name.title').text().toLowerCase();
                    if (serviceName.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });


        $(document).on('click', '.con-chat-list', function (e) {
            e.preventDefault();
            var conv_id = $(this).attr('data-conv');
            var recipient_id = $(this).attr('data-recipient_id');

            $('.message-spin-loader').show();

            $('.chat-list-detail li').removeClass('active');

            // Add 'active' class to parent <li> element
            $(this).parent('li').addClass('active');

            $.post(localize_vars.sbAjaxurl, {
                action: 'sb_notification_ajax',
                conv_id: conv_id,
                async: true
            }).done(function (response) {

                $('.message-spin-loader').hide();
                if (true === response.success) {
                    const url = response.data.url;
                    var result = response.data.result;
                    var chat_list = response.data.chat;
                    var msg_head = response.data.head;
                    var footer = response.data.footer;
                    //const nextTitle = 'Chat Dashboard';
                    //const nextState = { additionalInformation: 'Updated the URL with JS' };
                    // window.history.replaceState(nextState, nextTitle, url);
                    $(".messages-list").html(result);
                    $(".msg-head").html(msg_head);
                    //  $(".chat-list-detail").html(chat_list);
                    $('#conversation_id').val(conv_id);
                    $('#recipient_id').val(recipient_id);
                    //  $(".send-box").html(footer);
                    
                    // Add CSS for better message display
                    if (!$('#chat-refresh-styles').length) {
                        $('head').append('<style id="chat-refresh-styles">' +
                            '.dz-error-message { color: #dc3545; font-size: 12px; margin-top: 5px; }' +
                            '.message-bubble.new-message { opacity: 0.7; }' +
                            '.message-bubble.new-message.sending { opacity: 1; }' +
                            '</style>');
                    }
                    
                    // Scroll to bottom after loading
                    setTimeout(function() {
                        var scrollToTarget = $('#sbModalBody');
                        if (scrollToTarget.length) {
                            scrollToTarget.animate({scrollTop: 9999}, 500);
                        }
                    }, 300);
                } else {
                    console.log('error');
                }

            }).fail(function () {
                console.log('error 2 | sb_notification_ajax');
            });
        });



        $('#attach_files').on('change', function () {

            var media_max_upload_size = 1048576;    //1MB
            var allowed_mime_types = ['image/jpeg', 'image/png', 'image/bmp', 'image/gif', 'image/svg+xml', 'image/webp'];
            var upload_preview_html = '';

            var conversation_id = $('#conversation_id').val();
            if (conversation_id == 0 || conversation_id == null || conversation_id == '')
                return false;

            var media_upload_form_data = new FormData();
            var xml_http_request_url = localize_vars.sbAjaxurl;

            var attached_files = $('#attach_files')[0].files;
            if (attached_files.length > 0) {
                for (var i = 0; i < attached_files.length; i++) {
                    if ((attached_files[i].size <= media_max_upload_size) && ($.inArray(attached_files[i].type, allowed_mime_types) !== -1)) {

                        upload_preview_html += '<li class="preview-box" style="display: inline-block; margin-right: 7px; position: relative;">';
                        upload_preview_html += '<img src="' + (window.URL || window.webkitURL).createObjectURL(attached_files[i]) + '" height="400" width="400" style="max-width:100px; max-height:50px; width: auto; height: auto;" />';
                        upload_preview_html += '<span style="position: absolute; top: 0; right: 0; line-height: 1; color: #ffffff; background-color: green; height: 17px; width: 17px; text-align: center; vertical-align: middle; font-weight: bolder; font-size: 17px; line-height: 1;">x</span>';
                        upload_preview_html += '</li>';

                        //media_upload_form_data.append( 'file_attachments_' + i, attached_files[i] );
                    }
                }

                //media_upload_form_data.append( 'action', 'localize_file_attachments' );
                //media_upload_form_data.append( 'conversation_id', conversation_id );

                console.log(attached_files[0]);

                return false;
                $.ajax({
                    type: 'post',
                    url: xml_http_request_url,
                    data: media_upload_form_data,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response) {
                            if (typeof response.data.sbchat_upload_preview !== 'undefined') {
                                var sbchat_upload_preview = response.data.sbchat_upload_preview;
                                $('.send-box.chat-footer').append(sbchat_upload_preview);
                                $('.sbchat-file-attachments').show(5000);
                            }
                            console.log(sbchat_inbox_file_attachments);
                            //alert( response );
                        }
                    },
                    error: function (error) {
                        if (error.status !== 200) {
                            //alert( error );
                        }
                    }
                });
            }
        });

        $(document).on('change', '#upload_attachment', function () {
            $('.myprogress').css('width', '0');
            var fd = new FormData();
            var files_data = $('#upload_attachment');
            var name = $('#upload_attachment').attr("name");
            var pid = $(this).attr('data-post-id');
            $.each($(files_data), function (i, obj) {
                $.each(obj.files, function (j, file) {

                    fd.append('upload_attachment[' + j + ']', file);
                });
            });
            $.each(files_data[0]['files'], function (key, value) {
                var kb = value["size"] / 1000;
                var mb = kb / 1000;
                $('.sb-attachment-box').show();
                $(".sb-attachment-box").append('<div class="attachments temp-atatchment"><i class="fa fa-spinner fa-spin"></i> <span class="attachment-data"> <h4> ' + value["name"] + '</h4> <p>' + value["size"] + ' Bytes</p>  </span></div>');
            });
            fd.append('action', 'sb_upload_attachments');
            fd.append('post-id', pid);
            fd.append('field-name', name);

            $.ajax({
                type: 'POST',
                url: localize_vars.sbAjaxurl,
                data: fd,
                contentType: false,
                processData: false,
                success: function (res) {
                    $('.loader-outer').hide();
                    var res_arr = res.split("|");
                    if ($.trim(res_arr[0]) == "1") {
                        $('.temp-atatchment').hide();
                        $('#upload_attachment').hide();
                        $('.sb-wrapper').hide();
                        $(".sb-attachment-box").append(res_arr[2]);

                        var ex_values = $("#attachments_ids_sb").val();
                        if (ex_values != '') {
                            var new_val = ex_values + ',' + res_arr[3];
                            $("#attachments_ids_sb").val(new_val);
                        } else {
                            $("#attachments_ids_sb").val(res_arr[3]);
                        }
                    } else {
                        $('.temp-atatchment').hide();
                        $('.temp-atatchment').hide();
                        $(".sb-attachment-box").append(res_arr[2]);
                    }

                }
            });
        });
        $(document).on('click', '.sb-attach-delete', function () {
            var this_value = $(this);
            var attach_id = this_value.attr('data-id');
            var pid = this_value.attr('data-pid');
            var ex_values = $("#attachments_ids_sb").val();
            $.post(localize_vars.sbAjaxurl, {
                action: 'delete_sb_attachment',
                attach_id: attach_id,
                pid: pid,
                ex_values: ex_values
            }).done(function (response) {
                if (true === response.success) {
                    $('.loader-outer').hide();
                    var deleted_id = '.pro-atta-' + attach_id;
                    $(deleted_id).hide();
                    $('#upload_attachment').show();
                    $('.sb-wrapper').show();
                    $('#attachments_ids_sb').val('');
                    $('.sb-attach-delete').attr('data-id', '');
                } else {
                    $('.loader-outer').hide();
                }

            }).fail(function () {
                $('.loader-outer').hide();
            });
        });

        $(document).on('focus', '.message-details', function (e) {

            e.preventDefault();
            var conversation_id = $('#conversation_id').val();

            $.ajax({
                type: 'POST',
                url: localize_vars.sbAjaxurl,
                data: {action: 'sb_read_message', conversation_id: conversation_id, async: true},
                success: function (res) {
                    // Do something with the data
                    console.log(res);
                    if (res.success == true) {
                        $("li[data-id= " + conversation_id + " ]").removeClass('unread');
                    }
                },
            });
        });
    });


}(jQuery));
