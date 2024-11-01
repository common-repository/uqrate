<?php
/**
 * @package Uqrate
 * 
 * Uqrate core class loads plugin config, all child classes, 
 * and a common set of scripts and styles for Post and Page types.
 * Child classes are orthogonal to siblings.
 */
defined( 'ABSPATH' ) || die( 'ABSPATH' );
class Uqrate
{
    const SEP = ' : ';
    protected static $cfg = [];
    private static $initiated = false;
    protected static $state;
    public static function init( $cfg ) {
        if ( self::$initiated ) return;
        self::$cfg = $cfg;
        self::load_child_classes();
        self::init_hooks();
    }
    private static function load_child_classes() {
        foreach ( self::cfg('classes') as $class => $name ) {
            if ( $name ) {
                if ( $name  == __CLASS__ ) continue; 
                require_once( self::cfg('plugins_path') . 'class.' . $class . '.php' );
            }
        }
        Uqrate_Admin::init_hooks();
    }
    private static function init_hooks() {
        self::$initiated = true;
        add_filter('the_post', [ __CLASS__, 'the_post' ] );
        add_filter('the_content',  [ __CLASS__, 'the_content' ], 12 );
        add_filter( 'comments_template', [ __CLASS__, 'comments_template' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'wp_enqueue_scripts' ] );
    }
    public static function the_post( $post ) {
        self::cfg()['comment_status'] = $post->comment_status;
        Uqrate_Sync::sync( $post );
    }
    public static function the_content( $content ) {
        if ( ! ( in_the_loop() && is_main_query() ) ) 
            return $content;
        $Post = is_single();
        $Page = ( is_singular() && ! $Post );
        $Admin = is_admin();
        switch ( true ) {
            case $Post :
                self::cfg()['type'] = 'Post';
                if ( self::cfg('mode_debug') )
                    $content = self::test( $content );
                if ( self:: okayCommentsSection() )
                    return Uqrate_Post::comments_content( $content );
                return $content;
                break;
            case $Page :
                self::cfg()['type'] = 'Page';
                return Uqrate_Page::select( $content );
                break;
            case $Admin :
                self::cfg()['type'] = 'Admin';
                return $content;
                break;
            default :
                self::cfg()['type'] = 'UNKNOWN';
                self::errWrap('UNKNOWN webpage TYPE', __FUNCTION__); 
                return $content;
                break;
        }
    }
    public static function comments_template( $default_tpl ) {
        if ( ! ( is_single() && in_the_loop() && is_main_query() ) ) 
            return;
        if ( ! self::okayCommentsSection() ) 
            return;
        return Uqrate_Post::_comments_template( $default_tpl );
    }
    public static function wp_enqueue_scripts() {
        wp_enqueue_style( 'uqrate-css-p1', plugins_url( '/views/css/p.base.css', __FILE__ ) );
        if ( self::GetCfg('mode_debug') ) 
            wp_enqueue_style( 'uqrate-css-p2', plugins_url( '/views/css/p.test.css', __FILE__ ) );
        wp_enqueue_script( 'uqrate-js-p1', plugins_url( '/views/js/p.base.js', __FILE__ ) );
        wp_enqueue_script( 'uqrate-js-p2', 
            self::GetCfg('origin_pwa') . '/sa/scripts/uqrate.min.js',
            [],
            null,
            true 
        );
    }
    private static function test( $content ) {
        if ( ! self::cfg('mode_debug') ) return $content;
        $state = (object) [];
        foreach ( [
            'mid', 'url', 'rpt', 'http', 'exit', 'exit_txt', 'err', 'mode_local', 'mode_debug',
            'comments_template', 'comments_template_hook', 'comments_content', 'type'
            ] as $key ) {
            $state->$key = self::cfg($key);
        }
        if ( true ) {
            global $post;
            $state->comment_status = $post->comment_status;
            $state->guid = $post->guid;
            $state->uri = self::reqURI();
        }
        return $content
                . self::preObj( $state, 'state' );
    }
    protected static function reqURI() {
        return sanitize_url(strtolower( $_SERVER['REQUEST_URI'] ));
    }
    private static function okayCommentsSection() {
        return true;
        if ( self::GetCfg('comments_template') ) return false;
        global $post;
        return ( have_comments() || 'open' == $post->comment_status );
    }
    protected static function state( $key = false ) {
        if ( ! self::$state ) self::$state = (object) [];
        if ( $key ) 
        foreach ( ['mid', 'rpt', 'http', 'exit', 'exit_txt', 'err', 'mode_local', 'mode_debug'] as $key ) {
            self::$state->$key = self::cfg($key);
        }
        return self::$state;
    }
    protected static function cfgObj() { 
        self::GetChnKey();
        return json_decode( json_encode( self::$cfg ));
    }
    public static function GetCfg( $k1, $k2 = false ) {
        if ( ! array_key_exists ( $k1, self::cfg() ) ) 
            return false;
        if ( $k1 == 'chn_key' ) {
            return self::GetChnKey() ? true : false;
        }
        $v = self::cfg($k1);
        return $v;
    }
    protected static function &cfg( $k = '' ) {
        if ( $k ) return self::$cfg[$k];
        return self::$cfg;
    }
    protected static function view( $fname, array $args = [] ) {
        foreach ( $args AS $key => $val ) {
            $$key = $val;
        }
        load_plugin_textdomain( self::cfg('textdomain') );
        require_once( self::cfg('views_path') . $fname . '.php' );
    }
    public static function okTLS() {
        return (
            ( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
        ) ? true : false;
    }
    public static function rfc3339z( $date ) {
        return substr(date_format( date_create( $date ), 'c' ), 0, 19) . 'Z';
    }
    public static function toCamelCaseAlphaNum( $str, $jsCompliant = false ) {
        if ( !$str ) return $str;
        if ( str_replace(' ', '', $str ) == $str ) return $str;
        $str = str_replace(' ', '', ucwords(str_replace('-', ' ', $str )));
        if ( $jsCompliant) $str[0] = strtolower($str[0]);
        $str = preg_replace( '/[^a-z0-9]/i', '', $str);
        return $str;
    }
    public static function errWrap($what, $where = '') {
        self::wrap('err', $what, $where);
    }
    public static function rptWrap($what, $where = '') {
        self::wrap('rpt', $what, $where);
    }
    private static function wrap($key, $what, $where) {
        if ( ! $what ) return;
        $where = ($where ? '@'.$where.self::SEP : '');
        $what = is_string($what) ? $what : json_encode($what, JSON_PRETTY_PRINT);
        self::cfg($key)
            ? (
                self::cfg()[$key] = $where . print_r($what, true) 
                . self::SEP. self::cfg($key)
            ) 
            : (
                self::cfg()[$key] = $where . print_r($what, true)
            );
    }
    protected static function notice_err( $reason ) {
        //if ( self::cfg('exit') )  self::msgUpsertExit_text();
        return self::notice( 3, $reason);
    }
    protected static function notice( $level, $reason ) {
        switch ( $level ) {
            case 0:
                $class = 'notice notice-info is-dismissible';
                break;
            case 1:
                $class = 'notice notice-success is-dismissible';
                break;
            case 2:
                $class = 'notice notice-warning is-dismissible';
                break;
            case 3:
                $class = 'notice notice-error is-dismissible';
                break;
            }
        $notice = is_string( $reason ) 
                    ? __( $reason, self::cfg('textdomain') ) 
                    : json_encode( $reason, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES );
        if ( is_string( $reason ) ) {
            if ( ! $reason ) return;
            return sprintf( '<div class="%1$s"><p>%2$s</p></div>', 
                esc_attr( $class ), wp_kses_post( $notice )
            );
        } else {
            return sprintf( '<div class="%1$s"><pre>%2$s</pre></div>', 
                esc_attr( $class ), wp_kses_post( $notice )
            );
        }
    }
    public static function preObj( $obj, $title = '' ) {
        if ( ! self::cfg('mode_debug') ) return;
        ob_start();
        if ( $title ) { 
            echo '<div class="pre-obj">@ ' . esc_html( $title ) . '</div>'; 
        } else { 
            echo '';
        }
        echo '<pre class="pre-obj">' . esc_html( print_r( $obj, true) ) . '</pre>';
        return ob_get_clean();
    }
    protected static $prefix_chnkey;
    protected static $options_chnkey;
    protected static function wp_opts_cfg() {
        self::$prefix_chnkey = self::cfg('wp_opts_key_prefix');
        self::$options_chnkey = self::cfg('chn_key_subkeys');
    }
    protected static function SetChnKey( $obj ) {
        self::wp_opts_cfg();
        foreach ( $obj as $key => $val ) {
            update_option( self::$prefix_chnkey . $key, $val );
        }
    }
    protected static function GetChnKey() {
        if (   self::cfg('chn_key') 
                && self::cfg('chn_key')['key'] 
                && self::cfg('chn_key')['chn_id'] 
                && self::cfg('chn_key')['key_name'] 
                && !self::cfg('chn_key')['error']
            ) 
            return true;
        self::wp_opts_cfg();
        foreach ( self::$options_chnkey as $key ) {
            self::cfg()['chn_key'][$key] = get_option( self::$prefix_chnkey . $key );
        }
        if ( self::cfg('key_length') == strlen( self::cfg('chn_key')['key'] ) ) {
            if ( self::cfg('chn_key')['error']
                || ! self::cfg('chn_key')['chn_id'] 
                || ! str_contains( self::cfg('chn_key')['key'], self::cfg('chn_key')['key_name'] )
            ) {
                if ( self::keyChk() ) return true;
                return false;
            }
            return true;
        }
        return false;
    }
    protected static function DelChnKey() {
        self::wp_opts_cfg();
        foreach ( self::$options_chnkey as $key ) {
            delete_option( self::$prefix_chnkey . $key );
            self::cfg()['chn_key'][$key] = false;
        }
    }
    protected static function keyChk() {
        $url = self::cfg('key_check_url');
        $arr = [
            'method'    => 'GET',
            'headers'   => [
                'X-Api-Key' => self::cfg('chn_key')['key']
            ]
        ];
        $log = ['error' => ''];
        $resp = wp_remote_get( $url, $arr);
        if ( is_wp_error( $resp ) ) {
            $log['error'] = $resp->get_error_message();
        } else {
            $body = wp_remote_retrieve_body( $resp );
            $x = json_decode( $body );
            $code = wp_remote_retrieve_response_code( $resp );
            self::cfg()['http'] = $code;
            if ( $code == 200 ) {
                foreach ( $x as $key => $val ) {
                    update_option( self::$prefix_chnkey . $key, $val );
                    self::cfg()['chn_key'][$key] = get_option( self::$prefix_chnkey . $key );
                }
                update_option( self::$prefix_chnkey . 'error', '' );
                return true;
            }
            $log['error'] = $x->error;
            self::errWrap('HTTP '.$code, __FUNCTION__ );
        }	
        self::errWrap( $log['error'], __FUNCTION__ ); 
        update_option( self::$prefix_chnkey . 'error', self::cfg('err') );
        return false;
    }
    public static function activate() {
        update_option( self::cfg('wp_opts_flag_active') , true ); 
        Uqrate_Admin::create_posts_threads_jt();
        flush_rewrite_rules();
    }
    public static function deactivate() {
        update_option( self::cfg('wp_opts_flag_active') , false ); 
        flush_rewrite_rules();
    }
    public static function uninstall() { 
        Uqrate_Post::DelChnKey(); 
    }
}
if ( ! function_exists('str_contains') ) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}