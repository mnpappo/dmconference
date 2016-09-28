<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if ( ! class_exists( 'SiteDuplicatorLibFilesystem' ) ) {
    class SiteDuplicatorLibFilesystem
    {

        public static $dirs     = array();
        public static $files    = array();

        public static function listFiles($path = '.', $recursive = true, $options = array()){
            $items      = array();

            $maxFileSize    = @$options['maxFileSize'];    // 0 - get any file
            $skipFileExt    = @(string)$options['skipFileExt'];  // array of extensions to skip
            $maxFileSize    = (!empty($maxFileSize) ? ceil($maxFileSize * 1024 * 1024) : 0);
            $skipFileExt    = (!empty($skipFileExt) ? explode(';',$skipFileExt) : array());
            $skipDirs       = (empty($options['skipDirs']) ? array() : $options['skipDirs']);


            $path       = self::normalizePath($path);
            $dirsItems  = self::listDirs($path, $recursive, $options);

            //prepare files
            if( !empty($dirsItems) ){
                foreach($dirsItems as $k_dir => $v_dir){
                    if( @(int)$v_dir['r_f'] == 1 and @(int)$v_dir['c_f'] == 1 and @(int)$v_dir['s_f'] == 0 ){
                        $files = self::filesIterator($k_dir);
                        if( !empty($files) ){
                            foreach($files as $k_file => $v_file){
                                $fileInfo       = @pathinfo($v_file);
                                $fileExtension  = @(string)$fileInfo['extension'];
                                $fileSize       = @filesize($v_file);
                                $fileReadable   = (is_readable($v_file) ? 1 : 0);
                                $fileWritable   = (is_writable($v_file) ? 1 : 0);
                                $fileCorrect    = (self::checkPath($v_file,'file') ? 1 : 0);
                                $fileSkip       = 0;
                                if( !empty($fileExtension) and (!empty($skipFileExt) and in_array($fileExtension, $skipFileExt)) ){
                                    $fileSkip = 1;
                                }
                                if( !empty($maxFileSize) and $fileSize > $maxFileSize ){
                                    $fileSkip = 1;
                                }
                                if( !$fileReadable ){
                                    $fileSkip = 1;
                                }

                                /*self::$files[(string)$v_file] = array(
                                    'e'         => $fileExtension,          // extension
                                    's'         => $fileSize,               // size
                                    'rf'        => $fileReadable,           // 1 - file is readable
                                    'wf'        => $fileWritable,           // 1 - file is writable
                                    'cf'        => $fileCorrect,            // 1 - file has correct name and path
                                    'sf'        => $fileSkip,               // 1 - file should be skipped
                                    'n'         => @$fileInfo['filename'],  // filename without extension
                                    'md5'       => @md5_file($v_file),      // empty for nor readable files
                                    'd'         => $k_dir,                  // dir of file
                                    //'path'      => utf8_encode($v_file)
                                );*/
                                self::$files[(string)$v_file] = array(
                                    $fileExtension,          // extension
                                    $fileSize,               // size
                                    $fileReadable,           // 1 - file is readable
                                    $fileWritable,           // 1 - file is writable
                                    $fileCorrect,            // 1 - file has correct name and path
                                    $fileSkip,               // 1 - file should be skipped
                                    @$fileInfo['filename'],  // filename without extension
                                    @md5_file($v_file),      // empty for nor readable files
                                    $k_dir,                  // dir of file
                                );
                            }
                        }
                    }
                }
                $items = self::$files;
            }

            return $items;
        }

        public static function listDirs($path = '.', $recursive = true, $options = array()){
            $items  = array();
            $path   = self::normalizePath($path);

            //options
            $skipDirs = (empty($options['skipDirs']) ? array() : $options['skipDirs']);

            $dirsItems   = self::dirsIterator($path, $recursive, $skipDirs);
            $dirsItems[] = $path;

            if( !empty($dirsItems) ){
                foreach($dirsItems as $k_dir => $v_dir){
                    self::$dirs[(string)$v_dir] = array(
                        'path'  => utf8_encode($v_dir),
                        'r_f'   => ( is_readable($v_dir) ? 1 : 0),              // 1 - dir is readable
                        'c_f'   => ( self::checkPath($v_dir, 'dir') ? 1 : 0),   // 1 - dir has correct name and path
                        's_f'   => 0                                            // 1 - folder should be skipped
                    );
                }
                $items = self::$dirs;
            }

            return $items;
        }

        public static function dirsIterator($path = '.', $recursive = true, $skipDirs = array()){
            $items          = array();
            $path           = self::normalizePath($path);

            $handle = @opendir($path);
            if($handle){
                while( ($file = readdir($handle)) !== false ){
                    if($file != '.' and $file != '..'){
                        $fullPath = self::normalizePath($path.'/'.$file);
                        if ( is_dir($fullPath) ){

                            //exclude some dirs
                            if( empty($skipDirs) or !in_array($fullPath, $skipDirs) ){
                                if($recursive){
                                    $items = array_merge($items, self::dirsIterator($fullPath,$recursive,$skipDirs));
                                }
                                $items[] = $fullPath;
                            }
                        }
                    }
                }
                closedir($handle);
            }
            return $items;
        }

        public static function filesIterator($path = '.'){
            $items          = array();
            $path           = self::normalizePath($path);

            $handle = @opendir($path);
            if($handle){
                while( ($file = readdir($handle)) !== false ){
                    if($file != '.' and $file != '..'){
                        $fullPath = self::normalizePath($path.'/'.$file);
                        if ( is_file($fullPath) ){
                            $items[] = $fullPath;
                        }
                    }
                }
                closedir($handle);
            }
            return $items;
        }

        public static function normalizePath($path, $makeSave = false){
            $saveRegex = array('#[^A-Za-z0-9_\\\/\(\)\[\]\{\}\#\$\^\+\.\'~`!@&=;,-]#');

            $path   = str_replace(DIRECTORY_SEPARATOR, '/', (string)$path);
            $path   = str_replace(array('/////','////','///', '//'), '/', $path);
            $path   = rtrim($path,'/');
            $path   = ($makeSave ? preg_replace($saveRegex, '', $path) : $path);

            return $path;
        }

        /**
         * @param string $path
         * @param string $mode - dir or file
         *
         * @return bool
         */
        public static function checkPath($path, $mode = 'dir'){
            $name = trim( basename($path) );

            if(strlen($path) > 250){
                return false;
            }
            if( empty($name) or preg_match('/(\/|\*|\?|\>|\<|\:|\\|\|)/', $name)  or preg_match('/[^\x20-\x7f]/', $name) ){

            }

            if($mode == 'dir'){
                if( strrpos($name, '.') == (strlen($name) - 1) and substr($name, -1) == '.' ){
                    return false;
                }
            }

            if($mode == 'file'){

            }

            return true;
        }

        public static function makeFilePath($extension,$filename,$path){
            $path       = str_replace(ABSPATH, '', $path);
            $filePath   =  ABSPATH.'/'.$path.'/'.$filename;
            $filePath   =  ( !empty($extension) ? $filePath.'.'.$extension : $filePath);
            $filePath   = SiteDuplicatorLibFilesystem::normalizePath($filePath);

            return $filePath;
        }

        /**
         * Create files with data
         *
         * @param string $filePath
         * @param array $data
         * * @param string $writer all -- save full content, by_line - iterate content by lines
         * @param string $writeMode http://php.net/manual/en/function.fopen.php
         *
         * @return array
         */
        public static function writeContentFile($filePath, $data, $writer = 'all', $writeMode = 'wb+'){
            $filePath   = self::normalizePath($filePath,true);
            $writeMode  = ( empty($writeMode) ? 'wb+' : $writeMode);

            //check and prepare folder
            $pathInfo   = pathinfo($filePath);
            if( !is_dir($pathInfo['dirname']) ){
                @mkdir($pathInfo['dirname'],0755,true);
                @chmod($pathInfo['dirname'], 0755);
            }

            //check again
            if( !is_dir($pathInfo['dirname']) or !is_writable($pathInfo['dirname']) ){
                return SiteDuplicatorLibUtility::logEvent(1, $pathInfo['dirname']);
            }

            //check and prepare file
            if( !file_exists($filePath) ){
                $handle = @fopen($filePath, $writeMode);
                if( $handle ){
                    @fwrite($handle, '');
                    @fclose($handle);
                    @chmod($filePath, 0755);
                }

            }

            //check file again
            if( !file_exists($filePath) ){
                return SiteDuplicatorLibUtility::logEvent(2, $filePath);
            }
            if( !is_readable($filePath) ){
                return SiteDuplicatorLibUtility::logEvent(3, $filePath);
            }

            //write
            if( class_exists('SplFileObject') ){
                try{
                    $file = @new SplFileObject($filePath, $writeMode);
                    if($file){
                        if($writer == 'all'){
                            $data = (is_array($data) ? json_encode($data) : $data);
                            if (!$file->fwrite($data)) {
                                return SiteDuplicatorLibUtility::logEvent(3);
                            }
                        }
                        if($writer == 'line'){
                            $dataLine = (is_array($data) ? json_encode($data) : $data)."\n";
                            if (!$file->fwrite($dataLine)) {
                                return SiteDuplicatorLibUtility::logEvent(3);
                            }
                        }
                        if($writer == 'by_line'){
                            if( !empty($data) and is_array($data) ){
                                foreach($data as $k_item => $v_item){
                                    $dataLine = (is_array($v_item) ? json_encode($v_item) : $v_item)."\n";
                                    if (!$file->fwrite($dataLine)) {
                                        return SiteDuplicatorLibUtility::logEvent(3);
                                        break;
                                    }
                                }
                            }
                        }
                        $file = null;
                    }else{
                        return SiteDuplicatorLibUtility::logEvent(2, $filePath);
                    }
                }catch (Exception $e){
                    return SiteDuplicatorLibUtility::logEvent(3, $filePath);
                }

            }else{
                $handle = @fopen($filePath, $writeMode);
                if($handle){
                    if($writer == 'all'){
                        $data = (is_array($data) ? json_encode($data) : $data);
                        if (fwrite($handle, $data) === FALSE) {
                            return SiteDuplicatorLibUtility::logEvent(3);
                        }
                    }
                    if($writer == 'line'){
                        $dataLine = (is_array($data) ? json_encode($data) : $data)."\n";
                        if (fwrite($handle, $dataLine) === FALSE) {
                            return SiteDuplicatorLibUtility::logEvent(3);
                        }
                    }
                    if($writer == 'by_line'){
                        if( is_array($data) ){
                            foreach($data as $k_item => $v_item){
                                $dataLine = (is_array($v_item) ? json_encode($v_item) : $v_item)."\n";
                                if (fwrite($handle, $dataLine) === FALSE) {
                                    return SiteDuplicatorLibUtility::logEvent(3);
                                }
                            }
                        }
                    }

                    fclose($handle);
                }else{
                    return SiteDuplicatorLibUtility::logEvent(2, $filePath);
                }
            }

            return SiteDuplicatorLibUtility::logEvent(4, $filePath);
        }

        public static function addLastLine($filePath, $data, $writer = 'line'){
            return self::writeContentFile($filePath, $data, $writer, 'ab+');
        }

        /**
         * Read full content of file
         *
         * @param string $filePath
         * @param string $readMode
         *
         * @return array
         */
        public static function readContentFile($filePath, $readMode = 'rb'){
            $items      = array();
            $filePath   = self::normalizePath($filePath);
            $readMode   = (empty($readMode) ? 'rb' : $readMode);

            if( !file_exists($filePath) or !is_readable($filePath) ){
                return SiteDuplicatorLibUtility::logEvent(5, $filePath);
            }

            if( class_exists('SplFileObject') ){
                $file = @new SplFileObject($filePath, $readMode);
                if($file){
                    //$file->setFlags(SplFileObject::SKIP_EMPTY);
                    while (!$file->eof()) {
                        $line       = @$file->fgets();
                        $dataLine   = @json_decode($line, true);
                        $dataLine   = ($dataLine ? $dataLine : $line);
                        if(!empty($dataLine)){
                            $items[] = $dataLine;
                        }
                    }
                    $file = null;
                }else{
                    return SiteDuplicatorLibUtility::logEvent(5, $filePath);
                }
            }else{
                $lines = @file($filePath, FILE_SKIP_EMPTY_LINES);
                if($lines === false){
                    if( !empty($lines) and is_array($lines) ){
                        foreach ($lines as $k_line => $v_line) {
                            $dataLine   = @json_decode($v_line, true);
                            $dataLine   = ($dataLine ? $dataLine : $v_line);
                            if(!empty($dataLine)){
                                $items[] = $dataLine;
                            };
                        }
                    }
                }else{
                    return SiteDuplicatorLibUtility::logEvent(5, $filePath);
                }
            }

            return $items;
        }

        //read line from file. LAST LINES
        public static function readContentFileLasLine($filePath, $lines = 1, $readMode = 'rb+', $remove = false){
            $items      = array();
            $filePath   = self::normalizePath($filePath);
            $readMode   = (empty($readMode) ? 'rb+' : $readMode);

            /*if( class_exists('SplFileObject') ){
                $file = @new SplFileObject($filePath, $readMode);
                if($file){

                    $file = null;
                }else{
                    return SiteDuplicatorLibUtility::logEvent(5, $filePath);
                }
            }else{*/
            $handle     = @fopen($filePath, $readMode);
            $buffer     = 256;
            $output     = '';
            $outputSize = 0;
            if ($handle) {
                if (flock($handle, LOCK_EX)) {
                    fseek($handle, -1, SEEK_END);                               // jump to last char
                    if(fread($handle, 1) != "\n"){$lines -= 1;}                 //case when last line empty
                    while( ftell($handle) > 0 && $lines >= 0) {
                        $seek = min(ftell($handle), $buffer);                   //calculate size of chunk
                        fseek($handle, -$seek, SEEK_CUR);                       //to start of chunk
                        $output = ($chunk = fread($handle, $seek)) . $output;   //read chunk
                        fseek($handle, -mb_strlen($chunk, '8bit'), SEEK_CUR);   //jump back to start of chunk
                        $lines -= substr_count($chunk, "\n");                   //decrease line numbers
                    }

                    //prepare lines
                    while ($lines++ < 0) {
                        $output     = substr($output, strpos($output, "\n") + 1);   //find last end of line and remove all text before
                    }
                    $outputSize = @(int)mb_strlen($output, '8bit');                  //calculate size of last lines
                    if( !empty($output) ){
                        $output = explode("\n", $output);
                        foreach($output as $k_item => $v_item){
                            $dataLine   = @json_decode($v_item, true);
                            $dataLine   = ($dataLine ? $dataLine : $v_item);
                            if( !empty($dataLine) ){
                                $items[] = $dataLine;
                            }
                        }
                    }

                    //cut last lines from file
                    if($outputSize > 0 and $remove){
                        clearstatcache();
                        $newSize = (@(int)filesize($filePath) - $outputSize);
                        ftruncate($handle, ($newSize < 0 ? 0 : $newSize));
                        rewind($handle);
                    }

                    flock($handle, LOCK_UN);
                }
                fclose($handle);
            }else{
                return SiteDuplicatorLibUtility::logEvent(5, $filePath);
            }
            //}

            return $items;
        }

        //read line from file. LAST LINES
        //DEPRECATED
        public static function readContentFileLasLine1($filePath, $linesCount = 1, $readMode = 'rb+', $remove = false){
            $items      = array();
            $lines      = array();
            $filePath   = self::normalizePath($filePath);
            $readMode   = (empty($readMode) ? 'rb+' : $readMode);

            $handle     = @fopen($filePath, $readMode);
            if ($handle) {
                if (flock($handle, LOCK_EX)) {
                    $cursor = -1;
                    $line   = '';

                    fseek($handle, $cursor, SEEK_END);
                    $char = fgetc($handle);

                    //get lines
                    $outputSize = 0;
                    for($i = 0; $i < $linesCount; $i++){
                        $line = '';

                        //trim
                        while ($char === "\n") {
                            fseek($handle, $cursor--, SEEK_END);
                            $char = fgetc($handle);
                        }

                        //Read until the start of file or first newline char
                        while ($char !== false && $char !== "\n") {
                            //Prepend the new char
                            $line = $char . $line;
                            fseek($handle, $cursor--, SEEK_END);
                            $char = fgetc($handle);
                        }

                        $lines[] = $line;
                        $outputSize += @(int)mb_strlen($line)+1;
                    }


                    //remove lines
                    if($remove){
                        clearstatcache();
                        $newSize    = (filesize($filePath) - $outputSize);
                        ftruncate($handle, $newSize);
                        rewind($handle);
                    }

                    flock($handle, LOCK_UN);
                }
                fclose($handle);
            }else{
                return SiteDuplicatorLibUtility::logEvent(5, $filePath);
            }

            //prepare lines
            if( !empty($lines) ){
                foreach($lines as $k_line => $v_line){
                    $dataLine   = @json_decode($v_line, true);
                    $dataLine   = ($dataLine ? $dataLine : $v_line);
                    if( !empty($dataLine) ){
                        $items[] = $dataLine;
                    }
                }
            }

            return $items;
        }
        public static function readLastFileLines($filePath, $linesCount = 1, $readMode = 'rb+', $remove = false){
            $items = array();

            for($i = 0; $i < $linesCount; $i++){
                $line = self::readLastFileLine($filePath, $readMode, $remove);

                if( !empty($line) and is_string($line) ){
                    $dataLine   = @json_decode($line, true);
                    $dataLine   = ($dataLine ? $dataLine : $line);
                    if( !empty($dataLine) ){
                        $items[] = $dataLine;
                    }
                }
            }


            return $items;
        }
        public static function readLastFileLine($filePath, $readMode = 'rb+', $remove = false){
            $line       = '';
            $filePath   = self::normalizePath($filePath);
            $readMode   = (empty($readMode) ? 'rb+' : $readMode);

            $handle     = @fopen($filePath, $readMode);
            if ($handle) {
                if (flock($handle, LOCK_EX)) {
                    $cursor = -1;

                    //go to end
                    fseek($handle, $cursor, SEEK_END);
                    $char = fgetc($handle);

                    //trim
                    while ($char === "\n") {
                        fseek($handle, $cursor--, SEEK_END);
                        $char = fgetc($handle);
                    }

                    //Read until the start of file or first newline char
                    while ($char !== false && $char !== "\n") {
                        //Prepend the new char
                        $line = $char . $line;
                        fseek($handle, $cursor--, SEEK_END);
                        $char = fgetc($handle);
                    }

                    //remove lines
                    if($remove){
                        $outputSize = @(int)mb_strlen($line)+1;
                        $newSize    = (filesize($filePath) - $outputSize);
                        ftruncate($handle, $newSize);
                        rewind($handle);
                    }

                    flock($handle, LOCK_UN);
                }
                fclose($handle);
            }else{
                return SiteDuplicatorLibUtility::logEvent(5, $filePath);
            }

            return $line;
        }

        public static function removeFile($filePath){
            $filePath = self::normalizePath($filePath);

            if( file_exists($filePath) ){
                if( !@unlink($filePath) ){
                    @chmod($filePath, 0755);
                    if( !@unlink($filePath) ){
                        @chmod($filePath, 0777);
                        if( !@unlink($filePath) ){
                            return SiteDuplicatorLibUtility::logEvent(21, $filePath);
                        }
                    }
                }
            }

            return SiteDuplicatorLibUtility::logEvent(20, $filePath);
        }

        public static function removeDir($path, $onlyInside = false){
            $path = self::normalizePath($path);
            if (is_dir($path)) {
                //remove files from dir
                $files = self::filesIterator($path);
                if (!empty($files)) {
                    foreach ($files as $k_item => $v_item) {
                        if (is_file($v_item)) {
                            self::removeFile($v_item);
                        }
                    }
                }

                //remove dirs from dir
                $dirs = self::dirsIterator($path, false);
                if (!empty($dirs)) {
                    foreach ($dirs as $k_item => $v_item) {
                        self::removeDir($v_item);
                    }
                }

                //remove dir
                if(!$onlyInside){
                    @rmdir($path);
                }
            }

            return true;
        }

        public static function checkDir($path){
            $path = self::normalizePath($path);

            if(!file_exists($path) ){
                @mkdir($path,0755,true);
            }

            $answer = array(
                'exists'    => (file_exists($path) ? 1 : 0),
                'writable'  => (is_writable($path) ? 1 : 0),
                'readable'  => (is_readable($path) ? 1 : 0)
            );

            return $answer;
        }

        public static function copyFile($pathFrom, $pathTo){
            return @copy($pathFrom,$pathTo);
        }
    }
}