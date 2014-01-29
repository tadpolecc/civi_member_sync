<?php
 /* 
    Plugin Name: Tadpole CiviMember Role Synchronize 
    Depends: CiviCRM
    Plugin URI: https://tadpole.cc
    Description: Plugin to syncronize members in CiviCRM with WordPress
    Author: Jag Kandasamy and Tadpole Collective
    Version: 0.1
    Author URI: https://tadpole.cc

    Based on CiviMember Role Synchronize by Jag Kandasamy of http://www.orangecreative.net.  This has been
    altered to use WP $wpdb class.
    
    */  
    
global $tadms_db_version;
$tadms_db_version = "1.0";

function tadms_install() {
   global $wpdb;
   global $tadms_db_version;

   $table_name = $wpdb->prefix . "civi_member_sync";
      
   $sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `wp_role` varchar(255) NOT NULL,
          `civi_mem_type` int(11) NOT NULL,
          `current_rule` varchar(255) NOT NULL,
          `expiry_rule` varchar(255) NOT NULL,
          `expire_wp_role` varchar(255) NOT NULL,
           PRIMARY KEY (`id`),         
           UNIQUE KEY `civi_mem_type` (`civi_mem_type`)
           )ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);    
   add_option("jal_db_version", $jal_db_version);    
}    
    
register_activation_hook(__FILE__,'tadms_install');    

/** function to schedule manual sync daily **/

function civi_member_sync_daily() {
  $users = get_users();

    require_once('civi.php');
    require_once 'CRM/Core/BAO/UFMatch.php';

        foreach($users as $user) {       
           $uid = $user->ID;
           if (empty($uid)) {
            continue;
           }           
             $sql = "SELECT * FROM civicrm_uf_match WHERE uf_id =$uid";
             $contact = CRM_Core_DAO::executeQuery($sql); 
             
            
            if($contact->fetch()) {
                $cid = $contact->contact_id;               
              $memDetails=civicrm_api("Membership","get", array ('version' => '3','page' =>'CiviCRM', 'q' =>'civicrm/ajax/rest', 'sequential' =>'1','contact_id' =>$cid));          
              if (!empty($memDetails['values'])) {
              foreach($memDetails['values'] as $key => $value){
                  $memStatusID = $value['status_id']; 
                  $membershipTypeID = $value['membership_type_id'];  
              }         
              }
        
                $userData = get_userdata( $uid );
                if(!empty($userData)){
                  $currentRole = $userData->roles[0];
                }
                //checking membership status and assign role
                $check = member_check($cid,$uid, $currentRole);     
                
            }       
         }
}

if( !wp_next_scheduled( 'civi_member_sync_refresh' ) ) {  
   wp_schedule_event( time(), 'daily', 'civi_member_sync_refresh' );  
} 
add_action( 'civi_member_sync_refresh', 'civi_member_sync_daily' );  
    
/** function to check user's membership record while login and logout **/   
function civi_member_sync_check() {    
    
    global $wpdb;
    global $user;
    global $current_user;  
    //get username in post while login  
    if (!empty($_POST['log'])) {        
      $username = $_POST['log']; 
      $userDetails = $wpdb->get_results("SELECT * FROM $wpdb->users WHERE user_login ='$username'");
      $currentUserID = $userDetails[0]->ID;             
    } else {
      $currentUserID = $current_user->ID;          
    }  
    //getting current logged in user's role    
    $current_user_role = new WP_User( $currentUserID );
    $current_user_role =  $current_user_role->roles[0];
    //echo $current_user_role . "\n";
       
    civicrm_wp_initialize( ); 
    //getting user's civi contact id and checkmembership details     
    if($current_user_role !='administrator') {       
      require_once 'CRM/Core/Config.php';
      $config = CRM_Core_Config::singleton();
      require_once 'api/api.php'; 
      $params = array ('version' => '3',
                          'page' => 'CiviCRM', 
                             'q' => 'civicrm/ajax/rest', 
                    'sequential' => '1', 
                        'uf_id' => $currentUserID);
      $contactDetails = civicrm_api("UFMatch","get", $params);
      $contactID = $contactDetails['values'][0]['contact_id'];       
      if(!empty($contactID)) {  
        $member = member_check($contactID,$currentUserID, $current_user_role );                             
      }       
    }
    return true; 
}
    
add_action('wp_login', 'civi_member_sync_check');
add_action('wp_logout', 'civi_member_sync_check');
//add_action('profile_update', 'civi_member_sync_check');
  
/** function to check membership record and assign wordpress role based on themembership status
input params
#CiviCRM contactID 
#ordpress UserID and 
#User Role **/
function member_check($contactID,$currentUserID, $current_user_role) {

  global $wpdb;
  global $user;
  global $current_user;  
  if($current_user_role !='administrator') {
    //fetching membership details
	  $memDetails=civicrm_api("Membership","get", array ('version' => '3','page' =>'CiviCRM', 'q' =>'civicrm/ajax/rest', 'sequential' =>'1','contact_id' =>$contactID));
    //print_r($memDetails); echo "\n";
    if (!empty($memDetails['values'])) {
		  foreach($memDetails['values'] as $key => $value){
		      $memStatusID = $value['status_id']; 
		      $membershipTypeID = $value['membership_type_id'];  
		  }         
	  }
      
    //fetching member sync association rule to the corsponding membership type 
    $table_name = $wpdb->prefix . "civi_member_sync";
	  $memSyncRulesDetails = $wpdb->get_results("SELECT * FROM $table_name WHERE `civi_mem_type`='$membershipTypeID'"); 
    //print_r($memSyncRulesDetails);
	  if(!empty($memSyncRulesDetails)) {
	    $current_rule =  unserialize($memSyncRulesDetails[0]->current_rule);
      //print_r($current_rule); echo "\n";
	    $expiry_rule =  unserialize($memSyncRulesDetails[0]->expiry_rule );
      //print_r($expiry_rule); echo "\n";
	    //checking membership status
	    if (isset($memStatusID) && array_search($memStatusID,$current_rule)) {       
		    $wp_role =  strtolower($memSyncRulesDetails[0]->wp_role);
        //print $wp_role; 
		    if($wp_role == $current_user_role){
         //print 'current member, up to date';      
		      return;
		    } else {
          //print 'current member, update';
		      $wp_user_object = new WP_User($currentUserID);
		      $wp_user_object->set_role("$wp_role"); 
		    }
	    } else {
		    $wp_user_object = new WP_User($currentUserID);
		    $expired_wp_role = strtolower($memSyncRulesDetails[0]->expire_wp_role);
        //print $expired_wp_role;
		    if (!empty($expired_wp_role)) {            
          //print 'expired member, update';
		   	  $wp_user_object->set_role("$expired_wp_role"); 
		    } else {
          //print 'expired member, up to date';
		   	  $wp_user_object->set_role("");           
		    }
	    }
	  }
   }
	 return true;
} 

/** function to set setings page for the plugin in menu **/
function setup_civi_member_sync_check_menu() {  
    
    add_submenu_page('CiviMember Role Sync', 'CiviMember Role Sync', 'List of Rules', 'add_users', 'civi_member_sync/settings.php');  
    add_submenu_page('CiviMember Role Manual Sync', 'CiviMember Role Manual Sync', 'List of Rules', 'add_users', 'civi_member_sync/manual_sync.php'); 
    add_options_page( 'CiviMember Role Sync', 'CiviMember Role Sync', 'manage_options', 'civi_member_sync/list.php');        
}  

add_action("admin_menu", "setup_civi_member_sync_check_menu"); 
add_action('admin_init', 'my_plugin_admin_init');
 
//create the function called by your new action
function my_plugin_admin_init() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-form');
}

function plugin_add_settings_link($links) {
	$settings_link = '<a href="admin.php?page=civi_member_sync/list.php">Settings</a>';
  	array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter( "plugin_action_links_$plugin", 'plugin_add_settings_link' );
?>