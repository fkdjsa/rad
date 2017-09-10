<?php 
/* #####################################################################################################################
 *  FREEMAN DIGITAL VENTURES - KLOWD
 *  (c) 2016 All Rights Reserved
 *
 *  app_autoloader.php
 * 
 *   Loads all classes automatically when instantiated.
 *
 *   Date      Author              Description
 *   --------  -----------------   -----------------------------------------------------
 *   06/2016   Casey R. McMullen   Designed and coded
 *
 * ################################################################################################################## */
// if (!defined('CONST_INCLUDE_PATH')) {
//   // if accessing this class directly through URL, send 404 and exit
//   // this section of code will only work if you have a 404.html file in your root document folder.
//   header("Location: /404.html", TRUE, 404);
//   echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/404.html');
//   die;
// }
if (!defined('CONST_INCLUDE_PATH')) {define('CONST_INCLUDE_PATH', '../');}
//----------------------------------------------------------------------------------------------------------------------
// Build the class mapping array
$mapping = array(
    
    // interface classes
    // 'App_Email' => CONST_INCLUDE_PATH . 'classes/app_email.php',
    // 'App_Utility' => CONST_INCLUDE_PATH . 'classes/app_utility.php',
    // 'App_API_Key' => CONST_INCLUDE_PATH . 'classes/interfaces/app_api_key.php',
    // 'App_Attr' => CONST_INCLUDE_PATH . 'classes/interfaces/app_attr.php',
    // 'App_Attr_Type' => CONST_INCLUDE_PATH . 'classes/interfaces/app_attr.php',
    // 'App_Config' => CONST_INCLUDE_PATH . 'classes/interfaces/app_config.php',
    'App_Response' => CONST_INCLUDE_PATH . 'classes/interfaces/app_response.php',
    'Data_Access' =>  CONST_INCLUDE_PATH . 'classes/interfaces/data_access.php',
    'User_Profile' => CONST_INCLUDE_PATH . 'classes/interfaces/user_profile.php'
);

//----------------------------------------------------------------------------------------------------------------------
spl_autoload_register(function ($class) use ($mapping) {
    if (isset($mapping[$class])) {
        require_once $mapping[$class];
    }
}, true);