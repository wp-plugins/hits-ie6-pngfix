<?php
/*
	Plugin Name: HITS- IE6 PNGFix
	Version: 2.0
	Author: Adam Erstelle
	Author URI: http://www.homeitsolutions.ca
	Plugin URI: http://www.homeitsolutions.ca/websites/wordpress-plugins/ie6-png-fix
	Description: Adds IE6 Compatability for PNG transparency, using 1 of 3 configured approaches
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
* Guess the wp-content and plugin urls/paths
*/
// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

if (!class_exists('hits_ie6_pngfix')) {
    class hits_ie6_pngfix {
        //This is where the class variables go, don't forget to use @var to tell what they're for
        /**
        * @var string The options string name for this plugin
        */
        var $optionsName = 'hits_ie6_pngfix_options';
        
        /**
        * @var string $localizationDomain Domain used for localization
        */
        var $localizationDomain = "hits_ie6_pngfix";
        
        /**
        * @var string $pluginurl The path to this plugin
        */ 
        var $thispluginurl = '';
        /**
        * @var string $pluginurlpath The path to this plugin
        */
        var $thispluginpath = '';
            
        /**
        * @var array $options Stores the options for this plugin
        */
        var $options = array();
        
        //Class Functions
        /**
        * PHP 4 Compatible Constructor
        */
        function hits_ie6_pngfix(){$this->__construct();}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct(){
            //Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . $this->localizationDomain . "-".$locale.".mo";
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup
            $this->thispluginurl = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            $this->thispluginpath = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
            
            //Initialize the options
            //This is REQUIRED to initialize the options when the plugin is loaded!
            $this->getOptions();
            
            //Actions        
            add_action("admin_menu", array(&$this,"admin_menu_link"));

            
            //Widget Registration Actions
           // add_action('plugins_loaded', array(&$this,'register_widgets'));
            
            
            add_action('wp_head', array(&$this,'wp_head'));
            /*
			add_action('wp_print_scripts', array(&$this, 'add_js'));
            */
            
            //Filters
            /*
            add_filter('the_content', array(&$this, 'filter_content'), 0);
            */
        }
        
        function wp_head()
		{
			echo "\n";
			echo "\n<!-- Begin - HITS-IE6 PNGFix -->";
			echo "\n";
			
			if (strcmp($this->options['hits_ie6_pngfix_method'],'THM1')==0)
			{
				echo "\n<style type='text/css'>img, div { behavior: url(". $this->thispluginurl."THM1/iepngfix.htc) }</style>";
			}
			else if (strcmp($this->options['hits_ie6_pngfix_method'],'THM2')==0)
			{
				echo "\n<style type='text/css'>img, div, a, input { behavior: url(". $this->thispluginurl."THM2/iepngfix.htc) }</style>";
				echo "\n<script type='text/javascript' src='". $this->thispluginurl."THM2/iepngfix_tilebg.js'></script>";
				echo "\n<script type='text/javascript'>IEPNGFix.blankImg = '". $this->thispluginurl."THM2/blank.gif';</script>";
			}
			else if (strcmp($this->options['hits_ie6_pngfix_method'],'UPNGFIX')==0)
			{
				echo "\n<!--[if lt IE 7]>";
        		echo "\n<script type='text/javascript' src='". $this->thispluginurl."UPNGFIX/unitpngfix.js'></script>";
				echo "\n<![endif]-->";
			}
			
			echo "\n";
			echo "\n<!--  End  - HITS-IE6 PNGFix -->\n";
			echo "\n";
		}
        
        
        /**
        * Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() {
            //Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array('hits_ie6_pngfix_method'=>'THM1');
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //There is no return here, because you should use the $this->options variable!!!
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }
        /**
        * Saves the admin options to the database.
        */
        function saveAdminOptions(){
            return update_option($this->optionsName, $this->options);
        }
        
        /**
        * @desc Adds the options subpanel
        */
        function admin_menu_link() {
            //If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
            //reflect the page filename (ie - options-general.php) of the page your plugin is under!
            add_options_page('HITS- IE6 PNG Fix', 'HITS- IE6 PNG Fix', 10, basename(__FILE__), array(&$this,'admin_options_page'));
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
        }
        
        /**
        * @desc Adds the Settings link to the plugin activate/deactivate page
        */
        function filter_plugin_actions($links, $file) {
           //If your plugin is under a different top-level menu than Settiongs (IE - you changed the function above to something other than add_options_page)
           //Then you're going to want to change options-general.php below to the name of your top-level page
           $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
           array_unshift( $links, $settings_link ); // before other links

           return $links;
        }
        
        /**
        * Adds settings/options page
        */
        function admin_options_page() { 
            if($_POST['hits_ie6_pngfix_save']){
                if (! wp_verify_nonce($_POST['_wpnonce'], 'hits_ie6_pngfix-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
                $this->options['hits_ie6_pngfix_method'] = $_POST['hits_ie6_pngfix_method'];                                                           
                $this->saveAdminOptions();
                
                echo '<div class="updated"><p>Success! Your changes were sucessfully saved!</p></div>';
            }
?>                                   
                <div class="wrap">
                <h2>HITS- IE6 PNG Fix</h2>
                <form method="post" id="hits_ie6_pngfix_options">
                <?php wp_nonce_field('hits_ie6_pngfix-update-options'); ?>
                	<p>I take no credit for the great effort authors have gone into making each method of getting IE6 PNG compatability to work. I just did the work to merge them all into a single wordpress plugin.</p>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
                        <tr valign="top"> 
                            <th width="33%" scope="row"><?php _e('PNG Fix Method:', $this->localizationDomain); ?></th> 
                            <td>
                            <select name="hits_ie6_pngfix_method" id="hits_ie6_pngfix_method" style="width:200px;">
								<option value="THM1"<?php if (strcmp($this->options['hits_ie6_pngfix_method'],'THM1')==0) { echo ' selected="selected"';} ?>>Twin Helix v1.0</option>
								<option value="THM2"<?php if (strcmp($this->options['hits_ie6_pngfix_method'],'THM2')==0) { echo ' selected="selected"';} ?>>Twin Helix v2.0</option>
								<option value="UPNGFIX"<?php if (strcmp($this->options['hits_ie6_pngfix_method'],'UPNGFIX')==0) { echo ' selected="selected"';} ?>>Unit PNG Fix</option>
							</select>
                        </td> 
                        </tr>

                        <tr>
                            <th colspan=2><input type="submit" name="hits_ie6_pngfix_save" value="Save" /></th>
                        </tr>
                    </table>
                    
                    <p>Feedback and requests are always welcome. Visit the plugin website <a href="http://www.homeitsolutions.ca/websites/wordpress-plugins/ie6-png-fix">here</a> to leave any feedback, comments or donations. All donations will go towards micro loans through Kiva at www.kiva.org</p>
                    <h3>PNG Fix Credits</h3>
                    <p>The Twin Helix approaches were taken from <a href="http://www.twinhelix.com/css/iepngfix/">the twinhelix website</a>.</p>
                    <p>The UnitInteractive approach was taken from <a href="http://labs.unitinteractive.com/unitpngfix.php"> the unit interactive labs website</a>.</p>
                </form>
                <?php
        }
  } //End Class
} //End if class exists statement

//instantiate the class
if (class_exists('hits_ie6_pngfix')) {
    $hits_ie6_pngfix_var = new hits_ie6_pngfix();
}
?>
