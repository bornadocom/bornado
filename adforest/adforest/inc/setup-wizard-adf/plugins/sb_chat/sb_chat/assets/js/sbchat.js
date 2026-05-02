jQuery(document).ready( function( $ ) {

     if($('.clear_sb_data').length > 0){

        var oldHref  = $('#clear_url').val();

         console.log("sss");
   $(document).on('click', '.clear_sb_data', function (e) {
       e.preventDefault();
       const confirmed = confirm('Are you sure you want to clear plugin data?');
       // If the user clicked "OK"
       if (confirmed) {

          window.location.href = oldHref;
       }
    });
   }
    if (typeof sbchat !== 'undefined' && sbchat.length > 0) {
        var xhr = sbchat.xhr;
        // your code here
    initialize_search_select2();
    }


    
function initialize_search_select2() {

    var defaultChoice = { id: -1, text: '— Select User —' };
    var sbchatUsersDefaultOption = new Option( defaultChoice.text, defaultChoice.id, true, true );
    var sbchatUsersOptions = null;

    $('.sbchat-users').select2({
        minimumInputLength: 1,
        maximumInputLength: 14,
        minimumResultsForSearch: 7,
        ajax: {
            url: xhr,
            dataType: 'json',
            data: function ( params ) {
                // modify or add additional query parameters to the request, which will then be sent to server.
                var query = { term: params.term, criteria: 'name', order: 'ASC', action: 'search_users' }
                return query;
            },
            processResults: function ( data ) {
                
                if ( data.success ) {
                    var subchatUsers = [];
                    var usersFound = data.data.usersFound;
                    if ( $.isArray( usersFound ) && usersFound.length > 0 ) {
                        $.each( usersFound, function ( key, val ) {
                            subchatUsers.push({ id: val[0], text: val[1] });
                        });
                    }
                }

                return {
                    results: subchatUsers
                };
            },
        }
    }).append( sbchatUsersDefaultOption ).trigger( 'change' );
    
    $( document ).on( 'change', '.sbchat-users', function() {

        var sbchatUsers = $( this );
        var userId = sbchatUsers.val();
        if ( userId == 0 || userId === '' || userId <= 0 )
            return false;

        var sbchatConversationsUrl = window.location.origin + window.location.pathname;
        sbchatConversationsUrl += '?page=sbchat_conversations&user_id=';

        var redirectToUrl = sbchatConversationsUrl + userId;
        window.location.replace( redirectToUrl );
    });


  /*Delete chat by admin*/
    $(document).on('click', '.delete-single-chat', function () {
        // var sb_nonce = $(".sb_nonce"). val();
        var conv_id = $(this).attr('data-conversation');
        var delete_text =  $(this).attr('data-delete');
        if (confirm(delete_text)) {
            $.post(ajaxurl, {action: 'sb_delete_single_user_chat', conv_id: conv_id , from_admin : 'yes'}).done(function (response) {
                if (true === response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    console.log("something went wrong");
                }
            }).fail(function () {
                console.log("something went wrong");
            });
        }
    });

   




 
    /*block user by admin*/
    $(document).on('click', '.block-user-admin', function () {
        // var sb_nonce = $(".sb_nonce"). val();
        var user_id = $(this).attr('data-user_id');
        var block_text =  $(this).attr('data-block');

        var block_status   =  $(this).attr('block_status');
        if (confirm(block_text)) {
            $.post(ajaxurl, {action: 'sb_block_single_user', user_id: user_id , from_admin : 'yes' ,  'security': sbchat.ajax_nonce, 'block_status' : block_status}).done(function (response) {
                if (true === response.success) {
            
                    alert(response.data.message);
                    window.location.reload();
                    
                } else {
                    console.log("something went wrong");
                }
            }).fail(function () {
               alert('Security issue , please verify your nonce');
            });
        }
    });




}

});