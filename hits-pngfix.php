<?php
/*
	Plugin Name: HITS- IE6 PNGFix
	Version: 3.3.1
	Author: Adam Erstelle
	Author URI: http://www.homeitsolutions.ca
	Plugin URI: http://www.homeitsolutions.ca/websites/wordpress-plugins/ie6-png-fix
	Description: Adds IE6 Compatability for PNG transparency, using 1 of 5 configured approaches either server side or client side
	Text Domain: hits-ie6-pngfix
	
	PLEASE NOTE: If you make any modifications to this plugin file directly, please contact me so that
	             the plugin can be updated for others to enjoy the same freedom and functionality you
				 are trying to add. Thank you!
	
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
// Pre-WP-2.6 compatibility
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
        var $wp_version;
		var $version = '3.3.1';
        
        /**
        * @var string $localizationDomain Domain used for localization
        */
        var $localizationDomain = "hits-ie6-pngfix";
        
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
            $mo = dirname(__FILE__) . "/languages/" . strtolower($this->localizationDomain) . "-".strtolower($locale).".mo";
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup
            $this->thispluginurl = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            $this->thispluginpath = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
			
			global $wp_version;
            $this->wp_version = substr(str_replace('.', '', $wp_version), 0, 2);
            
            //Initialize the options
            //This is REQUIRED to initialize the options when the plugin is loaded!
            $this->getOptions();
            $this->actions_filters();
        }
		
		/*
		 * Centralized place for adding all actions and filters for the plugin into wordpress
		*/
		function actions_filters()
		{
			add_action("admin_menu", array(&$this,"admin_menu_link"));
			add_action('wp_head', array(&$this,'wp_head'));
			add_action('admin_head', array(&$this, 'admin_head'));
			
			add_action('after_plugin_row', array(&$this,'plugin_check_version'), 10, 2);
		}
		
		function admin_head()
		{
            echo('<link rel="stylesheet" href="'.$this->thispluginurl.'css/admin.css" type="text/css" media="screen" />');			
		}
		
        function plugin_check_version($file, $plugin_data) 
		{
            static $this_plugin;
            if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
			
            if ($file == $this_plugin)
			{
                $current = $this->wp_version < 28 ? get_option('update_plugins') : get_transient('update_plugins');
				$someValue = $current->response[$file];
                if (!isset($someValue)) 
					return false;

                $columns = $this->wp_version < 28 ? 5 : 3;
			
				if($this->options['hits_ie6_debug']=='true')
				{
					echo "\n<!-- HITS IE6 PNG Fix Debug For Displaying Update Box -->";
				}
                $url = "http://svn.wp-plugins.org/hits-ie6-pngfix/trunk/updateText.txt";
                $update = wp_remote_fopen($url);
                if ($update != "") 
				{
					$updateForVersion = trim(substr($update,79,6));
					if($this->options['hits_ie6_debug']=='true')
					{
						echo "\n<!-- updateForVersion=$updateForVersion -->\n";	
						echo "\n<!-- this->version=$this->version -->\n";	
					}
					if(strcmp($this->version,$updateForVersion)<0)
					{
						echo '<td colspan="'.$columns.'" class="hits-plugin-update"><div class="hits-plugin-update-message">';
						echo $update;
						echo '</div></td>';
					}
                }
            }
        }

        /*
		 * Writes the IE6 fix code if IE6 has been detected as the user's browser
		*/
        function wp_head()
		{
			$fixMethod = $this->options['hits_ie6_pngfix_method'];
			$pagesAreCached = $this->options['hits_ie6_pngfix_pagesAreCached'];
			
			global $wp_version;
			echo "\n";
			echo "\n<!-- Begin - HITS-IE6 PNGFix -->";
			if($this->options['hits_ie6_debug']=='true')
			{
				echo "\n<!-- DEBUG: Plugin Version=$this->version\n     DEBUG: Fix Method=$fixMethod\n     DEBUG: PagesAreCached=$pagesAreCached -->";
			}
			if($pagesAreCached=='false')
			{
				if($this->isIE6())
				{
					echo "\n<!-- IE6 has been detected as the users browser version by the server -->";
					$this->write_ie6_fix_nodes($fixMethod);
				}
				else
					echo "\n<!-- IE6 has not been detected as the users browser version by the server -->";
			}
			else
			{
				echo "\n<!-- The browser itself will determine if IE6 code will be used -->";
				echo "\n<!--[if lte IE 6]>";
				$this->write_ie6_fix_nodes($fixMethod);
				echo "\n<![endif]-->";
			}
			
			echo "\n<!--  End  - HITS-IE6 PNGFix -->\n";
			echo "\n";
		}
		
		function write_ie6_fix_nodes($fixMethod)
		{
			if (strcmp($fixMethod,'THM1')==0)
			{
				echo "\n<style type='text/css'>".$this->options['hits_ie6_pngfix_THM_CSSSelector']." { behavior: url(". $this->thispluginurl."THM1/iepngfix.php) }</style>";
			}
			else if (strcmp($fixMethod,'THM2')==0)
			{
				echo "\n<style type='text/css'>".$this->options['hits_ie6_pngfix_THM_CSSSelector']." { behavior: url(". $this->thispluginurl."THM2/iepngfix.php) }</style>";
				echo "\n<script type='text/javascript' src='". $this->thispluginurl."THM2/iepngfix_tilebg.js'></script>";
			}
			else if (strcmp($fixMethod,'UPNGFIX')==0)
			{
				echo "\n<script type='text/javascript' src='". $this->thispluginurl."UPNGFIX/unitpngfix.js.php'></script>";
			}
			else if (strcmp($fixMethod,'SUPERSLEIGHT')==0)
			{
				echo "\n<script type='text/javascript' src='". $this->thispluginurl."supersleight/supersleight-min.js.php'></script>";
			}
			else if (strcmp($fixMethod,'DD_BELATED')==0)
			{
				echo "\n<script type='text/javascript' src='". $this->thispluginurl."DD_belatedPNG/DD_belatedPNG_0.0.8a-min.js'></script>";
				echo "\n<script type='text/javascript'>DD_belatedPNG.fix('".$this->options['hits_ie6_pngfix_THM_CSSSelector']."');</script>";
			}
		}
        
		// IE6 Check
		function isIE6()
		{
			$browser = 'mozilla';
			$majorVersion = 5;
		
			if(get_cfg_var('browscap')) 
			{
				$browserTab = get_browser();
				$browser = strtolower($browserTab->browser);
				$majorVersion = intval($browserTab->majorver);
			}
			else 
			{
				$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
				if (preg_match('|msie ([0-9]).[0-9]{1,2}|',$userAgent,$matched)) 
				{
					$browser = 'ie';
					$majorVersion = intval($matched[1]);
				}
			}
		
			if($this->options['hits_ie6_debug']=="true") {
				echo "\n<!-- DEBUG: HTTP_USER_AGENT='$userAgent' -->";
				echo "\n<!-- DEBUG: DETECT BROWSER='$browser' -->";
				echo "\n<!-- DEBUG: DETECT M VERSION='$majorVersion' -->";
			}
		
			if($browser == 'ie' && $majorVersion <= 6) { // if IE<=6
				return true;
			}
			else { //if IE>6
				return false;
			}
		}
        
        /**
        * Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() {
            //Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) 
			{//default options
                $theOptions = array('hits_ie6_pngfix_method'=>'THM1', //Added V2.0
									'hits_ie6_pngfix_THM_CSSSelector'=>'img, div', //Added V2.1
									//'hits_ie6_pngfix_THM_image_path'=>'Initiated',//Added V2.2  Removed in V3.2
									'hits_ie6_pngfix_version'=>$this->version, //Added V2.3
									'hits_ie6_debug'=>"false", //Added V3.0
									'hits_ie6_pngfix_pagesAreCached'=>'false' //Added V3.1
									//'hits_ie6_pngfix_image_path'=>'Initiated'//Added V3.2 Removed in V3.3
									);
                update_option($this->optionsName, $theOptions);
				$this->persist_optionsFile();
            }
            $this->options = $theOptions;
            
			//check for missing fields on an upgrade
			$missingOptions=false;
			if(!$this->options['hits_ie6_pngfix_version'] || (strcmp($this->options['hits_ie6_pngfix_version'],$this->version)!=0))
			{
				$missingOptions=true;
				//an upgrade, run upgrade specific tasks.
				
				//upgrading from pre-version 2.2
				if(!$this->options['hits_ie6_pngfix_THM_CSSSelector'])
				{
					if(strcmp($this->options['hits_ie6_pngfix_method'],'THM1')==0)
						$this->options['hits_ie6_pngfix_THM_CSSSelector'] = 'img,div';
					else if(strcmp($this->options['hits_ie6_pngfix_method'],'THM2')==0)
						$this->options['hits_ie6_pngfix_THM_CSSSelector'] = 'img, div, a, input';
				}				
				//upgrading from version 2.2
				
				//set the version and update the database.
				$this->options['hits_ie6_pngfix_version']=$this->version;
				
				//added in 3.0
				if(!$this->options['hits_ie6_debug'])
				{
					$this->options['hits_ie6_debug']="false";
				}
				
				//added in 3.1
				if(!$this->options['hits_ie6_pngfix_pagesAreCached'])
				{
					$this->options['hits_ie6_pngfix_pagesAreCached']='false';	
				}
				
				//upgrading to V3.2
				if($this->options['hits_ie6_pngfix_THM_image_path'])
				{
					//remove the old options	
					unset($this->options['hits_ie6_pngfix_THM_image_path']);
				}
				if($this->options['hits_ie6_pngfix_image_path'])
				{
					//remove old option
					unset($this->options['hits_ie6_pngfix_image_path']);
					$this->persist_optionsFile();
				}
			}
			
			//if missing options found, update them.
			if($missingOptions==true)
				$this->saveAdminOptions();
			
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //There is no return here, because you should use the $this->options variable!!!
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }
        /**
        * @desc Saves the admin options to the database.
        */
        function saveAdminOptions(){
			//save options to database
			return update_option($this->optionsName, $this->options);
        }
		
		function persist_optionsFile()
		{
			$propFile = $this->thispluginpath.'hits-pngfix.properties';
			if($this->is__writable($propFile))
			{
				$propFileHandle = @fopen($propFile, 'w') or die("can't open file");
				fwrite($propFileHandle,$this->thispluginurl."clear.gif");
				fclose($propFileHandle);
			}
			else
			{
				if($this->options['hits_ie6_debug']=='true')
					echo "<!-- DEBUG: Options file is not writeable -->";
			}
		}
		
		//following code taken from http://us.php.net/manual/en/function.is-writable.php
		function is__writable($path) {
		//will work in despite of Windows ACLs bug
		//NOTE: use a trailing slash for folders!!!
		//see http://bugs.php.net/bug.php?id=27609
		//see http://bugs.php.net/bug.php?id=30931
		
			if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
				return is__writable($path.uniqid(mt_rand()).'.tmp');
			else if (is_dir($path))
				return is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
			// check tmp file for read/write capabilities
			$rm = file_exists($path);
			$f = @fopen($path, 'a');
			if ($f===false)
				return false;
			fclose($f);
			if (!$rm)
				unlink($path);
			return true;
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
                if (! wp_verify_nonce($_POST['_wpnonce'], 'hits_ie6_pngfix-update-options') ) die(_e('Whoops! There was a problem with the data you posted. Please go back and try again.', $this->localizationDomain)); 
                $this->options['hits_ie6_pngfix_method'] = $_POST['hits_ie6_pngfix_method'];   
				$this->options['hits_ie6_pngfix_THM_CSSSelector'] = $_POST['hits_ie6_pngfix_THM_CSSSelector'];
				$this->options['hits_ie6_debug']= $_POST['hits_ie6_debug'];
				$this->options['hits_ie6_pngfix_pagesAreCached'] = $_POST['hits_ie6_pngfix_pagesAreCached'];
                $this->saveAdminOptions();
                
                echo '<div class="updated"><p>'. __('Success! Your changes were sucessfully saved!', $this->localizationDomain) .'</p></div>';
            }
?>                                   
                <div class="wrap">
                <h2>HITS- IE6 PNG Fix</h2>
                <form method="post" id="hits_ie6_pngfix_options">
                <?php wp_nonce_field('hits_ie6_pngfix-update-options');?>
                <p><?php _e('This plugin brought to you for free by ', $this->localizationDomain);?><a href="http://www.homeitsolutions.ca/websites/wordpress-plugins/ie6-png-fix">Home I.T. Solutions</a>.</p>
                <p><?php _e('I take no credit for the great effort authors have gone into making each method of getting IE6 PNG compatability to work. I just did the work to merge them all into a single wordpress plugin.', $this->localizationDomain);?></p>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
                        <tr valign="top"> 
                            <th width="33%" scope="row"><?php _e('PNG Fix Method:', $this->localizationDomain); ?></th> 
                            <td>
                            <select name="hits_ie6_pngfix_method" id="hits_ie6_pngfix_method" style="width:200px;">
								<option value="THM1"<?php if (strcmp($this->options['hits_ie6_pngfix_method'],'THM1')==0) { echo ' selected="selected"';} ?>><?php _e('Twin Helix v1.0', $this->localizationDomain);?></option>
								<option value="THM2"<?php if (strcmp($this->options['hits_ie6_pngfix_method'],'THM2')==0) { echo ' selected="selected"';} ?>><?php _e('Twin Helix v2.0', $this->localizationDomain);?></option>
								<option value="UPNGFIX"<?php if (strcmp($this->options['hits_ie6_pngfix_method'],'UPNGFIX')==0) { echo ' selected="selected"';} ?>><?php _e('Unit PNG Fix', $this->localizationDomain);?></option>
								<option value="SUPERSLEIGHT"<?php if (strcmp($this->options['hits_ie6_pngfix_method'],'SUPERSLEIGHT')==0) { echo ' selected="selected"';} ?>><?php _e('SuperSleight', $this->localizationDomain);?></option>
                                <option value="DD_BELATED"<?php if (strcmp($this->options['hits_ie6_pngfix_method'],'DD_BELATED')==0) { echo ' selected="selected"';} ?>><?php _e('DD_belatedPNG', $this->localizationDomain);?></option>
                                
							</select>
                        </td> 
                        </tr>
                        <tr>
                        	<th width="33%" scope="row"><?php _e('CSS Selector:', $this->localizationDomain); ?></th>
                            <td><input type="text" name="hits_ie6_pngfix_THM_CSSSelector" value="<?php echo $this->options['hits_ie6_pngfix_THM_CSSSelector'] ?>" size="100" /><br /><?php _e('Note: CSS Selector is not used for Unit PNG Fix and SuperSleight.', $this->localizationDomain);?></td>
						</tr>
                        <tr valign="top"> 
                            <th width="33%" scope="row"><?php _e('Where detection should occur:', $this->localizationDomain); ?></th> 
                            <td>
                            <select name="hits_ie6_pngfix_pagesAreCached" id="hits_ie6_pngfix_pagesAreCached" style="width:200px;">
								<option value="false"<?php if (strcmp($this->options['hits_ie6_pngfix_pagesAreCached'],'false')==0) { echo ' selected="selected"';} ?>><?php _e('Pages are not cached (default)', $this->localizationDomain);?></option>
								<option value="true"<?php if (strcmp($this->options['hits_ie6_pngfix_pagesAreCached'],'true')==0) { echo ' selected="selected"';} ?>><?php _e('Pages are cached', $this->localizationDomain);?></option>
							</select><br /><?php _e('Note: Pages being cached rely on browser conditional comments, and can interfere with your theme.', $this->localizationDomain);?>
                        </td> 
                        </tr>
                        <tr>
                        	<th width="33%" scope="row"><?php _e('Plugin Debug Mode:', $this->localizationDomain); ?></th>
                            <td>
                            <select name="hits_ie6_debug" id="hits_ie6_debug" style="width:100px;">
                            	<option value="false" <?php if (strcmp($this->options['hits_ie6_debug'],'false')==0) { echo ' selected="selected"';} ?>><?php _e('False',$this->localizationDomain);?></option>
                            	<option value="true" <?php if (strcmp($this->options['hits_ie6_debug'],'true')==0) { echo ' selected="selected"';} ?>><?php _e('True',$this->localizationDomain);?></option>
                            </select><br /><?php _e('Note: Please set this to true if you are having difficulties with this plugin.', $this->localizationDomain);?>
                            </td>
                        </tr>
                        <tr>
                            <th colspan=2><input type="submit" name="hits_ie6_pngfix_save" value="<?php _e('Save',$this->localizationDomain);?>" /></th>
                        </tr>
                    </table>
                    
                    <p><?php _e('Feedback and requests are always welcome. ', $this->localizationDomain);?><a href="http://www.homeitsolutions.ca/websites/wordpress-plugins/ie6-png-fix"> <?php _e('Visit the plugin website', $this->localizationDomain);?></a> <?php _e('to leave any feedback, translations, comments or donations. All donations will go towards micro loans through', $this->localizationDomain);?> <a href="http://www.kiva.org">Kiva</a>.</p>
                    <h3><?php _e('PNG Fix Credits', $this->localizationDomain);?></h3>
                    <p><?php _e('The Twin Helix approaches were taken from', $this->localizationDomain);?> <a href="http://www.twinhelix.com/css/iepngfix/">Twin Helix</a></p>
                    <p><?php _e('The UnitInteractive approach was taken from', $this->localizationDomain);?> <a href="http://labs.unitinteractive.com/unitpngfix.php"> Unit Interactive Labs</a>.</p>
                    <p><?php _e('The SuperSleight apprach was taken from', $this->localizationDomain);?> <a href="http://allinthehead.com/retro/338/supersleight-jquery-plugin">Drew McLellan</a></p>
                	<p><?php _e('The DD_belatedPNG approach was taken from', $this->localizationDomain);?> <a href="http://dillerdesign.com/experiment/DD_belatedPNG/">DillerDesign</a></p>
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
