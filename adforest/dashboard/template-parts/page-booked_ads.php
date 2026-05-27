<?php echo adforest_dashboard_breadcrumb(esc_html__("Booked Ads", "adforest")); ?>
<div class="card-style mb-30">
    <div class="content">
        <div class="row">
            <?php
            echo apply_filters('adforest_pro_get_booked_ads_list', '');
            ?>
            <div class="modal fade" id="ad-booking-modal" tabindex="-1" aria-labelledby="ad-booking-modal" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content"  id = "ad-booking-content" >

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>