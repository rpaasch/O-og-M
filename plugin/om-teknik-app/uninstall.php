<?php
/**
 * Rydder op, når pluginet slettes.
 *
 * @package OmTeknikApp
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'om_teknik_app_base' );
delete_option( 'om_teknik_app_flush_rules' );
