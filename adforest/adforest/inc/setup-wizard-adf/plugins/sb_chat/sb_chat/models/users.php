<?php
function sbchat_search_users() {

    if ( ! is_array( $_GET ) && count( $_GET ) === 0 )
        wp_send_json_error( array( 'message' => __( 'The request sent is invalid.', 'sb_chat' ) ) );

    if ( ! isset( $_GET['term'] ) || empty( $_GET['term'] ) )
        wp_send_json_error( array( 'message' => __( 'No search term provided.', 'sb_chat' ) ) );

    global $wpdb;

    $term =  '%' . $wpdb->esc_like( $_GET['term'] ) . '%';
    $criteria = ( isset( $_GET['criteria'] ) && ! empty( $_GET['criteria'] ) ) ? esc_html( $_GET['criteria'] ) : 'name';
    $order = ( isset( $_GET['order'] ) && ! empty( $_GET['order'] ) ) ? esc_html( $_GET['order'] ) : 'ASC';
    $limit = ( isset( $_GET['limit'] ) && ! empty( $_GET['limit'] ) ) ? esc_html( $_GET['limit'] ) : 10;

    if ( $criteria === 'email' )
        $search_columns = array( 'user_email' );

    else if ( $criteria === 'name' )
        $search_columns = array( 'display_name', 'user_login', 'user_nicename' );

    else if ( $criteria === 'id' )
        $search_columns = array( 'ID' );


         $users_found  =  array();
    foreach( $search_columns as $search_column ) {

        $search_query = $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE $search_column LIKE %s", $term );
        $searched_users = $wpdb->get_results( $search_query );

        if ( is_array( $searched_users ) && count( $searched_users ) > 0 ) {
            foreach( $searched_users as $searched_user ) {
                $search_id = 'sid_' . $searched_user->ID;
                $users_found[$search_id] = array( $searched_user->ID ,  $searched_user->display_name );
            }
        }
    }

    if ( ! is_array( $users_found ) || count( $users_found ) === 0 )
        wp_send_json_error( array( 'message' => __( 'No users found.', 'sb_chat' ) ) );

    $users_found = array_values( $users_found ); 
    wp_send_json_success( array( 'message' => count( $users_found ) . ' Users found against your search term.', 'usersFound' => ( $users_found ) ) );
}
add_action( 'wp_ajax_search_users', 'sbchat_search_users' );
add_action( 'wp_ajax_nopriv_search_users', 'sbchat_search_users' );