<!-- "Setup ..." button at Plugins page upon activation -->
<?php if ( $type == 'test' ) : ?>
<div>
    <h2>TEST</h2>
    <?php echo Uqrate::preObj(Uqrate::GetCfg('classes'));?>
</div>
<?php elseif ( $type == 'uqrate_setup_prompt' ) : ?>
<style>	
    :root {
        --color-1: #2271b1;
        --color-2: #13578f;
        --color-3: #0092BF;
    }
    #uqrate_setup_prompt form {
        display: flex;
        align-items: center;
        justify-content: left;
    }
    #uqrate_setup_prompt form .description {
        font-size: 1.5em;
        color: #555;
        margin: .2em 0 .2em 1em;
        padding: .55em;
        border-radius: .5em;
        background: #eee;
    }
    #uqrate_setup_prompt form .description span {
        color: #888;
    }
    #uqrate_setup_prompt form .description.err {
        color: #fff;
        background: #f06;
    }
</style>
<?php $err = '';if ( Uqrate::cfg('chn_key')['error'] ) { $err = 'notice-error'; } ?>
<div id="uqrate_setup_prompt" class="notice <?php echo $err; ?> is-dismissible">
    <form name="uqrate_activate" action="<?php echo esc_url( Uqrate_Admin::get_page_url( $type ) ); ?>" method="POST">
        <div class="">
            <button class="activate" type="submit"><?php _e( 'Setup your', UQRATE_TEXTDOMAIN );?> <span class="uqrate">uqrate</span> <?php _e( 'plugin', UQRATE_TEXTDOMAIN );?></button> 
        </div>
        <?php if ( $err == '' ) : ?>
        <div class="description">
            Speak your mind, mind your <span class="uqrate">q</span>'s and <span class="uqrate">P</span>'s, and prosper.
        </div>
        <?php else : ?>
        <div class="description err">
            <?php echo 'Bad API Key'; ?>
        </div>
        <?php endif; ?>
    </form>
</div>
<!-- Custom settings page of our sub-menu under Dashboard > Settings menu -->
<?php elseif ( $type == 'uqrate_settings' ) : ?>
<style>
    #uqrate_settings .uqrate {
        color: #664;
        opacity: .8;
    }
    #uqrate_settings h1 {
        display: none;
    }
    #uqrate_settings .uqrate_settings_header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    #uqrate_settings .uqrate_setup_footer {
        display: flex;
        align-items: center;
        justify-content: left;
    }
    #uqrate_settings .uqrate_setup_footer .build {
        font-family: monospace;
        opacity: .5;
    }
    #uqrate_settings .uqrate_setup_footer .uqrate_logo {
        display: none;
    }
    #uqrate_settings .uqrate_settings_header h2 {
        margin: .5em 0 .1em 0;
    }
    #uqrate_settings .uqrate_settings_header h2,
    #uqrate_settings .uqrate_settings_header h2 span.uqrate {
        font-size: 3rem;
        font-weight: bold;
        color: #330;
        line-height: 1.2em;
        opacity: 1;
    }
    #uqrate_settings .uqrate_settings_header h2 span.uqrate strong {
        color: #af9e06;
    }
    #uqrate_settings .uqrate_settings_header .build {
        display: none;
        font-size: .8em;
        font-family: monospace;
        opacity: .7;
    }
    #uqrate_settings .uqrate_logo {
        width: 8em;
        height: 8em;
        fill: #c6b305;
    }
    #uqrate_settings .button {
        font-size: 2em;
        border-radius: .5em;
        box-shadow: .1em .1em .2em rgba(0, 0, 0, 0.3); /* x, y, blur */
    }
    #uqrate_settings .button:active {
        opacity: .9;
    }
    #uqrate_settings dl {
        display: flex;
        align-items: center;
        margin-bottom: 2em;
        flex-wrap: wrap;
    }
    #uqrate_settings dl dt {
        font-size: 3em;
        padding: .7em;
        background:#c6b305; 
        border-radius: 50%;
    }
    #uqrate_settings dd .button {
        width: 8em;
        text-align: center;
    }
    #uqrate_settings_block input.button {
        width: 8em; /* keep uniform */
    }
    #uqrate_settings dd.info{
        font-size: 1.2em;
        max-width: 30em;
        margin: 1em 0 1.5em 2em;
    }
    /** $title @ add_settings_section(..) */
    #uqrate_settings_block dd {
        width: 100%;
        margin: 0;
    }
    #uqrate_settings_block form {
        margin: 0;
    }
    #uqrate_setup dd.link,
    #uqrate_setup dd.info {
        padding-top: .2em;
    }
    #uqrate_setup a.button,
    #uqrate_settings_update a.button {
        height: auto;
        padding: .2em;
    }
    #uqrate_settings_block input[name="uqrate_chnkey_key"] {
        height: auto;
        width: 100%;
        font-size: .8em;
        font-family: monospace;
        color: #999;
        padding: .5em .4em .5em .4em;
        max-width: 53em;
    }
    #uqrate_settings_block input[name="submit"] {
        height: auto;
        padding: .2em;
        font-size: 2em;
    }
    #uqrate_settings_block input[name="submit"]:focus {
        opacity: .9;
    }
    #uqrate_settings_block form table th {
        width: 7em;
        font-size: 2em;
        color: #330;
    }
    /**  status  **/
    #uqrate_status_block dd {
        margin: 0;
        padding: 0;
    }
    #uqrate_status_block table td.key {
        font-family: monospace;
        width: 7em;
        text-align: right;
        padding-right: .7em;
    }
    #uqrate_status_block table td.val {
        font-family: monospace;
        min-width: 20em;
        text-align: left;
        padding-right: .7em;
    }
    #uqrate_status_block table td .warn {
        text-align: left;
        color: #f06;
        font-weight: bold;
        padding-left: 2em;
    }
    #uqrate_status_block table td .warn a,
    #uqrate_status_block table tr.warn td.key {
        color: #f06;
    }
</style>
<div id="uqrate_settings" class="wrap" data-api-key="<?php if ( Uqrate::GetCfg('chn_key') ) echo '1'; ?>">
    <h1><!-- wp notices --></h1>
    <div class="uqrate_settings_header">
        <h2>
            <span>
                <span class="uqrate">u<strong>q</strong>rate</span>
                <?php 
                if ( Uqrate::GetCfg('chn_key') ) { echo 'Settings'; } else { echo 'Setup';} 
                if ( self::GetCfg('err') ) { 
                    echo self::notice_err( 'Try page reload, else get new key : ERR : ' . self::cfg('err') ); 
                }
                ?>
            </span>
        </h2>
        <!-- 
        <h2>Uqrate Settings</h2> 
        -->
        <h5 class="build">
            v<?php echo esc_html( Uqrate::GetCfg('plugin_version') .' '. Uqrate::GetCfg('build') );?>
        </h5>
    </div>
    <div id="uqrate_setup" class="<?php if ( Uqrate::GetCfg('chn_key') ) echo 'hide'; ?>">
        <dl id="uqrate_setup_account">
            <dt>1</dt>
            <dd class="link">
                <a  class="button button-primary" 
                    target="_blank"
                    href="<?php echo esc_attr( Uqrate::GetCfg('login_pg_url') ); ?>">Login/Signup</a>
            </dd>
            <dd class="info">
                Signup or login at your <span class="uqrate">uqrate</span> account to enable revenue streams and increase your visibility through the comments, subscriptions, and broadcasting services provided by this plugin.
            </dd>
        </dl>
        <dl id="uqrate_setup_channel">
            <dt>2</dt>
            <dd class="link">
                <a  class="button button-primary" 
                    target="_blank"
                    href="<?php echo esc_url( Uqrate::GetCfg('chn_pg_url') ); ?>">Add Channel</a>
            </dd>
            <dd class="info">
                Your <span class="wpf">WordPress</span> blog is synchronized with the channel you create at your <span class="uqrate">uqrate</span> account by declaring your blog's URL as the channel's <b>Host</b>. Your posts, their comments threads, the subscribe button and such are made available to visitors at both sites.
            </dd>
        </dl>
        <dl id="uqrate_setup_key">
            <dt>3</dt>
            <dd class="link">
                <a 
                    class="button button-primary" 
                    target="_blank"
                    href="<?php echo esc_url( Uqrate::GetCfg('key_pg_url') ); ?>">Get API Key</a>
            </dd>
            <dd class="info">
                Your <b>secret</b> key authorizes access to <span class="uqrate">uqrate</span> services for this plugin. Copy the key you get from there, and paste it into the input box provided below at &ldquo;Secret&nbsp;API&nbsp;Key&rdquo;.
            </dd>
        </dl>
    </div>
    <!-- FORM -->
    <div id="uqrate_settings_block">
        <dl>
            <dd class="<?php if ( Uqrate::GetCfg('chn_key') ) echo 'hide'; ?>">
                <form action="options.php" method="POST">
                    <!-- START : add_settings_section(..) and add_settings_field(..) -->
                    <?php
                    settings_fields( Uqrate_Admin::OPTION_GROUP ); 
                    do_settings_sections( Uqrate_Admin::SETUP_PAGE );
                    ?>
                    <!-- END  : add_settings_section(..) and add_settings_field(..) -->
                    <?php
                    $buttonText = 'Save Settings';
                    submit_button( 
                        __( $buttonText, 'textdomain' ), 
                        'primary', 'submit', 'false',
                    );
                    ?>
                </form>
            </dd>
        </dl>
    </div>
    <div id="uqrate_status_block">
        <dl>
            <dd class="">
                <table class="<?php if ( ! Uqrate::GetCfg('chn_key') ) echo 'hide'; ?>">
                    <tr class="hide">
                        <td class="key">version:</td>
                        <td class="val">v<?php echo esc_html( Uqrate::GetCfg('plugin_version') .' '. Uqrate::GetCfg('build') ); ?></td>
                    </tr>
                    <tr class="">
                        <td class="key">key:</td>
                        <td class="val">
                        <?php 
                        if ( Uqrate::cfg('chn_key')['key'] ) { 
                            echo esc_html( substr( Uqrate::cfg('chn_key')['key'], 0, 30 ) ) . '&hellip;'; 
                        } else { 
                            echo '[NOT SAVED]';
                        }; 
                        ?></td>
                    </tr>
                    <tr class="<?php if ( ! Uqrate::cfg('chn_key')['error'] ) {echo 'hide';} else {echo 'warn';} ?>">
                        <td class="key">error:</td>
                        <td class="val"><?php echo esc_html( Uqrate::cfg('chn_key')['error'] );?></td>
                    </tr>
                    <tr class="hide">
                        <td class="key">key_name:</td>
                        <td class="val"><?php echo esc_html( Uqrate::cfg('chn_key')['key_name'] );?></td>
                    </tr>
                    <tr>
                        <td class="key">chn_id:</td>
                        <td class="val"><?php echo esc_html( Uqrate::cfg('chn_key')['chn_id'] );?></td>
                    </tr>
                    <tr class="">
                        <td class="key">chn_slug:</td>
                        <td class="val"><?php echo esc_html( Uqrate::cfg('chn_key')['chn_slug'] );?></td>
                    </tr>
                    <tr class="">
                        <td class="key">host_url:</td>
                        <td class="val"><?php echo esc_html( Uqrate::cfg('chn_key')['host_url'] );?></td>
                    </tr>
                    <tr>
                        <td class="key">date_create:</td>
                        <td class="val"><?php echo esc_html( Uqrate::cfg('chn_key')['date_create'] );?></td>
                    </tr>
                    <tr>
                        <td class="key">date_update:</td>
                        <td class="val"><?php echo esc_html( Uqrate::cfg('chn_key')['date_update'] );?></td>
                    </tr>
                    <tr>
                        <td class="key">rotations:</td>
                        <td class="val"><?php echo esc_html( Uqrate::cfg('chn_key')['rotations'] );?></td>
                    </tr>
                    <tr>
                        <td class="key">Age:</td>
                        <td class="val">
                            <?php 
                            $date = new DateTime( Uqrate::cfg('chn_key')['date_create'] );
                            $now = new DateTime();
                            $interval = $now->diff($date);
                            echo esc_html( ( $interval->y * 12 ) + $interval->m ) ;
                            ?> months
                            <span class="warn<?php if ( $interval->y < 1 ) echo ' hide';?>">
                            <a  href="<?php echo esc_url( Uqrate::GetCfg('key_pg_url') );?>" 
                                target="_blank" 
                                title="Anyone with your key can make changes to your Uqrate account, so don't keep the same key for too long.">Rotate your key</a>
                            </span>
                        </td>
                    </tr>
                </table>
            </dd>
        </dl>
        <dl id="uqrate_settings_update" class="<?php if ( ! Uqrate::GetCfg('chn_key') ) echo 'hide'; ?>">
            <dd class="link">
                <a  class="button button-primary" 
                    href="#">New Key</a>
            </dd>
            <dd class="info">
                <a target="_blank" href="<?php echo esc_url( Uqrate::GetCfg('key_pg_url') );?>">Request a new API key</a> from your <span class="uqrate">uqrate</span> account. Copy it from there, and paste it into the form selected by this &ldquo;New Key&rdquo; button.
            </dd>
            <dd class="info">
                The key is scoped to a <span class="uqrate">uqrate</span> channel, so be sure to select the same channel  (<code>chn_slug</code>) that you declared for this plugin, unless you want to change that too. You may update (<code>rotations</code>) your API key at any time. Doing so invalidates the old key.
            </dd>
        </dl>
    </div>
    <div class="uqrate_setup_footer">
        <div class="build">
            uqrate plugin v<?php 
                echo esc_html( Uqrate::GetCfg('plugin_version') 
                .' build '
                . Uqrate::GetCfg('build') ); 
            ?>
        </div>
        <div class="uqrate_logo">
            <!--
            <svg class="">
                <use href="#uqrate_logo"></use>
            </svg>
            -->
            <svg id="" viewBox="0 0 460 460" xmlns="http://www.w3.org/2000/svg">
                <path fill="#c6b305" d="M372.07,76.22a66.68,66.68,0,0,0-22.76,4.56q-14,5.85-14,16.25V275.52q0,35.1-14.31,89.73Q308.36,409.79,295.35,454h-8.13Q250.8,377.61,239.1,260.24q-61.11,38-92.33,67-58.84-31.86-58.84-64V97.68q0-13.65,41.29-32.51l55.26-22.11Q218,27.46,241.7,6q48.45,44.86,126.15,63.4A8.73,8.73,0,0,1,372.07,76.22ZM240.4,250.81V96.38Q206.92,84,183.18,68.1V230q0,9.75,9.43,22.11t18.86,12.35q3.25,0,14-5.85Q239.44,251.13,240.4,250.81Z"/>
            </svg>
        </div>
    </div>
</div>
<?php symbols(); ?>
<?php endif; ?>
<?php function symbols() {?>
    <svg id="uqrate_symbols" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <symbol id="uqrate_logo" viewBox="0 0 460 460">
                <path fill="" d="M372.07,76.22a66.68,66.68,0,0,0-22.76,4.56q-14,5.85-14,16.25V275.52q0,35.1-14.31,89.73Q308.36,409.79,295.35,454h-8.13Q250.8,377.61,239.1,260.24q-61.11,38-92.33,67-58.84-31.86-58.84-64V97.68q0-13.65,41.29-32.51l55.26-22.11Q218,27.46,241.7,6q48.45,44.86,126.15,63.4A8.73,8.73,0,0,1,372.07,76.22ZM240.4,250.81V96.38Q206.92,84,183.18,68.1V230q0,9.75,9.43,22.11t18.86,12.35q3.25,0,14-5.85Q239.44,251.13,240.4,250.81Z"/>
            </symbol>
        </defs>
    </svg>
<?php }?>
