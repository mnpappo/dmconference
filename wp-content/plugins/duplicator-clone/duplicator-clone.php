<?php
/*
Plugin Name: Duplicator / Cloner / Migrator
Plugin URI: http://www.wpadm.com/duplicator-clone
Description: Duplicator clone plugin to duplicate website, clone websites or migrate WordPress website from one hosting place to another
Version: 2.1.2
Author: www.wpadm.com
Author URI: http://www.wpadm.com/duplicator-clone/
*/

defined( 'ABSPATH' ) or die( 'Go away!' );
//if (is_admin() == true) {}
if (!class_exists('SiteDuplicator')) {
    define('PLUGIN_ACCESS_FLAG', 'S0FVdf98LdWgeZrf');

    class SiteDuplicator
    {
        const EMAIL_SUPPORT = 'support@wpadm.com';

        public static $plugin_name      = 'duplicator-clone';
        public static $plugin_version   = '2.0.1';
        public static $plugin_page_url  = '/wp-admin/admin.php?page=duplicator-clone-page';
        public static $optionKey        = 'site_duplicator_options';
        public static $defaultOptions   = array(
            'ftp_host'          => '',
            'ftp_username'      => '',
            'ftp_password'      => '',
            'ftp_path'          => '/',
            'ftp_port'          => 21,
            'ftp_mode'          => 3,

            'db_host'           => 'localhost',
            'db_port'           => '3306',
            'db_name'           => '',
            'db_username'       => '',
            'db_password'       => '',
            'db_table_prefix'   => 'dcwp_',

            'url'                   => 'http://',
            'duplicate_mode'        => array(1,2),    //array(1,2) - files and db
            'skip_file_size'        => 3,    //size in MB
            'skip_file_extension'   => 'avi;mov;mp4;mpeg;mpg;wmv;aac;m3u;mpa;wma;', //'avi;mov;mp4;mpeg;mpg;swf;wmv;aac;m3u;mp3;mpa;wav;wma;'
            'archive_size'          => 2,    //size in MB

            'max_execution_time'    => 300, //recommend value, 5 min in sec (some servers stop any script after 6 min)
            'memory_limit'          => 128, //recommend value, in mb

            'active_job'        => '',  // name of last and active job
            'active_job_status' => 0,   // 0 - none, 1 - process, 2 - pause, 3 - stop, 100 - success, 200 - error,
            'active_job_step'   => 0,   // 0 - not started,
                                        // 1 - scan started,                    2 - scan finished,
                                        // 3 - archives creation started,       4 - archives creation finished
                                        // 5 - db exporting started,            6 - db exporting finished
                                        // 7 - files/archives transfer started, 8 - files/archives transfer finished
                                        // 9 - update process,                  10 - update finished
                                        // 11 - job files removing started,     12 - job finished,

            'auth_email'        => '',  //remember email for login in admin panel
            'check_update'      => 0,   //0 - do not check update, 1 - check update
            'check_update_key'  => '',  //key for getting update
            'save_local_backup' => 0,   //1 - create local backup
        );
        public static $translation      = array(
            'en' => array(
                'plugin_page_title'     => 'Duplicator / Cloner / Migrator',
                'settings_title'        => 'Fill data of the destination host',
                'settings_title_desc'   => 'Files and MySQL tables of <b>THIS</b> site will be transferred to destination',
                'scan_process'          => 'Scan system',
                'run_process'           => 'Start cloning to destination',
                'stop_process'          => 'Stop process and clear logs',
                'check_process'         => 'Check destination FTP/MySQL connection',
                'ftp_destination'       => 'Destination FTP Server',
                'db_destination'        => 'Destination MySQL Server',
                'site_settings'         => 'Destination Site',
                'main_settings'         => 'Duplication settings',
                'logs_title'            => 'Logs',
                'scan_title'            => 'Result of scanning',
                'new_site_destination'  => 'Destination',

                'field_ftp_host'        => 'FTP Host',
                'field_ftp_username'    => 'FTP Username',
                'field_ftp_password'    => 'FTP Password',
                'field_ftp_path'        => 'Root folder path of site',
                'field_ftp_port'        => 'FTP Port',
                'field_ftp_mode'        => 'Mode',
                'field_ftp_mode_active' => 'Active',
                'field_ftp_mode_passive'=> 'Passive',
                'field_ftp_mode_auto'   => 'Auto',

                'field_db_host'         => 'MySQL Host',
                'field_db_port'         => 'MySQL Host Port',
                'field_db_name'         => 'MySQL Database to use',
                'field_db_username'     => 'MySQL Username',
                'field_db_password'     => 'MySQL Password',
                'field_db_table_prefix' => 'MySQL Table Prefix',

                'field_new_url'                 => 'New URL',
                'field_duplicate_mode'          => 'Duplicate',
                'field_duplicate_mode_files'    => 'Files',
                'field_duplicate_mode_database' => 'Database',
                'field_skip_file_size'          => 'Skip file larger than',
                'field_skip_file_extension'     => 'Skip file extensions',
                'field_skip_file_extension_desc'=> 'Use semicolons to separate all items.',
                'field_save_local_backup'       => 'Save local backup',

                'installer_not_found'   => 'Could not find file for testing MySQL Connection',

                'queue_count'           => 'Count files for cloning',
                'queue_size'            => 'Total size of files for cloning',
                'skipped_count'         => 'Excluded files from cloning',
                'skipped_size'          => 'Total size of excluded files',
                'skipped_count_desc'    => 'The files corresponding to conditions of the user like "file size" and "file type", and not readable files, restricted with file permissions.',
                'mysql_tables_count'    => 'Count MySQL tables for cloning',
                'mysql_version'         => 'MySQL version',
                'mysql'                 => 'MySQL connection',
                'zip'                   => 'Zip library',
                'memory_limit'          => 'PHP memory limit',
                'memory_limit_up'       => 'Capability to increase memory limit',
                'max_execution_time'    => 'PHP max execution time, sec',
                'max_execution_time_up' => 'Capability to increase execution time',
                'server_free_space'     => 'Free space of server',
                'scan_process_desc_message'  => 'Please be patient it can take some minutes',

                'registration_title'        => 'Free Sign Up',
                'registration_description'  => 'to duplicate, clone, migrate or backup more than one website...',
                'registration_but_register' => 'Register/Login',
                'buy_support_title'         => 'Use Professional version of "Duplicator / Cloner / Migrator" plugin and get',
                'buy_support_description'   => '
                    <ul>
                        <li>Automated Local backup;</li>
                        <li>Status E-Mail Reporting;</li>
                        <li>Online Service "Backup Website Manager" (Backup, Copy, Clone or Migrate of websites);</li>
                        <li>One Year Free Updates for PRO version;</li>
                        <li>One Year Priority support.</li>
                    </ul>
                ',
                'buy_support_but'           => 'Get PRO',

                'auth_title'                => 'Register/Login',
                'auth_existing_user'        => 'Existing user',
                'auth_new_user'             => 'New user',
                'auth_email'                => 'Login (E-mail)',
                'auth_password1'            => 'Password',
                'auth_password2'            => 'Confirm Password',
                'auth_but_login'            => 'Login',
                'auth_but_register'         => 'Register',

                //server answer
                'answer_server_not_answer'          => 'Could not connect to server',
                'answer_incorrect_url'              => 'Incorrect url of domain',
                'answer_incorrect_data'             => 'Form was filed incorrectly',
                'answer_incorrect_login_or_password'=> 'Incorrect username or password',
                'answer_user_not_found'             => 'Username not found',
                'answer_this_username_busy'         => 'Username is busy or incorrect',
                'answer_login_ok'                   => 'Login successful',

                'pay_cancel'    => 'Checkout was canceled',
                'pay_success'   => 'Checkout was success',

                'deactivate_free_plugin' => 'Please deactivate free version of Duplicator Clone plugin',

                'update_link_text'      => 'New version of Duplicator Clone Pro is available for downloading',
                'update_link_download'  => 'Download',
                
                'close'   => 'Close',
            )
        );
        public static $logEvents = array(
            0 => array('status' => 1, 'msg' => 'request success'),
            1 => array('status' => 0, 'msg' => 'could nor create job file'),
        );

        public static function admin_init()
        {
            if (get_option(self::$optionKey . '_redirect', false)) {
                delete_option(self::$optionKey . '_redirect');
                wp_redirect(site_url() . self::$plugin_page_url);
                exit;
            }
	        
	        if (!get_option(self::$optionKey . '_private_key')) {
		        self::generateKeys();
	        }
        }

	    protected static function generateKeys() {
		    $d = DIRECTORY_SEPARATOR;
		    require_once dirname(__FILE__) . $d . 'libs' . $d . 'phpseclib' . $d . 'Crypt' . $d . 'RSA.php';
		    require_once dirname(__FILE__) . $d . 'libs' . $d . 'phpseclib' . $d . 'Math' . $d . 'BigInteger.php';

		    $rsa = new Crypt_RSA();
		    $keys = $rsa->createKey();

		    update_option(self::$optionKey . '_public_key', $keys['publickey']);
		    update_option(self::$optionKey . '_private_key', $keys['privatekey']);

	    }
	    
        public static function admin_menu()
        {
            if (is_admin()) {
                //settings menu for admin
                add_menu_page('DuplicatorClone', 'DuplicatorClone', 'manage_options', 'duplicator-clone-page', array('SiteDuplicator', 'plugin_page'),plugins_url(self::$plugin_name . '/assets/icon18.png'));
            }
        }

        public static function plugin_page()
        {
            if (!is_admin()) {exit;}
            $pageClass = '';

            $personalKey    = (string)get_option(SiteDuplicatorApi::$optionKey, '');
            $auth_type      = 0;
            $payFlag        = @$_GET['pay'];
            if($payFlag == 'success'){
                $options['check_update'] = 1;
                SiteDuplicatorLibUtility::plugin_options('add',$options);
            }

            //assets
            $pluginPath = plugins_url(self::$plugin_name . '/assets');

            wp_enqueue_script( 'jquery' );
            wp_enqueue_style( 'sd_admin_css', $pluginPath.'/admin.css' );
            wp_enqueue_script( 'sd_admin_js', $pluginPath.'/admin.js' );
            wp_enqueue_style('arctic_css', $pluginPath . '/jquery.arcticmodal.css');
            wp_enqueue_script('arctic_js', $pluginPath . '/jquery.arcticmodal.min.js');

            //plugin options
            $options = SiteDuplicatorLibUtility::plugin_options( 'get' );
            $options = self::validate_options($options);

            //current job status
            $jobStatus      = SiteDuplicatorLibJob::getJobStatus();
            $jobStep        = SiteDuplicatorLibJob::getJobStep();
            $jobSettings    = '';
            $jobLogs        = '';
            if($jobStatus != 0){
                $jobSettings    = SiteDuplicatorLibJob::readJobSettings();
                $jobLogs        = SiteDuplicatorLibJob::readJobLog();
            }

            //check folder for jobs
            $jobDirCheck = SiteDuplicatorLibFilesystem::checkDir(PLUGIN_DIR_JOBS);

            ?>
            <script>
                var sd_parameters = {
                    'url': '<?php echo admin_url();?>',
                    'scan_process_message': '<?php echo SiteDuplicatorLibUtility::trans('scan_process_desc_message');?>'
                };
            </script>

            <?php add_thickbox(); ?>

            <div id="sd_support_container" style="display:none;">
                <div id="sd_support_text_container">
                    <h2>Suggestion</h2>
                    <textarea style="width: 100%; height: 300px" id="sd_support_text"></textarea>
                    <br>
                    <br>
                    <label style="margin: 10px;"><input type="checkbox" id="sd_attach_logs"> Attach logs</label>
                </div>

                <div id="sd_support_thank_container" style="display: none;">
                    <h2>Thanks for your suggestion!</h2>
                    Within next plugin updates we will try to satisfy your request.
                </div>

                <div id="sd_support_error_container" style="display: none;">
                    <br><b>At your website the mail functionality is not available.</b><br /><br />
                    Your request was not sent.
                </div>


                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="button" onclick="jQuery('.tb-close-icon').click()">close</button>
                    <button type="button" class="button-primary" id="sd-support_send_button" onclick="sd_sendSupportText()">Send suggestion</button>

                </div>
            </div>

            <div class="wrap sd_job_status_<?php echo $jobStatus;?> sd_job_step_<?php echo $jobStep;?>" id="site_duplicator_page_over">
                <div style="float: left">
                    <h2><?php echo SiteDuplicatorLibUtility::trans('plugin_page_title'); ?> <small style="font-size: 13px;">ver. <?php echo self::$plugin_version; ?></small></h2>
                </div>
                <div style="text-align: right; padding-top: 16px; font-size: 14px;">
                    If you have any suggestions or wishes  <a class="button-primary thickbox" href="#TB_inline?width=600&height=500&inlineId=sd_support_container" style="margin-right: 20px; font-size: 14px; font-weight: bold;" onclick="sd_supportFormNormalize()">Contact us</a>
                </div>
                <div style="clear: left;"></div>


                <?php
                if( @(int)$jobDirCheck['writable'] == 0 or @(int)$jobDirCheck['readable'] == 0 ){
                    $jobDirError = SiteDuplicatorLibUtility::logEvent(1,PLUGIN_DIR_JOBS);
                    echo '<div class="sd_global_msg status_0">'.$jobDirError['msg'].'</div>';

                    $pageClass = 'global_error';
                }

                //check pro plugin
                if( file_exists(ABSPATH.'wp-content/plugins/duplicator-clone-pro/duplicator-clone-pro.php') and is_plugin_active('duplicator-clone-pro/duplicator-clone-pro.php') ){
                    $pageClass = 'global_error';
                    echo '<div class="sd_global_msg status_0">'.SiteDuplicatorLibUtility::trans('deactivate_free_plugin').'</div>';
                }
                ?>

                <?php
                //payment message
                if( !empty($payFlag) ){
                    if($payFlag == 'cancel'){
                        echo '<div class="sd_global_msg status_0">'.SiteDuplicatorLibUtility::trans('pay_cancel').'</div>';
                    }
                    if($payFlag == 'success'){
                        echo '<div class="sd_global_msg status_1">'.SiteDuplicatorLibUtility::trans('pay_success').'</div>';
                    }
                }

                //check update
                if($options['check_update'] == 1){
                    $checkRes = SiteDuplicatorApi::checkUpdate();
                    if( !empty($checkRes['key']) ){
                        $options['check_update_key'] = $checkRes['key'];
                        SiteDuplicatorLibUtility::plugin_options('add',$options);
                    }

                    if( !empty($checkRes['url']) ){
                        echo '<div class="sd_global_msg status_2">'.SiteDuplicatorLibUtility::trans('update_link_text').' <a targe="_blank" href="'.esc_html($checkRes['url']).'">'.SiteDuplicatorLibUtility::trans('update_link_download').'</a></div>';
                    }
                }
                ?>

               <!-- <div id="sd_main_page_top">
                    <div class="sd_tmpl_block">
                        <div class="sd_block_body">
                            <div class="sd_table_overlay" id="buy_overlay">
                                <table class="form-table sd_settings_table" style="width:100%;">
                                    <tr class="sd_tr_title2">
                                        <th colspan="3"><?php echo SiteDuplicatorLibUtility::trans('buy_support_title');?>:</th>
                                    </tr>
                                    <tr>
                                        <td><?php echo SiteDuplicatorLibUtility::trans('buy_support_description');?></td>
                                        <td style="width: 150px;text-align: center";>
                                            <img style="height: 140px" alt="" src="<?php echo $pluginPath . '/duplicator-clone-pro.png';?>" />
                                        </td>
                                        <td style="width:200px;text-align: center">
                                            <?php
                                            $siteUrl    = site_url();
                                            $siteAdmin  = get_option('admin_email');
                                            $successUrl = $siteUrl.self::$plugin_page_url.'&pay=success';
                                            $cancelUrl  = $siteUrl.self::$plugin_page_url.'&pay=cancel';
                                            ?>
                                            <form method="post" action="<?php echo SiteDuplicatorApi::$adminRequestSite2.SiteDuplicatorApi::$buyRequestUrl;?>">
                                                <input type="hidden" name="site" value="<?php echo esc_html($siteUrl);?>" />
                                                <input type="hidden" name="actApi" value="proBackupPay" />
                                                <input type="hidden" name="email" value="<?php echo esc_html($siteAdmin);?>" />
                                                <input type="hidden" name="plugin" value="<?php echo esc_html(self::$plugin_name);?>" />
                                                <input type="hidden" name="success_url" value="<?php echo $successUrl;?>" />
                                                <input type="hidden" name="cancel_url" value="<?php echo $cancelUrl;?>" />
                                                <input onclick="buyLicense(this);" class="button button-primary" id="buy_but" type="submit" value="<?php echo SiteDuplicatorLibUtility::trans('buy_support_but');?>" />
                                            </form>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>   -->

<!--                <div id="sd_main_page_top">-->
<!--                    <div class="sd_tmpl_block">-->
<!--                        <div class="sd_block_body">-->
<!--                            <div class="sd_table_overlay" id="auth_form">-->
<!--                                <form method="post" action="--><?php //echo admin_url( 'admin-post.php?action=duplicator-clone-auth-page' )?><!--">-->
<!--                                <table class="form-table sd_settings_table">-->
<!--                                    <tr class="sd_tr_title3">-->
<!--                                        <th colspan="2">--><?php //echo SiteDuplicatorLibUtility::trans('registration_title');?><!--:</th>-->
<!--                                    </tr>-->
<!--                                    <tr>-->
<!--                                        <td colspan="2">-->
<!--                                            --><?php //echo SiteDuplicatorLibUtility::trans('registration_description');?>
<!--                                        </td>-->
<!--                                    </tr>-->
<!--                                    <tr class="row_title">-->
<!--                                        <td colspan="2">-->
<!--                                            <ul>-->
<!--                                                <li>-->
<!--                                                    <input class="auth_type_field" onchange="popupAuthType(this,1);" --><?php //echo ((!empty($personalKey) or $auth_type == 1) ? 'checked="checked"' : '');?><!-- type="radio" value="1" name="--><?php //echo self::$optionKey;?><!--[auth_type]" />-->
<!--                                                    <label>--><?php //echo SiteDuplicatorLibUtility::trans('auth_existing_user');?><!--</label>-->
<!--                                                </li>-->
<!--                                                <li>-->
<!--                                                    <input class="auth_type_field" onchange="popupAuthType(this,2);"  --><?php //echo ((empty($personalKey) or $auth_type == 2) ? 'checked="checked"' : '');?><!-- type="radio" value="2" name="--><?php //echo self::$optionKey;?><!--[auth_type]" />-->
<!--                                                    <label>--><?php //echo SiteDuplicatorLibUtility::trans('auth_new_user');?><!--</label>-->
<!--                                                </li>-->
<!--                                            </ul>-->
<!--                                        </td>-->
<!--                                    </tr>-->
<!--                                    <tr>-->
<!--                                        <th class="sd_line_title"><span class="label">--><?php //echo SiteDuplicatorLibUtility::trans('auth_email');?><!--:</span></th>-->
<!--                                        <td class="sd_line_value"><input class="auth_email_field" style="float:left;" --><?php //echo (!empty($options['auth_email']) ? 'readonly="readonly"' : '');?><!-- type="text" name="--><?php //echo self::$optionKey;?><!--[auth_email]" value="--><?php //echo @esc_attr($options['auth_email']);?><!--" /></td>-->
<!--                                    </tr>-->
<!--                                    <tr>-->
<!--                                        <th class="sd_line_title"><span class="label">--><?php //echo SiteDuplicatorLibUtility::trans('auth_password1');?><!--:</span></th>-->
<!--                                        <td class="sd_line_value"><input class="auth_password1_field" style="float:left;" type="password" name="--><?php //echo self::$optionKey;?><!--[auth_password1]" value="--><?php //echo esc_attr('');?><!--" /></td>-->
<!--                                    </tr>-->
<!--                                    <tr id="tr_confirm_password" style="--><?php //echo ((!empty($personalKey) or $auth_type == 1) ? 'display:none;' : '');?><!--">-->
<!--                                        <th class="sd_line_title"><span class="label">--><?php //echo SiteDuplicatorLibUtility::trans('auth_password2');?><!--:</span></th>-->
<!--                                        <td class="sd_line_value"><input class="auth_password2_field" style="float:left;" type="password" name="--><?php //echo self::$optionKey;?><!--[auth_password2]" value="--><?php //echo esc_attr('');?><!--" /></td>-->
<!--                                    </tr>-->
<!--                                    <tr>-->
<!--                                        <td colspan="2" style="text-align:center;">-->
<!--                                            <input onclick="processAuth(this,2);" style="--><?php //echo ((empty($personalKey) or $auth_type == 2) ? 'display:none;' : '');?><!--" class="button button-primary" id="auth_but_login" type="button" value="--><?php //echo SiteDuplicatorLibUtility::trans('auth_but_login');?><!--" />-->
<!--                                            <input onclick="processAuth(this,1);" style="--><?php //echo ((!empty($personalKey) or $auth_type == 1) ? 'display:none;' : '');?><!--" class="button button-primary" id="auth_but_register" type="button" value="--><?php //echo SiteDuplicatorLibUtility::trans('auth_but_register');?><!--" />-->
<!--                                        </td>-->
<!--                                    </tr>-->
<!--                                </table>-->
<!--                                    <input type="hidden" name="--><?php //echo self::$optionKey;?><!--[auth_process]" value="1" />-->
<!--                                </form>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->

                <div id="sd_main_page" class="<?php echo $pageClass;?>">
                    <div class="sd_tmpl_block">
                            <h2 class="sd_block_title">
                                <?php echo SiteDuplicatorLibUtility::trans('settings_title');?>
                                <span class="preloader"><img alt="loading" src="<?php echo $pluginPath.'/preloader.gif';?>" /></span>
                            </h2>
                        <div class="sd_block_title_desc"><?php echo SiteDuplicatorLibUtility::trans('settings_title_desc');?> <b>[ <?php echo site_url();?> => <?php echo SiteDuplicatorLibUtility::trans('new_site_destination');?> ]</b></div>
                        <div class="sd_block_body">
                            <form method="post" action="<?php echo admin_url( 'admin-post.php?action=sd_job_request' )?>">
                                <?php self::generateSecureCode(); ?>
                                <div class="sd_table_overlay">
                                    <div class="sd_table_overlay_shield">&nbsp;</div>
                                    <table class="form-table sd_settings_table">
                                        <tr class="sd_tr_title">
                                            <th colspan="2"><?php echo SiteDuplicatorLibUtility::trans('site_settings');?>:</th>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_new_url');?>:</th>
                                            <td class="sd_line_value"><input id="field_new_url" type="text" name="<?php echo self::$optionKey;?>[url]" value="<?php echo esc_attr( @$options['url'] ); ?>" /></td>
                                        </tr>
                                        <tr class="sd_tr_title">
                                            <th colspan="2"><?php echo SiteDuplicatorLibUtility::trans('main_settings');?>:</th>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_duplicate_mode');?>:</th>
                                            <td class="sd_line_value">
                                                <span class="sd_radio_item">
                                                    <input id="field_duplicate_mode_1" <?php echo (in_array(1, @(array)$options['duplicate_mode']) ? 'checked="checked"' : '');?> type="checkbox" name="<?php echo self::$optionKey;?>[duplicate_mode][]" value="1" />
                                                    <span class="sd_radio_item_title"><?php echo SiteDuplicatorLibUtility::trans('field_duplicate_mode_files');?></span>
                                                </span>
                                                <span class="sd_radio_item">
                                                    <input onchange="checkDuplicationMode();" id="field_duplicate_mode_2" <?php echo (in_array(2, @(array)$options['duplicate_mode']) ? 'checked="checked"' : '');?> type="checkbox" name="<?php echo self::$optionKey;?>[duplicate_mode][]" value="2" />
                                                    <span class="sd_radio_item_title"><?php echo SiteDuplicatorLibUtility::trans('field_duplicate_mode_database');?></span>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_skip_file_size');?>:</th>
                                            <td class="sd_line_value"><input style="width: 70px;" type="text" name="<?php echo self::$optionKey;?>[skip_file_size]" value="<?php echo esc_attr( @$options['skip_file_size'] ); ?>" />MB</td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_skip_file_extension');?>  <span class="desc tooltip" title="<?php echo SiteDuplicatorLibUtility::trans('field_skip_file_extension_desc');?>">(?)</span>:</th>
                                            <td class="sd_line_value">
                                                <textarea name="<?php echo self::$optionKey;?>[skip_file_extension]"><?php echo esc_attr( @$options['skip_file_extension'] ); ?></textarea>
                                            </td>
                                        </tr>
                                        <tr style="display:none;">
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_save_local_backup');?>:</th>
                                            <td class="sd_line_value">
                                                <input id="field_save_local_backup" type="checkbox" <?php echo (@$options['save_local_backup'] == 1 ? 'checked="checked"' : '');?> name="<?php echo self::$optionKey;?>[save_local_backup]" value="1" />
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="sd_table_overlay" id="sd_ftp_block">
                                    <div class="sd_table_overlay_shield">&nbsp;</div>
                                    <table class="form-table sd_settings_table">
                                        <tr class="sd_tr_title">
                                            <th colspan="2"><?php echo SiteDuplicatorLibUtility::trans('ftp_destination');?>:</th>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_host');?>:</th>
                                            <td class="sd_line_value"><input id="field_ftp_host" type="text" name="<?php echo self::$optionKey;?>[ftp_host]" value="<?php echo esc_attr( @$options['ftp_host'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_username');?>:</th>
                                            <td class="sd_line_value"><input id="field_ftp_username" type="text" name="<?php echo self::$optionKey;?>[ftp_username]" value="<?php echo esc_attr( @$options['ftp_username'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_password');?>:</th>
                                            <td class="sd_line_value"><input id="field_ftp_password" type="password" name="<?php echo self::$optionKey;?>[ftp_password]" value="<?php echo esc_attr( @$options['ftp_password'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_path');?>:</th>
                                            <td class="sd_line_value"><input id="field_ftp_path" type="text" name="<?php echo self::$optionKey;?>[ftp_path]" value="<?php echo esc_attr( @$options['ftp_path'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_port');?>:</th>
                                            <td class="sd_line_value"><input id="field_ftp_port" type="text" name="<?php echo self::$optionKey;?>[ftp_port]" value="<?php echo esc_attr( @$options['ftp_port'] ); ?>" /></td>
                                        </tr>
                                        <tr style="display: none;">
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_mode');?>:</th>
                                            <td class="sd_line_value">
                                                <span class="sd_radio_item">
                                                    <input <?php echo (@(int)$options['ftp_mode'] == 3 ? 'checked="checked"' : '');?> type="radio" name="<?php echo self::$optionKey;?>[ftp_mode]" value="3" />
                                                    <span class="sd_radio_item_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_mode_auto');?></span>
                                                </span>
                                                <span class="sd_radio_item">
                                                    <input <?php echo (@(int)$options['ftp_mode'] == 1 ? 'checked="checked"' : '');?> type="radio" name="<?php echo self::$optionKey;?>[ftp_mode]" value="1" />
                                                    <span class="sd_radio_item_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_mode_active');?></span>
                                                </span>
                                                <span class="sd_radio_item">
                                                    <input <?php echo (@(int)$options['ftp_mode'] == 2 ? 'checked="checked"' : '');?> type="radio" name="<?php echo self::$optionKey;?>[ftp_mode]" value="2" />
                                                    <span class="sd_radio_item_title"><?php echo SiteDuplicatorLibUtility::trans('field_ftp_mode_passive');?></span>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="sd_table_overlay" id="sd_mysql_block" style="<?php //echo (!in_array(2,@(array)$options['duplicate_mode']) ? 'display:none;' : '' );?>">
                                    <div class="sd_table_overlay_shield">&nbsp;</div>
                                    <table class="form-table sd_settings_table">
                                        <tr class="sd_tr_title">
                                            <th colspan="2"><?php echo SiteDuplicatorLibUtility::trans('db_destination');?>:</th>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_db_host');?>:</th>
                                            <td class="sd_line_value"><input id="field_db_host" type="text" name="<?php echo self::$optionKey;?>[db_host]" value="<?php echo esc_attr( @$options['db_host'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_db_port');?>:</th>
                                            <td class="sd_line_value"><input id="field_db_port" type="text" name="<?php echo self::$optionKey;?>[db_port]" value="<?php echo esc_attr( @$options['db_port'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_db_name');?>:</th>
                                            <td class="sd_line_value"><input id="field_db_name" type="text" name="<?php echo self::$optionKey;?>[db_name]" value="<?php echo esc_attr( @$options['db_name'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_db_username');?>:</th>
                                            <td class="sd_line_value"><input id="field_db_username" type="text" name="<?php echo self::$optionKey;?>[db_username]" value="<?php echo esc_attr( @$options['db_username'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_db_password');?>:</th>
                                            <td class="sd_line_value"><input id="field_db_password" type="password" name="<?php echo self::$optionKey;?>[db_password]" value="<?php echo esc_attr( @$options['db_password'] ); ?>" /></td>
                                        </tr>
                                        <tr>
                                            <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('field_db_table_prefix');?>:</th>
                                            <td class="sd_line_value"><input id="field_db_table_prefix" type="text" name="<?php echo self::$optionKey;?>[db_table_prefix]" value="<?php echo esc_attr( @$options['db_table_prefix'] ); ?>" /></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="sd_buttons_line">
                                    <input onclick="sdCheckProcess(this, '<?php echo admin_url( 'admin-post.php?action=sd_job_request' )?>')" class="button" id="check_but" type="button" value="<?php echo SiteDuplicatorLibUtility::trans('check_process');?>" />
                                    <input onclick="sdStartProcess(this, '<?php echo admin_url( 'admin-post.php?action=sd_job_request' )?>')" class="button-primary" id="scan_but" type="button" value="<?php echo SiteDuplicatorLibUtility::trans('scan_process');?>" />
                                    <input onclick="sdStopProcess(this, '<?php echo admin_url( 'admin-post.php?action=sd_job_request' )?>')" class="button-primary" id="stop_but" type="button" value="<?php echo SiteDuplicatorLibUtility::trans('stop_process');?>" />

                                    <input id="sd_sub_action" type="hidden" name="sub_action" value="start" />

                                    <div id="sd_settings_message" class="sd_messages_line">&nbsp;</div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="sd_tmpl_block" id="sd_scan_block">
                        <h2 class="sd_block_title">
                            <?php echo SiteDuplicatorLibUtility::trans('scan_title');?>
                            <span class="preloader"><img alt="loading" src="<?php echo $pluginPath.'/preloader.gif';?>" /></span>
                        </h2>
                        <div class="sd_block_body">
                            <div class="sd_table_overlay">
                                <table class="form-table sd_settings_table">
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('max_execution_time');?>:</th>
                                        <td id="max_execution_time" class="sd_line_value"><?php echo (is_array($jobSettings) && isset($jobSettings['server']['server_stat']['max_execution_time']) ? $jobSettings['server']['server_stat']['max_execution_time'] : '');?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('max_execution_time_up');?>:</th>
                                        <td id="max_execution_time_up" class="sd_line_value"><?php echo (is_array($jobSettings) && isset($jobSettings['server']['server_stat']['max_execution_time_up']) && @(int)$jobSettings['server']['server_stat']['max_execution_time_up'] == 1 ? 'YES' : 'NO');?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('memory_limit');?>:</th>
                                        <td id="memory_limit" class="sd_line_value"><?php echo (is_array($jobSettings) && isset($jobSettings['server']['server_stat']['memory_limit']) ? $jobSettings['server']['server_stat']['memory_limit'] : '');?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('memory_limit_up');?>:</th>
                                        <td id="memory_limit_up" class="sd_line_value"><?php echo (is_array($jobSettings) && isset($jobSettings['server']['server_stat']['memory_limit_up']) && @(int)$jobSettings['server']['server_stat']['memory_limit_up'] == 1 ? 'YES' : 'NO');?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('zip');?>:</th>
                                        <td id="zip" class="sd_line_value"><?php echo (is_array($jobSettings) && @(int)@$jobSettings['server']['server_stat']['zip'] == 1 ? 'OK' : 'ERROR');?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('server_free_space');?>:</th>
                                        <td id="server_free_space" class="sd_line_value"><?php echo is_array($jobSettings) && isset($jobSettings['server']['server_stat']['server_free_space']) ? $jobSettings['server']['server_stat']['server_free_space'] : '';?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('mysql');?>:</th>
                                        <td id="mysql" class="sd_line_value"><?php echo (is_array($jobSettings) && isset($jobSettings['server']['server_stat']['mysql'] ) && @(int)$jobSettings['server']['server_stat']['mysql'] == 1 ? 'OK' : 'ERROR');?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('mysql_version');?>:</th>
                                        <td id="mysql_version" class="sd_line_value"><?php echo is_array($jobSettings) && isset($jobSettings['server']['server_stat']['mysql_version']) ? esc_html($jobSettings['server']['server_stat']['mysql_version']) : '';?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('queue_count');?>:</th>
                                        <td id="queue_count" class="sd_line_value"><?php echo is_array($jobSettings) && isset($jobSettings['server']['files_stat']['queue_count']) ? @(int)$jobSettings['server']['files_stat']['queue_count'] : '';?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('queue_size');?>:</th>
                                        <td id="queue_size" class="sd_line_value"><?php echo @esc_html(SiteDuplicatorLibUtility::byteSize(is_array($jobSettings) ? @$jobSettings['server']['files_stat']['queue_size'] : 0));?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('skipped_count');?> <span title="<?php echo SiteDuplicatorLibUtility::trans('skipped_count_desc', '', true);?>">(?)</span>:</th>
                                        <td id="skipped_count" class="sd_line_value"><?php echo is_array($jobSettings) ? @(int)$jobSettings['server']['files_stat']['skipped_count'] : 0;?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('skipped_size');?>:</th>
                                        <td id="skipped_size" class="sd_line_value"><?php echo @esc_html(SiteDuplicatorLibUtility::byteSize(is_array($jobSettings) ? @$jobSettings['server']['files_stat']['skipped_size'] : 0));?></td>
                                    </tr>
                                    <tr>
                                        <th class="sd_line_title"><?php echo SiteDuplicatorLibUtility::trans('mysql_tables_count');?>:</th>
                                        <td id="mysql_tables_count" class="sd_line_value"><?php echo is_array($jobSettings) ? @(int)$jobSettings['server']['db_stat']['count_tables'] : 0;?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="sd_buttons_line">
                                <input onclick="sdJobProcess(this, '<?php echo admin_url( 'admin-post.php?action=sd_job_request&sub_action=job' )?>')" class="button button-primary" id="run_but" type="button" value="<?php echo SiteDuplicatorLibUtility::trans('run_process');?>" />
                            </div>
                        </div>
                    </div>

                    <div class="sd_tmpl_block" id="sd_logs_block">
                        <h2 class="sd_block_title"><?php echo SiteDuplicatorLibUtility::trans('logs_title');?></h2>
                        <div class="sd_block_body">
                            <div class="sd_block_body_over">
                                <?php
                                if(!empty($jobLogs) ){
                                    foreach($jobLogs as $k_item => $v_item){
                                        if(is_array($v_item)){
                                            $lineKey = (string)md5(print_r($v_item,1));
                                            echo '<div class="log_id_'.$lineKey.' log_line log_status_'.@(int)$v_item['status'].'"><span class="log_time">'.esc_html($v_item['time']).'</span><span class="log_msg">'.esc_html($v_item['msg']).'</span></div>';
                                        }else{
                                            $lineKey = (string)md5($v_item);
                                            echo '<div class="log_id_'.$lineKey.' log_line">'.esc_html($v_item).'</div>';
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php
        }

        public static function auth_page()
        {
            if (!is_admin()) {exit;}

            $authEmail      = @trim($_POST[self::$optionKey]['auth_email']);
            $authPassword1  = @trim($_POST[self::$optionKey]['auth_password1']);
            $authPassword2  = @trim($_POST[self::$optionKey]['auth_password2']);
            $auth_type      = @(int)$_POST[self::$optionKey]['auth_type'];
            $processRes     = array();

            if($auth_type){
                if($auth_type == 2){
                    //register
                    if( !empty($authEmail) and !empty($authPassword1) and !empty($authPassword2) and $authPassword1 == $authPassword2 ){
                        $processRes = SiteDuplicatorApi::send_auth('register', array('username' => $authEmail, 'password' => $authPassword1));
                    }else{
                        $processRes = array('status' => 0, 'msg' => SiteDuplicatorLibUtility::trans('answer_incorrect_login_or_password'));
                    }
                }else{
                    //login
                    if( !empty($authEmail) and !empty($authPassword1) ){
                        $processRes = SiteDuplicatorApi::send_auth('login', array('username' => $authEmail, 'password' => $authPassword1));
                    }else{
                        $processRes = array('status' => 0, 'msg' => SiteDuplicatorLibUtility::trans('answer_incorrect_login_or_password'));
                    }
                }
                print json_encode($processRes);exit;
            }
        }

        public static function validate_options($options){
            $currentOptions = SiteDuplicatorLibUtility::plugin_options( 'get' );

            $options = array(
                'ftp_host'          => @trim($options['ftp_host']),
                'ftp_username'      => @trim($options['ftp_username']),
                'ftp_password'      => @trim($options['ftp_password']),
                'ftp_path'          => @trim($options['ftp_path']),
                'ftp_port'          => @(int)$options['ftp_port'],
                'ftp_mode'          => @(int)$options['ftp_mode'],

                'db_host'           => @trim($options['db_host']),
                'db_port'           => @(int)$options['db_port'],
                'db_name'           => @trim($options['db_name']),
                'db_username'       => @trim($options['db_username']),
                'db_password'       => @trim($options['db_password']),
                'db_table_prefix'   => @trim($options['db_table_prefix']),

                'url'                   => @trim($options['url']),
                'duplicate_mode'        => @(array)$options['duplicate_mode'],
                'skip_file_size'        => @str_replace(',','.',trim($options['skip_file_size'])),
                'skip_file_extension'   => @trim($options['skip_file_extension']),
                'archive_size'          => @str_replace(',','.',trim($options['archive_size'])),
                'save_local_backup'     => @(int)$options['save_local_backup'],
            );

            //normalize
            $options['db_table_prefix']     = @preg_replace('/[^a-zA-Z0-9_]/', '', $options['db_table_prefix']);
            $options['skip_file_extension'] = @implode(';',array_map('trim',explode(';',$options['skip_file_extension'])));

            //default values
            $options['ftp_path']            = (empty($options['ftp_path'])              ? self::$defaultOptions['ftp_path']             : $options['ftp_path']);
            $options['ftp_port']            = (empty($options['ftp_port'])              ? self::$defaultOptions['ftp_port']             : $options['ftp_port']);
            $options['ftp_mode']            = (empty($options['ftp_mode'])              ? self::$defaultOptions['ftp_mode']             : $options['ftp_mode']);
            $options['db_host']             = (empty($options['db_host'])               ? self::$defaultOptions['db_host']              : $options['db_host']);
            $options['db_port']             = (empty($options['db_port'])               ? self::$defaultOptions['db_port']              : $options['db_port']);
            $options['duplicate_mode']      = (empty($options['duplicate_mode'])        ? self::$defaultOptions['duplicate_mode']       : $options['duplicate_mode']);
            $options['skip_file_size']      = (empty($options['skip_file_size'])        ? self::$defaultOptions['skip_file_size']       : $options['skip_file_size']);
            $options['skip_file_extension'] = (is_null($options['skip_file_extension']) ? self::$defaultOptions['skip_file_extension']  : $options['skip_file_extension']);
            $options['archive_size']        = (empty($options['archive_size'])          ? self::$defaultOptions['archive_size']         : $options['archive_size']);

            //constant values
            $options['max_execution_time']  = (empty($currentOptions['max_execution_time']) ? self::$defaultOptions['max_execution_time']   : $currentOptions['max_execution_time']);
            $options['memory_limit']        = (empty($currentOptions['memory_limit'])       ? self::$defaultOptions['memory_limit']         : $currentOptions['memory_limit']);
            $options['active_job']          = (empty($currentOptions['active_job'])         ? self::$defaultOptions['active_job']           : $currentOptions['active_job']);
            $options['active_job_status']   = (empty($currentOptions['active_job_status'])  ? self::$defaultOptions['active_job_status']    : $currentOptions['active_job_status']);
            $options['active_job_step']     = (empty($currentOptions['active_job_step'])    ? self::$defaultOptions['active_job_step']      : $currentOptions['active_job_step']);
            $options['auth_email']          = (empty($currentOptions['auth_email'])         ? self::$defaultOptions['auth_email']           : $currentOptions['auth_email']);
            $options['check_update']        = (empty($currentOptions['check_update'])       ? self::$defaultOptions['check_update']         : $currentOptions['check_update']);
            $options['check_update_key']    = (empty($currentOptions['check_update_key'])   ? self::$defaultOptions['check_update_key']     : $currentOptions['check_update_key']);

            return $options;
        }


        public static function generateSecureCode() {
            $code = self::getRequestSecurityCode();
            echo '<input type=hidden id="sd_sign" name="sign" value="'.$code.'">';
        }

        public static function getRequestSecurityCode() {
            $data = serialize(array('site_url'=>site_url()));

            $d = DIRECTORY_SEPARATOR;
            require_once dirname(__FILE__) . $d . 'libs' . $d . 'phpseclib' . $d . 'Crypt' . $d . 'RSA.php';
            require_once dirname(__FILE__) . $d . 'libs' . $d . 'phpseclib' . $d . 'Math' . $d . 'BigInteger.php';

            $rsa = new Crypt_RSA();
            $rsa->loadKey(get_option(self::$optionKey . '_public_key'));
            $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
            $signature = @base64_encode($rsa->encrypt($data));

            return $signature;
        }

        protected static function requestValid($sign) {
            $data = serialize(array('site_url'=>site_url()));

            $sign = base64_decode($sign);

            $d = DIRECTORY_SEPARATOR;
            require_once dirname(__FILE__) . $d . 'libs' . $d . 'phpseclib' . $d . 'Crypt' . $d . 'RSA.php';
            require_once dirname(__FILE__) . $d . 'libs' . $d . 'phpseclib' . $d . 'Math' . $d . 'BigInteger.php';
            $rsa = new Crypt_RSA();
            $rsa->loadKey(get_option(self::$optionKey . '_private_key'));
            $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);

            return (@$rsa->decrypt($sign) == $data);
        }




        public static function sd_job_request(){

            $postData   = $_POST;
            $getData    = $_GET;
            $answer     = array();
            $action = @(!empty($postData['sub_action']) ? $postData['sub_action'] : $getData['sub_action']);

            file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'log.log',
                intval(is_user_logged_in()) . ' ' .
                $action . ' ' .
                @$_POST['sign'] . "\n",
                FILE_APPEND
            );

            if (!isset($_POST['sign']) || !self::requestValid($_POST['sign'])) {
                //todo: ошибка
                file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'log.log', 'error' . "\n", FILE_APPEND);
                echo 'error';
                return;
            }
            file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'log.log', 'success'. "\n", FILE_APPEND);

            //vars
            $action = @(!empty($postData['sub_action']) ? $postData['sub_action'] : $getData['sub_action']);
//            file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'log.log', $action . ' ' . (int)is_user_logged_in() . ' ' . (int)is_admin() . $_SERVER['HTTP_REFERER'] .  "\n", FILE_APPEND);
////	        if (!is_user_logged_in()) {
//		        file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'log.log', SiteDuplicatorLibJob::getJobStatus() . "\n", FILE_APPEND);
//	        }

            if( !empty($action) ){
                //check
                if( $action == 'check' ){
                    //save options
                    SiteDuplicatorLibUtility::plugin_options('add',self::validate_options($postData['site_duplicator_options']));

                    $checkFtpRes = SiteDuplicatorLibRequest::checkFtpConnection(array(
                        'ftp_host'      => @trim($postData['site_duplicator_options']['ftp_host']),
                        'ftp_username'  => @trim($postData['site_duplicator_options']['ftp_username']),
                        'ftp_password'  => @trim($postData['site_duplicator_options']['ftp_password']),
                        'ftp_path'      => @trim($postData['site_duplicator_options']['ftp_path']),
                        'ftp_port'      => @(int)$postData['site_duplicator_options']['ftp_port'],
                    ));
	                $answer = $checkFtpRes;
                    if( @(int)$checkFtpRes['status'] == 1) {
                        sleep(1);
                        $checkMysqlRes = SiteDuplicatorLibRequest::checkMysqlConnection(array(
                            'ftp_host' => @trim($postData['site_duplicator_options']['ftp_host']),
                            'ftp_username' => @trim($postData['site_duplicator_options']['ftp_username']),
                            'ftp_password' => @trim($postData['site_duplicator_options']['ftp_password']),
                            'ftp_path' => @trim($postData['site_duplicator_options']['ftp_path']),
                            'ftp_port' => @(int)$postData['site_duplicator_options']['ftp_port'],

                            'host' => @trim($postData['site_duplicator_options']['db_host']),
                            'port' => @(int)$postData['site_duplicator_options']['db_port'],
                            'db' => @trim($postData['site_duplicator_options']['db_name']),
                            'user' => @trim($postData['site_duplicator_options']['db_username']),
                            'password' => @trim($postData['site_duplicator_options']['db_password']),
//                            'db_table_prefix'   => @trim($postData['site_duplicator_options']['db_table_prefix']),

                            'url' => @trim($postData['site_duplicator_options']['url']),
                        ));

                        //if($checkMysqlRes['status'] != -1){
                        $answer = $checkMysqlRes;
                        //}
                    }
                }

                //start scan. and switch to starting of jobs
                if( $action == 'start' ){
                    //save options
                    SiteDuplicatorLibUtility::plugin_options('add',self::validate_options($postData['site_duplicator_options']));

                    //start scan process
                    $answer = SiteDuplicatorLibJob::initJob(true);

                    if( !empty($answer['server']['files_stat']) ){
                        $answer['server']['files_stat']['queue_size']   = @SiteDuplicatorLibUtility::byteSize($answer['server']['files_stat']['queue_size']);
                        $answer['server']['files_stat']['skipped_size'] = @SiteDuplicatorLibUtility::byteSize($answer['server']['files_stat']['skipped_size']);
                        $answer['server']['server_stat']['max_execution_time_up']   = ($answer['server']['server_stat']['max_execution_time_up'] == 1 ? 'YES' : 'NO');
                        $answer['server']['server_stat']['memory_limit_up']         = ($answer['server']['server_stat']['memory_limit_up'] == 1 ? 'YES' : 'NO');
                        $answer['server']['server_stat']['zip']                     = ($answer['server']['server_stat']['zip'] == 1 ? 'OK' : 'ERROR');
                        $answer['server']['server_stat']['mysql']                   = ($answer['server']['server_stat']['mysql'] == 1 ? 'OK' : 'ERROR');
                    }
                }

                //stop jobs
                if( $action == 'stop' ){
                    $answer = SiteDuplicatorLibJob::stopJob();
                }

                //do job
                if( $action == 'job' ){
                    $answer = SiteDuplicatorLibJob::initJob();
                }

                //logs
                if( $action == 'logs' ){
                    $answer = array(
                        'logs'          => array(),
                        'job_status'    => 1,
                        'job_step'      => 1,
                    );
                    $logs   = SiteDuplicatorLibJob::readJobLog(50);
                    if( !empty($logs) ){
                        foreach($logs as $k_item => $v_item){
                            if(is_array($v_item)){
                                $lineKey = (string)md5(print_r($v_item,1));
                                $answer['logs'][$lineKey] = '<div class="log_id_'.$lineKey.' log_line log_status_'.@(int)$v_item['status'].'"><span class="log_time">'.esc_html($v_item['time']).'</span><span class="log_msg">'.esc_html($v_item['msg']).'</span></div>';
                            }else{
                                $lineKey = (string)md5($v_item);
                                $answer['logs'][(string)md5($v_item)] = '<div class="log_id_'.$lineKey.' log_line">'.esc_html($v_item).'</div>';
                            }
                        }
                    }
                    $answer['job_status']   = SiteDuplicatorLibJob::getJobStatus();
                    $answer['job_step']     = SiteDuplicatorLibJob::getJobStep();
                }
            }else{
                $answer = SiteDuplicatorLibUtility::logEvent(17);
            }

            print json_encode($answer);exit;
        }

        public static function install()
        {
            //Prepare redirect to page of plugin
            add_option(self::$optionKey . '_redirect', true);
        }

        public static function deactivation()
        {
            //SiteDuplicatorLibUtility::plugin_options('remove');
        }

        public static function uninstall()
        {
            //SiteDuplicatorLibUtility::plugin_options('remove');
        }
        public static function stars5()
        {
            $hide = get_option(self::$optionKey . '_hide');
            if ($hide === false || !isset($hide['stars5']) || (isset($hide['stars5']) && $hide['stars5'] === false)) {
            ?> 
                <div class="clear"></div>
                <div class="updated notice" style="position: relative;">
                    <p >
                        <div style="font-size:16px; margin-bottom: 15px; font-weight: 800;"><?php echo SiteDuplicatorLibUtility::trans('Duplicator / Cloner / Migrator'); ?></div>
                        <div style="font-size:14px;">                     
                            <?php echo SiteDuplicatorLibUtility::trans('Help us to make this plugin better! Leave your <a href="https://wordpress.org/support/view/plugin-reviews/duplicator-clone?filter=5" target="_blank">5 stars review</a>.'); ?>
                        </div>
                        <div style="font-size:14px; margin-top: 20px;">                     
                            <?php echo SiteDuplicatorLibUtility::trans('We would appricate you'); ?>
                        </div>
                        <a href="<?php echo admin_url( 'admin-post.php?action=duplicator_hide_notice&type=stars5' ); ?>" style="right: 0; font-size: 12px; line-height: 22px; top:0; margin-right: 5px; margin-top:5px; position: absolute;">[<?php echo SiteDuplicatorLibUtility::trans('close message')?>]</a>
                    </p>
                </div>
            <?php
            }
        }
        
        public static function hide_message()
        {
            
            if (isset($_GET['type']) && $_GET['type'] == 'stars5') {
                $hide = get_option(self::$optionKey . '_hide');
                $hide[$_GET['type']] = true;
                update_option(self::$optionKey . '_hide', $hide);
            }
            header('location:' . $_SERVER['HTTP_REFERER']);
            exit;
        }


        public static function sendSupport() {

            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $plugins = get_plugins('/duplicator-clone');

            $this_plugin = $plugins['duplicator-clone.php'];


            if (isset($_POST['message'])) {
                $ticket = date('ymdHis') . rand(1000, 9999);
                $subject = "Support [sug:$ticket]: {$this_plugin['Name']}, ver. {$this_plugin['Version']} ";
                $message = "Client email: " . get_option('admin_email') . "\n";
                $message .= "Client site: " . home_url() . "\n";
                $message .= "Client suggestion: " . $_POST['message']. "\n\n";
                $message .= "Client ip: " . self::getIp() . "\n";
                $browser = @$_SERVER['HTTP_USER_AGENT'];
                $message .= "Client useragent: " . $browser . "\n";

                if (isset($_POST['attache_logs']) && $_POST['attache_logs'] == 1) {
                    $logs = SiteDuplicatorLibJob::readJobLog();
                    $message .= "Plugin logs:\n";
                    foreach ($logs as $log) {
                        $message .= $log['time'] . ' ' . $log['msg'] . "\n";
                    }
                }


                $header[] = "Reply-To: " . get_option('admin_email') . "\r\n";
                if (wp_mail(self::EMAIL_SUPPORT, $subject, $message, $header)) {
                    echo json_encode(array(
                        'status' => 'success'
                    ));
                } else {
                    echo json_encode(array(
                        'status' => 'error'
                    ));
                }
                wp_die();
            }
        }

        protected static function getIp()
        {
            $user_ip = '';
            if ( getenv('REMOTE_ADDR') ){
                $user_ip = getenv('REMOTE_ADDR');
            }elseif ( getenv('HTTP_FORWARDED_FOR') ){
                $user_ip = getenv('HTTP_FORWARDED_FOR');
            }elseif ( getenv('HTTP_X_FORWARDED_FOR') ){
                $user_ip = getenv('HTTP_X_FORWARDED_FOR');
            }elseif ( getenv('HTTP_X_COMING_FROM') ){
                $user_ip = getenv('HTTP_X_COMING_FROM');
            }elseif ( getenv('HTTP_VIA') ){
                $user_ip = getenv('HTTP_VIA');
            }elseif ( getenv('HTTP_XROXY_CONNECTION') ){
                $user_ip = getenv('HTTP_XROXY_CONNECTION');
            }elseif ( getenv('HTTP_CLIENT_IP') ){
                $user_ip = getenv('HTTP_CLIENT_IP');
            }

            $user_ip = trim($user_ip);
            if ( empty($user_ip) ){
                return '';
            }
            if ( !preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $user_ip) ){
                return '';
            }
            return $user_ip;
        }

    }

    class SiteDuplicatorApi
    {
        public static $site                 = 'http://www.wpadm.com';

        public static $adminRequestSite     = 'http://secure.wpadm.com';
        public static $adminRequestSite2    = 'https://secure.wpadm.com';

        public static $adminRequestUrl      = '/from-plugin-dupclo';
        public static $buyRequestUrl        = '/api/';

        public static $optionKey        = 'duplicator_clone_key';
        public static $personalKey      = '';

        public static $requestAnswers = array(
            0 => array('status' => 1, 'msg' => 'request success'),
            1 => array('status' => 1, 'msg' => 'connection success'),
            2 => array('status' => 0, 'msg' => 'connection failed'),
            5 => array('status' => 1, 'msg' => 'ping success'),
            6 => array('status' => 0, 'msg' => 'incorrect request'),
            7 => array('status' => 0, 'msg' => 'plugin should be connected'),
            8 => array('status' => 0, 'msg' => 'wrong key'),
            9 => array('status' => 1, 'msg' => 'update options success'),
            10 => array('status' => 0, 'msg' => 'update options failed'),
        );

        public static $tagAnswer = 'duplicator_clone_tag';
        public static $requestParameter = 'duplicator_clone_request';

        /**
         * Listens incoming request
         *
         * Constructor for other methods
         */
        public static function init(){
            //check request
            if( !isset($_POST[self::$requestParameter]) or empty($_POST[self::$requestParameter]) ){
                return true;    //nothing necessary
            }

            self::$personalKey  = (string)get_option(self::$optionKey, '');
            $allowedHost        = @str_replace(array('http://','https://'),'',trim(self::$adminRequestSite,'/'));
            $allowedIP          = @gethostbyname( $allowedHost );

            $refererHost        = @str_replace(array('http://','https://'),'',trim($_SERVER["HTTP_REFERER"],'/'));
            $requestIP          = SiteDuplicatorLibUtility::getUserIP();

            //check request ip
            if( empty($requestIP) or $requestIP != $allowedIP ){
                return true;    //wrong request ip
            }

            //check referer
            if( empty($refererHost) or $refererHost != $allowedHost ){
                return true;    //wrong referer
            }

            //check data, action, key
            $requestData    = self::convertString($_POST[self::$requestParameter], 'decode');
            if( empty($requestData) or empty($requestData['action']) or !method_exists('SiteDuplicatorApi','action_'.$requestData['action']) ){
                self::printAnswer(6);
            }

            //check key for actions
            $action     = (string)'action_'.$requestData['action'];
            $requestKey = @(string)$requestData['key'];
            if( $action == 'action_connect' ){
                if( !empty(self::$personalKey) ){
                    self::printAnswer( (self::$personalKey == $requestKey ? 1 : 2) );
                }
            }else{
                if( empty(self::$personalKey) ){
                    self::printAnswer(7);
                }
                if( empty($requestKey) or self::$personalKey != $requestKey ){
                    self::printAnswer(8);
                }
            }

            //run action
            self::$action($requestData);

            exit;
        }

        /**
         * Send message from user to admin
         */
        public static function requestServer($args, $fullAnswer = 0, $url = ''){
            if( empty($url) ){
                $url = trim(self::$adminRequestSite,'/') . self::$adminRequestUrl;
            }
            $postRes    = wp_remote_post( $url, $args );

            if ( is_wp_error( $postRes ) ) {
                $error = array( 'wp_error' => $postRes->get_error_message() );

                return ($fullAnswer ? $postRes : false);
            }

            return ($fullAnswer ? $postRes : true);
        }

        /**
         * Encode and decode array for hiding parameters from server
         *
         * @param $data
         * @param string $mode
         * @return array|mixed|string|void
         */
        public static function convertString($data, $mode = ''){
            $dataAnswer = array();

            if( empty($data) ){
                return $dataAnswer;
            }

            if($mode == 'decode'){
                $dataAnswer = @urldecode($data);
                $dataAnswer = @base64_decode($dataAnswer);
                $dataAnswer = @strrev($dataAnswer);
                $dataAnswer = @base64_decode($dataAnswer);
                $dataAnswer = @json_decode($dataAnswer,true);
            }

            if($mode == 'encode'){
                $dataAnswer = @json_encode($data);
                $dataAnswer = @base64_encode($dataAnswer);
                $dataAnswer = @strrev($dataAnswer);
                $dataAnswer = @base64_encode($dataAnswer);
                $dataAnswer = @urldecode($dataAnswer);
            }

            return $dataAnswer;
        }

        /**
         * Print answer of action
         * exit at the end
         * @param int $readyAnswer
         * @param array $data
         */
        public static function printAnswer($readyAnswer = 0, $data = array()){
            $answer = array(
                'msg_code'  => $readyAnswer,
                'msg'       => self::$requestAnswers[$readyAnswer]['msg'],
                'status'    => self::$requestAnswers[$readyAnswer]['status'],
                'data'      => $data,
            );
            $answer = self::convertString($answer, 'encode');
            print '<'.self::$tagAnswer.'>'.$answer.'</'.self::$tagAnswer.'>';

            exit;
        }

        /**
         * Connect plugin
         */
        protected static function action_connect(){
            $personalKey = md5(time().rand(1,10000).microtime().rand(1,10000));
            if( !add_option(self::$optionKey, $personalKey) ){
                self::printAnswer(2);
            }

            //send personal key to admin
            $requestVars = array(
                'body' => array(
                    self::$tagAnswer => self::convertString(array(
                        'action'    => 'connect',
                        'key'       => $personalKey,
                        'site'      => site_url(),
                        'pl'        => SiteDuplicator::$plugin_name,
                        'pl_v'      => SiteDuplicator::$plugin_version
                    ),'encode')
                )
            );
            $requestRes = self::requestServer($requestVars);

            if($requestRes){
                self::printAnswer(1);
            }else{
                self::printAnswer(2);
            }
        }

        /**
         * Read message of user by hash
         */
        protected static function action_read_logs($data){
            $count_lines    = @$data['count_lines'];
            $logs       = SiteDuplicatorLibJob::readJobLog($count_lines);
            self::printAnswer(0, array('logs' => $logs));
        }

        /**
         * Update ping plugin
         */
        protected static function action_ping(){
            self::printAnswer(5, array('plugin_version' => SiteDuplicator::$plugin_version));
        }

        /**
         * Update settings plugin
         */
        protected static function action_update_options($data){
            $options = @(array)json_decode($data['options'],1);
            unset($options['personal_key']);
            if( !empty($options) ){
                $options = SiteDuplicator::validate_options($options);
            }
            if( !empty($options) ){
                SiteDuplicatorLibUtility::plugin_options('add',$options);
                self::printAnswer(9);
            }

            self::printAnswer(10);
        }

        /**
         * Read settings plugin
         */
        protected static function action_read_options(){

        }

        /**
         * Authorization of user from plugin
         */
        public static function send_auth($mode, $vars = array()){
            $personalKey    = (string)get_option(self::$optionKey, '');
            $personalKey    = (empty($personalKey) ? md5(time().rand(1,9999).microtime().rand(1,9999)) : $personalKey );
            $site           = site_url();
            $data           = array();

            if($mode == 'check'){
                $data = array(
                    'action'            => 'auth_check',
                    'plugin'            => SiteDuplicator::$plugin_name,
                    'plugin_version'    => SiteDuplicator::$plugin_version,
                    'domain'            => site_url()
                );
            }
            if($mode == 'register'){
                $data = array(
                    'action'            => 'auth_register',
                    'key'               => $personalKey,
                    'username'          => $vars['username'],
                    'password'          => $vars['password'],
                    'plugin'            => SiteDuplicator::$plugin_name,
                    'plugin_version'    => SiteDuplicator::$plugin_version,
                    'domain'            => site_url()
                );
            }
            if($mode == 'login'){
                $data = array(
                    'action'            => 'auth_login',
                    'key'               => $personalKey,
                    'username'          => $vars['username'],
                    'password'          => $vars['password'],
                    'plugin'            => SiteDuplicator::$plugin_name,
                    'plugin_version'    => SiteDuplicator::$plugin_version,
                    'domain'            => site_url()
                );
            }

            $requestVars    = array('body' => array(self::$tagAnswer => self::convertString($data,'encode')));
            $requestAnswer  =  self::requestServer( $requestVars, 1 );

            $answerBody     = @trim($requestAnswer['body']);
            $answerBodyData = array();
            if( !empty($answerBody) ){
                $answerBodyData = self::convertString($answerBody,'decode');
            }

            if(@(int)$answerBodyData['status'] == 1){
                //save key
                add_option(self::$optionKey, $personalKey);

                //save username of user
                $options = SiteDuplicatorLibUtility::plugin_options('get');
                $options['auth_email'] = $vars['username'];
                SiteDuplicatorLibUtility::plugin_options('add',$options);

            }

            return array(
                'status'        => @(int)$answerBodyData['status'],
                'msg'           => SiteDuplicatorLibUtility::trans( (empty($answerBodyData['msg']) ? 'answer_server_not_answer' : $answerBodyData['msg']) ),
                'redirect_url'  => @$answerBodyData['redirect_url']
            );
        }

        public static function checkUpdate(){
            $url = trim(self::$adminRequestSite,'/') . self::$buyRequestUrl;
            $options = SiteDuplicatorLibUtility::plugin_options( 'get' );
            $options = SiteDuplicator::validate_options($options);
            $data = array(
                'site'          => site_url(),
                'email'         => get_option('admin_email'),
                'plugin'        => SiteDuplicator::$plugin_name,
                'plugin_version'=> SiteDuplicator::$plugin_version,
                'key'           => @$options['check_update_key'],
                'actApi'        => 'proBackupCheck',
            );


            $requestVars    = array('body' => $data);
            $requestAnswer  =  self::requestServer( $requestVars, 1, $url );

            $answerBody     = @trim($requestAnswer['body']);
            $answerBodyData = array();
            if( !empty($answerBody) ){
                $answerBodyData = json_decode($answerBody,1);
            }

            return array(
                'status'    => @(int)$answerBodyData['status'],
                'key'       => @$answerBodyData['key'],
                'url'       => @$answerBodyData['url']
            );
        }


        
    }
    
    

    require_once dirname(__FILE__).'/libs/request.php';
    require_once dirname(__FILE__).'/libs/filesystem.php';
    require_once dirname(__FILE__).'/libs/db.php';
    require_once dirname(__FILE__).'/libs/job.php';
    require_once dirname(__FILE__).'/libs/utility.php';
    require_once dirname(__FILE__).'/libs/archive.php';
    require_once dirname(__FILE__).'/libs/ftp.php';
    require_once dirname(__FILE__).'/libs/server.php';
    require_once dirname(__FILE__).'/libs/exception.php';

    define('PLUGIN_DIR_TMP', SiteDuplicatorLibFilesystem::normalizePath(plugin_dir_path(__FILE__).'/tmp',true));
    define('PLUGIN_DIR_JOBS', SiteDuplicatorLibFilesystem::normalizePath(plugin_dir_path(__FILE__).'/tmp/jobs',true));
    define('PLUGIN_DIR_LIBS', SiteDuplicatorLibFilesystem::normalizePath(plugin_dir_path(__FILE__).'/libs',true));
    define('PLUGIN_DIR_ARCHIVES', SiteDuplicatorLibFilesystem::normalizePath(plugin_dir_path(__FILE__).'/tmp/archives',true));
}

//init plugin on admin
add_action('admin_init', array( 'SiteDuplicator', 'admin_init') );
add_action('admin_menu', array( 'SiteDuplicator', 'admin_menu') );

add_action('admin_post_sd_job_request', array( 'SiteDuplicator', 'sd_job_request') );
add_action('admin_post_nopriv_sd_job_request', array( 'SiteDuplicator', 'sd_job_request') );
add_action('admin_post_duplicator-clone-auth-page', array( 'SiteDuplicator', 'auth_page') );
add_action('admin_post_duplicator_hide_notice', array( 'SiteDuplicator', 'hide_message') );

add_action( 'wp_ajax_sd_sendSupport', array('SiteDuplicator', 'sendSupport') );

add_action('admin_notices', array('SiteDuplicator', 'stars5'));

//manipulation by environments for plugin
register_activation_hook( __FILE__,     array( 'SiteDuplicator', 'install' ) );
register_deactivation_hook( __FILE__,   array( 'SiteDuplicator', 'deactivation' ) );
register_uninstall_hook( __FILE__,      array( 'SiteDuplicator', 'uninstall' ) );