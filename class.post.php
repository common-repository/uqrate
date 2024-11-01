<?php
/**
 * @package Uqrate
 * 
 * Uqrate_Post handles all Post-type webpages.
 * It injects plugin-configured markup 
 * into each single Post webpage that enables 
 * Uqrate to render and service the comments section.
 */
defined( 'ABSPATH' ) || die( 'ABSPATH' );
class Uqrate_Post extends Uqrate
{
    /*******************************************************************
     * Two methods of appending our comments section are available.
     * 
     * Content method hook:  'the_content'
     * Template method hook: 'comments_template'
     * 
     * Both abide native comments-rendering rules.
     * JS repositions regardless of method. 
     * Position varies per template if template lacks MAIN.
     ******************************************************************/
    /*******************************************************************
     * Native, unstructured text-node residue lingers per template 
     * regardless of method if comment(s) exist.
     * See HTML node : '#main ARTICLE DIV.entry-meta' 
     ******************************************************************/
    protected static function comments_content( $content ) {
        if ( self::GetCfg('comments_template') ) 
            return $content;
        $always_by_content_method = true;
        switch ( true ) {
            case $always_by_content_method:
                self::cfg()['comments_content'] = true;
                break;
            default:
                if ( self::cfg()['comment_status'] == 'open' )     
                    return $content;
                break;
        }
        return $content . self::comment_section( 'content' );
    }
    protected static function _comments_template( $default_tpl ) {
        if ( self::GetCfg('comments_content') ) 
            return;
        self::cfg()['comments_template'] = true;
        return self::GetCfg('views_path') . 'comments.php';
    }
    private static function comment_section( $method ) {
        $key = false;
        if ( self::GetCfg('chn_key') )  
            $key = true;
        $mid = self::GetCfg('mid');
        $origin = self::GetCfg('origin_pwa');
        ob_start();
        ?>
            <!-- SECTION below is appended by Uqrate plugin -->
            <section id="uqrate-comments">
                <div 
                    id="uqrate-iframe-container" 
                    data-method="<?php echo esc_attr( $method ); ?>"
                    data-api-key="<?php echo esc_attr( $key ); ?>" 
                    data-msg-id="<?php echo esc_attr( $mid ); ?>">
                    <!-- SCRIPT below inserts IFRAME here -->
                </div>
            </section>
        <?php
        return ob_get_clean();
    }
}
