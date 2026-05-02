<?php
global $adforest_theme;
$pid = get_the_ID();
$event_id  =   $pid;
if ($adforest_theme['event_share_allow']) {
$flip_it = 'text-left';
if (is_rtl()) {
    $flip_it = 'text-right';
}
?>  
<?php 
$title =  get_the_title();
$content =  get_the_content();
$event_start_date = get_post_meta($event_id, 'sb_pro_event_start_date', true);
$event_end_date = get_post_meta($event_id, 'sb_pro_event_end_date', true);
$venue = get_post_meta($event_id, 'sb_pro_event_venue', true);

/* google calender start*/
$start_date = date("Ymd", strtotime($event_start_date));  
$start_time =   date("His", strtotime($event_start_date)); 
$start_format  =   $start_date  . "T" . $start_time;
$end_date = date("Ymd", strtotime($event_end_date));  
$end_time =   date("His", strtotime($event_end_date)); 
$end_format  =   $end_date  . "T" . $end_time;
$google_date_url   =  $start_format . "/" .$end_format ;
$google_calendar_url = esc_url( 'http://www.google.com/calendar/render?action=TEMPLATE&text=' . 
$title . 
( !empty( $event_start_date ) ? '&dates='  . $google_date_url. '' : '') .
( !empty( $get_loc ) ? '&location=' . esc_attr( $venue ) . '' : '' ) . 
( !empty( $my_content ) ? '&details=' . esc_attr( $content ) . '' : '' ) . '' );

/*google calender end*/
/* adding date and time format for outlook calendar */
$start_date_outlook = date("Y-m-d", strtotime($event_start_date));  
$start_time_outlook =   date("H:i:s\Z", strtotime($event_start_date)); 
$start_format_outlook  =   $start_date_outlook  . "T" . $start_time_outlook;
$end_date_outlooks = date("Y-m-d", strtotime($event_end_date));  
$end_time_outlooks =   date("H:i:s\Z", strtotime($event_end_date)); 
$end_format_outlooks  =   $end_date_outlooks  . "T" . $end_time_outlooks;
$outlook_date_url   =  $start_format_outlook . "/" . $end_format_outlooks ;
$outlook_calendar_url = esc_url('https://outlook.live.com/owa/?path=/calendar/view/Month&rru=addevent&subject='.
get_the_title( esc_attr( $event_id ) ) . 
( !empty( $event_start_date ) ? '&startdt=' . esc_attr( $start_format_outlook): '') .
( !empty( $event_start_date ) ? '&dtend='   . esc_attr( $end_format_outlooks): '') .
( !empty( $get_loc ) ? '&location=' . esc_attr( $venue ) . '' : '' ) . 
( !empty( $my_content ) ? '&body=' . esc_attr( $content ) . '' : '' ) . '');
/* outlook finished */
//Yahoo calendar event
$yahoo_calendar_url = esc_url( 'https://calendar.yahoo.com/?v=60&amp;&title=' . 
get_the_title( esc_attr( $event_id ) ) . 
( !empty( $event_start_date ) ? '&st='  . $end_format  : '') .
( !empty( $event_start_date ) ? '&et='  . $end_format  : '') .
( !empty( $get_loc ) ? '&in_loc=' . esc_attr( $venue ) . '' : '' ) . 
( !empty( $my_content ) ? '&desc=' . esc_attr( $my_content ) . '' : '' ) . '' );
?>
<div class="modal fade  share-events-map" tabindex="-1" role="dialog" aria-hidden="true">
<div class="modal-dialog modal-lg">
    <div class="modal-content <?php echo esc_attr($flip_it); ?>">
        <div class="modal-header">
            <button type="button" class="close" data-bs-dismiss="modal"><span aria-hidden="true">&#10005;</span><span class="sr-only">Close</span></button>
            <div class="modal-title"><?php echo __('Share', 'adforest'); ?></div>
        </div>
        <div class="modal-body <?php echo esc_attr($flip_it); ?>">
            <div class="share-map-container">
                <ul>                 
                   <li>
                       <div class="cal-svg"> <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve"> <g> <g> <g> <path d="M490.771,298.667l20.833-156.177c0.271-2.094,0.396-4.208,0.396-6.323c0-17.007-8.471-31.999-21.333-41.232V42.667 C490.667,19.135,471.521,0,448,0H64C40.479,0,21.333,19.135,21.333,42.667v52.268C8.471,104.168,0,119.16,0,136.167 c0,2.115,0.125,4.229,0.396,6.385l20.833,156.115L0.396,454.844C0.125,456.938,0,459.052,0,461.167 C0,489.198,22.813,512,50.833,512h410.333C489.188,512,512,489.198,512,461.167c0-2.115-0.125-4.229-0.396-6.385L490.771,298.667 z M42.667,42.667c0-11.76,9.563-21.333,21.333-21.333h384c11.771,0,21.333,9.573,21.333,21.333V86.16 c-2.674-0.438-5.37-0.827-8.167-0.827H50.833c-2.797,0-5.492,0.389-8.167,0.827V42.667z M469.417,300.073l21.021,157.458 c0.146,1.198,0.229,2.417,0.229,3.635c0,16.271-13.229,29.5-29.5,29.5H50.833c-16.271,0-29.5-13.229-29.5-29.5 c0-1.219,0.083-2.438,0.208-3.573l21.042-157.521c0.104-0.927,0.104-1.885,0-2.813L21.563,139.802 c-0.146-1.198-0.229-2.417-0.229-3.635c0-16.271,13.229-29.5,29.5-29.5h410.333c16.271,0,29.5,13.229,29.5,29.5 c0,1.219-0.083,2.438-0.208,3.573L469.417,297.26C469.313,298.188,469.313,299.146,469.417,300.073z"></path> <path d="M256,245.333c0-35.292-28.708-64-64-64s-64,28.708-64,64c0,5.896,4.771,10.667,10.667,10.667 c5.896,0,10.667-4.771,10.667-10.667c0-23.531,19.146-42.667,42.667-42.667s42.667,19.135,42.667,42.667S215.521,288,192,288 c-5.896,0-10.667,4.771-10.667,10.667c0,5.896,4.771,10.667,10.667,10.667c23.521,0,42.667,19.135,42.667,42.667 S215.521,394.667,192,394.667S149.333,375.531,149.333,352c0-5.896-4.771-10.667-10.667-10.667 c-5.896,0-10.667,4.771-10.667,10.667c0,35.292,28.708,64,64,64s64-28.708,64-64c0-22.264-11.454-41.865-28.751-53.333 C244.546,287.198,256,267.598,256,245.333z"></path> <path d="M378.375,182.594c-3.479-1.854-7.667-1.646-10.958,0.531l-64,42.667c-4.896,3.271-6.229,9.885-2.958,14.792 c3.271,4.896,9.917,6.188,14.792,2.958l47.417-31.615v193.406c0,5.896,4.771,10.667,10.667,10.667 c5.896,0,10.667-4.771,10.667-10.667V192C384,188.063,381.833,184.448,378.375,182.594z"></path> <circle cx="138.667" cy="53.333" r="10.667"></circle> <circle cx="373.333" cy="53.333" r="10.667"></circle> </g> </g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> </svg></div>
                       <a href= "<?php echo esc_url($google_calendar_url) ?>">  Google calender </a>  </li>
                   <li>
                        <div class="cal-svg"><svg version="1.1" x="0px" y="0px" viewBox="0 0 103.17322 104.31332" xml:space="preserve" inkscape:version="0.48.2 r9819"><metadata id="metadata45"><rdf:rdf><cc:work rdf:about=""><dc:format>image/svg+xml</dc:format><dc:type rdf:resource="http://purl.org/dc/dcmitype/StillImage"></dc:type><dc:title></dc:title></cc:work></rdf:rdf></metadata><defs id="defs43"></defs><sodipodi:namedview borderopacity="1" objecttolerance="10" gridtolerance="10" guidetolerance="10" inkscape:pageopacity="0" inkscape:pageshadow="2" inkscape:window-width="1600" inkscape:window-height="837" id="namedview41" showgrid="false" fit-margin-top="0" fit-margin-left="0" fit-margin-right="0" fit-margin-bottom="0" inkscape:zoom="1" inkscape:cx="91.558992" inkscape:cy="89.87632" inkscape:window-x="-8" inkscape:window-y="-8" inkscape:window-maximized="1" inkscape:current-layer="Layer_1"></sodipodi:namedview> <path d="m 64.566509,22.116383 v 20.404273 l 7.130526,4.489881 c 0.188058,0.05485 0.595516,0.05877 0.783574,0 L 103.16929,26.320259 c 0,-2.44867 -2.28412,-4.203876 -3.573094,-4.203876 H 64.566509 z" id="path3" inkscape:connector-curvature="0"></path> <path d="m 64.566509,50.13308 6.507584,4.470291 c 0.916782,0.673874 2.021622,0 2.021622,0 -1.100922,0.673874 30.077495,-20.035993 30.077495,-20.035993 v 37.501863 c 0,4.082422 -2.61322,5.794531 -5.551621,5.794531 H 64.562591 V 50.13308 z" id="path5" inkscape:connector-curvature="0"></path> <g id="g23" transform="matrix(3.9178712,0,0,3.9178712,-13.481403,-41.384473)"> <path d="m 11.321,20.958 c -0.566,0 -1.017,0.266 -1.35,0.797 -0.333,0.531 -0.5,1.234 -0.5,2.109 0,0.888 0.167,1.59 0.5,2.106 0.333,0.517 0.77,0.774 1.31,0.774 0.557,0 0.999,-0.251 1.325,-0.753 0.326,-0.502 0.49,-1.199 0.49,-2.09 0,-0.929 -0.158,-1.652 -0.475,-2.169 -0.317,-0.516 -0.75,-0.774 -1.3,-0.774 z" id="path25" inkscape:connector-curvature="0"></path> <path d="m 3.441,13.563 v 20.375 l 15.5,3.25 V 10.563 l -15.5,3 z m 10.372,13.632 c -0.655,0.862 -1.509,1.294 -2.563,1.294 -1.027,0 -1.863,-0.418 -2.51,-1.253 C 8.094,26.4 7.77,25.312 7.77,23.97 c 0,-1.417 0.328,-2.563 0.985,-3.438 0.657,-0.875 1.527,-1.313 2.61,-1.313 1.023,0 1.851,0.418 2.482,1.256 0.632,0.838 0.948,1.942 0.948,3.313 10e-4,1.409 -0.327,2.545 -0.982,3.407 z" id="path27" inkscape:connector-curvature="0"></path> </g> </svg></div>
                       <a href= "<?php echo esc_url($outlook_calendar_url) ?>">  outlook calender url </a>  </li>
                   <li>
                        <div class="cal-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M252 292l4 220c-12.7-2.2-23.5-3.9-32.3-3.9-8.4 0-19.2 1.7-32.3 3.9l4-220C140.4 197.2 85 95.2 21.4 0c11.9 3.1 23 3.9 33.2 3.9 9 0 20.4-.8 34.1-3.9 40.9 72.2 82.1 138.7 135 225.5C261 163.9 314.8 81.4 358.6 0c11.1 2.9 22 3.9 32.9 3.9 11.5 0 23.2-1 35-3.9C392.1 47.9 294.9 216.9 252 292z"></path></svg></div>
                       <a href= "<?php echo esc_url($yahoo_calendar_url) ?>">   yahoo calender url </a>  </li>   
                </ul>
                </div>
            </div>
        </div>
        <div class="modal-footer">
        </div>
    </div>
</div>
<?php } ?>