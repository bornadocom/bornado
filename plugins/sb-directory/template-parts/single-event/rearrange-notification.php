<?php
$event_id = $pid  = get_the_id() ;
$uid  =   get_current_user_id();
$media = sb_pro_fetch_event_gallery($event_id);
global $adforest_theme; 
	$flip_it = 'text-left';
	if ( is_rtl() )
	{
		$flip_it = 'text-right';
	}
?>
<div class="modal fade sortable-images" tabindex="-1" role="dialog" aria-hidden="true">
 <div class="modal-dialog">
    <div class="modal-content <?php echo esc_attr( $flip_it ); ?>">
       <div class="modal-header">
<!--          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&#10005;</span><span class="sr-only">Close</span></button>-->
          <div class="modal-title"><?php echo __('Re-arrange your ad photos.','adforest'); ?></div>
       </div>
       <div class="modal-body <?php echo esc_attr( $flip_it ); ?>">
          <div class="row">
            <div class="col-md-12 col-xs-12 col-sm-12">
            	<em><small><?php echo __('*First image will be main display image of this ad.','adforest'); ?></small></em>
                <ul id="sortable">
                <?php
				$img_ids	=	'';
				if( count( $media ) > 0 )
				{
				foreach( $media as $m )
				{
					$mid	=	'';
					if ( isset( $m->ID ) )
						$mid	= 	$m->ID;
					else
						$mid	=	$m;		
					$img  = wp_get_attachment_image_src($mid, 'adforest-single-small');
					if( $img[0] == "" )
						continue;
					
					$img_ids	=	$img_ids . $mid . ',';
				?>
                <li class="ui-state-default">
                <img alt="<?php echo get_the_title(); ?>" data-img-id="<?php echo esc_attr($mid); ?>" draggable="true" src="<?php echo esc_attr( $img[0] ); ?>">
                </li>
                <?php
				  }
				}
				$img_ids	= rtrim($img_ids,',');
				if( get_post_meta( $pid, 'downotown_event_arrangement_', true ) == "" )
					update_post_meta( $pid, 'downotown_event_arrangement_', $img_ids );
				?>
                </ul>
                <input type="hidden" id="post_img_ids" value="<?php echo esc_attr( $img_ids ); ?>" />
                <input type="hidden" id="current_pid" value="<?php echo esc_attr( $pid ); ?>" />
                <input type="hidden" id="re-arrange-msg" value="<?php echo __( 'Events photos has been re-arranged.', 'adforest' ); ?>" />
            </div>
        </div>
       </div>
       <div class="modal-footer">
          <input type="button" class="btn btn-theme btn-block" value="<?php echo __('Re-arrange','adforest' ); ?>" id="sb_sort_event_images" />
       </div>
    </div>
 </div>
</div>
