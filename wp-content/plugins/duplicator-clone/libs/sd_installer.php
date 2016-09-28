<?php
//vars
$mode = $_GET['mode'];
$pass = $_GET['pass'];

//stop any request if file in plugin
if( @file_exists(dirname(__FILE__).'/../duplicator-clone.php') ){exit;}

//settings
$passReady  = 'gtr87sdfSDFoiwer';
$answer     = array();


if( empty($mode) or empty($pass) or $pass !== $passReady ){
    exit;
}

//ping for this file
if( $mode == 'check_me' ){
    $answer = array('status' => 1, 'msg' => 'file_exist', 'mode' => 'check_me');
}

//kill this file
if( $mode == 'kill_me' ){
    @chmod(__FILE__, 0777);
    @unlink(__FILE__);
    $answer = array('status' => 1, 'msg' => 'file_was_deleted', 'mode' => 'kill_me');
}

//check capability create any files in root dir
if( $mode == 'writable_root_dir' ){
    $is_writable = @(int)is_writable( dirname(__FILE__) );
    $answer = array(
        'status'    => $is_writable, 'msg' => ($is_writable ? 'root_dir_is_writable' : 'root_dir_not_writable'), 'mode' => 'writable_root_dir'
    );
}

//test connection to mysql
if( $mode == 'test_mysql' ){
	error_reporting(0);
	
    $dbConnection = array(
        'host'      => @trim($_POST['host']),
        'user'      => @trim($_POST['user']),
        'password'  => @trim($_POST['password']),
        'db'        => @trim($_POST['db'])    ,
        'port'      => @trim($_POST['port'])    ,
	    
    );
	

	

    if( empty($dbConnection['host']) or empty($dbConnection['user']) or empty($dbConnection['password'])   or empty($dbConnection['db']) ){
        $answer = array('status' => 0, 'msg' => 'could_not_connect_to_mysql', 'mode' => 'test_mysql');
    }else{
        //try connect
        $mysqli = connectToMysql($dbConnection['host'], $dbConnection['user'], $dbConnection['password'], $dbConnection['db'], $dbConnection['port']);
        
	    if( is_array($mysqli) and isset($mysqli['status']) and $mysqli['status'] == 0){
            $answer = array('status' => $mysqli['status'], 'msg' => $mysqli['msg'], 'mode' => 'test_mysql');
        }else{
            $answer = array('status' => 1, 'msg' => 'mysql_connection_ok', 'mode' => 'test_mysql');
		    @$mysqli->close();
        }
        
    }
}

//restore table
if( $mode == 'restore_table' ){
    $dbConnection = array(
        'host'          => @trim($_POST['host']),
        'user'          => @trim($_POST['user']),
        'password'      => @trim($_POST['password']),
        'db'            => @trim($_POST['db']),
        'table'         => @trim($_POST['table']),
        'prefix'        => @trim($_POST['prefix']),
        'old_prefix'    => @trim($_POST['old_prefix']),
        'old_url'       => @trim($_POST['old_url']),
        'url'           => @trim($_POST['url']),
    );
    $continueFlag = true;

    $filePath   = dirname(__FILE__).'/db/'.$dbConnection['table'];
    $mysqli     = connectToMysql($dbConnection['host'], $dbConnection['user'], $dbConnection['password'], $dbConnection['db']);
    if( is_array($mysqli) and isset($mysqli['status']) and $mysqli['status'] == 0){
        $answer = array('status' => $mysqli['status'], 'msg' => $mysqli['msg'], 'mode' => 'restore_table');
        $continueFlag = false;
    }

    if($continueFlag){
        if(!file_exists($filePath) or !is_readable($filePath) ){
            $answer = array('status' => $mysqli['status'], 'msg' => 'file_is_not_exist_or_not_readable', 'mode' => 'restore_table');
            $continueFlag = false;
        }
    }

    if($continueFlag){
        $restoreRes = restoreTable($filePath, $mysqli, array(
            'table'         => $dbConnection['table'],
            'prefix'        => $dbConnection['prefix'],
            'old_prefix'    => $dbConnection['old_prefix'],
            'old_url'       => @trim($_POST['old_url']),
            'url'           => @trim($_POST['url']),
        ));

        if($restoreRes){
            $answer = array('status' => 1, 'msg' => 'table_restore_ok', 'mode' => 'restore_table');

            killFile($filePath);
        }else{
            $answer = array('status' => 0, 'msg' => 'table_restore_error', 'mode' => 'restore_table');
        }
    }
}

//update wp-config.php file
if( $mode == 'config_wordpress' ){
    $directory = dirname(__FILE__).DIRECTORY_SEPARATOR;

    $configData = array(
        'dbname'    => @trim($_POST['dbname']),
        'uname'     => @trim($_POST['uname']),
        'pwd'       => @trim($_POST['pwd']),
        'dbhost'    => @trim($_POST['dbhost']),
        'prefix'    => @trim($_POST['prefix']),
    );
    $continueFlag = true;

    if( !file_exists($directory . 'wp-config.php') ){
        $answer = array('status' => 0, 'msg' => 'config_file_not_found', 'mode' => 'config_wordpress');
        $continueFlag = false;
    }

    $config_file = @file( $directory . 'wp-config.php' );

    //check config data
    if( $continueFlag ) {
        if (empty($configData['dbname']) or empty($configData['uname']) or empty($configData['pwd']) or empty($configData['dbhost']) or empty($configData['prefix'])) {
            $continueFlag = false;
            $answer = array('status' => 0, 'msg' => 'incorrect_config_data', 'mode' => 'config_wordpress');
        }
    }

    //prepare paremaeters
    if( $continueFlag ){
        // update config data
        $key = 0;
        foreach ( $config_file as &$line ) {
            if ( '$table_prefix  =' == substr( $line, 0, 16 ) ) {
                $line = '$table_prefix  = \'' . sanit( $configData['prefix'] ) . "';\r\n";
                continue;
            }

            if ( ! preg_match( '/^define\(\'([A-Z_]+)\',([ ]+)/', $line, $match ) ) {
                continue;
            }

            $constant = $match[1];

            switch ( $constant ) {
                case 'WP_DEBUG'	   :
                    $line = "define('WP_DEBUG', 'false');\r\n";
                    break;
                case 'DB_NAME'     :
                    $line = "define('DB_NAME', '" . sanit( $configData['dbname'] ) . "');\r\n";
                    break;
                case 'DB_USER'     :
                    $line = "define('DB_USER', '" . sanit( $configData['uname'] ) . "');\r\n";
                    break;
                case 'DB_PASSWORD' :
                    $line = "define('DB_PASSWORD', '" . sanit( $configData['pwd'] ) . "');\r\n";
                    break;
                case 'DB_HOST'     :
                    $line = "define('DB_HOST', '" . sanit( $configData['dbhost'] ) . "');\r\n";
                    break;
            }
        }
        unset( $line );
    }

    //save config file
    if( $continueFlag ){
        @chmod( $directory . 'wp-config.php', 0755 );
        if( $handle = fopen( $directory . 'wp-config.php', 'w' ) ){
            foreach ( $config_file as $line ) {
                fwrite( $handle, $line );
            }
            fclose( $handle );

            // We set the good rights to the wp-config file
            @chmod( $directory . 'wp-config.php', 0666 );
        }else{
            $continueFlag = false;
            $answer = array('status' => 0, 'msg' => 'could_not_update_config_file', 'mode' => 'config_wordpress');
        }
    }

    //final OK
    if( $continueFlag ){
        $answer = array('status' => 1, 'msg' => 'config_file_updated', 'mode' => 'config_wordpress');
    }
}

//extract archive
if( $mode == 'extract_archive' ){
    $vars = array(
        'root_path' => dirname(__FILE__),
        'archive'   => @trim($_POST['archive'])
    );
    $continueFlag = true;

    $vars['root_path'] = (empty($vars['root_path']) ? '/' : '/'.trim($vars['root_path']).'/');

    //check zip library
    if($continueFlag){
        if( !class_exists('ZipArchive') ){
            $answer = array('status' => 0, 'msg' => 'zip_library_not_found', 'mode' => 'extract_archive');
            $continueFlag = false;
        }
    }

    //find archive
    $archivePath = dirname(__FILE__).'/archives/'.$vars['archive'];
    if($continueFlag){
        if( empty($vars['archive']) or !file_exists($archivePath) or !is_readable($archivePath) ){
            $answer = array('status' => 0, 'msg' => 'file_is_not_exist_or_not_readable', 'mode' => 'extract_archive', 'path' => $archivePath);
            $continueFlag = false;
        }
    }

    //check archive and destination directory
    if($continueFlag){
        if( !is_writable($vars['root_path']) ){
            @chmod($vars['root_path'],0755);
        }
        if( !is_writable($vars['root_path']) ){
            @chmod($vars['root_path'],0777);
        }

        if( strpos($vars['archive'],'_db_') !== FALSE ){
            $vars['root_path'] .= 'db/';

            @mkdir(dirname(__FILE__).DIRECTORY_SEPARATOR.'db', 0777, true);
        }

        if( !is_writable($vars['root_path']) ){
            @chmod($vars['root_path'],0755);
        }
        if( !is_writable($vars['root_path']) ){
            @chmod($vars['root_path'],0777);
        }

        if( !is_writable($vars['root_path']) ){
            $answer = array('status' => 0, 'msg' => 'destination_directory_not_writable', 'mode' => 'extract_archive', 'path' => $vars['root_path']);
            $continueFlag = false;
        }
    }

    //extract archive
    if($continueFlag){
        $zip        = new ZipArchive();
        if ($zip->open($archivePath) === TRUE) {
            $zip->extractTo($vars['root_path']);
            $zip->close();

            killFile($archivePath);

            $answer = array('status' => 1, 'msg' => 'archive_extracted', 'mode' => 'extract_archive', 'archive' => $vars['archive']);
        }else{
            $answer = array('status' => 0, 'msg' => 'failed_extract_archive', 'mode' => 'extract_archive');
        }
    }
}

if( !empty($mode) ){
    print json_encode($answer);exit;
}

// --- MYSQL LIB ---
function connectToMysql($host, $user, $password, $db, $port=3306){
    //try connect
    $mysqli = new mysqli($host, $user, $password, $db, $port);

    //check connection
    if (mysqli_connect_errno($mysqli)) {
	    
        return array(
            'status'    => 0,
            'msg'       => mysqli_connect_error($mysqli)
        );
    }

    @$mysqli->query("set names utf8 collate utf8_general_ci");
    @$mysqli->set_charset("utf8");

    return $mysqli;
}

function restoreTable($file, $mysqli, $options = array()){
    $current_query = '';
    $fp = @fopen($file, 'r');
    if (!$fp) {
        return false;
    }
    while (!feof($fp)) {
        $line = fgets($fp);

        // Skip it if it's a comment
        if (substr($line, 0, 2) == '--' || $line == '') {
            continue;
        }

        // Add this line to the current query
        $current_query .= $line;
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';') {
            // Perform the query
            $trimmed = trim($current_query, " ;\n");
            if (!empty($trimmed)) {
                $current_query = str_replace('DROP TABLE IF EXISTS', 'DROP TABLE', $current_query);
                $current_query = str_replace('DROP TABLE', 'DROP TABLE IF EXISTS', $current_query);

                if(!empty($options) and $options['prefix'] != $options['old_prefix']){
                    $current_query = str_replace(' '.$options['old_prefix'], ' '.$options['prefix'], $current_query);
                    $current_query = str_replace('"'.$options['old_prefix'], '"'.$options['prefix'], $current_query);
                    $current_query = str_replace("'".$options['old_prefix'], "'".$options['prefix'], $current_query);
                    $current_query = str_replace('`'.$options['old_prefix'], '`'.$options['prefix'], $current_query);
                }

                //update url of site
                if($options['table'] == $options['old_prefix'].'options' and !empty($options['old_url']) and !empty($options['url']) ){
                    $current_query = str_replace($options['old_url'], $options['url'], $current_query);
                }

                if($options['table'] == $options['old_prefix'].'posts' and !empty($options['old_url']) and !empty($options['url']) ){
                    $current_query = str_replace($options['old_url'], $options['url'], $current_query);
                }

                $result = $mysqli->query($current_query);
                if ($result === false) {
                    //var_dump($current_query);
                    return false;
                }
            }
            // Reset temp variable to empty
            $current_query = '';
        }
    }
    @fclose($fp);

    return true;
}

function sanit( $str ) {
    return addcslashes( str_replace( array( ';', "\n" ), '', $str ), '\\' );
}

// --- OTHER LIB ---
function killFile($filePath){
    if( !empty($filePath) and file_exists($filePath) ){
        @chmod($filePath, 0777);
        @unlink($filePath);
        if(file_exists($filePath)){
            @chmod($filePath, 0755);
            @unlink($filePath);
        }
    }
}
