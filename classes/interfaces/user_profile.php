<?php 
/* #####################################################################################################################
 *  FREEMAN DIGITAL VENTURES - KLOWD
 *  (c) 2016 All Rights Reserved
 *
 *  user_profile.php
 *    Contains properties and functions for the "user_profile" database table.
 * 
 *   Date      Author              Description
 *   --------  -----------------   -----------------------------------------------------
 *   06/2016   Casey R. McMullen   Designed and coded
 *   06/2016   Michael Sayer       Designed and coded
 *
 * ################################################################################################################## */
if (!defined('CONST_INCLUDE_PATH')) {
  // if accessing this class directly through URL, send 404 and exit
  // this section of code will only work if you have a 404.html file in your root document folder.
  header("Location: /404.html", TRUE, 404);
  echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/404.html');
  die;
}

// start the PHP session
session_name('PROJECTGO');
if (!isset($_SESSION)) {session_start();}

//----------------------------------------------------------------------------------------------------------------------
class User_Profile extends Data_Access {
  
  public $id;                               // INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Record ID'
  public $first_name;                       // VARCHAR(45) NOT NULL COMMENT 'Users first name'
  public $last_name;                        // VARCHAR(45) NOT NULL COMMENT 'Users last name'
  public $email;                            // VARCHAR(128) NOT NULL COMMENT 'Users email'
  public $line_one;                         // varchar(45) DEFAULT NULL COMMENT 'Address line 1'
  public $line_two;                         // varchar(45) DEFAULT NULL COMMENT 'Address line 2'
  public $city;                             // varchar(45) DEFAULT NULL COMMENT 'City'
  public $postal_code;                      // varchar(20) DEFAULT NULL COMMENT 'Postal code'
  public $packet_sent_flag;                 // TINYINT(4) NOT NULL DEFAULT 1 COMMENT '0=Pending, 1=Sent'
  public $active_status_flag;               // TINYINT(4) NOT NULL DEFAULT 2 COMMENT '0=Disabled, 1=Active verfied, 2=Active non-verified'
  public $create_timestamp;                 // INT(11) NOT NULL COMMENT 'Creation date'
  public $last_modified_timestamp;          // INT(11) NOT NULL COMMENT 'Last modified date'
  
  protected $object_name = 'user_profile';
  protected $object_view_name = 'user_profile';

  //--------------------------------------------------------------------------------------------------------------------
  public function delete($varParams = NULL) {
    
    // default parameters
    $recordId = 0;
    
    // override parameter defaults
    if (isset($varParams['id']))      { $recordId = $varParams['id']; }
    
    // record id is required
    if ($recordId == 0) {
      $returnArray = $this->responseBuilder('400');
      return $returnArray;
    }
    
    // insert code here to remove all AWS images for this object
    $cAWSS3 = new AWS_S3();
    $returnArray = $cAWSS3->deleteContentFolder(['prefix' => 'content/users/' . $recordId . '/']);
    
    // if the AWS content got deleted successfully, proceed with the database record.
    if ($returnArray['response'] == '200') {
      $sqlScript = "DELETE FROM `" . CONST_DB_SCHEMA . "`.`" . $this->object_name . "` WHERE id = " . $recordId .";";
      $returnArray = $this->dbRunScript($sqlScript);
    }
    
    return $returnArray;

  }
  
  //--------------------------------------------------------------------------------------------------------------------
  public function getObjectByEmail($varParams) { 
    // input: an email address (VARCHAR)
    // input: a password (VARCHAR)
    // returns nothing. Populates db class object
    
    $email = '';
    
    if (isset($varParams['email'])) { $email = $varParams['email']; }
    
    $query = "SELECT * FROM `" . CONST_DB_SCHEMA . "`.`" . $this->object_view_name . "` ";
    $query .= "WHERE (email = '" . $email . "');";
    
    $returnObject = $this->getObject($query);
    
    return $returnObject;
  }
  
  //--------------------------------------------------------------------------------------------------------------------
  private function insertDefaultAttrRels($varNewUserId) {
    
    if (isset($varNewUserId)) {$userId = $varNewUserId;}
    
    $cUserProfileAppAttrRel = new User_Profile_App_Attr_Rel();
    
    $userProfileAppAttrRelDefaults = array(
        'EMAIL_SYSTEM_NOTIFICATIONS' => 1,
        'EMAIL_CONNECTION_REQUESTS' => 1,
    );
    
    $cAppAttr = new App_Attr();
    
    // loop through attr's
    foreach ($userProfileAppAttrRelDefaults as $key => $val) {
      
      $cAppAttr->getObjectByAttrName($key);
      
      $updateArray = array();
      $updateArray['userProfileAppAttrRelId'] = 0;
      $updateArray['objectProperties']['user_profile_id'] = $userId;
      $updateArray['objectProperties']['app_attr_id'] = $cAppAttr->id;
      $updateArray['objectProperties']['paired_value'] = $val;
      
      $cUserProfileAppAttrRel->update($updateArray);
      
    }
    
    if (isset($cAppAttr)) {unset($cAppAttr);}
    if (isset($cUserProfileAppAttrRel)) {unset($cUserProfileAppAttrRel);}
    
  }
  
  //--------------------------------------------------------------------------------------------------------------------
  public function listUserProfile($varParams = NULL) { 
    
    // set parameter defaults
    $userProfileId = 0;
    $offset = 0;
    $resultsPerPage = 10;
    $sortField = 'first_name';
    
    // unpack the parameters
    if (isset($varParams['userProfileId']))     {$userProfileId = $varParams['userProfileId'];}
    if (isset($varParams['userProfileEmail']))  {$email = $varParams['userProfileEmail'];}
    if (isset($varParams['postalCode']))        {$postalCode = $varParams['postalCode'];}
    if (isset($varParams['searchText']))        {$searchText = $varParams['searchText'];}
    if (isset($varParams['sortField']))         {$sortField = $varParams['sortField'];}
    if (isset($varParams['offset']))            {$offset = $varParams['offset'];}
    
    $whereIncluded = FALSE;
    $firstIteration = TRUE;
    
    // start the query
    $query = "SELECT * FROM `" . CONST_DB_SCHEMA . "`.`" . $this->object_view_name . "` ";
    
    // if user profile id was included (will be used for search)
    if ($userProfileId != 0){
      $query .= " WHERE (id = " . $userProfileId . ")";
      $whereIncluded = TRUE;
    }
    
    if (isset($email) && $email !== '') {
      if ($whereIncluded == FALSE) {
        $query .= " WHERE (email = '" . $email . "')";
        $whereIncluded = TRUE;
      } else {
        $query .= " AND (email = '" . $email . "')";
      }
    }

    if (isset($postalCode) && $postalCode !== '') {
      if ($whereIncluded == FALSE) {
        $query .= " WHERE (postal_code = '" . $postalCode . "')";
        $whereIncluded = TRUE;
      } else {
        $query .= " AND (postal_code = '" . $postalCode . "')";
      }
    }
    
    // if search text was included, add it
    if (isset($searchText) && $searchText != "") {
      
      if( preg_match('/\s/',$searchText)){ //if there are spaces in the search query
        $searchTerms = explode(' ', $searchText);
        foreach($searchTerms as $key => $value){
          if ($firstIteration === TRUE) {
            $query .= ' AND ((first_name LIKE "%' . $value . '%") OR (last_name LIKE "%' . $value . '%")';
            $query .= ' OR (email LIKE "%' . $value . '%")';
            $firstIteration = FALSE;
          } else {
            $value = ' ' .$value; // add the space back into the query
            $query .= ' OR (first_name LIKE "%' . $value . '%") OR (last_name LIKE "%' . $value . '%")';
            $query .= ' OR (email LIKE "%' . $value . '%")';
          }
        }//end for each
        $query .= ')';
        
      } else { // if no spaces were included
        if ($whereIncluded == TRUE) {
          $query .= ' AND ((first_name LIKE "%' . $searchText . '%") OR (last_name LIKE "%' . $searchText . '%")';
          $query .= ' OR (email LIKE "%' . $searchText . '%"))';
        } else {
          $query .= ' WHERE ((first_name LIKE "%' . $searchText . '%") OR (last_name LIKE "%' . $searchText . '%")';
          $query .= ' OR (email LIKE "%' . $searchText . '%"))';
        }
      }
    }
    
    // add sort field(s)
    if (isset($sortField) && ($sortField !== '')) {
      $query .= ' ORDER BY ' . $sortField;
    } else {
      $query .= ' ORDER BY last_name, first_name';
    }
    
    // if paging through results, then limit our query
    if ($resultsPerPage != 0) {
      $query .= " LIMIT ".$offset.", ".$resultsPerPage.";";
    } else {
      $query .= ";";
    }
    
    // get the result set array
    $returnArray = $this->getResultSetArray($query);
    return $returnArray;
    
  }
  
  //--------------------------------------------------------------------------------------------------------------------
  public function listUsersNotVerified() {
    
    $query = "SELECT * FROM `" . CONST_DB_SCHEMA . "`.`" . $this->object_view_name . "` ";
    
    $query .= " WHERE active_status_flag = 0 OR active_status_flag = 2;";
    
    // get the result set array
    $returnArray = $this->getResultSetArray($query);
    return $returnArray;
    
  }
  
  //--------------------------------------------------------------------------------------------------------------------
  public function update($varParams) {
    
    // set defaults
    $updateArray = NULL;
    $errorFreeFlag = TRUE;

    $userProfileId = 0;
    
    // unpack the parameters
    if (isset($varParams['userProfileId'])) {$userProfileId = $varParams['userProfileId'];}
    
    // unpack and verify object properties to be updated
    if (isset($varParams['objectProperties'])) {
      $objectProperties = $varParams['objectProperties'];
      
      // if it existed but doesn't have anything in it, then we're done
      if (count($objectProperties) == 0) {
        $returnArray = $this->responseBuilder('400');
        return $returnArray;
      }
    }
    else {
      // we didn't get handed anything to insert or update, so why bother?
      $returnArray = $this->responseBuilder('400');
      return $returnArray;
    }
    
    // we need to instantiate the object and populate it, if possible
    $cUserProfile = new User_Profile();
    if ($userProfileId > 0) {
      $returnArray = $cUserProfile->getObjectById($userProfileId);
      if ($returnArray['success'] == FALSE) {
        return $returnArray;
      }
    }
    
    // load up the object properties that were passed
    foreach($objectProperties as $propertyName => $propertyValue){
      $cUserProfile->$propertyName = $propertyValue;
    }
    
    // validate fields
    $uaIndex = 0; // array element zero must always be the record Id. 
    $updateArray[$uaIndex]['field_type'] = 0;
    $updateArray[$uaIndex]['field_name'] = 'id';
    $updateArray[$uaIndex]['field_value'] = $userProfileId;
    
    if (!isset($cUserProfile->first_name)) {
      $errorFreeFlag = FALSE;
    } else {
      $uaIndex++;
      $updateArray[$uaIndex]['field_type'] = 1;    // 0=Number, 1=String
      $updateArray[$uaIndex]['field_name'] = "first_name";
      $updateArray[$uaIndex]['field_value'] = addslashes($cUserProfile->first_name);
    }
    
    if (!isset($cUserProfile->last_name)) {
      $errorFreeFlag = FALSE;
    } else {
      $uaIndex++;
      $updateArray[$uaIndex]['field_type'] = 1;    // 0=Number, 1=String
      $updateArray[$uaIndex]['field_name'] = "last_name";
      $updateArray[$uaIndex]['field_value'] = addslashes($cUserProfile->last_name);
    }
    
    if (!isset($cUserProfile->email)) {
      $errorFreeFlag = FALSE;
    } else {
      $uaIndex++;
      $updateArray[$uaIndex]['field_type'] = 1;    // 0=Number, 1=String
      $updateArray[$uaIndex]['field_name'] = "email";
      $updateArray[$uaIndex]['field_value'] = $cUserProfile->email;
    }
    
    $uaIndex++;
    $updateArray[$uaIndex]['field_type'] = 1;    // 0=Number, 1=String
    $updateArray[$uaIndex]['field_name'] = "line_one";
    if (!isset($cUserProfile->line_one) || $cUserProfile->line_one === '') {
      $updateArray[$uaIndex]['field_value'] = "NULL";
    } else {
      $updateArray[$uaIndex]['field_value'] = addslashes($cUserProfile->line_one);
    }
    
    $uaIndex++;
    $updateArray[$uaIndex]['field_type'] = 1;    // 0=Number, 1=String
    $updateArray[$uaIndex]['field_name'] = "line_two";
    if (!isset($cUserProfile->line_two) || $cUserProfile->line_two === '') {
      $updateArray[$uaIndex]['field_value'] = "NULL";
    } else {
      $updateArray[$uaIndex]['field_value'] = addslashes($cUserProfile->line_two);
    }
    
    $uaIndex++;
    $updateArray[$uaIndex]['field_type'] = 1;    // 0=Number, 1=String
    $updateArray[$uaIndex]['field_name'] = "city";
    if (!isset($cUserProfile->city) || $cUserProfile->city === '') {
      $updateArray[$uaIndex]['field_value'] = "NULL";
    } else {
      $updateArray[$uaIndex]['field_value'] = addslashes($cUserProfile->city);
    }
    
    $uaIndex++;
    $updateArray[$uaIndex]['field_type'] = 0;    // 0=Number, 1=String
    $updateArray[$uaIndex]['field_name'] = "postal_code";
    if (!isset($cUserProfile->postal_code) || $cUserProfile->postal_code === '') {
      $updateArray[$uaIndex]['field_value'] = 1;
    } else {
      $updateArray[$uaIndex]['field_value'] = $cUserProfile->postal_code;
    }
    
    $uaIndex++;
    $updateArray[$uaIndex]['field_type'] = 0;    // 0=Number, 1=String
    $updateArray[$uaIndex]['field_name'] = "packet_sent_flag";
    if (!isset($cUserProfile->packet_sent_flag) || $cUserProfile->packet_sent_flag === '') {
      $updateArray[$uaIndex]['field_value'] = 1;
    } else {
      $updateArray[$uaIndex]['field_value'] = $cUserProfile->packet_sent_flag;
    }

    $uaIndex++;
    $updateArray[$uaIndex]['field_type'] = 0;    // 0=Number, 1=String
    $updateArray[$uaIndex]['field_name'] = "active_status_flag";
    if (!isset($cUserProfile->active_status_flag) || $cUserProfile->active_status_flag === '') {
      $updateArray[$uaIndex]['field_value'] = 2;
    } else {
      $updateArray[$uaIndex]['field_value'] = $cUserProfile->active_status_flag;
    }

    if ($cUserProfile->id == 0) {
      $uaIndex++;
      $updateArray[$uaIndex]['field_type'] = 0; // 0=Number, 1=String
      $updateArray[$uaIndex]['field_name'] = "create_timestamp";
      $updateArray[$uaIndex]['field_value'] = strtotime(date("Y-m-d H:i:s"));
    }
    
    $uaIndex++;
    $updateArray[$uaIndex]['field_type'] = 0; // 0=Number, 1=String
    $updateArray[$uaIndex]['field_name'] = "last_modified_timestamp";
    $updateArray[$uaIndex]['field_value'] = strtotime(date("Y-m-d H:i:s"));
    
    if ($errorFreeFlag == TRUE) {
      
      $returnArray = $cUserProfile->updateRecord($updateArray);
      $newRecordId = (int) $returnArray['dataArray']['id'];
      
      // if this is a new user
      if (isset($newRecordId) && $newRecordId != 0) {
        
        // insert default App Attr Relationships 
        // $cUserProfile->insertDefaultAttrRels($newRecordId);
        
        // send Welcome email
        // $cAppEmail = new App_Email();
        // $cAppEmail->sendWelcomeEmail( $cUserProfile->email );
        // $cAppEmail->sendVerificationEmail( $cUserProfile, $newRecordId );
        // unset($cAppEmail);
        
        // if a new record - return the entire object to be used in the calling function 
        $newRecordItem = $this->listUserProfile(['userProfileId'=>$newRecordId]);
        if ($newRecordItem['response'] == '200') {
          $returnArray['dataArray'] = $newRecordItem['dataArray'];
        }
      }
      
    } else {
      $returnArray = $cUserProfile->responseBuilder('400');
    }
    
    if (isset($cUserProfile)) {unset($cUserProfile);}
    
    return $returnArray;
    
  }
  
  //--------------------------------------------------------------------------------------------------------------------
  public function validateUserEmail($varParams) {
    
    // set defaults
    $userProfileId = 0;
    $userEmail = '';
    $callingFunction = '';
    
    // unpack the parameters
    if (isset($varParams['userProfileId']))  {$userProfileId = $varParams['userProfileId'];}
    if (isset($varParams['userEmail']))      {$userEmail = $varParams['userEmail'];}
    if (isset($varParams['callingFunction'])){$callingFunction = $varParams['callingFunction'];}
    
    // check to see if email address is valid
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
      $returnArray['validationNote'] = 'NOT VALID';
      $returnArray['callingFunction'] = $callingFunction;
      return $returnArray;
    } else {
      
      // check to see if this email address is being used.
      $cUserProfile = new User_Profile();

      // if this is an existing user, we need to get their record
      if ($userProfileId != 0) {
        $cUserProfile->getObjectById($userProfileId);
        $existingUserEmail = $cUserProfile->user_email;
      } else {
        $existingUserEmail = '';
      }

      $queryAddon = "WHERE email = '" . $userEmail . "'";
      $returnArray = $cUserProfile->getRowCount( array('queryAddOn' => $queryAddon) );
      $rowCount = $returnArray['dataArray'];
      
      
      
      // if the email matches the existing user, that's ok.
      if ($userProfileId != 0 && strtoupper($userEmail) == strtoupper($existingUserEmail)) {
        $rowCount = 0;
      }
      if ($rowCount != 0) {
        $returnArray['validationNote'] = 'EMAIL FOUND';
        $returnArray['callingFunction'] = $callingFunction;
        return $returnArray;
      }
      
    }
    
    // if we have gotten this far, the email is valid
    $returnArray['validationNote'] = 'EMAIL VALID AND NOT FOUND';
    $returnArray['callingFunction'] = $callingFunction;
    return $returnArray;
    
    
    
  }

  

  
  
} // end class