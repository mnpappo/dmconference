<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if (!class_exists('SiteDuplicator')) {
    require_once dirname(__FILE__).'/../duplicator-clone.php';
}

if ( ! class_exists( 'SiteDuplicatorLibUtility' ) ) {
    class SiteDuplicatorLibUtility extends SiteDuplicator
    {

        public static function logEvent($eventNumber = 0, $addText = ''){
            $eventNumber    = @(int)$eventNumber;

            $logEvents      = array(
                0 => array('status' => 0, 'msg' => 'undefined error'),
                1 => array('status' => 0, 'msg' => 'dir for tmp files is not writable'),
                2 => array('status' => 0, 'msg' => 'could not create job file'),
                3 => array('status' => 0, 'msg' => 'job file is not writable'),
                4 => array('status' => 1, 'msg' => 'job file was created'),
                5 => array('status' => 0, 'msg' => 'job file is nor exist or not readable'),
                6 => array('status' => 0, 'msg' => 'job file is empty'),

                7 => array('status' => 0, 'msg' => 'FTPS disabled: Please enable ftp_ssl_connect in PHP'),
                8 => array('status' => 0, 'msg' => 'FTP disabled: Please enable ftp_connect in PHP'),
                9 => array('status' => 0, 'msg' => 'Failed connecting to the FTP server, please check FTP host and port'),
                10 => array('status' => 0, 'msg' => 'FTP login failed, please check your FTP login details'),
                11 => array('status' => 0, 'msg' => 'Unable to list FTP directory content'),
                12 => array('status' => 0, 'msg' => 'Unable to make directory'),
                13 => array('status' => 1, 'msg' => 'Directory was created'),
                14 => array('status' => 0, 'msg' => 'Failed to upload the file'),
                15 => array('status' => 1, 'msg' => 'File was uploaded'),
                16 => array('status' => 1, 'msg' => 'FTP connection is ok'),
                18 => array('status' => 0, 'msg' => 'Incorrect FTP data'),

                17 => array('status' => 0, 'msg' => 'Incorrect action'),
                19 => array('status' => 1, 'msg' => 'Process was stopped'),
                20 => array('status' => 1, 'msg' => 'File was removed'),
                21 => array('status' => 0, 'msg' => 'Could not remove file'),

                22 => array('status' => 1, 'msg' => 'Created export of table'),
                23 => array('status' => 0, 'msg' => 'Could not create export of table'),
                24 => array('status' => 1, 'msg' => 'Installer uploaded'),
                25 => array('status' => 1, 'msg' => 'Wordpress config updated'),
                26 => array('status' => 1, 'msg' => 'MySQL table was imported'),
                27 => array('status' => 1, 'msg' => 'Installer removed'),
                28 => array('status' => 1, 'msg' => 'Table was uploaded'),
                29 => array('status' => 1, 'msg' => 'Process finished'),
                30 => array('status' => 0, 'msg' => 'Failed import MySQL table'),
                31 => array('status' => 0, 'msg' => 'Failed removing installer'),
                32 => array('status' => 0, 'msg' => 'Failed creation of archive'),
                33 => array('status' => 1, 'msg' => 'Archive created'),
                34 => array('status' => 1, 'msg' => 'Archive was uploaded'),
                35 => array('status' => 1, 'msg' => 'Archive was extracted'),
                36 => array('status' => 0, 'msg' => 'Failed extract archive'),
                37 => array('status' => 0, 'msg' => 'Tmp directory with archives was removed'),
                38 => array('status' => 1, 'msg' => 'Failed remove directory with archives'),
                39 => array('status' => 0, 'msg' => 'Tmp directory with tables was removed'),
                40 => array('status' => 1, 'msg' => 'Failed remove directory with tables'),
	            
	            41 => array('status' => 0, 'msg' => 'MySQL connect failed'),
	            42 => array('status' => 1, 'msg' => 'MySQL connect is ok'),
	            43 => array('status' => 1, 'msg' => 'FTP and MySQL connect is ok'),

                99 => array('status' => 1, 'msg' => 'request success'),
            );

            if( !empty($addText) ){
                $logEvents[$eventNumber]['msg'] .= ' ('.$addText.')';
            }
            $logEvents[$eventNumber]['code'] = $eventNumber;

            return $logEvents[$eventNumber];
        }

        public static function byteSize($size) {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');
            for ($i = 0; $size >= 1024 && $i < 4; $i++)
                $size /= 1024;
            return round($size, 2) . $units[$i];
        }

        public static function plugin_options($mode = 'add', $options = array()){
            if(empty($options)){
                $options = parent::$defaultOptions;
            }

            if($mode == 'add'){
                update_option(parent::$optionKey, $options);
            }

            if( $mode == 'get' ){
                $optionsValue = (array)get_option(parent::$optionKey, $options);
                foreach(parent::$defaultOptions as $k_op => $v_op){
                    $optionsValue[$k_op] = ( !array_key_exists($k_op, $optionsValue) ? $v_op : $optionsValue[$k_op]);
                }
                return $optionsValue;
            }

            if( $mode == 'remove' ){
                delete_option( parent::$optionKey );//delete settings options
            }
        }

        public static function trans($var, $lang = 'en', $escape = false){
            $lang = (empty($lang) ? 'en' : $lang);
            $text = $var;
            if (isset(parent::$translation[$lang])) {
                if (isset(parent::$translation[$lang][$var])) {
                    $text = parent::$translation[$lang][$var];
                } elseif (isset(parent::$translation['en'][$var])) {
                    $text = parent::$translation['en'][$var];
                }
            }

            if($escape){
                $text = esc_html($text);
            }

            return $text;
        }

        public static function increaseLimits($options = array()){
            $options = ( empty($options) ? SiteDuplicatorLibUtility::plugin_options('get') : $options);

            @set_time_limit( $options['max_execution_time'] );
            @ini_set('memory_limit', $options['memory_limit'].'M');
        }

        public static function getUserIP(){
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
}