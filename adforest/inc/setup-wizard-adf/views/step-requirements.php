<?php if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_version, $wpdb;

// Gather values
$php_version     = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
$mysql_version   = $wpdb->get_var("SELECT VERSION()");
$memory_limit    = ini_get( 'memory_limit' );
$max_exec        = ini_get( 'max_execution_time' ) . 's';
$upload_max      = ini_get( 'upload_max_filesize' );
$post_max        = ini_get( 'post_max_size' );
$allow_url_fopen = ini_get( 'allow_url_fopen' );

// Uploads writable
$uploads_path     = wp_upload_dir()['basedir'];
$uploads_writable = is_writable( $uploads_path );

// Requirements list
$requirements = [
  __( 'WordPress Version', 'adforest' )       => [ $wp_version,      '>=', '5.8' ],
  __( 'PHP Version', 'adforest' )             => [ $php_version,     '>=', '7.4' ],
  __( 'MySQL Version', 'adforest' )           => [ $mysql_version,   '>=', '5.7' ],
  __( 'PHP memory_limit', 'adforest' )        => [ $memory_limit,    '>=', '128M' ],
  __( 'max_execution_time', 'adforest' )      => [ $max_exec,        '>=', '120s' ],
  __( 'upload_max_filesize', 'adforest' )     => [ $upload_max,      '>=', '64M' ],
  __( 'post_max_size', 'adforest' )           => [ $post_max,        '>=', '64M' ],
  __( 'allow_url_fopen', 'adforest' )         => [ $allow_url_fopen, '===','1' ],
  __( 'Uploads Folder Writable', 'adforest' ) => [ $uploads_writable,'===', true ],
];

$extensions = [ 'curl', 'json', 'mbstring', 'zip' ];

// Byte conversion
function sw_convert_bytes( $value ) {
  if ( is_numeric( $value ) ) return (int) $value;
  $unit = strtoupper( substr( $value, -1 ) );
  $num  = (int) $value;
  switch ( $unit ) {
    case 'G': $num *= 1024;
    case 'M': $num *= 1024;
    case 'K': $num *= 1024;
  }
  return $num;
}

$all_ok = true;
?>

<div class="sb-requirements-card">
  <header class="sb-requirements-header">
    <h3 id="requirements-heading"><?php esc_html_e( 'Server & Environment Requirements', 'adforest' ); ?></h3>
    <p class="sb-requirements-intro"><?php esc_html_e( 'Ensure your site meets these specs before continuing.', 'adforest' ); ?></p>
  </header>

  <div class="sb-requirements-body">
    <nav class="sb-requirements-tabs" role="tablist">
      <button role="tab" aria-selected="true" data-tab="server" class="active"><?php esc_html_e( 'Core Requirements', 'adforest' ); ?></button>
      <button role="tab" aria-selected="false" data-tab="extensions"><?php esc_html_e( 'PHP Extensions', 'adforest' ); ?></button>
    </nav>

    <div class="sb-requirements-panels">
      <div class="tab-panel" data-tab="server">
        <div class="sb-table-wrapper">
          <table class="sb-requirements-table widefat fixed">
            <thead>
              <tr><th><?php esc_html_e( 'Requirement', 'adforest' ); ?></th><th><?php esc_html_e( 'Current', 'adforest' ); ?></th><th><?php esc_html_e( 'Minimum', 'adforest' ); ?></th><th><?php esc_html_e( 'Status', 'adforest' ); ?></th></tr>
            </thead>
            <tbody>
              <?php foreach ( $requirements as $label => list( $current, $op, $min ) ) :
                switch ( $op ) {
                  case '===':
                    $ok = ( $current === $min || ( '1' === $current && true === $min ) ); break;
                  case '>=':
                    if ( preg_match( '/[KMG]/', $min ) ) {
                      $ok = sw_convert_bytes( $current ) >= sw_convert_bytes( $min );
                    } else {
                      $ok = version_compare( preg_replace('/[^0-9\.]/','',$current), $min, '>=' );
                    }
                    break;
                  default:
                    $ok = version_compare( $current, $min, $op );
                }
                if ( ! $ok ) $all_ok = false;
              ?>
              <tr class="<?php echo esc_attr($ok) ? 'ok' : 'fail'; ?>">
                <td><?php echo esc_html( $label ); ?></td>
                <td><?php echo esc_html( $current ); ?></td>
                <td><?php echo esc_html( $min ); ?></td>
                <td><span class="dashicons <?php echo esc_attr($ok) ? 'dashicons-yes-alt' : 'dashicons-no-alt'; ?>"></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-panel" data-tab="extensions" hidden>
        <div class="sb-table-wrapper">
          <table class="sb-requirements-table widefat fixed">
            <thead>
              <tr><th><?php esc_html_e( 'Extension', 'adforest' ); ?></th><th><?php esc_html_e( 'Status', 'adforest' ); ?></th></tr>
            </thead>
            <tbody>
              <?php foreach ( $extensions as $ext ) : $loaded = extension_loaded( $ext ); if ( ! $loaded ) $all_ok = false; ?>
              <tr class="<?php echo esc_attr($loaded) ? 'ok' : 'fail'; ?>">
                <td><?php echo sprintf( esc_html__( 'PHP %s extension', 'adforest' ), strtoupper( $ext ) ); ?></td>
                <td><span class="dashicons <?php echo esc_attr($loaded) ? 'dashicons-yes-alt' : 'dashicons-no-alt'; ?>"></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ( ! $all_ok ) : ?>
      <div class="sb-requirements-notice">
        <?php esc_html_e( 'Some requirements are not met. Please address them before continuing.', 'adforest' ); ?>
      </div>
    <?php endif; ?>
  </div>

  <nav class="sb-requirements-footer">
    <button type="button" class="sb-btn sb-btn-secondary sb-btn-back" data-next="license"><?php esc_html_e( 'Back', 'adforest' ); ?></button>
    <button type="button" class="sb-btn sb-btn-primary sb-btn-next" data-next="plugins" <?php disabled( ! $all_ok ); ?>><?php esc_html_e( 'Continue', 'adforest' ); ?></button>
  </nav>
</div>
