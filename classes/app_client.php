<?php
/* #####################################################################################################################
 *  FREEMAN DIGITAL VENTURES - KLOWD
 *  (c) 2016 All Rights Reserved
 *
 *  app_client.php
 *    Contains properties and functions for centralized calls to application functions.
 * 
 *    This interface operates as an "INCLUDE" as well as a "RESPONDER" depending on how it's used.
 *    If the request type is a "POST", then this class is being used as a responder (e.g. AJAX or API).
 *
 *   Date      Author              Description
 *   --------  -----------------   -----------------------------------------------------
 *   06/2016   Casey R. McMullen   Designed and coded
 *
 * ################################################################################################################## */
if (!defined('CONST_INCLUDE_PATH')) {define('CONST_INCLUDE_PATH', '../');}

header('Access-Control-Allow-Origin: *');

// start the PHP session
session_name('PROJECTGO');
if (!isset($_SESSION)) {session_start();}

// run the class autoloader
require_once(CONST_INCLUDE_PATH.'classes/app_autoloader.php');
// require_once(CONST_INCLUDE_PATH.'includes/config.php');

//----------------------------------------------------------------------------------------------------------------------
class App_Client {
  
  public $function_map;                     // Map to all public functions
  public $api_authorized;                   // Used for validating API calls.
  public $client_token;                     // Application client token.


//----------------------------------------------------------------------------------------------------------------------
  public function __construct($varAPIKey = NULL, $varAPISecureKey = NULL) {
    
    // this function requires parameters, if any of them are missing, then 
    // we need to just bomb out of this
    if (!$varAPIKey || !$varAPISecureKey) {
      $returnArray = $this->apiResponseBuilder('403');
      return $returnArray;
    }
    
    // get the api key object
    $cAppAPIKey = new App_API_Key;
    $returnArray = $cAppAPIKey->getObjectByKey($varAPIKey, $varAPISecureKey);
    if ($returnArray['success'] == TRUE && $returnArray['response'] != '204') {
      $this->api_authorized = TRUE;
      $this->client_token = $returnArray['dataArray'][0]['client_token'];
      $this->loadFunctionMap();
    } else {
      $this->api_authorized = FALSE;
    }
    
  }
  
  //--------------------------------------------------------------------------------------------------------------------
  public function apiResponseBuilder($varRespCode) {
    
    $cAppResponse = new App_Response();
    $returnArray = $cAppResponse->getResponse($varRespCode);
    unset($cAppResponse);
    return $returnArray;
    
  }

//----------------------------------------------------------------------------------------------------------------------
  public function execCommand($varFunctionName, $varFunctionParams) {
    
    // get the actual function name (if necessary) and the class it belongs to.
    $returnArray = $this->getCommand($varFunctionName);
    
    // if we don't get a function back, then raise the error
    if ($returnArray['success'] == FALSE) {
      return $returnArray;
    }
    
    $class = $returnArray['dataArray']['class'];
    $functionName = $returnArray['dataArray']['function_name'];
    
    // Execute User Profile Commands
    $cObjectClass = new $class();
    $returnArray = $cObjectClass->$functionName($varFunctionParams);
    
    unset($this);
    
    return $returnArray;
    
  }

//----------------------------------------------------------------------------------------------------------------------
  protected function getCommand($varFunctionName) {
    
    // get the actual function name (if necessary) and the class it belongs to.
    if (isset($this->function_map[$varFunctionName])) {
      $dataArray['class'] = $this->function_map[$varFunctionName]['class'];
      $dataArray['function_name'] = $this->function_map[$varFunctionName]['function_name'];
      $returnArray = $this->apiResponseBuilder('200');
      $returnArray['dataArray'] = $dataArray;
    } else {
      $returnArray = $this->apiResponseBuilder('400');
    }
    
    return $returnArray;
    
  }

//----------------------------------------------------------------------------------------------------------------------
  protected function loadFunctionMap() {
    
    // load up all public functions
    $this->function_map = array(
        
      'deleteUserProfile' => array('class' => 'User_Profile','function_name' => 'delete'),
      'listUserProfile' => array('class' => 'User_Profile','function_name' => 'listUserProfile'),
      'updateUserProfile' => array('class' => 'User_Profile','function_name' => 'update'),

    );
    
  }
  
} // end class 

//--------------------------------------------------------------------------------------------------------------------
// if this interface was posted to then it's being used as an API style call
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apiFunctionName'])) {
  
  // get the post parms
  if (isset($_POST['apiKey']))            {$apiKey = $_POST['apiKey'];}
  if (isset($_POST['apiSecureKey']))      {$apiSecureKey = $_POST['apiSecureKey'];}
  if (isset($_POST['apiFunctionName']))   {$functionName = $_POST['apiFunctionName'];}
  if (isset($_POST['apiFunctionParams'])) {$functionParams = $_POST['apiFunctionParams'];}
  
  // decode the function parameters array.
  if (isset($functionParams) && $functionParams != '') {$functionParams = json_decode($functionParams, true);}
  
  // unset the apiFunctionName in POST so that we don't accidentally snag included interfaces after this.
  if (isset($_POST['apiFunctionName'])) {unset($_POST['apiFunctionName']);}

  // instantiate this class
  $cAppClient = new App_Client($apiKey, $apiSecureKey); 
  
  if ($cAppClient->api_authorized == FALSE) {
    $returnArray = $cAppClient->apiResponseBuilder('403');
    $returnArray['params'] = $functionParams;
    echo(json_encode($returnArray));
  } 
  else {
    // you're good bro
    // let's add the client_app_token to the functionParams array ... because then, guess what?
    // in every function that's called in ProjectGo, we'll know what client application is calling it.
    $functionParams['appClientToken'] = $cAppClient->client_token;
    $returnArray = $cAppClient->execCommand($functionName, $functionParams);
    $returnArray['params'] = $functionParams;
    echo(json_encode($returnArray, JSON_PRETTY_PRINT));
  }
  
  if (isset($cAppClient)) {unset($cAppClient);}
  
}