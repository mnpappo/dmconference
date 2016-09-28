<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if ( ! class_exists( 'SiteDuplicatorLibFtp' ) ) {
    class SiteDuplicatorLibFtp
    {

        private static $instance;

        /**
         * @param string $filesQueuePath        - path on file with list of files for transfering
         * @param array $options                - options for work
         * @param string $queueType             - file: work with queue of files, db: work with queue of db, archive: work with queue of archives
         * @return bool                         - false - we should repeat archiving, true - all necessary files was archived
         */
        public static function transferFiles($filesQueuePath, $options, $queueType = 'file'){
            $jobName    = $options['active_job'];
            $settings   = SiteDuplicatorLibJob::readJobSettings($jobName);

            $maxExecTime        = @(int)ini_get('max_execution_time');
            $maxExecTime        = (empty($maxExecTime) ? $options['max_execution_time'] : $maxExecTime);
            $timePast           = 0;
            $operationTime      = 0;

            $continueWorkFlag   = true;
            $processFinished    = false;

            //init ftp
            $ftp        = self::ftpFactory($options['ftp_username'], $options['ftp_password'], $options['ftp_host'], $options['ftp_path'], $options['ftp_port']);
            $openRes    = true;
            if( is_array($ftp) and array_key_exists('msg',$ftp) and $ftp['status'] == 0  ){
                $openRes            = false;
                $continueWorkFlag   = false;
                SiteDuplicatorLibJob::doJobLog($ftp);
            }

            while( ($timePast+$operationTime) < $maxExecTime and $continueWorkFlag === true){
                $startTime = time();

                //file
                $line = SiteDuplicatorLibFilesystem::readContentFileLasLine( $filesQueuePath, 1, '', true );//remove line in any case, for prevent loop
                $line = @$line[0];

                if( !empty($line) ){
                    //prepare file
                    if($queueType == 'file'){
                        $filePath           = SiteDuplicatorLibFilesystem::makeFilePath($line[0],$line[6],$line[8]);
                        $filePath           = SiteDuplicatorLibFilesystem::normalizePath($filePath);
                        $filePathRemote     = SiteDuplicatorLibFilesystem::normalizePath($options['ftp_path'].'/'.$line[8]);
                    }
                    if($queueType == 'db'){
                        $filePath           = SiteDuplicatorLibFilesystem::makeFilePath($line[0],$line[6],$line[8]);
                        $filePath           = SiteDuplicatorLibFilesystem::normalizePath($filePath);
                        $filePathRemote     = SiteDuplicatorLibFilesystem::normalizePath($options['ftp_path'].'/db');
                    }
                    if($queueType == 'archive'){
                        $filePath           = SiteDuplicatorLibArchive::getArchivePath($line[1],$jobName);
                        $filePath           = SiteDuplicatorLibFilesystem::normalizePath($filePath);
                        $filePathRemote     = SiteDuplicatorLibFilesystem::normalizePath($options['ftp_path'].'/archives');
                    }

                    //transfer file
                    $ftpPutRes  = SiteDuplicatorLibFtp::ftpPutFile($ftp, $filePath, $filePathRemote);

                    if( is_array($ftpPutRes) and array_key_exists('msg',$ftpPutRes) and $ftpPutRes['status'] == 0  ){
                        $continueWorkFlag = false;
                        SiteDuplicatorLibJob::doJobLog($ftpPutRes);
                    }else{
                        if($queueType == 'file'){
                            SiteDuplicatorLibJob::doJobLog($ftpPutRes);

                            //save transferred file
                            $settings['processes']['transferred_files'] = (int)$settings['processes']['transferred_files'] + 1;
                            SiteDuplicatorLibFilesystem::writeContentFile( SiteDuplicatorLibJob::getJobFilePath('settings'), $settings);
                        }
                        if($queueType == 'db'){
                            SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(28,$line[6]));

                            //save transferred table
                            $settings['processes']['transferred_tables'][] = $line[6];
                            SiteDuplicatorLibFilesystem::writeContentFile( SiteDuplicatorLibJob::getJobFilePath('settings'), $settings);
                        }
                        if($queueType == 'archive'){
                            SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(34,$line[1].'.zip'));

                            //save transferred table
                            $settings['processes']['transferred_archives'][] = $line[1].'.zip';
                            SiteDuplicatorLibFilesystem::writeContentFile( SiteDuplicatorLibJob::getJobFilePath('settings'), $settings);
                        }
                    }
                }else{
                    $timePast = $maxExecTime;
                    $continueWorkFlag   = false;
                    $processFinished    = true;
                }

                $operationTime = (time() -$startTime);
                $timePast += $operationTime;
            }

            //close ftp
            if($openRes){
                self::closeFtp($ftp);
            }

            return $processFinished;
        }

        public static function getInstance(){
            $options = SiteDuplicatorLibUtility::plugin_options('get');

            if( empty(self::$instance) ){
                self::$instance = self::ftpFactory($options['ftp_username'], $options['ftp_password'], $options['ftp_host'], $options['ftp_path'], $options['ftp_port']);
            }

            return self::$instance;
        }

        public static function ftpFactory($username, $password, $host, $rootDir = '/', $port = 21, $ssl = false, $timeout = 10, $mode = 3){
            if ($ssl) {
                if (!function_exists('ftp_ssl_connect')) {
                    //throw new SiteDuplicatorLibException();
                    return SiteDuplicatorLibUtility::logEvent(7);
                }
                $ftp = @ftp_ssl_connect($host, $port, $timeout);
            } else {
                if (!function_exists('ftp_connect')) {
                    return SiteDuplicatorLibUtility::logEvent(8);
                }
                $ftp = @ftp_connect($host, $port, $timeout);
            }

            if ($ftp === false) {
                return SiteDuplicatorLibUtility::logEvent(9);
            }

            $login = @ftp_login($ftp, $username, $password);

            if ($login === false) {
                return SiteDuplicatorLibUtility::logEvent(10);
            }

            //if($mode == 2 or $mode == 3){
            //    @ftp_pasv($ftp, true);
            //}

            return $ftp;
        }

        public static function ftpMkdir($ftp, $path){
            $path  = SiteDuplicatorLibFilesystem::normalizePath($path);

            if (substr($path, 0, 1) === '/') {
                $currentPath = '/';
                $path        = substr($path, 1);
            } else {
                $currentPath = '';
            }

            $path  = rtrim($path, '/');
            $paths = explode('/', $path);
            while ($directory = array_shift($paths)) {
                $dirList = @ftp_nlist($ftp, $currentPath);
                $currentPath .= $directory;

                if ($dirList === false) {
                    return SiteDuplicatorLibUtility::logEvent(11, $currentPath);
                }

                $dirList = array_map('basename', $dirList);

                $dirExists = in_array($directory, $dirList);

                if (!$dirExists) {
                    $dirMade = @ftp_mkdir($ftp, $currentPath);
	                @ftp_chmod($ftp, 0777, $currentPath);

                    if (!$dirMade) {
                        return SiteDuplicatorLibUtility::logEvent(12, $currentPath);
                    }
                }

                $currentPath .= '/';
            }

            return SiteDuplicatorLibUtility::logEvent(13, $currentPath);
        }

        public static function ftpPutFile($ftp, $currentFilePath, $remoteFolder){
            $fileSize       = @filesize($currentFilePath);
            $remoteFilePath = $remoteFolder.'/'.basename($currentFilePath);

            $mkDirRes = self::ftpMkdir($ftp, $remoteFolder);
            if($mkDirRes['status'] == 0){
                return $mkDirRes;
            }

            $uploaded = @ftp_put($ftp, $remoteFilePath, $currentFilePath, FTP_BINARY);
            if (!$uploaded) {
                $uploaded = @ftp_put($ftp, $remoteFilePath, $currentFilePath, FTP_ASCII);
            }
            if (!$uploaded) {
                @ftp_pasv($ftp, true);
                $uploaded = @ftp_put($ftp, $remoteFilePath, $currentFilePath, FTP_BINARY);
            }
            if (!$uploaded) {
                @ftp_pasv($ftp, true);
                $uploaded = @ftp_put($ftp, $remoteFilePath, $currentFilePath, FTP_ASCII);
            }

            if (!$uploaded) {
                ftp_close($ftp);
                return SiteDuplicatorLibUtility::logEvent(14, $currentFilePath.' => '.$remoteFolder);
            }else{
                if ( @ftp_size($ftp, $remoteFilePath) != $fileSize) {
                    return SiteDuplicatorLibUtility::logEvent(14, $currentFilePath.' => '.$remoteFolder);
                }
            }

	        @ftp_chmod($ftp, 0666, $remoteFilePath);
            //@ftp_close($ftp);
            return SiteDuplicatorLibUtility::logEvent(15, $currentFilePath.' => '.$remoteFolder);
        }

        public static function ftpDelete($ftp, $path){
            $result = true;

            //try delete directory or file
            if( @ftp_rmdir($ftp, $path) === false and @ftp_delete($ftp, $path) === false ) {
                $fileList = @ftp_nlist($ftp, $path);
                if( !empty($fileList) ){
                    foreach($fileList as $k_file => $v_file) {
                        self::ftpDelete($ftp, $v_file);
                    }

                    $result = self::ftpDelete($ftp, $path);
                }else{
                    $result = false;
                }
            }

            return $result;
        }

        public static function ftpChmod($ftp, $path = '.', $mode = '0777'){
            if(@ftp_site($ftp, 'CHMOD ' . $mode . ' ' . $path) === false){
                return false;
            }
            return true;
        }

        public static function closeFtp($ftp){
            @ftp_close($ftp);
        }
    }
}