<?php
/**
 * @package Uqrate
 * 
 * Uqrate_Page handles all Page-type web pages
 */
defined( 'ABSPATH' ) || die( 'ABSPATH' );
class Uqrate_Page extends Uqrate
{
    protected static function select( $content ) {
        if ( ! ( is_singular() && in_the_loop() && is_main_query() ) ) 
            return;
        if ( ! ( self::GetCfg('mode_debug') && current_user_can( 'install_plugins' ) ) ) 
            return $content;
        switch ( true ) {
            case ( is_page( 'php-admin' ) || is_page( 'sample-page' ) ):
                return self::page_php_admin( $content );
                break;
            case is_page( 'php-dev' ):
                return self::page_php_dev( $content );
                break;
            default:
                return $content;
                break;
        }
    }
    private static function page_php_admin( $content ) {
        global $wpdb; 
        return $content
                . self::preObj( wp_get_theme(), 'wp_get_theme()' )
                . self::preObj( $wpdb->db_version(), 'DB Version' )
                . self::preObj( phpversion(), 'PHP Version' )
                . self::preObj( self::cfg(), 'cfg()' )
                . self::preObj( $_SERVER, '$_SERVER' )
                . self::preObj( $_REQUEST, '$_REQUEST' )
                . self::preObj( $GLOBALS['wp_actions'], '$_GLOBALS[\'wp_actions\'] => count' );
    }
    private static function page_php_dev( $content ) {
        if ( ! is_page( 'php-dev' ) ) return $content;
        return $content;
    }
}
