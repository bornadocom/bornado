<?php
$post_id = get_the_ID();
if (class_exists('ACF') && function_exists('get_field_objects')) {
    $custom_data = $cus_fields = array();
    $cus_fields = get_field_objects($post_id);
    if (isset($cus_fields) && !empty($cus_fields) && is_array($cus_fields)) {
        foreach ($cus_fields as $key => $value) {
            $each_field_data = array();
            $each_field_data['label'] = $value['label'];
            $each_field_data['type'] = $value['type'];
            $each_field_data['value'] = $value['value'];
            $custom_data[] = $each_field_data;
        }
    }

    if (!empty($custom_data)) {
        ?>
        <div id="adt-ad-general-info-box" class="adt-ad-general-info">
            <h4><?php echo esc_html__("General Details:", "adforest"); ?></h4>
            <ul>
                <?php
                foreach ($custom_data as $data) {
                    if (!empty($data['value'])) {
                        ?>
                        <li>
                            <span><?php echo esc_html($data['label']); ?>:</span>
                            <small>
                                <?php
                                // Handle different field types
                                if ($data['type'] == 'url') {
                                    echo '<a href="' . esc_url($data['value']) . '" target="_blank">' . esc_html__('Link', 'adforest') . '</a>';
                                } elseif ($data['type'] == 'true_false') {
                                    echo esc_html($data['value']) ? esc_html__('Yes', 'adforest') : esc_html__('No', 'adforest');
                                } elseif (is_array($data['value'])) {
                                    echo implode(', ', array_map('esc_html', $data['value']));
                                } else {
                                    echo esc_html($data['value']);
                                }
                                ?>
                            </small>
                        </li>
                        <?php
                    }
                }
                ?>
            </ul>
        </div>
        <?php
    }
}
?>