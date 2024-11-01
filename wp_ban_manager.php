<?php
/*
Plugin Name: WP ban manager
Description: Ban selected users and ips from commenting on your site. Also blocks commenting from Tor, if desired
Version: 1.1
Author: Joost Berculo
Author URI: http://www.sargasso.nl
*/
if (!class_exists('WPBMAdminPageFramework'))    include("framework/admin-page-framework.min.php");
if (!class_exists('wpTemplate'))                include("wpTemplate.class.php");

class WP_Ban_Manager extends WPBMAdminPageFramework
{

    static      $plugin_name            = "WP_Ban_Manager";
    static      $hidden_settings        = "hidden_settings";
    static      $max_tor_list_age       = 3600; //seconds
    static      $max_expired_ban_age    = 365; //in days, one year

    protected   $template;
    protected   $variables;

    public function __construct() {
        parent::__construct();
//		delete_option(self::$plugin_name); //uncomment this to delete the settings of the controller (can not be undone)
        $this->template                 = new wpTemplate();
        $this->variables                = WPBMAdminPageFramework::getOption(self::$plugin_name);
        add_action('preprocess_comment',    array(&$this,'check_comment_for_tor'),               10);
        add_action('preprocess_comment',    array(&$this,'check_for_ban'),                       20);
    }

    public function setUp() {
//		self::debug($this->variables);
        //set the left menu
        $this->setRootMenuPage( 'WP ban manager' );
        //add submenu items
        $this->addSubMenuItems(array(
            'title'     => 'Current bans',
            'page_slug' => 'wp_ban_manager_current'
        ),
            array(
                'title'     => 'Ban definitions',
                'page_slug' => 'wp_ban_manager_definitions'
            ),
            array(
                'title'     => 'General settings',
                'page_slug' => 'wp_ban_manager_general'
            )
        );
        //now fill the tabs!
        $this->_set_current();
        $this->_set_definitions();
        $this->_set_general();
    }

    /**
     * This function creates the list with the active and inactive bans.
     */
    public function do_wp_ban_manager_current() {
        if (!(isset($_GET['edit_ban'])||isset($_GET['add_ban']))) {
            if(isset($_GET['delete_ban'])) {
                $this->_delete_item_from_settings('wp_ban_manager_current_items',$_GET['delete_ban']);
            }
            if(isset($_GET['unban'])) {
                $this->set_variable('wp_ban_manager_current_items',$_GET['unban']."_is_active",0);
            }

//			$this->template->set('actions', $actions);
            //first update the ban list
            $this-> _get_current_bans();
            $bans =  $this->_get_all_bans();
            foreach($bans as $key => $ban) {
//				self::debug($ban);
                $ban_definition = $this->_get_ban_definitions($ban['ban_type']);
                $bans[$key]['ban_type'] = $ban_definition['name'];
            }
            $this->template->set('current_bans',$bans);
            $this->template->set('base_tab','wp_ban_manager_current');
            echo $this->template->fetch('current.tpl');
        }
    }

    /**
     * This function creates the list with defined banning options.
     */
    public function do_wp_ban_manager_definitions() {
        if (!(isset($_GET['edit_definition'])||isset($_GET['add_definition']))) {
            if(isset($_GET['delete_definition'])) {
                $this->_delete_item_from_settings('wp_ban_manager_definitions_items',$_GET['delete_definition']);
            }
            $this->template->set('ban_definitions', $this->_get_ban_definitions());
            $this->template->set('base_tab','wp_ban_manager_definitions');
            echo $this->template->fetch('definitions.tpl');
        }
    }

    private function _get_ban_definitions($type_id=false) {
        $flat_ban_definitions   = $this->get_variable('wp_ban_manager_definitions_items');
        $ban_definitions        = array();
        if(is_array($flat_ban_definitions)) foreach($flat_ban_definitions as $key => $value ) {
            list($id,$variable_name) = explode("_",$key,2);
            if ("submit"==$id) continue;
            if(!isset($ban_definitions[$id])) $ban_definitions[$id] = array();
            $ban_definitions[$id][$variable_name] = $value;
        }
//		debug($ban_definitions);
//		debug($type_id);

        if (!$type_id)  return $ban_definitions;
        else {
            if(isset($ban_definitions[$type_id])) {
                return $ban_definitions[$type_id];
            } else {
                return false;
            }
        }
    }

    private function _get_all_bans($ban_id=false) {
        $flat_current_bans   = $this->get_variable('wp_ban_manager_current_items');
        $current_bans        = array();
        if (is_array($flat_current_bans)) foreach($flat_current_bans as $key => $value ) {
            list($id,$variable_name) = explode("_",$key,2);
            if ("submit"==$id) continue;
            if(!isset($current_bans[$id])) $current_bans[$id] = array();
            $current_bans[$id][$variable_name] = $value;
        }
        if (!$ban_id)   return $current_bans;
        else            return $current_bans[$ban_id];
    }


    private function _set_current() {
        if (isset($_GET['edit_ban'])||isset($_GET['add_ban'])) {
            //Set up a section
            $this->addSettingSections(
                array(
                    'section_id'    => 'wp_ban_manager_current_items',
                    'page_slug'     => 'wp_ban_manager_current',
                    'title'         => 'Ban a user',
                    'description'   => 'Please provide the ban information (<a href="'.site_url().'/wp-admin/admin.php?page=wp_ban_manager_current">Back to overview</a>)',
                )
            );

            if(isset($_GET['edit_ban'])) {
                $id = $_GET['edit_ban'];
            } else {
                $id = md5(microtime());
            }

            $ip_field = array(
                'field_id'      => $id.'_ip',
                'section_id'    => 'wp_ban_manager_current_items',
                'title'         => 'IP',
                'type'          => 'text',
                'description'   => 'Please specify the IPs to ban. You can add multiple IPs, comma separated. You can also use wildcards for the last two positions: xxx.xxx.*.* and xxx.xxx.xxx.* are allowed.'
            );

            $name_field = array(
                'field_id'      => $id.'_name',
                'section_id'    => 'wp_ban_manager_current_items',
                'title'         => 'Name',
                'type'          => 'text',
                'description'   => 'Please provide the name of the banned person (use the username if you want to block the user as well)'
            );

            $definitions = $this->_get_ban_definitions();
            $definition_dropdown = array();
            foreach($definitions as $key => $definition) {
                $definition_dropdown[$key] = $definition['name'];
            }
            $ban_type_field = array(
                'field_id'	    =>	$id.'_ban_type',
                'section_id'	=>	'wp_ban_manager_current_items',
                'title'	        =>	"Ban Type",
                'type'	        =>	'select',
                //'tip'	        =>	__( 'This is the <em>select</em> field type.', 'admin-page-framework-demo' ),
                //'default'	    =>	2,	// the index key of the label array below which yields 'Yellow'.
                'label'	        =>	$definition_dropdown,
                'description'	=>	"Please specify the ban type",
            );

            $also_ban_user = array(
                'field_id'      => $id.'_ban_user',
                'section_id'    => 'wp_ban_manager_current_items',
                'title'         => 'Ban user account',
                'type'          => 'checkbox',
                'description'   => 'Should we ban the user name too? Make sure the name at the above field is a valid username, or this will not work',
            );

            $start_date = array(
                'field_id'      => $id.'_start_date',
                'section_id'    => 'wp_ban_manager_current_items',
                'title'         => 'Start date',
                'type'          => 'text',
                'default'       => date('Y-m-d'),
                'description'   => 'Set the start date of the ban, use YYYY-MM-DD format',
            );

            $message_field = array(
                'field_id'      => $id.'_message',
                'section_id'    => 'wp_ban_manager_current_items',
                'title'         => 'Personal message',
                'type'          => 'textarea',
                'rich'          => true,
                'description'   => 'Optional personal message that will be shown instead of the default message',
            );

            $ban_active = array(
                'field_id'      => $id.'_is_active',
                'section_id'    => 'wp_ban_manager_current_items',
                'title'         => 'Ban is active',
                'type'          => 'checkbox',
                'default'       => 1,
                'description'   => 'Unchecking this box means that the ban is not active, and although it is still in the list, it is not enforced',
            );

            //add the fields to the section
            $this->addSettingFields(
                $ban_active,
                $ip_field,
                $name_field,
                $ban_type_field,
                $also_ban_user,
                $start_date,
                $message_field,
                array(
                    'section_id'    =>   'wp_ban_manager_current_items',
                    'field_id'      =>   'submit',
                    'type'          =>   'submit',
                )
            );
        }
    }

    private function _set_definitions() {
        if (isset($_GET['edit_definition'])||isset($_GET['add_definition'])) {
            //Set up a section
            $this->addSettingSections(
                array(
                    'section_id'    => 'wp_ban_manager_definitions_items',
                    'page_slug'     => 'wp_ban_manager_definitions',
                    'title'         => 'Add ban definition',
                    'description'   => 'Please set a new ban definition (<a href="'.site_url().'/wp-admin/admin.php?page=wp_ban_manager_definitions">Back to overview</a>)',
                )
            );

            if(isset($_GET['edit_definition'])) {
                $id = $_GET['edit_definition'];
            } else {
                $id = md5(microtime());
            }
            $name_field = array(
                'field_id'      => $id.'_name',
                'section_id'    => 'wp_ban_manager_definitions_items',
                'title'         => 'Name',
                'type'          => 'text',
                'description'   => 'Please specify a name for the ban type'
            );

            $duration_field = array(
                'field_id'      => $id.'_duration',
                'section_id'    => 'wp_ban_manager_definitions_items',
                'title'         => 'Duration (coupled to period)',
                'type'          => 'text',
                'description'   => 'Please specify a duration for the ban type'
            );

            $timeframes = $this->_get_timeframes();

            $duration_period_field = array(
                'field_id'	    =>	$id.'_duration_period',
                'section_id'	=>	'wp_ban_manager_definitions_items',
                'title'	        =>	"Period",
                'type'	        =>	'select',
                //'tip'	        =>	__( 'This is the <em>select</em> field type.', 'admin-page-framework-demo' ),
                //'default'	    =>	2,	// the index key of the label array below which yields 'Yellow'.
                'label'	        =>	$timeframes,
                'description'	=>	"Please specify the period",
            );

            $message_field = array(
                'field_id'      => $id.'_message',
                'section_id'    => 'wp_ban_manager_definitions_items',
                'title'         => 'Message',
                'type'          => 'textarea',
                'rich'          => true,
                'description'   => 'Optional message that will be shown',);

            //add the fields to the section
            $this->addSettingFields(
                $name_field,
                $duration_field,
                $duration_period_field,
                $message_field,
                array(
                    'section_id'    =>   'wp_ban_manager_definitions_items',
                    'field_id'      =>   'submit',
                    'type'          =>   'submit',
                )
            );
        }
    }

    private function _get_timeframes() {
        return array(
            (60*60)         => "Hours",
            (24*60*60)      => "Days",
            (7*24*60*60)    => "Weeks",
            (30*24*60*60)   => "Months",
            (365*24*60*60)  => "Years"
        );
    }

    public function get_timeframe_name($key) {
        $timeframes = self::_get_timeframes();
        return $timeframes[$key];
    }

    private function _set_general() {
        $this->addSettingSections(
            array(
                'section_id'    => 'wp_ban_manager_general_items',
                'page_slug'     => 'wp_ban_manager_general',
                'title'         => 'General settings',
            )
        );
        $block_tor_field = array(
            'field_id'      => 'block_tor',
            'section_id'    => 'wp_ban_manager_general_items',
            'title'         => 'Block Tor comments',
            'type'          => 'checkbox',
            'default'       => 0,
            'description'   => 'Block comments, when originating from the Tor network',
        );

        $block_tor_for_accounts = array(
            'field_id'      => 'block_tor_for_accounts',
            'section_id'    => 'wp_ban_manager_general_items',
            'title'         => 'Block Tor comments for logged in users',
            'type'          => 'checkbox',
            'default'       => 0,
            'description'   => 'Block Tor comments, also when the commenter is logged in',
        );

        $block_tor_message = array(
            'field_id'      => 'block_tor_message',
            'section_id'    => 'wp_ban_manager_general_items',
            'title'         => 'Message',
            'type'          => 'textarea',
            'rich'          => true,
            'description'   => 'Optional message, that is shown to the offending commenter',
        );

        $erase_bans_after_time = array(
            'field_id'      => 'erase_bans_after_time',
            'section_id'    => 'wp_ban_manager_general_items',
            'title'         => 'Erase bans after expiration',
            'type'          => 'text',
            'default'       => self::$max_expired_ban_age,
            'description'   => 'Provide the number of days after which an expired ban will be removed from the system',
        );

        //add the fields to the section
        $this->addSettingFields(
            $block_tor_field,
            $block_tor_for_accounts,
            $block_tor_message,
            $erase_bans_after_time,
            array(
                'section_id'    =>   'wp_ban_manager_general_items',
                'field_id'      =>   'submit',
                'type'          =>   'submit',
            )
        );
    }

    public function check_comment_for_tor($commentdata) {
        if (function_exists('curl_version')) {
            $block_tor_for_accounts = $this->get_variable("wp_ban_manager_general_items","block_tor_for_accounts");
            $block_tor              = $this->get_variable("wp_ban_manager_general_items","block_tor");
            if (!$block_tor_for_accounts&&is_user_logged_in()) return $commentdata;
            if($block_tor) {
                $ip = $_SERVER['REMOTE_ADDR'];
                //$ip = "1.0.204.250";
                if ($this->_is_ip_in_tor_list($ip)) {
                    $alert = "Comments from the Tor network are not allowed.";
                    if(!$block_tor_for_accounts) $alert .= " However it is allowed to comment when logged in.";
                    $custom_message = $this->get_variable("wp_ban_manager_general_items","block_tor_message");
                    $raw_message = strip_tags($custom_message);
                    if (!empty($raw_message)) {
                        $alert .= "<br /><br />----------------- Message ---------------<br /><br />";
                        $alert .= $custom_message;
                    }
                    wp_die($alert);
                }
            }
        }
        return $commentdata;
    }

    private function _is_ip_in_tor_list($ip) {
        $options = $this->get_variable(self::$hidden_settings);
        if(time()-$options['tor_last_updated'] >= self::$max_tor_list_age) {
            $this->_retrieve_tor_list();
            $options = $this->get_variable(self::$hidden_settings);
        }
        $ips = unserialize($options['tor_ip_list']);
//		self::debug($ips);
        if(in_array(trim($ip),$ips)) return true;
        return false;
    }

    /**
     * Retrieves the list of TOR endpoints for the current domain.
     */
    private function _retrieve_tor_list() {
        $url            = "https://check.torproject.org/cgi-bin/TorBulkExitList.py?ip=".$_SERVER['SERVER_ADDR']."&port=";
        $ch             = curl_init();
        $timeout        = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data           = curl_exec($ch);
        if($data) $this->_process_tor_list($data);
        curl_close($ch);
    }

    private function _process_tor_list($data) {
        $tor_ips                        = explode("\n",$data);
        $options                        = $this->get_variable(self::$hidden_settings);
        $options['tor_ip_list']         = array();
        foreach ($tor_ips as $ip) {
            if(strpos($ip,"#")!==false) continue;
            $options['tor_ip_list'][]   = trim($ip);
        }
        $options['tor_ip_list']         = serialize($options['tor_ip_list']);
        $options['tor_last_updated']    = time();
        $this->set_variable(self::$hidden_settings,$options);
    }

    public function check_for_ban($commentdata) {
        $commenter_ip = $_SERVER['REMOTE_ADDR'];
        $bans = $this->_get_current_bans();
        $is_banned = false;
        foreach($bans as $id => $ban) {
            $ips = array_map('trim', explode(',', $ban['ip']));
            foreach($ips as $ip) {
                if ($ip == $commenter_ip) {
                    $is_banned = $id;
                    break 2; //break out of two foreach loops
                }
                if(strpos($ip,"*")) {
                    //below code allows for x.x.x.* and x.x.*.* entries. Skip other possibilities
                    //x.*.x.*  is not allowed
                    //first check for more than two *
                    if (substr_count($ip,"*")>2) continue;
                    //now check for a non empty field after the first *
                    $check = explode("*",$ip);
                    if (!empty($check[1]) && ($check[1]!=".")) continue;

                    $ip = str_replace("*","",$ip);
                    $ip = str_replace("..",".",$ip);
                    if(strpos($commenter_ip,$ip)===0) {
                        $is_banned = $id;
                        break 2; //break out of two foreach loops
                    }
                }
            }
            if($ban['ban_user'] && !empty($ban['name'])) {
                $user = wp_get_current_user();
                if (strtolower($user->user_login) == strtolower($ban['name'])) {
                    $is_banned = $id;
                    break;
                }
            }
        }
        if($is_banned) {
            $raw_message = strip_tags($bans[$is_banned]['message']);
            if (!empty($raw_message)) {
                $message = $bans[$is_banned]['message'];
            } else {
                $raw_message = strip_tags($bans[$is_banned]['ban_type_message']);
                if(!empty($raw_message)) {
                    $message = $bans[$is_banned]['ban_type_message'];
                } else {
                    $message = "You are banned";
                }
            }
//			self::debug($bans[$is_banned]);
            $message .= "<br /><br />Ban will end at: ".date('Y-m-d',$bans[$is_banned]['ban_end'])." (Y-m-d)";
            wp_die($message);
        }
        return $commentdata;
    }

    private function _get_current_bans() {
        $all_bans       = $this->_get_all_bans();
        $bans           = array();
        foreach($all_bans as $id => $ban) {
            $ban_definition = $this->_get_ban_definitions($ban['ban_type']);
            $ban_end = strtotime($ban['start_date'])+($ban_definition['duration']*$ban_definition['duration_period']);
            if($ban_end>time()) {
                //yes, ban is still active!
                $ban['ban_type_message']    = $ban_definition['message'];
                $ban['ban_end']             = $ban_end;
                $bans[$id]                  = $ban;
            } else {
                //ban is expired. Checck if it should be removed
                $days = $this->get_variable("wp_ban_manager_general_items","erase_bans_after_time");
                if(!$days) {
                    $days = self::$max_expired_ban_age;
                }
                $max_expiration_date_allowed = strtotime('now -'.$days.' days');
                if($max_expiration_date_allowed > $ban_end) {
                    //remove ban
                    $this->_delete_item_from_settings('wp_ban_manager_current_items',$id);
                } else {
                    //set ban to expired if not yet done
                    if ($ban['is_active']) {
                        $this->set_variable('wp_ban_manager_current_items',$id."_is_active",0);
                    }
                }
            }
        }
        return $bans;
    }

    /**
     * Get a a value from the $group and $key
     * @param $group
     * @param null $key
     * @return mixed
     */
    public function get_variable($group,$key = null) {
        if (is_null($key)) {
            if (isset($this->variables[$group])) return $this->variables[$group];
        }
        if (isset($this->variables[$group][$key])) return $this->variables[$group][$key];
        else return false;
    }

    /**
     * Set a $value in the $group with $key
     * @param $group
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set_variable($group,$key, $value = null) {
        $options = get_option(self::$plugin_name);
        if(!isset($options[$group])) $options[$group] = array();
        if(is_array($key)) $options[$group] = $key;
        else $options[$group][$key] = $value;
        update_option(self::$plugin_name,$options);
        $this->variables = $options; //also set the loaded variables...
    }

    private function _delete_item_from_settings($group,$id) {
        $options = get_option(self::$plugin_name);
        if(isset($options[$group])) foreach($options[$group] as $key => $value) {
            if(strpos($key,$id)===0) unset($options[$group][$key]);
        }
        update_option(self::$plugin_name,$options);
        $this->variables = $options; //also set the loaded variables...
    }
    /**
     * My own debug functions. Nothing much to it.
     * @param $value
     */
    public function debug($value) {
        echo "<pre style='display:block; border:1px solid black; padding:5px; background-color:lightgray; color: black;'>";
        if(is_array($value)||is_object($value)) print_r($value);
        else {
            if(is_null($value)) echo "Value is null";
            elseif($value===false) echo "Value is false";
            elseif($value===true) echo "Value is true";
            else echo "Value: ".$value;
        }
        echo "</pre>";
    }

    /**
     * Same here, but kill the script in the process :-)
     * @param null $value
     */
    public function debug_die($value = null) {
        self::debug($value);
        die;
    }
}

$wp_ban_manager = new WP_Ban_Manager();