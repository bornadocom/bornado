<?php
function time_elapsed_string( $datetime, $full = false ) {

    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ( $string as $k => &$v ) {
        if ( $diff->$k ) {
            $v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? 's' : '' );
        } else {
            unset( $string[$k] );
        }
    }

    if ( ! $full ) $string = array_slice( $string, 0, 1 );
    return $string ? implode( ', ', $string ) . ' ago' : 'just now';
}


if ( ! function_exists( 'time_ago_function' ) )
{
	function time_ago_function($timestamp)
	{
		// $timestamp = strtotime($timestamp);
        $strTime = array(
            __('second', 'gigzeal_framework'),
            __('minute', 'gigzeal_framework'),
            __('hour', 'gigzeal_framework'),
            __('day', 'gigzeal_framework'),
            __('month', 'gigzeal_framework'),
            __('year', 'gigzeal_framework')
        );
        $length = array("60", "60", "24", "30", "12", "10");

        $currentTime = strtotime(current_time('mysql'));
        if ($currentTime >= $timestamp) {
            $diff = $currentTime - $timestamp;
            for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
                $diff = $diff / $length[$i];
            }
            $diff = round($diff);
			if($diff == 1)
			{
				return $diff . " " . $strTime[$i] . __(' ago', 'gigzeal_framework');
			}
			else
			{
				return $diff . " " . $strTime[$i] . __('s ago', 'gigzeal_framework');
			}
        }	
	}
}

function get_unix_timestamp( $mysql_timestamp ) {

    if ( ! empty( $mysql_timestamp ) ) {

        $timestamp_str = strtotime( $mysql_timestamp );
        $unix_timestamp = wp_date( 'M d, Y H:i', $timestamp_str );

        return $unix_timestamp;
    }
}