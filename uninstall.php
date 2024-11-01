<?php
/**
 * Trigger this file on Plugin uninstall
 *
 * @package Uqrate
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || die( 'Forbidden.' );
current_user_can( 'install_plugins' ) || die( 'Forbidden.' );
function uqrate_delete_plugin() {
    /**
     * Delete the plugin's namespaced records @ wp_options table
     */
    global $wpdb;
    $sql = "DELETE FROM wp_options
            WHERE option_name LIKE 'uqrate\_%'
    ";
    $wpdb->query($sql);
    /**
     * Drop the plugin's posts-threads junction table
     */
    $table_name = $wpdb->prefix . 'uqrate_posts_threads';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}
if ( ! defined( 'UQRATE_PLUGIN_NAME' ) ) {
	uqrate_delete_plugin();
}
