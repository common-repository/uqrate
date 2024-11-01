<?php
/**
 * This file is the plugin entrypoint. 
 * It sets the environment, loads the core class,
 * and registers (de)activation hooks,
 *
 * @link              https://uqrate.org/app/install
 * @since             1.0.0
 * @package           Uqrate
 *
 * @wordpress-plugin
 * Plugin Name:       Uqrate
 * Plugin URI:        https://uqrate.org/app/install
 * Description:       Member-curated comments, paid subscriptions and broadcasting services.
 * Version:           1.0.0
 * Author:            Sempernow LLC
 * Author URI:        https://uqrate.org/app
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       uqrate
 */
defined( 'ABSPATH' ) || die( 'ABSPATH' );
define( 'UQRATE_CLASS_CORE', implode( '_', explode(' ', 'Uqrate') ) );
class_exists( UQRATE_CLASS_CORE ) && die( UQRATE_CLASS_CORE.' class already exists.' );
define( 'UQRATE_PLUGIN_NAME', dirname( plugin_basename( __FILE__ ) ) );
if ( UQRATE_PLUGIN_NAME != 'uqrate' ) 
    die( 'Plugin name mismatch : declared versus actual' );
define( 'UQRATE_TEXTDOMAIN', UQRATE_PLUGIN_NAME . '_i18n' );
define( 'UQRATE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) ); 
$_UQRATE_ENV = [
    'build'                 => "2022-11-04T19.03.42Z",
    'mode_debug'            => false,
    'mode_local'            => false,
    'plugin_version'        => '1.0.0',
    'plugin_path'           => UQRATE_PLUGIN_PATH,
    'includes_path'         => UQRATE_PLUGIN_PATH . 'includes/',
    'views_path'            => UQRATE_PLUGIN_PATH . 'views/',
    'origin_pwa'            => 'https://uqrate.org',
    'login_pg_url'          => 'https://uqrate.org/app/signup',
    'chn_pg_url'            => 'https://uqrate.org/app/channel',
    'key_pg_url'            => 'https://uqrate.org/app/apikey',
    'key_check_url'        	=> 'https://uqrate.org/aoa/v1/key/chk/chn',
    'base_api_url'          => 'https://uqrate.org/api/v1/',
    'base_msg_upsert_url'   => 'https://uqrate.org/api/v1/key/m/upsert/',
    'base_msg_delete_url'   => 'https://uqrate.org/api/v1/key/m/delete/',
    'base_mid_url'          => 'https://uqrate.org/aoa/v1/ops/uuidv5/',
    'plugin_slug'           => UQRATE_PLUGIN_NAME, 
    'textdomain'            => UQRATE_TEXTDOMAIN, 
    'wp_opts_flag_active'   => UQRATE_PLUGIN_NAME . '_activated',
    'wp_opts_flag_jt'       => UQRATE_PLUGIN_NAME . '_created_jt',
    'wp_opts_key_prefix'    => UQRATE_PLUGIN_NAME . '_chnkey_',
    'wp_opts_setup_prefix'  => UQRATE_PLUGIN_NAME . '_setup_',
    'posts_threads_jt'      => UQRATE_PLUGIN_NAME . '_posts_threads',
    'plugin_file'           => basename( __FILE__ ),                    
    'plugin_relpath'        => plugin_basename( __FILE__ ),             
    'plugin_name'           => 'UQRATE_CLASS_CORE',
    'chn_key'               => false,
    'chn_key_subkeys'       => [
        'key', 'key_name', 'chn_id', 'chn_slug', 'host_url', 
        'date_create', 'date_update', 'rotations', 'error',
    ],
    'key_length'            => 87,
    'classes'               => [
        'core'  => UQRATE_CLASS_CORE, 
        'admin' => UQRATE_CLASS_CORE . '_Admin', 
        'page'  => UQRATE_CLASS_CORE . '_Page',
        'post'  => UQRATE_CLASS_CORE . '_Post',
        'sync'  => UQRATE_CLASS_CORE . '_Sync',
    ],
    'comments_tpl'          => false,
    'comments_app'          => false,
];
require_once UQRATE_PLUGIN_PATH . 'class.core.php';
class_exists( UQRATE_CLASS_CORE ) || die( UQRATE_CLASS_CORE . ' class failed to load.' );
$class = UQRATE_CLASS_CORE;
$class::init( $_UQRATE_ENV );
register_activation_hook( __FILE__, [ UQRATE_CLASS_CORE, 'activate' ] );
register_deactivation_hook( __FILE__, [ UQRATE_CLASS_CORE, 'deactivate' ] );
