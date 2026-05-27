<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Step: Demo Import
 * File: inc/setup-wizard-adf/views/step-demo.php
 */
$imported_demo = sanitize_title( get_option( 'adforest_imported_demo', '' ) );
?>

    <header class="demo-header">
        <h3 id="demo-heading"><?php esc_html_e( 'Import Demo Content', 'adforest' ); ?></h3>
        <p><?php esc_html_e( 'Select one demo below. Only one import at a time. ', 'adforest' ); ?></p>
        <?php if(($imported_demo != "")){?>
            <p><?php esc_html_e( 'To import another demo you need to reset the database.', 'adforest' ); ?></p>
        <?php }?>
    </header>



    <div class="demo-grid">
        <?php
        $folders = glob( __DIR__ . '/../demo/*', GLOB_ONLYDIR ) ?: [];
        foreach ( $folders as $folder ) :
            $name        = basename( $folder );
            $slug        = sanitize_title( $name );
            $is_imported = ( $slug === $imported_demo );
            $is_locked   = $imported_demo && ! $is_imported;
            $img_src     = esc_url( get_template_directory_uri() . '/inc/setup-wizard-adf/demo/' . rawurlencode( $name ) . '/screen-image.png' );
        ?>
        <article
            class="demo-card<?php echo esc_attr($is_imported) ? ' imported' : ''; ?><?php echo esc_attr($is_locked) ? ' is-locked' : ''; ?>"
            data-demo="<?php echo esc_attr( $slug ); ?>"
            tabindex="<?php echo esc_attr($is_locked) ? '-1' : '0'; ?>"
            aria-disabled="<?php echo esc_attr($is_locked) ? 'true' : 'false'; ?>"
        >
            <figure class="demo-thumb">
                <?php if ( file_exists( $folder . '/screen-image.png' ) ) : ?>
                    <img loading="lazy" src="<?php echo esc_url($img_src); ?>" alt="<?php echo esc_attr( $name ); ?>">
                <?php else : ?>
                    <div class="demo-placeholder"><?php esc_html_e( 'No Preview Available', 'adforest' ); ?></div>
                <?php endif; ?>
            </figure>

            <h4 class="demo-title"><?php echo esc_html( $name ); ?></h4>

            <div class="actions">
                <?php if ( $is_imported ) : ?>
                    <button type="button" class="sb-btn sb-btn-primary" disabled><?php esc_html_e( 'Imported & Locked', 'adforest' ); ?></button>
                <?php elseif ( $is_locked ) : ?>
                    <button type="button" class="sb-btn sb-btn-secondary" disabled><?php esc_html_e( 'Locked', 'adforest' ); ?></button>
                <?php else : ?>
                    <button type="button" class="sb-btn sb-btn-primary sb-btn-select-demo"><?php esc_html_e( 'Import This Demo', 'adforest' ); ?></button>
                <?php endif; ?>
            </div>

            <div class="import-confirmation" role="status" aria-live="polite" style="display:none;">
                <p><?php printf( esc_html__( '%s selected. Continue to import?', 'adforest' ), esc_html( $name ) ); ?></p>
                <button type="button" class="sb-btn sb-btn-secondary sb-btn-cancel-select"><?php esc_html_e( 'Cancel', 'adforest' ); ?></button>
                <button type="button" class="sb-btn sb-btn-primary sb-btn-confirm-import"><?php esc_html_e( 'Confirm', 'adforest' ); ?></button>
            </div>





            <div class="import-ui" role="region" aria-live="polite" style="display:none;">


                <div style="background-color:#fff4e5; border-left:5px solid #ff9900; padding:20px; margin:20px 0; font-family:Arial, sans-serif; border-radius:6px;">
                <h3 style="margin-top:0; color:#d35400;"><?php echo esc_html__('🚨 AdForest v6.0 Update Alert', 'adforest'); ?></h3>
                <p style="margin:10px 0;">
                    <strong><?php echo esc_html__('Existing Users:', 'adforest'); ?></strong> ⚠️ 
                    <strong><?php echo esc_html__('Do NOT use Fresh Import', 'adforest'); ?></strong> — 
                    <?php echo esc_html__('it will erase your current data.', 'adforest'); ?>
                </p>
                <ul style="margin:10px 0 0 20px; padding-left:0;">
                    <li><?php echo esc_html__('✅ Backup your site before updating', 'adforest'); ?></li>
                    <li><?php echo esc_html__('❌ WPBakery is no longer supported — switch to Elementor', 'adforest'); ?></li>
                </ul>
                <p style="margin-top:10px;"><?php echo esc_html__('Stay tuned for a faster, cleaner AdForest!', 'adforest'); ?></p>
                </div>


                <fieldset class="import-mode-fieldset">
                    <legend><?php esc_html_e( 'Import Mode', 'adforest' ); ?></legend>
                    <label>
                        <input type="radio" name="mode_<?php echo esc_attr( $slug ); ?>" value="new" checked>
                        <?php esc_html_e( 'Fresh Import', 'adforest' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="mode_<?php echo esc_attr( $slug ); ?>" value="existing">
                        <?php esc_html_e( 'Existing (Specific new data)', 'adforest' ); ?>
                    </label>
                </fieldset>

                <fieldset class="parts-fieldset fresh-parts">
                    <legend><?php esc_html_e( 'Fresh Import Parts', 'adforest' ); ?></legend>
                    <?php if ( file_exists( $folder . '/content.xml' ) ) : ?>
                        <label><input type="checkbox" name="parts_<?php echo esc_attr( $slug ); ?>[]" value="content" checked> <?php esc_html_e( 'Content XML', 'adforest' ); ?></label>
                    <?php endif; ?>
                    <?php if ( file_exists( $folder . '/widgets.wie' ) ) : ?>
                        <label><input type="checkbox" name="parts_<?php echo esc_attr( $slug ); ?>[]" value="widgets" checked> <?php esc_html_e( 'Widgets', 'adforest' ); ?></label>
                    <?php endif; ?>
                    <?php if ( file_exists( $folder . '/theme-options.json' ) ) : ?>
                        <label><input type="checkbox" name="parts_<?php echo esc_attr( $slug ); ?>[]" value="options" checked> <?php esc_html_e( 'Theme Options', 'adforest' ); ?></label>
                    <?php endif; ?>
                </fieldset>

                <fieldset class="parts-fieldset existing-parts" style="display:none;">
                    <legend><?php esc_html_e( 'Existing Import Parts', 'adforest' ); ?></legend>
                    <?php if ( file_exists( $folder . '/pages.xml' ) ) : ?>
                        <label><input type="checkbox" name="parts_<?php echo esc_attr( $slug ); ?>[]" value="pages"> <?php esc_html_e( 'Pages XML', 'adforest' ); ?></label>
                    <?php endif; ?>
                    <?php if ( file_exists( $folder . '/media.xml' ) ) : ?>
                        <label><input type="checkbox" name="parts_<?php echo esc_attr( $slug ); ?>[]" value="media"> <?php esc_html_e( 'Media XML', 'adforest' ); ?></label>
                    <?php endif; ?>
                    <?php if ( file_exists( $folder . '/new-widgets.wie' ) ) : ?>
                        <label><input type="checkbox" name="parts_<?php echo esc_attr( $slug ); ?>[]" value="new-widgets"> <?php esc_html_e( 'New Widgets', 'adforest' ); ?></label>
                    <?php endif; ?>
                    <?php if ( file_exists( $folder . '/new-theme-options.json' ) ) : ?>
                        <label><input type="checkbox" name="parts_<?php echo esc_attr( $slug ); ?>[]" value="new-options"> <?php esc_html_e( 'New Theme Options', 'adforest' ); ?></label>
                    <?php endif; ?>
                </fieldset>


                <div class="import-controls">
                    <button type="button" class="sb-btn sb-btn-secondary sb-btn-stop-import" disabled><?php esc_html_e( 'Stop', 'adforest' ); ?></button>
                    <button type="button" class="sb-btn sb-btn-primary sb-btn-confirm-stream-import"><?php esc_html_e( 'Start Import', 'adforest' ); ?></button>
                </div>

                <div class="import-error" role="alert"></div>
                <div class="progress-container">
                    <div
                        id="progress-<?php echo esc_attr( $slug ); ?>"
                        class="progress-bar"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="0"
                    ></div>
                </div>
                <div id="status-<?php echo esc_attr( $slug ); ?>" class="import-status" role="status"></div>
                <pre id="log-<?php echo esc_attr( $slug ); ?>" class="import-log"></pre>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    

    <nav class="sb-demo-importer-nav" aria-label="<?php esc_attr_e( 'Demo Navigation', 'adforest' ); ?>">

        <?php if(($imported_demo != "")){?>
        <button
            id="reset-demo"
            class="sb-btn sb-btn-secondary"
            aria-label="<?php esc_attr_e( 'Reset Demo Import', 'adforest' ); ?>"
        >
            <?php esc_html_e( 'Enable Import Option again!.', 'adforest' ); ?>
        </button>
        
        <?php } ?>

        <button
            type="button"
            class="sb-btn sb-btn-secondary sb-btn-back"
            data-next="plugins"
        ><?php esc_html_e( 'Back', 'adforest' ); ?></button>

        <button
            type="button"
            id="demo-next"
            class="sb-btn sb-btn-primary sb-btn-continue"
            data-next="done"
            <?php echo (esc_attr($imported_demo) != "") ? "" : "disabled='disabled'";?>
        ><?php esc_html_e( 'Continue', 'adforest' ); ?></button>
    </nav>
