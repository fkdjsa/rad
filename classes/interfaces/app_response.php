<?php 
/* #####################################################################################################################
 *  FREEMAN DIGITAL VENTURES - KLOWD
 *  (c) 2016 All Rights Reserved
 *
 *  app_Response.php
 *    Contains properties and functions for the "app response" utility.
 * 
 *    This interface operates only an "INCLUDE".
 *
 *   Date      Author              Description
 *   --------  -----------------   -----------------------------------------------------
 *   10/2016   Casey R. McMullen   Designed and coded
 *
 * ################################################################################################################## */
if (!defined('CONST_INCLUDE_PATH')) {
  // if accessing this class directly through URL, send 404 and exit
  // this section of code will only work if you have a 404.html file in your root document folder.
  header("Location: /404.html", TRUE, 404);
  echo file_get_contents($_SERVER['DOCUMENT_ROOT'].'/404.html');
  die;
}

// start the PHP session
session_name('PROJECTGO');
if (!isset($_SESSION)) {session_start();}

//----------------------------------------------------------------------------------------------------------------------
class App_Response  {

  //--------------------------------------------------------------------------------------------------------------------
  public function getResponse($varRespCode) {
    
    switch ($varRespCode) {
      
      case '200':
        $success = TRUE;
        $response = '200';
        $responseDesc = 'The request has succeeded';
        break;
      
      case '201':
        $success = TRUE;
        $response = '201';
        $responseDesc = 'Limited success. One or more batch requests failed for the command executed.';
        break;

      case '204':
        $success = TRUE;
        $response = '204';
        $responseDesc = 'The server has fulfilled the request, but there is no content.';
        break;

      case '400':
        $success = FALSE;
        $response = '400';
        $responseDesc = 'Bad Request. One or more required parameters were missing or invalid';
        break;

      case '401':
        $success = FALSE;
        $response = '401';
        $responseDesc = 'Forbidden. User does not exist.';
        break;

      case '402':
        $success = FALSE;
        $response = '402';
        $responseDesc = 'Forbidden. Authorization token does not exist.';
        break;
      
      case '403':
        $success = FALSE;
        $response = '403';
        $responseDesc = 'Forbidden. Request is missing proper API key data.';
        break;
      
      case '500':
        $success = FALSE;
        $response = '500';
        $responseDesc = 'Internal Server Error. The server encountered an unexpected condition which prevented it from fulfilling the request.';
        break;
      
      default:
        $success = TRUE;
        $response = '000';
        $responseDesc = 'Unknown application response request.';
      
    } // end switch
    
    // return array for when the API needs to return the passed params
    $returnArray = array('success' => $success, 'response' => $response, 'responseDesc' => $responseDesc);
    return $returnArray;
    
  }
  
} // end class