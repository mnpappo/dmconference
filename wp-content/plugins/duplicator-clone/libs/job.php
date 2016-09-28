<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if ( ! class_exists( 'SiteDuplicatorLibJob' ) ) {
    class SiteDuplicatorLibJob extends SiteDuplicator
    {

        //MAIN

        public static function initJob($forceNewJob = false, $options = array()){
            $options    = (empty($options) ? SiteDuplicatorLibUtility::plugin_options('get') : $options);
            $jobName    = self::getJobName($forceNewJob, $options);
            $jobStatus  = self::getJobStatus($options);
            $jobStep    = self::getJobStep($options);
            $jobSettings= SiteDuplicatorLibJob::readJobSettings();
            $jobRes     = array();


            if($jobStatus == 0){
                // start step
                self::setJobName($jobName); //remember job name
                self::setJobStatus(1);      //start job
                $jobRes = self::doStats($jobName, $options);  //so stats
                self::setJobStatus(2);      //pause job. we will waiting starting of main job
            }elseif($jobStatus == 2){
                //run process after pause
                self::setJobStatus(1);
            }

            // db dump step
            if($jobStatus == 1 and in_array($jobStep, array(2,3)) ){
                SiteDuplicatorLibUtility::increaseLimits();
                @session_write_close();
                @ignore_user_abort(true);

                if( in_array(2,$options['duplicate_mode']) ) {
                    //START STEP
                    if (self::getJobStep() == 2) {
                        self::doJobLog(array('status' => 3, 'msg' => '=== STARTED CREATION DB DUMP ==='));
                        self::setJobStep(3);
                    }

                    //DO STEP
                    $continueJob = self::doDB($jobName, $options);
                }else{
                    $continueJob = false;
                }

                //FINISH STEP
                if($continueJob === false){
                    self::setJobStep(4);
                }

                @session_start();
            }

            // archives files step
            if($jobStatus == 1 and in_array($jobStep, array(4,5)) ){

                SiteDuplicatorLibUtility::increaseLimits();
                @session_write_close();
                @ignore_user_abort(true);

                if( @(int)$jobSettings['server']['server_stat']['zip'] == 1){
                    //START STEP
                    if ($jobStep == 4) {
                        self::doJobLog(array('status' => 3, 'msg' => '=== STARTED CREATION ARCHIVES ==='));
                        self::setJobStep(5);
                    }

                    //DO STEP
                    $continueJob = self::doArchives($jobName, $options);
                }else{
                    $continueJob = false;
                }

                //FINISH STEP
                if($continueJob === false){
                    self::setJobStep(6);
                }

                @session_start();
            }

            // transfer files to destination step
            if( $jobStatus == 1 and in_array($jobStep, array(6,7)) ){
                SiteDuplicatorLibUtility::increaseLimits();
                @session_write_close();
                @ignore_user_abort(true);

                //START STEP
                if ($jobStep == 6) {
                    self::doJobLog(array('status' => 3, 'msg' => '=== STARTED TRANSFER OF FILES ==='));
                    self::setJobStep(7);
                }

                //DO STEP
                $continueJob = self::doTransfer($jobName, $options);

                //FINISH STEP
                if($continueJob === false){
                    self::setJobStep(8);
                }

                @session_start();
            }

            // update of destination step
            if($jobStatus == 1 and in_array($jobStep, array(8,9)) ){
                SiteDuplicatorLibUtility::increaseLimits();
                @session_write_close();
                @ignore_user_abort(true);

                //START STEP
                if ($jobStep == 8) {
                    self::doJobLog(array('status' => 3, 'msg' => '=== STARTED DESTINATION UPDATE ==='));
                    self::setJobStep(9);
                }

                //DO STEP
                $continueJob = self::doRemoteUpdate($jobName, $options);

                //FINISH STEP
                if($continueJob === false){
                    self::setJobStep(10);
                }

                @session_start();
            }

            // removing of job files step
            if($jobStatus == 1 and in_array($jobStep, array(10,11)) ){
                if($jobStep == 10){
                    self::doJobLog(array('status' => 3, 'msg' => '=== ALL JOBS FINISHED ==='));
                }
                //$jobRes = self::doRemoving($jobName, $options);

                self::doJobLog(SiteDuplicatorLibUtility::logEvent(29));
                self::setJobStatus(100);
            }

            $jobStatus  = self::getJobStatus();
            $jobStep    = self::getJobStep();

            $jobRes['job_step']     = $jobStep;
            $jobRes['job_status']   = $jobStatus;
            $jobRes['status']       = '';
            $jobRes['msg']          = '';

            //repeat job only if status == 'process'
            if($jobStatus == 1){
                $repeatRes = SiteDuplicatorLibRequest::doJob(array(),true);
                //$repeatRes = SiteDuplicatorLibRequest::doJob();
                if( !$repeatRes ){
                    self::doJobLog(array('status' => 0, 'msg' => '=== http repeat of job failed ==='.print_r($repeatRes,1)));
                    //self::doJobLog(array('status' => 0, 'msg' => '=== http repeat of job failed ==='));
                }
            }

            return $jobRes;
        }

        /**
         * Stop current job. Remove all files of job
         */
        public static function stopJob(){
            $jobName    = self::getJobName();
            self::setJobStatus(3);      // stop job
            self::doRemoving($jobName); // remove job files

            self::setJobStatus(0);      // clear status
            self::setJobStep(0);        // clear step

            return SiteDuplicatorLibUtility::logEvent(19);
        }

        //ACTIONS

        public static function doStats($jobName, $options){
            //start step
            self::setJobStep(1);

            //create files and folders
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('full_files'), '');          //  file for full scan of files
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('queue_files'), '');         //  file for files for transfer of files
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('skipped_files'), '');       //  file for skipped files
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('db_files'), '');            //  file for list of tables of db
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('queue_db_files'), '');      //  file for files of db for transfer
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('archive_files'), '');       //  file for list of files for compressing to archive. used for preparing "queue_archive_files"
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('archive_db'), '');          //  file for list of db export for compressing to archive. used for preparing "queue_archive_files"
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('queue_archive_files'), ''); //  file for list of archive for transfer
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('settings'), '');            //  file for settings
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('logs'), '');                //  file for los
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('local_backup_files'), '');  //  file for files for local backup

            //get stats of server
            $serverStat = SiteDuplicatorLibServer::getStat();

            //save files map
            $filesStat = self::doFiles($jobName, $options, $serverStat);

            //save db stats
            $dbStat = SiteDuplicatorLibDb::getStats();

            //save settings and additional data
            $statsData = array(
                'settings'      => array(
                    'url'                       => $options['url'],
                    'duplicate_mode'            => $options['duplicate_mode'],  // 1-files, 2-db
                    'skip_file_size'            => $options['skip_file_size'],
                    'skip_file_extension'       => $options['skip_file_extension'],
                    'archive_size'              => $options['archive_size'],
                    'destination_ftp_test'      => 0,   //0 - need check, 1 - success, 2 - error
                    'destination_mysql_test'    => 0,   //0 - need check, 1 - success, 2 - error
                ),
                'server'    => array(
                    'server_stat'   => $serverStat,
                    'files_stat'    => $filesStat,  //if empty - seems files could not be prepared
                    'archives_stat' => array(),     //if empty - work only with files
                    'db_stat'       => $dbStat,     //if empty - work without db, but if we need duplicate db - seems error
                ),
                'destination_server' => array(
                    'server_stat'   => array(),
                    'files_stat'    => array(),
                    'archives_stat' => array(),
                    'db_stat'       => array(),
                ),
                'processes' => array(
                    'transferred_files'     => 0,       //count of files which was transferred without archiving
                    'transferred_archives'  => array(), //array of archives which was transferred
                    'extracted_archives'    => array(), //array of archives which was extracted

                    'exported_tables'       => array(), //array of tables which was exported
                    'transferred_tables'    => array(), //array of tables which was transferred
                    'imported_tables'       => array(), //array of tables which was imported
                    'config'                => 0,       //1 - config prepared, 0 - still waiting update
                    'installer'             => 0,       //1 - installer uploaded, 0 - installer still waiting upload
                )
            );

            //overwrite for db statistic
            if( !in_array(2,$options['duplicate_mode']) ){
                $statsData['server']['db_stat']['count_tables'] = 0;
            }

            SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('settings'), $statsData);

            //finish step
            self::setJobStep(2);

            return $statsData;
        }

        public static function doFiles($jobName, $options = array(), $serverStat = array()){
            $answer = array(
                'queue_count'   => 0,
                'queue_size'    => 0,
                'skipped_count' => 0,
                'skipped_size'  => 0,
            );

            //always skip dirs with this plugin
            $options['skipDirs'] = (empty($options['skipDirs']) ? array() : $options['skipDirs']);
            $options['skipDirs'][] = SiteDuplicatorLibFilesystem::normalizePath(ABSPATH.'/wp-content/plugins/'.parent::$plugin_name.'/tmp');

            $files = SiteDuplicatorLibFilesystem::listFiles(ABSPATH, true,array(
                'maxFileSize'   => @$options['skip_file_size'],
                'skipFileExt'   => @$options['skip_file_extension'],
                'skipDirs'      => @$options['skipDirs']
            ));
            if( !empty($files) ){
                //full scan of files
                $res = SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('full_files'), $files, 'by_line');  //  save full scan
                if( array_key_exists('msg', (array)$res) and $res['status'] == 0 ){
                    return $res;
                }

                //divide files on queue and skipped
                foreach($files as $k_item => $v_item){
                    $v_item[8] = str_replace( ABSPATH,'',$v_item[8]);
                    $v_item[8] = str_replace( rtrim(ABSPATH,'/'),'',$v_item[8]);
                    if( @(int)$v_item[5] == 0 and in_array(1,$options['duplicate_mode']) ){
                        $res = SiteDuplicatorLibFilesystem::addLastLine( self::getJobFilePath('queue_files'), $v_item);     //save queue for work
                        if( array_key_exists('msg', (array)$res) and $res['status'] == 0 ){
                            return $res;
                        }

                        $answer['queue_count']   += 1;
                        $answer['queue_size']    += $v_item[1];
                    }else{
                        $res = SiteDuplicatorLibFilesystem::addLastLine( self::getJobFilePath('skipped_files'), $v_item);   //save skipped files
                        if( array_key_exists('msg', (array)$res) and $res['status'] == 0 ){
                            return $res;
                        }

                        $answer['skipped_count'] += 1;
                        $answer['skipped_size']  += $v_item[1];
                    }
                }

                //prepare queue for archives
                if( @(int)$serverStat['zip'] == 1 and $answer['queue_count'] > 0){
                    //clone queue of files
                    $resCopy = SiteDuplicatorLibFilesystem::copyFile( self::getJobFilePath('queue_files'), self::getJobFilePath('archive_files'));
                }

                //prepare queue for local backup
                if( @(int)$serverStat['zip'] == 1 and $answer['queue_count'] > 0 and $options['save_local_backup'] == 1){
                    //clone queue of files
                    $resCopy = SiteDuplicatorLibFilesystem::copyFile( self::getJobFilePath('queue_files'), self::getJobFilePath('local_backup_files'));
                }
            }

            return $answer;
        }

        public static function doDB($jobName, $options = array()){
            $jobRes     = true;
            $settings   = self::readJobSettings($jobName);

            //find table for export
            $table = '';
            if( !empty($settings['server']['db_stat']['tables']) ){
                foreach($settings['server']['db_stat']['tables'] as $k_item => $v_item){
                    if( !in_array($v_item['name'], @(array)$settings['processes']['exported_tables']) ){
                        $table = $v_item['name'];
                    }
                }
            }

            //do export
            if( !empty($table) ){
                $exportRes = SiteDuplicatorLibDb::dumpTable( self::getJobDbDir( $jobName, $table ), $table );
                if( is_array($exportRes) and array_key_exists('msg',$exportRes) and $exportRes['status'] == 0  ){
                    self::doJobLog($exportRes);
                    return $exportRes;
                }
                self::doJobLog($exportRes);

                //save table as exported
                $settings['processes']['exported_tables'][] = $table;
                SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('settings'), $settings);
            }

            //finish step
            if(empty($table)){
                //prepare queue
                $files = SiteDuplicatorLibFilesystem::listFiles(self::getJobDbDir($jobName), false);
                if( !empty($files) ){
                    SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('queue_db_files'), $files, 'by_line');

                    //prepare queue for archives
                    if( @(int)$settings['server']['server_stat']['zip']  == 1){
                        //clone queue of files
                        SiteDuplicatorLibFilesystem::copyFile( self::getJobFilePath('queue_db_files'), self::getJobFilePath('archive_db'));
                    }

                    $jobRes = false;
                }
            }

            return $jobRes;
        }

        public static function doArchives($jobName, $options = array()){
            $filesQueue         = self::getJobFilePath('archive_files');
            $dbQueue            = self::getJobFilePath('archive_db');
            $archiveQueue       = self::getJobFilePath('queue_archive_files');
            $localBackupQueue   = self::getJobFilePath('local_backup_files');
            $settings           = self::readJobSettings($jobName);
            $jobRes             = false;

            //work with files
            if( $jobRes === false and file_exists($filesQueue) and @(int)filesize($filesQueue) > 0 ){
                $processRes = SiteDuplicatorLibArchive::doFilesArchive($filesQueue, $archiveQueue, array(
                    'max_archive_size'      => ($settings['settings']['archive_size']*1024*1024),
                    'jobName'               => $jobName,
                    'max_execution_time'    => $options['max_execution_time'],
                ));

                //repeat process if necessary
                if($processRes === false){
                    $jobRes = true;
                }
            }

            //work with db
            if( $jobRes === false and file_exists($dbQueue) and @(int)filesize($dbQueue) > 0 ){
                $processRes = SiteDuplicatorLibArchive::doDbArchive($dbQueue, $archiveQueue, array(
                    'max_archive_size'      => ($settings['settings']['archive_size']*1024*1024),
                    'jobName'               => $jobName,
                    'max_execution_time'    => $options['max_execution_time'],
                ));

                //repeat process if necessary
                if($processRes === false){
                    $jobRes = true;
                }
            }

            //work with local backup
            if( $jobRes === false and file_exists($localBackupQueue) and @(int)filesize($localBackupQueue) > 0 ){
                $processRes = SiteDuplicatorLibArchive::doLocalBackup($dbQueue, array(
                    'jobName'               => $jobName,
                    'max_execution_time'    => $options['max_execution_time'],
                ));
            }

            return $jobRes;
        }

        public static function doTransfer($jobName, $options){
            $filesQueue     = self::getJobFilePath('queue_files');
            $dbQueue        = self::getJobFilePath('queue_db_files');
            $archiveQueue   = self::getJobFilePath('queue_archive_files');
            $settings       = self::readJobSettings($jobName);
            $continueJob    = false;

            //work with archives
            if( $continueJob === false and file_exists($archiveQueue) and @(int)filesize($archiveQueue) > 0 ){
                //mark table as transferred
                if( !empty($settings['processes']['exported_tables']) and empty($settings['processes']['transferred_tables']) ){
                    $settings['processes']['transferred_tables'] = $settings['processes']['exported_tables'];
                    SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('settings'), $settings);
                }

                $processRes = SiteDuplicatorLibFtp::transferFiles($archiveQueue, $options, 'archive');

                //repeat process if necessary
                if($processRes === false){
                    $continueJob = true;
                }
            }else{
                //work with files
                if( $continueJob === false and file_exists($filesQueue) and @(int)filesize($filesQueue) > 0 ){
                    $processRes = SiteDuplicatorLibFtp::transferFiles($filesQueue, $options, 'file');

                    //repeat process if necessary
                    if($processRes === false){
                        $continueJob = true;
                    }
                }

                //work with db
                if( $continueJob === false and file_exists($dbQueue) and @(int)filesize($dbQueue) > 0 ){
                    $processRes = SiteDuplicatorLibFtp::transferFiles($dbQueue, $options, 'db');

                    //repeat process if necessary
                    if($processRes === false){
                        $continueJob = true;
                    }
                }
            }

            return $continueJob;
        }

        public static function doRemoteUpdate($jobName, $options){
            $settings       = self::readJobSettings($jobName);
            $finishProcess  = true;
            $continueJob    = false;

            //upload installer
            if( @(int)$settings['processes']['installer'] == 0){
                $ftp        = SiteDuplicatorLibFtp::ftpFactory($options['ftp_username'], $options['ftp_password'], $options['ftp_host'], $options['ftp_path'], $options['ftp_port']);
                $ftpPutRes  = SiteDuplicatorLibFtp::ftpPutFile($ftp, self::getInstallerPath(), $options['ftp_path']);
                SiteDuplicatorLibFtp::closeFtp($ftp);

                $settings['processes']['installer'] = 1;
                SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('settings'), $settings);

                self::doJobLog(SiteDuplicatorLibUtility::logEvent(24));

                return true;   //repeat jobs for other update
            }

            //extract archive if present
            if(!empty($settings['processes']['transferred_archives']) ){
                $doExtract      = false;

                foreach($settings['processes']['transferred_archives'] as $k_item => $v_item){
                    if( !in_array($v_item, @(array)$settings['processes']['extracted_archives']) ){
                        $doExtract  = true;
                        $archiveName = $jobName.$v_item;

                        //save extracted archive
                        $settings['processes']['extracted_archives'][] = $v_item;
                        SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('settings'), $settings);

                        //run extract process
                        $restoreRes = SiteDuplicatorLibRequest::extractArchive($options, false, $archiveName);

                        if($restoreRes){
                            self::doJobLog(SiteDuplicatorLibUtility::logEvent(35,$archiveName));
                        }else{
                            self::doJobLog(SiteDuplicatorLibUtility::logEvent(36,$archiveName));
                        }

                        break;
                    }
                }

                if($doExtract === true){
                    return true;   //repeat jobs for other update
                }
            }

            //update config
            //if( !empty($settings['processes']['transferred_files']) ) {
            if (@(int)$settings['processes']['config'] == 0) {
                SiteDuplicatorLibRequest::updateConfig($options);

                $settings['processes']['config'] = 1;
                SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('settings'), $settings);

                self::doJobLog(SiteDuplicatorLibUtility::logEvent(25));

                return true;   //repeat jobs for other update
            }
            //}

            //restore tables
            if( !empty($settings['processes']['transferred_tables']) ){
                $doImport   = false;

                foreach($settings['processes']['transferred_tables'] as $k_item => $v_item){
                    if( !in_array($v_item, $settings['processes']['imported_tables']) ){
                        $doImport = true;

                        //save imported table
                        $settings['processes']['imported_tables'][] = $v_item;
                        SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('settings'), $settings);

                        //run import process
                        $restoreRes = SiteDuplicatorLibRequest::restoreTable($options, false, $v_item);

                        if($restoreRes){
                            self::doJobLog(SiteDuplicatorLibUtility::logEvent(26,$v_item));
                        }else{
                            self::doJobLog(SiteDuplicatorLibUtility::logEvent(30,$v_item));
                        }

                        break;
                    }
                }

                if($doImport == true){
                    return true;   //repeat jobs for other update
                }
            }

            //remove installer after all updates
            if($finishProcess and @(int)$settings['processes']['installer'] == 1){
                //remove installer
                $settings['processes']['installer'] = 0;
                SiteDuplicatorLibFilesystem::writeContentFile( self::getJobFilePath('settings'), $settings);

                $removeRes = SiteDuplicatorLibRequest::removeInstaller($options);
                if($removeRes){
                    SiteDuplicatorLibUtility::logEvent(38);
                }else{
                    SiteDuplicatorLibUtility::logEvent(37);
                }

                //remove tmp dirs
                $ftp = SiteDuplicatorLibFtp::getInstance();
                SiteDuplicatorLibFtp::ftpDelete($ftp, '/'.trim($options['ftp_path'],'/').'/archives/');
                SiteDuplicatorLibFtp::ftpDelete($ftp, '/'.trim($options['ftp_path'],'/').'/db/');
                SiteDuplicatorLibFtp::closeFtp($ftp);

                return false;
            }

            return $continueJob;
        }

        public static function doRemoving($jobName, $options = array()){
            $answer = array();

            //start step
            self::setJobStep(11);

            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('full_files'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('skipped_files'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('queue_files'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('db_files'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('queue_db_files'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('archive_files'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('archive_db'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('queue_archive_files'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('settings'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('logs'));
            SiteDuplicatorLibFilesystem::removeFile( self::getJobFilePath('local_backup_files'));
            SiteDuplicatorLibFilesystem::removeDir( self::getJobDbDir());
            SiteDuplicatorLibFilesystem::removeDir( PLUGIN_DIR_ARCHIVES, true );    //directory woth archives
            //TODO remove archives

            //finish step
            self::setJobStep(12);

            return $answer;
        }

        public static function doJobLog($data){
            $jobName = self::getJobName();
            if( is_array($data) ){
                $data['time'] = date('d.m.Y H:i:s');
            }else{
                $data = date('d.m.Y H:i:s').' '.$data;
            }
            SiteDuplicatorLibFilesystem::writeContentFile(self::getJobFilePath('logs'), $data, 'line', 'ab+');
        }

        //READER

        public static function readJobSettings($jobName = ''){
            $settings   = array();

            $fileData   = SiteDuplicatorLibFilesystem::readContentFile( self::getJobFilePath('settings') );
            if(is_array($fileData) and !isset($fileData['msg'])){
                $settings = (empty($fileData[0]) ? array() : $fileData[0]);
            }

            return $settings;
        }

        public static function readJobLog($lines = 0){
            $answer   = array();

            if($lines == 0){
                $fileData   = SiteDuplicatorLibFilesystem::readContentFile( self::getJobFilePath('logs') );
                if(is_array($fileData) and !isset($fileData['msg'])) {
                    $fileData = array_reverse($fileData);
                }
                $answer     = $fileData;
            }else{
                $fileData   = SiteDuplicatorLibFilesystem::readContentFileLasLine( self::getJobFilePath('logs'), $lines);
                if(is_array($fileData) and !isset($fileData['msg'])) {
                    //$fileData = array_reverse($fileData);
                }
                $answer     = $fileData;
            }

            return $answer;
        }

        //HELPER

        public static function getJobName($forceNewJob = false, $options = array()){
            $options    = (empty($options) ? SiteDuplicatorLibUtility::plugin_options('get') : $options);
            $jobName    = ( ($forceNewJob or empty($options['active_job'])) ? 'job_'.date('Y-m-d_H-i-s').'_' : $options['active_job']);
            return $jobName;
        }

        public static function setJobName($name = 0){
            $options = SiteDuplicatorLibUtility::plugin_options('get');
            $options['active_job'] = $name;
            SiteDuplicatorLibUtility::plugin_options('add', $options);
        }

        public static function getJobStatus($options = array()){
            $options    = (empty($options) ? SiteDuplicatorLibUtility::plugin_options('get') : $options);
            $jobStatus  = @(int)$options['active_job_status'];
            return $jobStatus;
        }

        public static function setJobStatus($status = 0){
            $options = SiteDuplicatorLibUtility::plugin_options('get');
            $options['active_job_status'] = $status;
            SiteDuplicatorLibUtility::plugin_options('add', $options);
        }

        public static function getJobStep($options = array()){
            $options    = (empty($options) ? SiteDuplicatorLibUtility::plugin_options('get') : $options);
            $jobStep    = @(int)$options['active_job_step'];
            return $jobStep;
        }

        public static function setJobStep($step = 0){
            $options = SiteDuplicatorLibUtility::plugin_options('get');
            $options['active_job_step'] = $step;
            SiteDuplicatorLibUtility::plugin_options('add', $options);
        }

        public static function getJobFilePath($fileName, $jobName = ''){
            $jobName        = (empty($jobName) ? self::getJobName() : $jobName);
            $fileJobPath    = SiteDuplicatorLibFilesystem::normalizePath(PLUGIN_DIR_JOBS.'/'.$jobName.$fileName);

            return $fileJobPath;
        }

        public static function getJobDbDir($jobName = '', $fileName = ''){
            $jobName    = (empty($jobName) ? self::getJobName() : $jobName);
            $path       = SiteDuplicatorLibFilesystem::normalizePath(PLUGIN_DIR_JOBS.'/'.$jobName.'db');
            if( !empty($fileName) ){
                $path = SiteDuplicatorLibFilesystem::normalizePath($path.'/'.$fileName,true);
            }

            return $path;
        }

        public static function getInstallerPath($jobName = '', $fileName = ''){
            $jobName    = (empty($jobName) ? self::getJobName() : $jobName);
            $path       = SiteDuplicatorLibFilesystem::normalizePath(PLUGIN_DIR_LIBS.'/sd_installer.php');

            return $path;
        }
    }
}