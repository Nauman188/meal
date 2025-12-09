<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// delete plugin settings
delete_option('mrf_settings');

// clean transients if any (optional; safe)
global $wpdb;
$like = '%mrf_result_%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
