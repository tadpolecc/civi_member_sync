Wordpress CiviMember Role Sync Plugin: 
----------------------------------------

## The best bits of this made it in to @christianwach's new plugin - https://github.com/christianwach/civicrm-wp-member-sync  alos available here:  https://wordpress.org/plugins/civicrm-wp-member-sync/  We recommend to use that plugin for CiviCRM 4.6 or greater

A common need is to synchronize your CiviCRM members to your WordPress roles to allow you to have, among other things, members' only content on your website that is only accessible to current members defined by the rules and types you have set up in CiviCRM. 

This is a one way sync -- It syncs from CiviCRM to WordPress.  Users are not sync'd only roles.  WordPress roles will get updated based on CiviCRM memberships.

This plugin has been modified by Tadpole Collective, based on the work done by Jag Kandasamy http://www.orangecreative.net


Compatibility
--------------
This has been tested with CiviCRM 4.4.7 and 4.5.0

Configuring CiviMember Roles Sync Plugin:
------------------------------------------

Before you get started, be sure to have created all of your membership types and membership status rules for CiviMember as well as the WordPress role(s) you would like to synchronize them with.
Note: Only one membership type can synchronize with one WordPress role since a WordPress  user can have atmost only one role in WordPress).

1. The first step is to Download the Plugin and put it under your sites wp-content\plugins\ directory  and rename the folder as "civi_member_sync". 
   Then Activate the plugin by going to your WordPress site's Plugin page at http://example.com/wp-admin/plugins.php
   It will be CiviMember Role Synchronize.
   
2. Visit the Plugin's configuration page at http://example.com/wp-admin/admin.php?page=civi_member_sync/list.php or 
   go to http:///example.com/wp-admin/options-general.php menu and click CiviMember Roles Sync on the Left side.
      
3. Click on "Add Association Rule" to create a rule. You will need to create a rule for every CiviCRM membership type you would like to synchronize.
   For every membership type, you will need to determine the CiviMember states that define the member as "current" thereby granting them the 
   appropriate WordPress role. It is most common to define "New, Current, & Grace" as current. Similarly, select which states represent the "expired" 
   status thereby removing the WordPress role from the user. It is most common to define "Expired, Pending, Cancelled, & Deceased" as the expired
   status.Also Set the role to be assigned if the membership has Expired in  "Expiry Role".   
     
4. Return to the configuration page for CiviMember roles Sync (http://example.com/wp-admin/admin.php?page=civi_member_sync/list.php). 
   This setting determines when Wordpess will check if the user has a "Current" membership status in CiviCRM  whenever a user logs in or out of the
   site and synchronize the Wordpess Role.
   
5. The last option that is sometimes necessary is to manually synchronize users. Click on the "Manually Synchronize" in configuration page at 
   http://example.com/wp-admin/admin.php?page=civi_member_sync/list.php  tab to do so. 
   You will likely use this when you initially configure this module to synchronize your existing users.
   
Be sure to test this Plugin before using it in Production Environment. Log in as that user to ensure you have been granted the appropriate role. Then take away the membership for this user in their CiviCRM record, log back in as the test user, and make sure you no longer have that role.
This Plugin is Dependant only on CiviCRM.

Note: This plugin will sync membership roles on user login and on a daily basis.
  
