<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if ( ! class_exists( 'SiteDuplicatorLibArchive' ) ) {
    class SiteDuplicatorLibArchive
    {

        /**
         * @param string $filesQueuePath        - path on file with list of files for archiving
         * @param string $archivesQueuePath     - path on file with list created archives
         * @param array $options                - options for work
         * @return bool                         - false - we should repeat archiving, true - all necessary files was archived
         */
        public static function doFilesArchive($filesQueuePath, $archivesQueuePath, $options){
            $zipSizeMax         = $options['max_archive_size'];
            $jobName            = $options['jobName'];
            $archivePrefix      = 'archive_files_';

            $maxExecTime        = @(int)ini_get('max_execution_time');
            $maxExecTime        = (empty($maxExecTime) ? $options['max_execution_time'] : $maxExecTime);
            $timePast           = 0;
            $operationTime      = 0;

            $continueWorkFlag   = true;
            $processFinished    = false;

            //get last archive
            $archive        = SiteDuplicatorLibFilesystem::readContentFileLasLine( $archivesQueuePath, 1, '', true);
            $archive        = (empty($archive[0]) ? array(0, $archivePrefix.'1') : $archive[0]); //array('size of archive', 'name of archive')
            //check that it necessary archive
            if( strpos($archive[1],$archivePrefix) === false ){
                SiteDuplicatorLibFilesystem::addLastLine($archivesQueuePath, $archive); //return archive in queue

                $archive = array(0, $archivePrefix.'1');
            }
            $archiveSize    = $archive[0];
            $archiveName    = $archive[1];
            $archivePath    = self::getArchivePath($archiveName, $jobName);

            //init ZipArchive
            $zip        = new ZipArchive();

	        $dir = dirname($archivePath);
	        mkdir($dir, 0777);

            $openRes    = $zip->open( $archivePath, ZipArchive::CREATE );
            if($openRes !== true){
                SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(32, $archivePath));
                $continueWorkFlag = false;
            }

            //work with files
            while( ($timePast+$operationTime) < $maxExecTime and $continueWorkFlag === true){
                $startTime = time();

                //file
                $line = SiteDuplicatorLibFilesystem::readContentFileLasLine( $filesQueuePath, 1, '', true );//remove line in any case, for prevent loop
                $line = @$line[0];

                if( !empty($line) ){
                    //prepare file
                    $filePath           = SiteDuplicatorLibFilesystem::makeFilePath($line[0],$line[6],$line[8]);
                    $filePath           = SiteDuplicatorLibFilesystem::normalizePath($filePath);
                    $fileSize           = $line[1];
                    $fileSizeCompress   = @ceil($fileSize/4);
                    $filePathInZip      = str_replace(ABSPATH, '', $filePath);

                    //add file
                    $zipRes    = $zip->addFile($filePath, $filePathInZip);
                    if(!$zipRes){
                        $zipRes = $zip->addFromString( $filePathInZip, file_get_contents( $filePath ) );
                    }

                    if($zipRes) {
                        $archiveSize += $fileSizeCompress;
                    } else {
                        SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(32, $archivePath));
                        break;
                    }

                    //switch archives
                    if(!empty($zipSizeMax) and $archiveSize > $zipSizeMax){
                        //close current archive
                        $zip->close();
                        $openRes = false;

                        //update info about current archive
                        clearstatcache();
                        $archiveSize = @(int)filesize($archivePath);
                        SiteDuplicatorLibFilesystem::addLastLine( $archivesQueuePath, array($archiveSize, $archiveName));

                        //prepare new archive
                        $newArchiveName = explode($archivePrefix, $archiveName);
                        $newArchiveName = $archivePrefix.($newArchiveName[1]+1);
                        SiteDuplicatorLibFilesystem::addLastLine( $archivesQueuePath, array(0, $newArchiveName));

                        SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(33, $archivePath));

                        break;
                    }

                    $operationTime = (time() -$startTime);
                    $timePast += $operationTime;
                }else{
                    $timePast = $maxExecTime;
                    $continueWorkFlag   = false;
                    $processFinished    = true;
                }
            }

            //close archive if still opened
            if($openRes === true){
                //close current archive
                $zip->close();
                $openRes = false;

                //update info about current archive
                clearstatcache();
                $archiveSize = @(int)filesize($archivePath);
                SiteDuplicatorLibFilesystem::addLastLine( $archivesQueuePath, array($archiveSize, $archiveName));
            }

            return $processFinished;
        }

        /**
         * @param string $dbQueuePath           - path on file with list of files for archiving
         * @param string $archivesQueuePath     - path on file with list created archives
         * @param array $options                - options for work
         * @return bool                         - false - we should repeat archiving, true - all necessary files was archived
         */
        public static function doDbArchive($dbQueuePath, $archivesQueuePath, $options){
            $zipSizeMax         = $options['max_archive_size'];
            $jobName            = $options['jobName'];
            $archivePrefix      = 'archive_db_';

            $maxExecTime        = @(int)ini_get('max_execution_time');
            $maxExecTime        = (empty($maxExecTime) ? $options['max_execution_time'] : $maxExecTime);
            $timePast           = 0;
            $operationTime      = 0;

            $continueWorkFlag   = true;
            $processFinished    = false;

            //get last archive
            $archive        = SiteDuplicatorLibFilesystem::readContentFileLasLine( $archivesQueuePath, 1, '', true);
            $archive        = (empty($archive[0]) ? array(0, $archivePrefix.'1') : $archive[0]); //array('size of archive', 'name of archive')
            //check that it necessary archive
            if( strpos($archive[1],$archivePrefix) === false ){
                SiteDuplicatorLibFilesystem::addLastLine($archivesQueuePath, $archive); //return archive in queue

                $archive = array(0, $archivePrefix.'1');
            }
            $archiveSize    = $archive[0];
            $archiveName    = $archive[1];
            $archivePath    = self::getArchivePath($archiveName, $jobName);

            //init ZipArchive
            $zip        = new ZipArchive();
            $openRes    = $zip->open( $archivePath, ZipArchive::CREATE );
            if($openRes !== true){
                SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(32, $archivePath));
                $continueWorkFlag = false;
            }

            //work with files
            while( ($timePast+$operationTime) < $maxExecTime and $continueWorkFlag === true){
                $startTime = time();

                //file
                $line = SiteDuplicatorLibFilesystem::readContentFileLasLine( $dbQueuePath, 1, '', true );//remove line in any case, for prevent loop
                $line = @$line[0];

                if( !empty($line) ){
                    //prepare file
                    $filePath           = SiteDuplicatorLibFilesystem::makeFilePath($line[0],$line[6],$line[8]);
                    $filePath           = SiteDuplicatorLibFilesystem::normalizePath($filePath);
                    $fileSize           = $line[1];
                    $fileSizeCompress   = @ceil($fileSize/4);
                    //$filePathInZip      = str_replace(ABSPATH, '', $filePath);
                    $filePathInZip      = basename($filePath);

                    //add file
                    $zipRes    = $zip->addFile($filePath, $filePathInZip);
                    if($zipRes){
                        $archiveSize += $fileSizeCompress;
                    }else{
                        SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(32, $archivePath));
                        break;
                    }

                    //switch archives
                    if(!empty($zipSizeMax) and $archiveSize > $zipSizeMax){
                        //close current archive
                        $zip->close();
                        $openRes = false;

                        //update info about current archive
                        clearstatcache();
                        $archiveSize = @(int)filesize($archivePath);
                        SiteDuplicatorLibFilesystem::addLastLine( $archivesQueuePath, array($archiveSize, $archiveName));

                        //prepare new archive
                        $newArchiveName = explode($archivePrefix, $archiveName);
                        $newArchiveName = $archivePrefix.($newArchiveName[1]+1);
                        SiteDuplicatorLibFilesystem::addLastLine( $archivesQueuePath, array(0, $newArchiveName));

                        SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(33, $archivePath));

                        break;
                    }

                    $operationTime = (time() -$startTime);
                    $timePast += $operationTime;
                }else{
                    $timePast = $maxExecTime;
                    $continueWorkFlag   = false;
                    $processFinished    = true;
                }
            }

            //close archive if still opened
            if($openRes === true){
                //close current archive
                $zip->close();
                $openRes = false;

                //update info about current archive
                clearstatcache();
                $archiveSize = @(int)filesize($archivePath);
                SiteDuplicatorLibFilesystem::addLastLine( $archivesQueuePath, array($archiveSize, $archiveName));
            }

            return $processFinished;
        }

        /**
         * @param string $filesQueuePath        - path on file with list of files for archiving
         * @param array $options                - options for work
         * @return bool                         - false - we should repeat archiving, true - all necessary files was archived
         */
        public static function doLocalBackup($filesQueuePath, $options){
            $jobName            = $options['jobName'];

            $maxExecTime        = @(int)ini_get('max_execution_time');
            $maxExecTime        = (empty($maxExecTime) ? $options['max_execution_time'] : $maxExecTime);
            $timePast           = 0;
            $operationTime      = 0;

            $continueWorkFlag   = true;
            $processFinished    = false;

            $archivePath = PLUGIN_DIR_TMP.'/'.$jobName.'local_backup.zip';

            //init ZipArchive
            $zip        = new ZipArchive();
            $openRes    = $zip->open( $archivePath, ZipArchive::CREATE );
            if($openRes !== true){
                SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(32, $archivePath));
                $continueWorkFlag = false;
            }

            //work with files
            while( ($timePast+$operationTime) < $maxExecTime and $continueWorkFlag === true){
                $startTime = time();

                //file
                $line = SiteDuplicatorLibFilesystem::readContentFileLasLine( $filesQueuePath, 1, '', true );//remove line in any case, for prevent loop
                $line = @$line[0];

                if( !empty($line) ){
                    //prepare file
                    $filePath           = SiteDuplicatorLibFilesystem::makeFilePath($line[0],$line[6],$line[8]);
                    $filePath           = SiteDuplicatorLibFilesystem::normalizePath($filePath);
                    $filePathInZip      = str_replace(ABSPATH, '', $filePath);

                    //add file
                    $zipRes    = $zip->addFile($filePath, $filePathInZip);
                    if(!$zipRes){
                        SiteDuplicatorLibJob::doJobLog(SiteDuplicatorLibUtility::logEvent(32, $archivePath));
                        break;
                    }

                    $operationTime = (time() -$startTime);
                    $timePast += $operationTime;
                }else{
                    $timePast = $maxExecTime;
                    $continueWorkFlag   = false;
                    $processFinished    = true;
                }
            }

            //close archive if still opened
            if($openRes === true){
                //close current archive
                $zip->close();
            }

            return $processFinished;
        }

        public static function getArchivePath($archiveName, $jobName){
            $fileJobPath    = SiteDuplicatorLibFilesystem::normalizePath(PLUGIN_DIR_ARCHIVES.'/'.$jobName.$archiveName.'.zip');

            return $fileJobPath;
        }
    }
}