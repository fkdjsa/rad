<?php
/* #####################################################################################################################
 *  FREEMAN DIGITAL VENTURES - KLOWD
 *  (c) 2016 All Rights Reserved
 *
 *  data_access.php
 *    Base class object extended by other database interface classes
 *
 *   Date      Author              Description
 *   --------  -----------------   -----------------------------------------------------
 *   06/2016   Casey R. McMullen   Masterfully designed and coded
 *
 * ################################################################################################################## */
$url = $_SERVER['REQUEST_URI'];
$urlArr = parse_url($url);
if (isset($urlArr['path'])) {
  $pathArr = explode('/', $urlArr['path']);
  // the following comparisons will only work in production.
  if ((isset($pathArr[1])) AND ( $pathArr[1] == 'classes') AND ( isset($pathArr[2])) AND ( $pathArr[2] == 'interface')) {
    // if accessing this class directly through URL, send 404 and exit
    // this section of code will only work if you have a 404.html file in your root document folder.
    header("Location: /404.html", TRUE, 404);
    echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/404.html');
    die;
  }
}

//----------------------------------------------------------------------------------------------------------------------
class Data_Access {

  //--------------------------------------------------------------------------------------------------------------------
  // the constructor will validate the API Key and establish permissions.
  public function __construct() {
    
    // make the database connection
    $returnArray = $this->connectDB();
    
    if ($returnArray['success'] == FALSE) {
      
      // if the return array fails here, a developer email should be sent.
      $cAppEmail = new App_Email();
      $cAppEmail->sendAppErrorEmail(array('messageTypeCode' => 1000));
      unset($cAppEmail);
    }
    
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function __destruct() {}
    
  //--------------------------------------------------------------------------------------------------------------------
  //  Sets all database object properties to null
  protected function clearObjectProperties() {

    foreach ($this as $key => $value) {
      if ($key != 'object_name' || $key != 'object_view_name') {
        unset($this->$key);
      }
    }

  }

  //--------------------------------------------------------------------------------------------------------------------
  protected function connectDB() {

    // assume success
    $returnArray = $this->responseBuilder('200');
    
    // establish a database connection
    if (!isset($GLOBALS['dbConn'])) {
      $GLOBALS['dbConn'] = new mysqli('localhost', 'rad', 'idahoVote$', 'rad');
    }
    
    // if an error occurred, record it
    if (mysqli_connect_errno()) {
      // if an error occurred, raise it.
      $returnArray = $this->responseBuilder('500');
      $dataArray['origin'] = 'data_Access.connectDB';
      $dataArray['type'] = 'MySQL';
      $dataArray['msg_nbr'] = mysqli_connect_errno();
      $returnArray['dataArray'] = $dataArray;
    }
    
    return $returnArray;
    
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function convertObjectToArray($data) {
    if (is_array($data) || is_object($data)) {
      $result = array();
      foreach ($data as $key => $value) {
        $result[$key] = $this->objectToArray($value);
      }
      return $result;
    }
    return $data;
  }
  
  //--------------------------------------------------------------------------------------------------------------------
  protected function dbRunQuery($varQuery) {
    // input: a query string
    // returns result set if successful, error if not

    // assume success
    $returnArray = $this->responseBuilder('200');
    
    // attempt the query
    $rsArray = $GLOBALS['dbConn']->query($varQuery);
    
    // if an error occurred, raise it
    if (isset($GLOBALS['dbConn']->errno) && ($GLOBALS['dbConn']->errno != 0)) {
      // if an error occurred, raise it.
      $returnArray = $this->responseBuilder('500');
      $dataArray['origin'] = 'data_Access.class->dbRunQuery';
      $dataArray['type'] = 'MySQL';
      $dataArray['msg_nbr'] = $GLOBALS['dbConn']->errno;
      $dataArray['msg_descr'] = $GLOBALS['dbConn']->error;
      $dataArray['msg_query'] = $varQuery;
      $returnArray['dataArray'] = $dataArray;
      $rsArray = NULL;
      return $returnArray;
    }
    
    $returnArray['dataArray'] = $rsArray;
    return $returnArray;
    
  }

  //--------------------------------------------------------------------------------------------------------------------
  protected function dbRunScript($varSQLScript) {
    // input: a SQL script
    // returns: row number on INSERT
    
    // assume success
    $returnArray = $this->responseBuilder('200');
    
    // execute the query
    if (!$GLOBALS['dbConn']->query($varSQLScript)) {
      // if an error occurred, raise it.
      $returnArray = $this->responseBuilder('500');
      $dataArray['origin'] = 'dataAccess.class->dbRunScript';
      $dataArray['type'] = 'MySQL';
      $dataArray['msg_nbr'] = $GLOBALS['dbConn']->errno;
      $dataArray['msg_descr'] = $GLOBALS['dbConn']->error;
      $dataArray['msg_query'] = $varSQLScript;
      $returnArray['dataArray'] = $dataArray;
    } else {
      // capture the row if INSERT
      $dataArray['id'] = $GLOBALS['dbConn']->insert_id;
      $returnArray['dataArray'] = $dataArray;
    }

    return $returnArray;
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function delete($varParams = NULL) {
    // input:  record id (INT)
    
    $recordId = 0;
    
    if (isset($varParams['recordId'])) {$recordId = $varParams['recordId'];}
    
    $sqlScript = "DELETE FROM `" . CONST_DB_SCHEMA . "`.`" . $this->object_name . "` WHERE id = " . $recordId . ";";
    $returnArray = $this->dbRunScript($sqlScript);
    return $returnArray;
    
  }

  //--------------------------------------------------------------------------------------------------------------------
  protected function getObject($varQuery) {
    // input: a query string
    // populates the class object
    
    // get the data
    $returnArray = $this->getResultSetArray($varQuery);
    
    // if successful, load object properties
    if ($returnArray['success'] == TRUE) {
      if ($returnArray['response'] == '200') {
        $this->loadObjectProperties($returnArray['dataArray']);
      } 
    } else {
      $this->clearObjectProperties();
      $returnArray = $this->responseBuilder('400');
    }
    
    return $returnArray;
    
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function getObjectById($varRecordId) {
    // input: a record id (INT)
    // returns nothing, but populates the class object
    $query = "SELECT * FROM `" . CONST_DB_SCHEMA . "`.`" . $this->object_view_name . "` WHERE (id = " . $varRecordId . ");";
    $returnArray = $this->getObject($query);
    return $returnArray;
  }

  //--------------------------------------------------------------------------------------------------------------------
  protected function getResultSetArray($varQuery) {
    // input: a query string
    // returns: result set array if successful

    // assume success
    $returnArray = $this->responseBuilder('200');
    
    // get our record set
    $responseArray = $this->dbRunQuery($varQuery);
    
    if ($responseArray['success'] == FALSE) {
      // if an error occurred, raise it to the caller
      $returnArray = $this->responseBuilder($responseArray['response']);
      $returnArray['dataArray'] = NULL;
    }
    else {      
      
      $rsData = $responseArray['dataArray'];
      $rowCount = $rsData->num_rows;
      
      if ($rowCount != 0) {
        // move result set to an array
        while ($rsDataRow = mysqli_fetch_assoc($rsData)) {
          $rsArray[] = $rsDataRow;
        }
        
        // add array to return
        $returnArray['dataArray'] = $rsArray;
        
      } 
      else {
        // no data returned
        $returnArray['dataArray'] = NULL;
        $returnArray = $this->responseBuilder('204');
      }
      
    }
    
    return $returnArray;

  }

  //--------------------------------------------------------------------------------------------------------------------
  public function getRowCount($varParams = NULL) {
    // input: a query string add on for a "where" clause if necessary
    // returns: total number of rows in the table
    
    $queryAddOn = '';
    
    if (isset($varParams['queryAddOn'])) {$queryAddOn = $varParams['queryAddOn'];}
    
    // get our total dataset size, used for displaying paging results
    $query = "SELECT COUNT(id) as row_count FROM `" . CONST_DB_SCHEMA . "`.`" . $this->object_view_name . "`";
    // determine if we need to add on to this query
    if (isset($queryAddOn) && $queryAddOn != '') {
      $query .= " " . $queryAddOn . ";";
    } else {
      $query .= ";";
    }
    // run the query
    $rsData = $this->dbRunQuery($query);
    // if no error occurred, continue
    if (isset($rsData)) {
      if ($rsData['dataArray']->num_rows != 0) {
        // retrieve number of database rows
        $rsDataRow = $rsData['dataArray']->fetch_assoc();
        $varRowCount = $rsDataRow['row_count'];
        // kill the result set
        $rsData['dataArray']->free();
      }
    }
    // if no data return 0, otherwise return the row count
    if (!isset($varRowCount)) {
      $varRowCount = 0;
    };
    $returnArray = array('dataArray' => $varRowCount);
    return $returnArray;
  }
  
  //--------------------------------------------------------------------------------------------------------------------
  protected function loadObjectProperties($varResultSetArray) {

    foreach ($varResultSetArray[0] as $k => $v) {
      $this->$k = $v;
    }

  }

  //--------------------------------------------------------------------------------------------------------------------
  public function responseBuilder($varRespCode) {
    
    $cAppResponse = new App_Response();
    $returnArray = $cAppResponse->getResponse($varRespCode);
    unset($cAppResponse);
    return $returnArray;
    
  }

  //--------------------------------------------------------------------------------------------------------------------
  protected function updateRecord($varUpdateArray) {
    // input: field value array (array)
    // returns row number on insert
    
    // determine if we're updating or inserting.
    // if the value of array element zero (the record ID) is NOT equal to zero ...
    // then we're expecting to UPDATE a record.  I it IS zero, then we're going
    // to INSERT a record
    if ($varUpdateArray[0]['field_value'] != '0') {
      $insertData = FALSE;
      $sqlScript = "UPDATE `" . CONST_DB_SCHEMA . "`.`" . $this->object_name . "` SET ";
    } 
    else {
      $insertData = TRUE;
      $sqlScript = "INSERT INTO `" . CONST_DB_SCHEMA . "`.`" . $this->object_name . "` (";
      
      // load the INSERT sql script with the DB field names
      $uaCount = count($varUpdateArray);
      $uaIndex = 1; // purposely set it to 1, to skip the record ID element, which would be 0.
      $firstPass = TRUE;
      while ($uaIndex < $uaCount) {
        
        // if not first pass, add a comma behind the last field added to the script
        if ($uaIndex > 1) {
          $sqlScript .= ", ";
        }
        
        $sqlScript .= "`" . $varUpdateArray[$uaIndex]['field_name'] . "`";
        $uaIndex++;
        
      }
      
      $sqlScript .= ") VALUES (";
      
    }
    
    $uaCount = count($varUpdateArray);
    $uaIndex = 1; // purposely set it to 1, to skip the record ID element, which would be 0.
    $firstPass = TRUE;
    
    while ($uaIndex < $uaCount) {
      
      $fieldType = $varUpdateArray[$uaIndex]['field_type']; // 0=Number, 1=String
      $fieldName = $varUpdateArray[$uaIndex]['field_name'];
      $fieldValue = $varUpdateArray[$uaIndex]['field_value'];
      
      // string type fields will have single quotes around them.  Numbers won't.
      if ($fieldType == 1 && $fieldValue != "NULL") {
        $singleQuote = "'";
      } else {
        $singleQuote = "";
      }
      
      // if not first pass, add a comma behind the last field added to the script
      if ($uaIndex > 1) {
        $sqlScript .= ", ";
      }
      
      switch ($insertData) {
        case TRUE;
          $sqlScript .= $singleQuote . $fieldValue . $singleQuote;
          break;
        case FALSE;
          $sqlScript .= "`" . $fieldName . "`=" . $singleQuote . $fieldValue . $singleQuote;
          break;
      }
      
      $uaIndex++;
      
    }
    
    // add the closure to the sql statement, depending on statement type
    switch ($insertData) {
      case TRUE; // insert
        $sqlScript .= ");";
        break;
      case FALSE; // update
        $sqlScript .= " WHERE `id`=" . $varUpdateArray[0]['field_value'] . ";";
        break;
    }
    
    // execute the script
    $returnArray = $this->dbRunScript($sqlScript);
    return $returnArray;
  }
  
}