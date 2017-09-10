<?php
/* #####################################################################################################################
 *
 *  index.php
 *    Landing / Login page for RAD
 *
 *   Date      Author              Description
 *   --------  -----------------   -----------------------------------------------------
 *   08/2017   Michael Sayer       Designed and coded
 *
 * ################################################################################################################## */

require_once('./classes/app_autoloader.php');
// require_once (CONST_INCLUDE_PATH.'includes/config.php');
?>

<!--«« link styles »»--> 
<head>
<link href="./assets/css/style.css" rel="stylesheet" type="text/css" media="all" />
<link href="https://fonts.googleapis.com/css?family=Merriweather|Merriweather+Sans|Montserrat" rel="stylesheet">
<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
<meta charset="utf-8">
</head>

<body>

  <div class='pageContentContainer'>

    <!--««   nav bar   »»--> 
    <div class="navContainer">
      <h3 class="navLogo">RAD</h3>
    </div>

    <!--««   main form   »»--> 

    <div class='headlineContainer'>
      <h2>Republican for a Day</h2>
    </div>

    <div id='mainRadForm'>

        <div class='settingsContainer'>
          <h3 class='settingsInputLabel'>Name</h3>
          <div class='settingsHelpText'>This name will be on the voting registration form.</div>
          <form class='settingsForm'>
            <input class='settingsInputField requiredInputField' type='text' name='first_name' maxlength='256' placeholder='Enter your first name' value='' />
            <input class='settingsInputField requiredInputField' type='text' name='last_name' maxlength='256' placeholder='Enter your last name' value='' />
          </form>
        </div>
        <div class='settingsContainer'>
          <h3 class='settingsInputLabel'>Email</h3>
          <div class='settingsHelpText'>This email address will be how we contact you about future elections</div>
          <form class='settingsForm'>
            <input class='settingsInputField requiredInputField' type='text' name='email' placeholder='Enter your email address' value='' />
          </form>
        </div>
        <div class='settingsContainer'>
          <h3 class='settingsInputLabel'>Address</h3>
          <div class='settingsHelpText'>This address is where your registration packet will be sent</div>
          <form class='settingsForm'>
            <input id='addressOne' class='settingsInputField requiredInputField' type='text' name='addressOne' placeholder='Enter your street address' value='' /> 
            <input id='addressTwo' class='settingsInputField' type='text' name='addressTwo' placeholder='Enter your street address' value='' />
            <input class='settingsInputField requiredInputField' type='text' name='city' placeholder='Enter your city' value='' /> 
            <input class='settingsInputField requiredInputField' type='text' name='zip' placeholder='Enter your postal code' value='' /> 
          </form>
        </div>

        <div id='radSubmitButton' class='largeButton primaryButton'>SUBMIT</div>
        
    </div> <!--««  end mainRadForm   »»--> 

    <div class='mapContainer'>
        <!--«« MAP »»--> 
        <div id="map"></div>
    </div>

    <div class='footer'></div>

  </div> <!--««  end pageContentContainer   »»--> 


<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.js"></script>
<script type="text/javascript" src="./assets/js/app.js" async></script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB7lhnVdHj7ERMP_C8UzJLodOgdfEz1UaU"></script>
</body>