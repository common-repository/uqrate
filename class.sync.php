<?php
/**
 * @package Uqrate
 * 
 * Uqrate_Sync upserts Wordpress and Uqrate 
 * data stores as needed to keep each Wordpress
 * post synchronized with its paired message at Uqrate.
 * 
 * This class does not affect the DOM directly, 
 * but results are collected in a core-class state 
 * object pretty-printed per target Post in debug mode.
 */
defined( 'ABSPATH' ) || die( 'ABSPATH' );
class Uqrate_Sync extends Uqrate
{
    protected static function sync( $post ) {
        /***********************************************************************
         * Synchronize this $post with its pair at remote data store.
         * INSERT or UPDATE posts_threads junction table (WordPress), 
         * and UPSERT its paired record, processing as necessary per new, 
         * mismatch (post v. thread), or prior-attempt-error flag 
         * in its junction-table (JT) record.
         * 
         * This process should be triggered with each Post request.
         * If the Post is unchanged, the process cost is one JT (fast) query.
         **********************************************************************/
        if ( ! is_single() || ! is_main_query() ) return;
        if ( ! self::syncOnce() ) return;
        global $wpdb;
        $table_name = $wpdb->prefix . self::cfg('posts_threads_jt');
        self::cfg('mode_debug') || $wpdb->hide_errors();
        $jt_record = $wpdb->get_row( 
            "SELECT id, post_id, lower(insert(insert(insert(insert(hex(tid_bin), 
                9, 0, '-'), 14, 0, '-'), 19, 0, '-'), 24, 0, '-')
            ) AS tid_txt, date_create, date_modify, error 
            FROM $table_name
            WHERE post_id = $post->ID"
        );
        if ( is_object( $jt_record ) ) self::rptWrap( 'wpdb:SELECTed JT record: '. $jt_record->tid_txt );
        if ( is_object( $jt_record ) && property_exists( $jt_record, 'tid_txt' ) ) {
            self::cfg()['mid'] = $jt_record->tid_txt;
            if ( $post->post_modified_gmt > $jt_record->date_modify ) {
                $sql = "UPDATE $table_name SET 
                            date_modify = '$post->post_modified_gmt'
                        WHERE post_id = $post->ID
                    ";
                $rows = $wpdb->query($sql);
                if ( $rows ) {
                    self::rptWrap( 'wpdb:UPDATEd ' . $rows . ' JT record : requires remote UPSERT' );
                    self::msgUpsert( $post );
                } else {
                    self::errWrap('wpdb:UPDATE: FAIL : no JT record updated', __FUNCTION__); 
                }
            } else {
                if ( $jt_record->error != 0 ) {
                    self::rptWrap( 'remote UPSERT on prior-request error' );
                    self::msgUpsert( $post );
                } else {
                    self::rptWrap( 'no change' );
                }
            }
        } else {
            if ( self::getMID() ) {
                $mid = self::cfg('mid');
                $sql = "INSERT IGNORE INTO $table_name (
                            post_id, 
                            tid_bin, 
                            date_create, 
                            date_modify
                        )
                        VALUES (
                            $post->ID, 
                            unhex(replace('$mid','-','')), 
                            '$post->post_date_gmt',
                            '$post->post_modified_gmt'
                    )";
                $rows = $wpdb->query($sql);
                if ( $rows ) {
                    self::rptWrap( 'wpdb:INSERT: '. print_r($rows, true) . ' new JT record' );
                    self::rptWrap( 'requires remote UPSERT' );
                    self::msgUpsert( $post );
                } else {
                    self::errWrap( 'wpdb:INSERT: no new JT record', __FUNCTION__ );
                }
            } else {
                self::errWrap( 'remote UPSERT FAIL', __FUNCTION__ );
            }
        }
        self::rptWrap( 'post id: '. $post->ID );
        self::msgUpsertExit_text();
        return;
    }
    private static function syncOnce() {
        static $once = true;
        if ( ! $once ) return false;
        $once = false;
        return true;
    }
    private static function msgUpsert( $post ) {
        /**************************************************
         * Upsert the message (post) into remote store.
         * Reset error code in junction table regardless.
         *************************************************/
        $error = 0;
         if ( ! self::_msgUpsert( $post ) ) {
            $http  = (int) self::cfg('http');
            $exit  = (int) self::cfg('exit');
            $error = $http + $exit; 
            self::errWrap( 'remote UPSERT FAIL: error: ' . print_r( $error, true ) , __FUNCTION__ );
        }
        self::rptWrap( 'remote UPSERTed : requires UPDATE of JT record: post id: '. print_r( $post->ID, true), __FUNCTION__);
        global $wpdb;
        $table_name = $wpdb->prefix . self::cfg('posts_threads_jt');
        $sql = "UPDATE $table_name SET 
                    error = $error
                WHERE post_id = $post->ID
            ";
        $rows = $wpdb->query( $sql ); 
        if ( $rows ) {
            self::rptWrap( 'wpdb:UPDATEd JT: ' . $rows . ' record : reset error: ' . print_r( $error, true ), __FUNCTION__ );
        } else {
            self::rptWrap('wpdb:UPDATE JT: record okay: ' . print_r( $error, true ), __FUNCTION__); 
        }
        return $error ? false : true;
    }
    private static function _msgUpsert( $post ) {
        /***************************************************************
         * Request message UPSERT at remote service using API key.
         * Return true on success, else return false.
         * 
         * cfg('exit') : nnn reserved for HTTP codes; all others n000 .
         **************************************************************/
        self::cfg()['exit'] = 0;
        if ( ! self::GetChnKey() ) {
            self::errWrap( 'missing API key', __FUNCTION__ );
            self::cfg()['exit'] = 1000;
            return false;
        }
        $upsert_url = self::cfg('base_msg_upsert_url') . self::cfg('mid');
        if ( ! $post->post_content ) {
            self::errWrap( 'empty body', __FUNCTION__ );
            self::cfg()['exit'] = 2000;
            return false;
        }
        $date = '';
        if ( $post->post_modified_gmt ) {
            $date = self::rfc3339z( $post->post_modified_gmt );
        } else {
            $date = self::rfc3339z( $post->post_date_gmt );
        } 
        $tags = [];
        $got = get_the_tags($post->ID);
        if ( $got )
            foreach ( $got as $obj) {
                $tag = self::toCamelCaseAlphaNum($obj->name);
                if ( !$tag ) continue;
                array_push($tags, $tag);
            };
        $cats = [];
        $got = get_the_category($post->ID);
        if ( $got )
            foreach ( $got as $obj) {
                if ( $obj->name == 'Uncategorized' )
                    continue;
                $tag = self::toCamelCaseAlphaNum($obj->name);
                if ( !$tag ) continue;
                array_push($cats, $tag);
            };
        $msg = [
            'title'       => $post->post_title,
            'body'        => $post->post_content,
            'date_update' => $date,
            'uri'         => self::reqURI(),
        ];
        if ( $post->post_excerpt ) $msg['summary'] = $post->post_excerpt;
        if ( count( $tags ) ) $msg['tags'] = $tags;
        if ( count( $cats ) ) $msg['cats'] = $cats;
        $data = wp_json_encode( $msg, JSON_UNESCAPED_SLASHES );
        $resp = wp_remote_post( $upsert_url, [
                'method'  => 'POST',
                'headers' => [
                    'X-Api-Key' => self::cfg('chn_key')['key'],
                ],
                'body'    => $data,
            ]
        );
        if ( is_wp_error( $resp ) ) {
            self::errWrap( $resp->get_error_message(), __FUNCTION__ );
            self::cfg()['exit'] = 3000;
            return false;
        } else {
            $x = json_decode( wp_remote_retrieve_body( $resp ) );
            $code = wp_remote_retrieve_response_code( $resp );
            self::cfg()['http'] = $code;
            if ( $code > 299 ) { 
                if ( $x->error ) self::errWrap($x->error, __FUNCTION__ );
                self::cfg()['exit'] = 4000;
                if ( $code == 424 ) self::keyChk();
                return false;
            }
            return true;
        }
    }
    private static function msgUpsertExit_text() {
        $exit = self::cfg('exit');
        if ( $exit < 1000 ) return false;
        switch ( true ) {
            case 1000 == $exit:
                $txt = "Insufficient API-key object stored";
                break;
            case 2000 == $exit:
                $txt = "Invalid post field(s)";
                break;
            case 3000 == $exit:
                $txt = "Network exit" . ( self::cfg('err') ? ' : ' . self::cfg('err') : '' );
                break;
            case ( ( 4000 < $exit ) && ( 5000 > $exit ) ):
                if ( 4424 == $exit ) {
                     $txt = "HTTP 424 (Failed Dependency) : API-key name not found on remote UPSERT request";
                } else {
                     $txt = "HTTP ".strval( ( $exit - 4000 ) ) . ' response to remote UPSERT request';
                }
                break;
            default:
                $txt = 'unknown error';
                break;
        }
        self::cfg()['exit_txt'] = $txt;
        return true;
    }
    private static function getMID() {
        /**********************************************************************
         * Get the cross-app message id (MID) : UUIDv5 of OID namespace
         * 
         * A Wordpress Post is paired to its long-form message (thread root)
         * in the key-associated channel of the owner's account at Uqrate.
         * The comments section serviced by this plugin is comprised
         * of child messages to that thread-root message.
         * 
         * Message/Thread ID (MID/TID) are one and the same cross-app ID;
         * recorded at Wordpress data store as a binary in posts-threads JT.
         *********************************************************************/
        static $mid = '';
        if ( 36 == strlen($mid) ) return true;
        if ( $mid ) return false;
        $mid = 'UUID_NAME_FAIL';
        if ( ! self::GetChnKey() ) return false;
        $cid = strtolower( self::cfg('chn_key')['chn_id'] );
        $uri = self::reqURI();
        if ( ! ( $cid && $uri ) ) {
            self::cfg()['mid'] = $mid;
            self::errWrap( 'missing source(s)', __FUNCTION__ );
            return false;
        }
        $mid = 'UUID_MALFORMED';
        $code = 0;
        $url = self::cfg('base_mid_url') . urlencode( $cid ) . '/' . urlencode( $uri );
        self::cfg()['url'] = $url;
        $resp = wp_remote_get( $url );
        if ( is_wp_error( $resp ) ) {
            self::errWrap( $resp->get_error_message(), __FUNCTION__ );
        } else {
            $code = wp_remote_retrieve_response_code( $resp );
            if ( $code == 200 ) {
                $body = wp_remote_retrieve_body( $resp );
                $json = json_decode( $body );
                if ( !empty($json[0]) ) {
                    $mid = $json[0];
                }
            }
        }
        self::cfg()['mid'] = $mid;
        self::cfg()['http'] = $code;
        if ( 36 == strlen($mid) ) {
            return true;
        } 
        self::errWrap( $mid, __FUNCTION__ );
        return false;
    }
}