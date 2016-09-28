<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if ( ! class_exists( 'SiteDuplicatorLibServer' ) ) {
    class SiteDuplicatorLibServer
    {

        public static function getStat(){
            global $wpdb;

            $items = array();

            //check max_execution_time
            $items['max_execution_time']    = @(int)ini_get('max_execution_time');
            $newMaxExecTime = 500;
            @set_time_limit( 500 );
            $items['max_execution_time_up'] = ( @(int)ini_get('max_execution_time') == $newMaxExecTime ? 1 : 0);

            //check memory limit
            $items['memory_limit'] = ini_get('memory_limit');
            $newMaxMemory = 128;
            ini_set('memory_limit', $newMaxMemory.'M');
            $items['memory_limit_up'] = ( @(int)ini_get('memory_limit') == $newMaxMemory ? 1 : 0);

            //check zip
            $items['zip'] = (class_exists('ZipArchive')	? 1 : 0);

            //free space of server
            $items['server_free_space'] = @SiteDuplicatorLibUtility::byteSize(disk_free_space("."));

            //check mysql
            $items['mysql']         = (function_exists('mysqli_connect') ? 1 : 0);
            $items['mysql_version'] = $wpdb->db_version();

            return $items;
        }
    }
}