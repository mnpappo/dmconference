<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if ( ! class_exists( 'SiteDuplicatorLibRequest' ) ) {
    class SiteDuplicatorLibRequest extends SiteDuplicator
    {

        public static function checkFtpConnection($parameters = array()){
            if( empty($parameters['ftp_username']) or empty($parameters['ftp_password']) or empty($parameters['ftp_host']) or empty($parameters['ftp_path']) or empty($parameters['ftp_port']) ){
                return SiteDuplicatorLibUtility::logEvent(18);
            }

            $ftpConnection = SiteDuplicatorLibFtp::ftpFactory(
                $parameters['ftp_username'], $parameters['ftp_password'], $parameters['ftp_host'], $parameters['ftp_path'],
                $parameters['ftp_port']
            );

            if($ftpConnection === false or (is_array($ftpConnection) and !empty($ftpConnection['msg'])) ){
                return SiteDuplicatorLibUtility::logEvent(9);
            }

            SiteDuplicatorLibFtp::closeFtp($ftpConnection);

            return SiteDuplicatorLibUtility::logEvent(16);
        }

        public static function checkMysqlConnection($parameters = array()){
//            $checkFtpRes = self::checkFtpConnection($parameters);
//            if( @(int)$checkFtpRes['status'] == 1){
                //try upload library for testing
                $ftp        = SiteDuplicatorLibFtp::ftpFactory($parameters['ftp_username'], $parameters['ftp_password'], $parameters['ftp_host'], $parameters['ftp_path'], $parameters['ftp_port']);
                $ftpPutRes  = SiteDuplicatorLibFtp::ftpPutFile($ftp, SiteDuplicatorLibJob::getInstallerPath(), $parameters['ftp_path']);

                //check library
                $checkRes = self::testInstaller($parameters);
                if($checkRes['status'] != 1) {
                    $ret = array(
                        'status' => - 1,
                        'msg'    => $checkRes['msg'],
                    );
                }

                //$checkRes = @json_decode($checkRes,1);


                if( @(int)$checkRes == 1){
                    //test mysql
	                $mysqlRes = self::testMysql($parameters, true);

	                if (is_array($mysqlRes) && isset($mysqlRes['body'])) {
		                if ($test_result = json_decode($mysqlRes['body'], true)) {
			                if ($test_result['status']==1) {
				                $ret =  SiteDuplicatorLibUtility::logEvent(43);
			                } else {
				                $ret = SiteDuplicatorLibUtility::logEvent(41, $test_result['msg']);
			                }
		                }
	                } else {
		                $ret = array(
			                'status' => - 1,
			                'msg'    => ( ! empty( $checkRes['msg'] ) ? $checkRes['msg'] : SiteDuplicatorLibUtility::trans( 'installer_not_found' ) ).'. Please check "New URL" and "Root folder path of site"'
		                );
	                }
                }else{
                    $ret = array('status' => -1, 'msg' => (!empty($checkRes['msg']) ? $checkRes['msg'] : SiteDuplicatorLibUtility::trans('installer_not_found')).'. Please check "New URL" and "Root folder path of site"');
                }
	        if( @(int)$checkRes == 1) {
	        
		        SiteDuplicatorLibFtp::ftpDelete($ftp, '/'.trim($parameters['ftp_path'],'/') . '/' . basename(SiteDuplicatorLibJob::getInstallerPath()));
		        SiteDuplicatorLibFtp::closeFtp( $ftp );
	        }
	        
	        return $ret;
        }


        public static function checkDestinationStats(){}

        public static function updateConfig($options, $fullAnswer = false){
            $requestVars    = array(
                'method'    => 'POST',
                'timeout'   => 60,  //todo mex_execution_time of this server or destination server. min value
                'body'      => array(
                    'dbname'    => $options['db_name'],
                    'uname'     => $options['db_username'],
                    'pwd'       => $options['db_password'],
                    'dbhost'    => $options['db_host'],
                    'prefix'    => $options['db_table_prefix'],
                )
            );
            $url            = rtrim($options['url'],'/').'/sd_installer.php?mode=config_wordpress&pass=gtr87sdfSDFoiwer';
            //var_dump($url);
            $postRes        = wp_remote_post( $url, $requestVars );

            if ( is_wp_error( $postRes ) ) {
                $error = array( 'wp_error' => $postRes->get_error_message() );
                return ($fullAnswer ? $postRes : false);
            }else{
                $body = @json_decode($postRes['body'], true);
                return (@(int)$body['status'] == 1 ? true : false);
            }

            return ($fullAnswer ? $postRes : true);
        }

        public static function restoreTable($options, $fullAnswer = false, $table){
            global $wpdb;

            $requestVars    = array(
                'method'    => 'POST',
                'timeout'   => 60,  //todo max_execution_time of this server or destination server. min value
                'body'      => array(
                    'host'          => $options['db_host'],
                    'user'          => $options['db_username'],
                    'password'      => $options['db_password'],
                    'db'            => $options['db_name'],
                    'table'         => $table,
                    'prefix'        => $options['db_table_prefix'],
                    'old_prefix'    => $wpdb->base_prefix,
                    'old_url'       => site_url(),
                    'url'           => $options['url']

                )
            );
            $url            = rtrim($options['url'],'/').'/sd_installer.php?mode=restore_table&pass=gtr87sdfSDFoiwer';
            $postRes        = wp_remote_post( $url, $requestVars );

            if ( is_wp_error( $postRes ) ) {
                SiteDuplicatorLibJob::doJobLog(array('status' => 1, 'msg' => print_r($postRes,1)));

                $error = array( 'wp_error' => $postRes->get_error_message() );
                return ($fullAnswer ? $postRes : false);
            }else{
                $body = @json_decode($postRes['body'], true);
                if( !empty($body) and !empty($body['msg']) ){
                    //SiteDuplicatorLibJob::doJobLog(array('status' => $body['status'], 'msg' => $body['msg']));
                }
                return (@(int)$body['status'] == 1 ? true : false);
            }

            return ($fullAnswer ? $postRes : true);
        }

        public static function removeInstaller($options, $fullAnswer = false){
            $requestVars    = array(
                'method'    => 'POST',
                'body'      => array()
            );
            $url            = rtrim($options['url'],'/').'/sd_installer.php?mode=kill_me&pass=gtr87sdfSDFoiwer';
            $postRes        = wp_remote_post( $url, $requestVars );

            if ( is_wp_error( $postRes ) ) {
                $error = array( 'wp_error' => $postRes->get_error_message() );
                return ($fullAnswer ? $postRes : false);
            }else{
                $body = @json_decode($postRes['body'], true);
                return (@(int)$body['status'] == 1 ? true : false);
            }

            return ($fullAnswer ? $postRes : true);
        }

        public static function testInstaller($options, $fullAnswer = false){
            $requestVars    = array(
                'method'    => 'POST',
                'body'      => array()
            );
            $url            = rtrim($options['url'],'/').'/sd_installer.php?mode=check_me&pass=gtr87sdfSDFoiwer';

            $postRes        = wp_remote_post( $url, $requestVars );
            
            if ( is_wp_error( $postRes ) ) {
                $error = array( 'wp_error' => $postRes->get_error_message() );
                return ($fullAnswer ? $postRes : false);
            } else {
                $res = @json_decode($postRes['body'], true);
                if (
                    (function_exists('json_last_error') && (json_last_error() != JSON_ERROR_NONE || !isset($res['status']) || $res['status'] != 1))
                    || (!function_exists('json_last_error') && (empty($res) || !isset($res['status']) || $res['status'] != 1))
                ){
                    $error = array( 'wp_error' => 'Connect error' );
                    return ($fullAnswer ? $postRes : false);
                }
            }

            return ($fullAnswer ? $postRes : true);
        }

        public static function testMysql($options, $fullAnswer = false){
            $requestVars    = array(
                'method'    => 'POST',
                'body'      => $options
            );
            $url            = rtrim($options['url'],'/').'/sd_installer.php?mode=test_mysql&pass=gtr87sdfSDFoiwer';

            $postRes        = wp_remote_post( $url, $requestVars );


            if ( is_wp_error( $postRes ) ) {
                $error = array( 'wp_error' => $postRes->get_error_message() );
                return ($fullAnswer ? $postRes : false);
            } else {
                $res = @json_decode($postRes['body'], true);
//                if (json_last_error() != JSON_ERROR_NONE || !isset($res['status']) || $res['status'] != 1) {
                if (
                    (function_exists('json_last_error') && (json_last_error() != JSON_ERROR_NONE || !isset($res['status']) || $res['status'] != 1))
                    || (!function_exists('json_last_error') && (empty($res) || !isset($res['status']) || $res['status'] != 1))
                ){
                    $error = array( 'wp_error' => 'Mysql connect failed' );
                    return ($fullAnswer ? $postRes : false);
                } else {
                    return ($fullAnswer ? $postRes : (@$res['status'] == 1));
                }
            }

            return ($fullAnswer ? $postRes : true);
        }

        public static function extractArchive($options, $fullAnswer = false, $archive_name){
            $requestVars    = array(
                'method'    => 'POST',
                'timeout'   => 60,  //todo max_execution_time of this server or destination server. min value
                'body'      => array(
                    'archive'   => $archive_name
                )
            );
            $url            = rtrim($options['url'],'/').'/sd_installer.php?mode=extract_archive&pass=gtr87sdfSDFoiwer';
            $postRes        = wp_remote_post( $url, $requestVars );
            //print '<pre>';print_r($postRes);print '</pre>';
            if ( is_wp_error( $postRes ) ) {
                SiteDuplicatorLibJob::doJobLog(array('status' => 1, 'msg' => print_r($postRes,1)));

                $error = array( 'wp_error' => $postRes->get_error_message() );
                return ($fullAnswer ? $postRes : false);
            }else{
                $body = @json_decode($postRes['body'], true);
                if( !empty($body) and !empty($body['msg']) ){
                    //SiteDuplicatorLibJob::doJobLog(array('status' => $body['status'], 'msg' => $body['msg']));
                }

                if($fullAnswer){
                    return $body;
                }else{
                    return (@(int)$body['status'] == 1 ? true : false);
                }
            }

            return ($fullAnswer ? $postRes : true);
        }



        public static function doJob($data = array(), $fullAnswer = false){
            $data['sign'] = SiteDuplicator::getRequestSecurityCode();
            $requestVars    = array(
                'method'    => 'POST',
                'body'      => $data
            );
            $url            = admin_url( 'admin-post.php?action=sd_job_request&sub_action=job' );
            $postRes        = wp_remote_post( $url, $requestVars );

            if ( is_wp_error( $postRes ) ) {
                $error = array( 'wp_error' => $postRes->get_error_message() );
                return ($fullAnswer ? $postRes : false);
            }

            return ($fullAnswer ? $postRes : true);
        }



    }
}