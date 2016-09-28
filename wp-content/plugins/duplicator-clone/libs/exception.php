<?php
defined( 'PLUGIN_ACCESS_FLAG' ) or die( 'Go away!' );
if ( ! class_exists( 'SiteDuplicatorLibException' ) ) {
    class SiteDuplicatorLibException extends Exception
    {
        public function __construct(){

        }
    }
}