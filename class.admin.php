<?php
/**
 * @package Uqrate
 * 
 * Uqrate_Admin handles all Dashboard panels,
 * plugin setup, and all other administrative doings.
 */
defined( 'ABSPATH' ) || die( 'ABSPATH' );
class Uqrate_Admin extends Uqrate
{
    const SETUP_PAGE = 'uqrate-settings-page';
    const OPTION_GROUP = 'uqrate_options_group';
    protected static function init_hooks() {
        add_action( 'admin_init', [ __CLASS__, 'admin_init' ] );
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
        add_action( 'admin_notices', [ __CLASS__, 'admin_notices' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ] );
        add_filter( 'plugin_action_links_' . self::cfg('plugin_relpath'), [ __CLASS__, 'plugin_action_links' ] );
        add_action( 'current_screen', [ __CLASS__, 'prune_jt' ] );
    }
    public static function admin_enqueue_scripts() {
        wp_enqueue_style( 'a1style', plugins_url( '/views/css/a.base.css', __FILE__ ) );
        wp_enqueue_style( 'a2style', plugins_url( '/views/css/a.settings.css', __FILE__ ) );
        wp_enqueue_script( 'ascript', plugins_url( '/views/js/a.base.js', __FILE__ ) );
    }
    public static function admin_init() {
        if ( ! is_admin() ) return;
        self::settings_register();
        self::remove_native_comments();
    }
    public static function admin_menu() {
        if ( ! is_admin() ) return;
        //
        add_options_page(
            'Uqrate', 
            'Uqrate', 'manage_options', 
            self::SETUP_PAGE, 
            [ __CLASS__, 'settings_page' ]
         );
        //
    }
    public static function admin_notices( $title ) {
        if ( ! is_admin() ) return;
        if ( ! self::cfg('err') ) {
            if ( ! self::is_plugins_page() ) return;
            if ( self::GetChnKey() ) return;
            self::view( 'admin', [ 'type' => 'uqrate_setup_prompt' ] );
            //... want after hr.wp-header-end, inside #wpbody-content
        } else {
            echo self::notice_err( self::cfg('err') );
        }
    }
    public static function plugin_action_links( $links ) {
        $settings_link = '<a href="options-general.php?page=' . self::SETUP_PAGE . '">Settings</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }
    public static function prune_jt( $current_screen ) {
        /*****************************************************************
         * Iterate through the posts_threads juncton table (JT) records, 
         * and if a thread's post no longer exists, 
         * then delete the JT record and at its remote pair.
         ****************************************************************/
        if( $current_screen->id != 'edit-post' ) return false;
        if ( ! self::pruneOnce() ) return false;
        if ( ! self::GetChnKey() ) return;
        $sql = "SELECT lower(insert(insert(insert(insert(hex(th.tid_bin), 
                    9, 0, '-'), 14, 0, '-'), 19, 0, '-'), 24, 0, '-')
                ) AS tid_txt
                FROM wp_posts p
                JOIN wp_uqrate_posts_threads th
                ON p.ID = th.post_id
                WHERE p.post_status = 'trash'
            ";
        global $wpdb;
        $orphans = $wpdb->get_results( $sql ); 
        self::cfg()['orphans'] = $orphans;
        self::cfg()['deleted'] = [];
        foreach ( $orphans as $msg ) {
            $url = self::cfg('base_msg_delete_url') . $msg->tid_txt;
            $resp = wp_remote_request( $url, [
                    'method'  => 'DELETE',
                    'headers' => [
                        'X-Api-Key' => self::cfg('chn_key')['key'],
                    ]
                ]
            );
            if ( is_wp_error( $resp ) ) {
                self::errWrap( $resp->get_error_message(), __FUNCTION__ );
                self::cfg()['exit'] = 2000;
                continue;
            } else {
                $x = json_decode( wp_remote_retrieve_body( $resp ) );
                $code = wp_remote_retrieve_response_code( $resp );
                self::cfg()['http'] = $code;
                self::cfg()['body'] = $x;
                if ( $code > 299 ) { 
                    if ( $x->error ) self::errWrap($x->error . ' ' . $msg->tid_txt , __FUNCTION__ );
                    self::cfg()['exit'] = 3000;
                    if ($code == 404 ) {
                        self::delete_jt_record( $msg->tid_txt );
                    }
                    continue;
                }
                array_push( self::cfg()['deleted'], $msg->tid_txt );
                self::delete_jt_record( $msg->tid_txt );
            }
        } 
    }
    public static function settings_page() {
        Uqrate::view( 'admin', [ 'type' => 'uqrate_settings' ] );
    }
    public static function get_page_url( $page = 'uqrate_setup_prompt' ) {
        $url = '';
        switch ($page) {
            case 'uqrate_setup_prompt':
                $url = get_site_url() .'/wp-admin/options-general.php?page=uqrate-settings-page';
                break;
            case 1:
                break;
            case 2:
                break;
        }
        return $url;
    }
    public static function sanitizeChnKeyInput( $input ) {
        if ( ! preg_match( "/^([a-zA-Z0-9]){26}(\.){1}([a-zA-Z0-9]){60}$/", $input ) ) {
            add_settings_error( 
                'uqrate_chnkey_key', 
                'uqrate_chnkey_key_err', 
                'UNCHANGED : The input did not fit the expected pattern. (Paste the entire API key copied from Uqrate.)'
            );
            return get_option('uqrate_chnkey_key');
        }
        return $input;
    }
    public static function opt_dropdown_1_HTML() { 
        ?>
        <div class="">
            <select name="uqrate_opt_dropdown_1">
                <option value="1" <?php selected(get_option('uqrate_opt_dropdown_1'), '1');?>>First</option>
                <option value="2" <?php selected(get_option('uqrate_opt_dropdown_1'), '2');?>>Second</option>
                <option value="3" <?php selected(get_option('uqrate_opt_dropdown_1'), '3');?>>Third</option>
            </select>
        </div>
        <?php 
    }
    public static function opt_textinput_1_HTML() { 
        ?>
        <div class="">
            <input type="text" name="uqrate_chnkey_key" value="<?php echo esc_attr( get_option('uqrate_chnkey_key') ); ?>">
        </div>
        <?php 
    }
    public static function opt_checkbox_1_HTML($args) { 
        ?>
        <div class="">
            <input type="checkbox" name="<?php echo esc_attr( $args['box_name'] ); ?>" value="1" <?php checked(get_option( $args['box_name'] ), '1'); ?>>
        </div>
        <?php 
    }
    protected static function remove_native_comments() {
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_redirect( admin_url() );
            return;
        }
        remove_menu_page('edit-comments.php');
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        false && self::all_comments_closed_always();
    }
    private static function all_comments_closed_always() {
        foreach ( get_post_types() as $post_type ) {
            if ( post_type_supports($post_type, 'comments') ) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }
    protected static function is_plugins_page() {
        return ( get_current_screen()->parent_file == 'plugins.php' );
    }
    private static function settings_register() {
        add_settings_section(
            'uqrate_settings_block',
            null,  
            null, 
            self::SETUP_PAGE
        );
        if ( false) {
            add_settings_field(
                'uqrate_opt_dropdown_1', 
                'Option Dropdown 1', 
                [ __CLASS__, 'opt_dropdown_1_HTML' ],  
                self::SETUP_PAGE, 
                'uqrate_settings_block'
            );
            $args = [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '0',
            ]; 
            register_setting(self::OPTION_GROUP, 'uqrate_opt_dropdown_1', $args);
        }
        if ( true ) {
            /****************************************************************
             * Want reset of all chnkey-associated fields on form submit, 
             * and so want all input(s) rendered and registered;
             * HOWEVER, do not want user to see or interact with any
             * except for the key itself, so must hide all other inputs
             * using javascript.
             ***************************************************************/
            add_settings_field(
                'uqrate_chnkey_key', 
                'Secret API Key', 
                [ __CLASS__, 'opt_textinput_1_HTML' ],  
                self::SETUP_PAGE, 
                'uqrate_settings_block'
            );
            $args = [
                'type' => 'string',
                'description' => 'The API key',
                'sanitize_callback' => [ __CLASS__, 'sanitizeChnKeyInput' ], 
                'default' => ''
            ];
            register_setting(self::OPTION_GROUP, 'uqrate_chnkey_key', $args);
        }
        if ( false ) { 
            add_settings_field(
                'uqrate_opt_checkbox_1', 
                'Option Checkbox 1', 
                [ __CLASS__, 'opt_checkbox_1_HTML' ],  
                self::SETUP_PAGE, 
                'uqrate_settings_block',
                [ 'box_name' => 'uqrate_opt_checkbox_1' ]
            ); 
            $args = [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '1'
            ]; 
            register_setting( self::OPTION_GROUP, 'uqrate_opt_checkbox_1', $args );
        } 
    }
    private static function delete_jt_record( $tid ) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::cfg('posts_threads_jt');
        $sql = "DELETE FROM $table_name
                WHERE tid_bin = unhex(replace('$tid','-',''))
        ";
        $rows = $wpdb->query($sql);
        if ( $rows ) {
            self::rptWrap( 'DELETEd orphaned posts_threads record: tid: '. $tid );
            self::rptWrap( 'wpdb:DELETEd: '. print_r($rows, true) . ' record' );
        } else {
            self::errWrap( 'wpdb:DELETE: orphan posts_threads record NOT deleted: tid: ' . $tid, __FUNCTION__ );
        }
    }
    private static function pruneOnce() {
        static $once = true;
        if ( ! $once ) return false;
        $once = false;
        return true;
    }
    protected static function create_posts_threads_jt() {
        /**********************************************************
         * Create the posts-threads junction table;
         * pairs Wordpress Post with its Uqrate Message.
         *********************************************************/
        if ( get_option( self::cfg('wp_opts_flag_jt') ) ) 
            return true; 
        if ( ! self::cfg('posts_threads_jt') ) {
            $reason = "Missing 'posts_threads_jt' configuration value.";
            self::cfg()['err'] = self::notice_err( $reason );
            return false;
        }
        global $wpdb;
        $sql = '';
        $table_name = $wpdb->prefix . self::cfg('posts_threads_jt');
        $charset_collate = $wpdb->get_charset_collate();
        switch (6) {
            case 1:
                $sql =
                    "CREATE TABLE IF NOT EXISTS $table_name (
                        id BIGINT NOT NULL auto_increment,
                        post_id BIGINT NOT NULL,
                        thread_id varchar(36) NOT NULL,
                        date_create DATETIME DEFAULT now(),
                        date_modify DATETIME DEFAULT now(),
                        PRIMARY KEY  (id),
                        FOREIGN KEY  (post_id) REFERENCES wp_posts(id) ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE = InnoDB $charset_collate";
                break;
            case 2:
                $sql =
                    "CREATE TABLE IF NOT EXISTS $table_name (
                        id BIGINT NOT NULL auto_increment,
                        post_id BIGINT NOT NULL,
                        thread_id varchar(36) NOT NULL,
                        date_create DATETIME DEFAULT now(),
                        date_modify DATETIME DEFAULT now(),
                        PRIMARY KEY  (id),
                        INDEX (thread_id)
                    ) $charset_collate";
                break;
            case 3:
                /**************************************************
                 * The virtual column is unnecessary 
                 * and requires MySQL 5.7+
                 *************************************************/
                $sql =
                    "CREATE TABLE IF NOT EXISTS $table_name (
                        id BIGINT UNSIGNED NOT NULL auto_increment,
                        post_id BIGINT UNSIGNED NOT NULL UNIQUE,
                        tid_bin binary(16) NOT NULL,
                        tid_txt varchar(36) GENERATED ALWAYS AS
                            (INSERT(
                                INSERT(
                                    INSERT(
                                        INSERT(hex(tid_bin),9,0,'-'),
                                        14,0,'-'),
                                    19,0,'-'),
                                24,0,'-')
                            ) virtual,
                        date_create DATETIME DEFAULT now(),
                        date_modify DATETIME DEFAULT now(),
                        PRIMARY KEY  (id),
                        INDEX (tid_txt),
                        CONSTRAINT fk_uqrate_posts_threads 
                        FOREIGN KEY (post_id)
                        REFERENCES wp_posts(ID) ON DELETE CASCADE
                    ) $charset_collate";
                break;
            case 4:
                $sql =
                    "CREATE TABLE IF NOT EXISTS $table_name (
                        id BIGINT UNSIGNED NOT NULL auto_increment,
                        post_id BIGINT UNSIGNED NOT NULL UNIQUE,
                        tid_bin binary(16) NOT NULL UNIQUE,
                        date_create DATETIME DEFAULT now(),
                        date_modify DATETIME DEFAULT now(),
                        PRIMARY KEY  (id),
                        CONSTRAINT fk_uqrate_posts_threads 
                        FOREIGN KEY (post_id)
                        REFERENCES wp_posts(ID) ON DELETE CASCADE
                    ) $charset_collate";
                break;
            case 5:
                $sql =
                    "CREATE TABLE IF NOT EXISTS $table_name (
                        id BIGINT UNSIGNED NOT NULL auto_increment,
                        post_id BIGINT UNSIGNED NOT NULL UNIQUE,
                        tid_bin binary(16) NOT NULL UNIQUE,
                        date_create DATETIME DEFAULT now(),
                        date_modify DATETIME DEFAULT now(),
                        error INT DEFAULT 0,
                        PRIMARY KEY  (id),
                        CONSTRAINT fk_uqrate_posts_threads 
                        FOREIGN KEY (post_id)
                        REFERENCES wp_posts(ID) ON DELETE NO ACTION
                    ) $charset_collate";
                break;
            case 6:
                $sql =
                    "CREATE TABLE IF NOT EXISTS $table_name (
                    id BIGINT UNSIGNED NOT NULL auto_increment,
                    post_id BIGINT UNSIGNED NOT NULL UNIQUE,
                    tid_bin binary(16) NOT NULL UNIQUE,
                    date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    date_modify TIMESTAMP,
                    error INT DEFAULT 0,
                    PRIMARY KEY  (id),
                    CONSTRAINT fk_uqrate_posts_threads 
                    FOREIGN KEY (post_id)
                    REFERENCES wp_posts(ID) ON DELETE NO ACTION 
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                break;
        }
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
            $reason = 'FAILed @ "CREATE TABLE ... ' . self::cfg('posts_threads_jt') . ' ..."';
            self::cfg()['err'] = self::notice_err( $reason );
            update_option( self::cfg('wp_opts_flag_jt') , false ); 
            return false;
        }
        update_option( self::cfg('wp_opts_flag_jt') , true ); 
        return true;
    }
    protected static function drop_posts_threads_jt() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::cfg('posts_threads_jt');
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name ) {
            $reason = 'FAILed @ "DROP TABLE ... ' . self::cfg('posts_threads_jt') . '"';
            self::cfg()['err'] = self::notice_err( $reason );
            return false;
        }
        return true;
    }
    protected static function GetThreads( $n = 100 ) {
        global $wpdb;
        self::cfg('mode_debug') || $wpdb->hide_errors();
        $table_name = $wpdb->prefix . self::cfg('posts_threads_jt');
        $threads = $wpdb->get_results( 
            "SELECT id, post_id, lower(insert(insert(insert(insert(hex(tid_bin), 
                9, 0, '-'), 14, 0, '-'), 19, 0, '-'), 24, 0, '-')
            ) AS tid_txt, date_create, date_modify FROM $table_name
            ORDER BY date_create DESC LIMIT $n", 
            ARRAY_A 
        );
        return $threads;
    }
}