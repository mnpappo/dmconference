<?php
/**
 * @package Waiting
 * @version 0.3.9.5
 */
/*
	Plugin Name: Waiting
	Plugin URI: http://plugin.builders/waiting/?from=plugins
	Description: One-click countdowns.
	Author: Plugin Builders
	Author URI: http://plugin.builders/?from=plugins
	Version: 0.3.9.5
	Text Domain: waiting
	Domain Path: languages
*/

if( !defined( 'ABSPATH' ) ) exit;


class WPB_Waiting{
	static $version = '0.3.9.5';
	static $version_file = '-0.3.9.5';
	static $terms = array();

	function __construct(){
		$this->translateTerms();
		
		global $wpdb;
		$this->table = $wpdb->prefix.'waiting';
		
		$this->checkIfTableUpdated();
		
		register_activation_hook(__FILE__, array($this, 'createTable'));
		register_uninstall_hook(__FILE__, array('WPB_Waiting', 'onUninstall'));
		
		add_action('admin_menu', array($this, 'createMenu'));
		add_action('admin_init', array($this, 'deploy'));
		add_action('widgets_init', array($this, 'regWidget'));
		add_action('plugins_loaded', array($this, 'loadTextDomain') );
		
		add_action('admin_enqueue_scripts', array($this, 'loadDashJs'));
		add_action('wp_enqueue_scripts', array($this, 'loadJs'));
		
		add_action('wp_ajax_pbc_save_downs', array($this, 'saveDown'));
		add_action('wp_ajax_pbc_get_downs', array($this, 'getDowns'));
		add_action('wp_ajax_pbc_delete_down', array($this, 'deleteDown'));
		add_action('wp_ajax_pbc_save_lang', array($this, 'saveLang'));
		add_action('wp_ajax_pbc_save_other_settings', array($this, 'saveOtherSettings'));
		add_action('wp_ajax_pbc_get_fonts', array($this, 'getFonts'));
		
		add_action('pbc_admin_script', array($this, 'adminScript'));
		
		add_shortcode('waiting', array($this, 'shortcode') );
		
	}
	
	public function loadTextDomain(){
		load_plugin_textdomain( 'waiting', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
	public function createMenu(){
		add_menu_page(
			'Waiting',
			'Waiting',
			'manage_options',
			'waiting',
			array($this, 'pageTemplate'),
			'div'
		);
	}
	
	public function pageTemplate(){ ?>
		<div class="wrap">
			<div id="wpb-top">
				<a class="button pbc-cancel-form wpb-force-hide" id="pbc-back">Cancel</a>
				<div id="waiting-icon" class="wpb-inline"></div>
				<h2 class="wpb-inline" id="waiting-title">Waiting</h2>
			</div>
			<div id="pbc-wrapper" data-version="<?php echo self::$version; ?>">
				<img src="<?php echo site_url('wp-admin/images/spinner.gif'); ?>" id="pbc-init-loader"/>
			</div>
		</div>
		<?php
		$this->templates();
	}
	
	public function templates(){
		do_action('pbc_templates');
		include 'templates/templates.php';
	}
	
	
	public function deploy(){}
	
	public function adminScript(){
		wp_register_script('pbc_admin_settings', plugins_url('/js/admin.js?v='.self::$version, __FILE__), array('pbc_script'), null, 1);
		wp_enqueue_script('pbc_admin_settings');
	}
	
	public function loadDashJs($hook){
		if($hook === 'toplevel_page_waiting'){
			wp_register_script('pb_countdown', plugins_url('/js/jquery.countdown.js', __FILE__), array('jquery'), null, 1);
			wp_enqueue_script('pb_countdown');
			wp_enqueue_script('wp-color-picker');
			wp_register_script('pbc_script', plugins_url('/js/pbc.js?v='.self::$version, __FILE__), array('pb_countdown', 'underscore', 'wp-color-picker'), null, 1);
			wp_enqueue_script('pbc_script');
			do_action('pbc_admin_script');
			wp_register_style('pbc_admin_style', plugins_url('/css/admin-style.css?v='.self::$version, __FILE__));
			wp_enqueue_style('pbc_admin_style');
			wp_register_style('pbc_style', plugins_url('/css/style.css?v='.self::$version, __FILE__));
			wp_enqueue_style('pbc_style');
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_register_style('pbc-jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
			wp_enqueue_style( 'pbc-jquery-ui' );
		}
		$this->menuIconStyle();
	}
	
	public function loadJs(){}
	
	public function menuIconStyle(){
		?>	
		<style>
			#toplevel_page_waiting.wp-not-current-submenu .wp-menu-image{
				background:url(<?php echo plugins_url('/images/waiting-menu-icon.png', __FILE__); ?>) no-repeat 8px;
			}
			#toplevel_page_waiting.wp-not-current-submenu .wp-menu-image:hover{
				background:url(<?php echo plugins_url('/images/waiting-menu-icon-hover.png', __FILE__); ?>) no-repeat 8px;
			}
			#toplevel_page_waiting.current .wp-menu-image{
				background:url(<?php echo plugins_url('/images/waiting-menu-icon-current.png', __FILE__); ?>) no-repeat 8px;
			}
			#waiting-icon{
				width:35px; height:35px;
				background:url(<?php echo plugins_url('/images/waiting-icon.png', __FILE__); ?>) no-repeat;
				background-size:contain;
			}
		</style>
		<?php
	}
	
	
	public function createTable(){
		$charset_collate = '';
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		
		if(!empty($wpdb->charset)) $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		
		if(!empty($wpdb->collate)) $charset_collate .= " COLLATE {$wpdb->collate}";
				
		$sql = 
		  "CREATE TABLE IF NOT EXISTS `".$this->table."` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL,
		  `template` tinyint(1) NOT NULL DEFAULT '0',
		  `data` longtext NOT NULL,
		  `offertext` text NOT NULL,
		  `replacetext` text NOT NULL,
		  `extras` longtext NOT NULL,
		  PRIMARY KEY (`id`)
		) $charset_collate;";
		
		dbDelta($sql);
	}
	
	public static function onUninstall(){
		global $wpdb;
		
		if( get_option('waiting_clean_on_uninstall') ){
			$sql = "DROP TABLE IF EXISTS ".$wpdb->prefix.'waiting';
			$wpdb->query($sql);
			delete_option('waiting_clean_on_uninstall');
			delete_option('pbc_version');
			delete_option('pbc_waiting_license_key');
			delete_option('pbc_waiting_name');
			delete_option('pbc_license_status');
		}
	}
	
	public function checkIfTableUpdated(){
		$version = get_option('pbc_version');
		if( !$version ) $this->upgradeTable();
		else update_option('pbc_version', WPB_Waiting::$version);
	}
	
	public function upgradeTable(){
		global $wpdb;
		
		$sql = "SHOW TABLES LIKE 'wp_waiting'";
		$table = $wpdb->query($sql);
		
		if( $table && $this->table !== 'wp_waiting'){
			$sql = 'RENAME TABLE wp_waiting TO '.$this->table;
			$wpdb->query( $sql );
		}		
		
		if( $table ){
			$sql = 'ALTER TABLE '.$this->table.' ADD `extras` longtext NOT NULL';
			$wpdb->query( $sql );
		}
		
		update_option('pbc_version', WPB_Waiting::$version);
	}
	
	public function regWidget(){
		register_widget('WaitingWidget');
	}
		
	public function getDowns(){
		global $wpdb; $re = array();
		$downs = $wpdb->get_results('SELECT * FROM '.$this->table);
		
		foreach($downs as $down){
			$re[] = $this->downDetail( $down );
		}
		
		wp_send_json($re);
	}
	
	public function inZone( $down ){
		if($down['meta']['timezone'] !== 'USER'){
			$now = new DateTime( "NOW" );
			$tmz = ($down['meta']['timezone'] === 'WP') ? $this->getTimezone() : 'UTC';
			
			$now->setTimezone(new DateTimeZone( $tmz ));
			$down['meta']['offset'] = ($tmz === 'UTC') ? $down['meta']['offset'] : (($now->getOffset()/60) + $down['meta']['offset']);
		} else $down['meta']['offset'] = 0;
		return $down;
	}
	
	public function saveDown(){
		$down = $this->sanitize( $_POST['pbc_down'] );
		
		$re = array();
		$id = $down['meta']['id'];
		$down = $this->inZone( $down );							
		$re[] = $this->countdownTo($down);
		
		$html = $down['html'];
		unset($down['html']);
		
		$html['replacetext'] = isset($html['replacetext']) ? $html['replacetext'] : '';
		$html['offertext'] = isset($html['offertext']) ? $html['offertext'] : '';
				
		global $wpdb;
		if($id === 'nw'){
			$wpdb->insert($this->table, array(
				'name' => $down['meta']['name'],
				'data' => maybe_serialize($down),
				'offertext' => $html['offertext'],
				'replacetext' => $html['replacetext']
			));
			$re[] = $wpdb->insert_id;
		} else {
			$extras = maybe_unserialize( $wpdb->get_var("SELECT `extras` FROM ".$this->table." WHERE `id` = ".$id) );	
			if( isset( $extras['emailed'] ) ) unset( $extras['emailed'] );	
			$sql = "UPDATE ".$this->table."
					SET 
						`name` = %s,
						`data` = %s, 
						`offertext` = %s, 
						`replacetext` = %s,
						`extras` = %s 
					WHERE id = ".$id;
			$wpdb->query( $wpdb->prepare( $sql, array($down['meta']['name'], maybe_serialize($down), $html['offertext'], $html['replacetext'], maybe_serialize( $extras ) ) ));
			$re[] = $id;
		}
		wp_send_json($re);
	}
	
	public static function getTimestampWithFallback( $time ){
		return method_exists('DateTime', 'getTimestamp') ? 
             $time->getTimestamp() : $time->format('U');
	}
		
	public static function countdownTo( $down ){
		$ofs = $down['meta']['offset']*60;
		
		$from = round($down['meta']['occurence'][0][0]);
		$from = new DateTime( '@'.$from );
		$from = self::getTimestampWithFallback( $from );
		$from -= $ofs;
						
		if($down['meta']['insta'][0]){		// Countdown to duration
			return array($from*1000, $down['meta']['occurence'][0][1]*1000);
		}
												
		$to = $down['meta']['occurence'][0][1];		
		$to = new DateTime( '@'.$to );
		$to = self::getTimestampWithFallback( $to );
		$to -= $ofs;
				
		return array($from*1000, $to*1000);
	}
	
	public function deleteDown(){
		global $wpdb;
		$key = sanitize_text_field( $_POST['pbc_down'] );
		$re = $wpdb->query("DELETE FROM ".$this->table." WHERE id = $key");
		echo $re;
		die();
	}
		
	public static function getDown( $name ){
		global $wpdb;
		$down = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->prefix."waiting WHERE `name` = %s", array( addslashes($name) )) );
		return self::downDetail( $down, true );
	}
	
	public static function sss($s){
		return stripslashes( html_entity_decode($s) );
	}
	
	public static function downDetail( $down, $single = false ){
		if(!$down) return false;
		$id = $down->id;
		$down->data = maybe_unserialize($down->data);
		$down->data['html'] = array('offertext' => self::sss($down->offertext), 'replacetext' => self::sss($down->replacetext), 'd'=>'');
		$down->data['meta']['id'] = $id;
		$down->data['meta']['name'] = stripslashes($down->data['meta']['name']) ;
		$down->data['meta']['to'] = self::countdownTo($down->data);
		if($single) unset($down->data['meta']['occurence']);
		return $down->data;
	}
		
	public static function getTimezone() {
		if ( $timezone = get_option( 'timezone_string' ) )
			return $timezone;
			
		if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
			return 'UTC';
	 
		// adjust UTC offset from hours to seconds
		$utc_offset *= 3600;
	 
		if ( $timezone = timezone_name_from_abbr( '', $utc_offset, 0 ) ) {
			return $timezone;
		}
		
		$is_dst = date( 'I' );
	 
		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset )
					return $city['timezone_id'];
			}
		}
		 
		// fallback to UTC
		return 'UTC';
	}

	public static function downs(){
		global $wpdb;
		return $wpdb->get_results('SELECT `name` from '.$wpdb->prefix.'waiting');
	}
	
	
	/**
	
		Shortcode 
		
	**/
	
	public function shortcode($atts){
		$atts = shortcode_atts(
			array(
				'name' => '',
			), $atts );
		
		return self::output( $atts['name'] );
	}
	
	public static function output( $name ){ 
		$name = html_entity_decode($name);
		if(!$name) return '';
		if($down = self::getDown($name))
			return self::outputString( $down );
		return '<span class="pbc-wrong-shortcode"></span>';
	}
		
	public function translateTerms(){
				
		/*// Write German term for each unit below.
		
		$german = array(
			'years' => 'german for years',
			'months' => '',
			'weeks' => '',
			'days' => '',
			'hours' => '',
			'minutes' => '',
			'seconds' => ''
		);
		
		// Write French term for each unit below.
		
		$french = array(
			'years' => 'french for years',
			'months' => '',
			'weeks' => '',
			'days' => '',
			'hours' => 'aa',
			'minutes' => '',
			'seconds' => ''
		);
		
		self::$terms['units'] = ( get_locale() === 'de_DE' ? $german : $french );
		*/
		
		self::$terms['fui'] = array(
		
		);
		
		$units = get_option('pbc_unit_terms');
		self::$terms['units'] = $units ? $units : array(
			'years' => __('Years', 'waiting'),
			'months' => __('Months', 'waiting'),
			'weeks' => __('Weeks', 'waiting'),
			'days' => __('Days', 'waiting'),
			'hours' => __('Hours', 'waiting'),
			'minutes' => __('Minutes', 'waiting'),
			'seconds' => __('Seconds', 'waiting')
		);
	}
	
	public function getFonts(){
		include 'templates/fonts.php';
		wp_send_json( pbcGoogleFonts() );
	}
	
	public static function outputString( $down ){
		$cd_url = plugins_url('/js/jquery.countdown.js', __FILE__);
		$url = plugins_url('/js/pbc.js?v='.self::$version, __FILE__);
		$css_url = plugins_url('/css/style.css?v='.self::$version, __FILE__);
		$font_url = '//fonts.googleapis.com/css?family='.$down['style']['css']['unit'][3];
				
		$html = $down['html']; 
		
		unset($down['html']);
		unset($down['extras']);
		if( $down['meta']['onfinish'][0] === 'email' ) 
			unset( $down['meta']['onfinish'][1] );
		
		return '<div class="pbc-cover wpb-inline" dir="ltr" data-pbc-setup="" data-countdown="'. htmlentities( json_encode($down) ) .'">'.
			self::rawDowntexts($html)
		.'</div>
		<script>
			var PBCUtils = PBCUtils || {};
			PBCUtils.ajaxurl = "'. admin_url( 'admin-ajax.php' ) .'";
			PBCUtils.url = "'. plugins_url('/', __FILE__) .'";
			(function(){
				if(!PBCUtils.loaded){
					PBCUtils.loaded = true;
					PBCUtils.lang = '.json_encode(self::$terms).';	
					var script = document.createElement("script");
						script.src = "'. $cd_url .'";
						document.querySelector("head").appendChild(script);
					  script = document.createElement("script");
					  script.src = "'. $url .'";
					  document.querySelector("head").appendChild(script);
					var style = document.createElement("link");
						style.rel = "stylesheet";
						style.href = "'. $css_url .'";
						style.type = "text/css";
						document.querySelector("head").appendChild(style);'.
					(
					$down['style']['css']['unit'][3] === 'inherit' ? '' :
						'style = document.createElement("link");
						style.rel = "stylesheet";
						style.href = "'. $font_url .'";
						style.type = "text/css";
						document.querySelector("head").appendChild(style);'
					).
				'} else { if(PBCUtils.em) PBCUtils.em.trigger("pbc.run");}
			}());
		</script>';
	}
	
	public static function rawDowntexts( $htmls ){
		$text = '';
		foreach($htmls as $key=>$html){
			$text .= '<div class="wpb-force-hide pbc-downtext-raw pbc-'.$key.'-raw">'.$html.'</div>';
		}
		return $text;
	}
	
	public function saveLang(){
		$lang = $this->sanitize( $_POST['pbc_lang'] );
		echo update_option('pbc_unit_terms', $lang);
		die();
	}
	
	public function saveOtherSettings(){
		$clean = intval( $_POST['pbc_clean_on_uninstall'] ); 
		echo update_option('waiting_clean_on_uninstall', $clean);
		die();
	}
	
	/**
	
		Sanitizing
		
	**/
	
	public $fields = array('offertext' => 'html', 'replacetext' => 'html');
	
	public function sanitize($ins){
		return $this->validation( $ins );
	}
	
	public function validation( $ins ){
		$rins = array();
		foreach($ins as $key=>$value){
			$rins[$key] = $this->validateField( $key, $value );
		}
		return $rins;
	}
	
	public function validateField( $k, $val ){
		if(is_array($val)){
			$clean_val = $this->validation( $val );
		} else {
			$clean_val = $this->cleanse(
				( array_key_exists($k, $this->fields) ? $this->fields[$k] : 'string' ),
			$val);
		}
		return $clean_val;
	}
	
	public function cleanse($type, $value){
		switch($type){
			case 'int':
				return intval($value);
				break;
			case 'url':
				return esc_url($value);
				break;
			case 'html':
				return esc_html( wp_kses_post( (string)$value ) );
				break;
			default:
				return sanitize_text_field($value);
				break;
		} 
	}
	
} 





class WaitingWidget extends WP_Widget{

	public $wpb_id = 'wpb_waiting';
	public $wpb_name = 'Waiting';
	public $wpb_description = 'One-click countdowns';
	
	function __construct(){
		parent::__construct(
			$this->wpb_id,
			__($this->wpb_name, 'waiting'),
			array('description' => __($this->wpb_description, 'waiting'))
		);
	}
	
	public function widget($args, $instance){
		echo $args['before_widget']; ?>
		<?php echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title']; ?>
		<div class="pbc-wrapper">
			<?php echo WPB_Waiting::output( $instance['key'] ); ?>
		</div>
		<?php echo $args['after_widget'];
	} 
	
	public function form($instance){
		$title = empty($instance['title']) ? '' : $instance['title'];
		$key = empty($instance['key']) ? '' : $instance['key'];
		?>
		
		<div>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'waiting'); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" />
		</div>
		
		<div>
			<label for="<?php echo $this->get_field_id('key'); ?>"><?php _e('Name', 'waiting'); ?>:</label>
			<select class="widefat" id="<?php echo $this->get_field_id('key'); ?>" name="<?php echo $this->get_field_name('key'); ?>">
				<?php foreach(WPB_Waiting::downs() as $d): ?>
					<option value="<?php echo $d->name; ?>" <?php echo $d->name == $key ? 'selected' : ''; ?>><?php echo $d->name; ?></option>
				<?php endforeach; ?>
			<select/>
		</div>
		
		<?php
	}
	
	public function update($n, $o){
		$instance = array();
		$instance['title'] = $n['title'] ? $n['title'] : '';
		$instance['key'] = $n['key'] ? $n['key'] : 'Please select one';
		return $instance;
	}
	
}








new WPB_Waiting();

?>
