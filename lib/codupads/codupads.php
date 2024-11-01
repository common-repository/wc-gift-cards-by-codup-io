<?php
if (!class_exists('CodupAds')){
	class CodupAds{	

		public function __construct(){

			add_shortcode('codup_ads_top', array($this, 'codup_render_top_ads'));
			add_shortcode('codup_ads_right', array($this, 'codup_render_right_ads'));
			add_action('admin_init', array($this, 'add_style_scripts'));
			add_action('init', array($this, 'add_style_scripts'));
			
		}

		function cwrf_get_woo_version_number(){
			if ( ! function_exists( 'get_plugins' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			
			$plugin_folder = get_plugins( '/' . 'woocommerce' );
			$plugin_file = 'woocommerce.php';
			
			if(isset( $plugin_folder[$plugin_file]['Version'])) {
				return $plugin_folder[$plugin_file]['Version'];

			} 
			else{

				return NULL;
			}
		}

		function add_style_scripts(){

			$pluginConfig = array(    
			    "pluginName" => CWRF_PLUGIN_NAME,
			    "pluginVersion" => CWRF_PLUGIN_VER,
			    "pageSlug" => CWRF_PAGE_SLUG,
			    "wpVersion" => get_bloginfo('version'),
			    "wcVersion" => $this->cwrf_get_woo_version_number()
			
			);
			wp_enqueue_script(CWRF_PLUGIN_NAME, plugin_dir_url( __FILE__ ) . 'scripts/adscript.js', array( 'jquery' ), CWRF_PLUGIN_VER, false );
			wp_enqueue_style(CWRF_PLUGIN_NAME.'- codupads-styles', plugin_dir_url( __FILE__ ) . 'styles/style.css', array(), CWRF_PLUGIN_VER, 'all' );
			wp_localize_script(CWRF_PLUGIN_NAME,'PluginConfig',$pluginConfig);
		} 	

		function codup_render_top_ads(){
		    echo '<div id="codup-topad" class="wrap"></div>';
		}

		function codup_render_right_ads(){	    
		    echo '<div id="codup-rightad" class="stick-to-right"></div>';
		}
	}
}
	        


