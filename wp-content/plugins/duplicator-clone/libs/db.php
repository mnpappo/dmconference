<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if ( ! class_exists( 'SiteDuplicatorLibDb' ) ) {
    class SiteDuplicatorLibDb
    {

        public static function getStats(){
            global $wpdb;

            $data = array(
                'tables'        => array(),
                'count_tables'  => 0,
                'size_tables'   => 0,
            );
            $data['tables']         = self::getTables();
            $data['count_tables']   = @(int)count($data['tables']);
            if( !empty($data['tables']) ){
                foreach($data['tables'] as $k_item => $v_item){
                    $data['size_tables'] += $v_item['size'];
                }
            }

            return $data;
        }

        public static function getTables(){
            global $wpdb;

            $list = array();

            $tables	 = $wpdb->get_results("SHOW TABLE STATUS like '".$wpdb->prefix."%'", ARRAY_A);
            if( !empty($tables) ){
                foreach($tables as $k_item => $v_item){
                    $list[] = array(
                        'name'      => $v_item['Name'],
                        'size'      => $v_item['Data_length'],
                        'rows'      => $v_item['Rows'],
                        'collation' => $v_item['Collation'],
                    );
                }
            }

            return $list;
        }

        public static function getTableStructure($table){
            global $wpdb;

            $data = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
            $data = @$data[1];

            return $data;
        }

        public static function getTableInsert($table, $filePath){
            global $wpdb;
            $limit = 1000;

//            $wpdb->query('optimize table ' . $table);

            $count = $wpdb->get_var('SELECT count(*) FROM `'.$table.'`');
            if ($count > $limit) {
                $count = ceil($count / $limit);
            } else {
                $count = ($count > 0 ? 1 : $count);
            }

            for ($i = 0; $i < $count; $i++) {
                $data = array();
                $low_limit = $i * $limit;
                $sqlQuery  = 'SELECT * FROM `'.$table.'` LIMIT '.(int)$low_limit.', '. $limit;
                $rows      = $wpdb->get_results($sqlQuery, ARRAY_A);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        //insert single row
                        $data[] = 'INSERT INTO `'.$table.'` VALUES(';
                        $num_values = count($row);
                        $j          = 1;
                        foreach ($row as $value) {
                            $value = addslashes($value);
                            $value = preg_replace("/\n/Ui", "\\n", $value);
                            $num_values == $j ? $data[]= "'".$value."'" : $data[]= "'".$value."', ";
                            $j++;
                            unset($value);
                        }
                        $data[]= ");\n";
                    }
                    $data = implode('', $data);
                    SiteDuplicatorLibFilesystem::writeContentFile($filePath, $data, 'line', 'ab+');
                }
            }

            //return $data;
        }

        public static function dumpTable($filePath, $table){

            $structure  = self::getTableStructure($table);
            //$insert     = self::getTableInsert($table);

            $data = '';
            $data .= 'SET FOREIGN_KEY_CHECKS = 0;'."\n\n";
            $data .= 'DROP TABLE IF EXISTS `'.$table.'`;'."\n";
            $data .= rtrim($structure,';').';'."\n\n";
            SiteDuplicatorLibFilesystem::writeContentFile($filePath,$data);
//            $data .= $insert."\n\n";
            //SiteDuplicatorLibFilesystem::writeContentFile($filePath, $insert, 'line', 'a+');
            self::getTableInsert($table, $filePath);

            //$data .= 'SET FOREIGN_KEY_CHECKS = 1;'."\n";
            $dumpRes = SiteDuplicatorLibFilesystem::writeContentFile($filePath,"\n\n" . 'SET FOREIGN_KEY_CHECKS = 1;'."\n", 'line', 'ab+');





            //$dumpRes = SiteDuplicatorLibFilesystem::writeContentFile($filePath,$data);
            if( @(int)$dumpRes['status'] == 1){
                $dumpRes = SiteDuplicatorLibUtility::logEvent(22, $table);
            }else{
                $dumpRes = SiteDuplicatorLibUtility::logEvent(23, $table);
            }

            return $dumpRes;
        }
    }
}