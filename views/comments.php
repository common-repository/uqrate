<?php
$key = false;
if ( Uqrate::GetCfg('chn_key') )  
    $key = true;
$mid = Uqrate::GetCfg('mid');
$origin = Uqrate::GetCfg('origin_pwa');
?>
    <!-- Uqrate comments_template -->
    <section id="uqrate-comments">
        <div 
            id="uqrate-iframe-container" 
            data-method="template"
            data-api-key="<?php echo esc_attr( $key ); ?>" 
            data-msg-id="<?php echo esc_attr( $mid ); ?>">
            <!-- The uqrate script inserts IFRAME here -->
        </div>
    </section>
