/* #####################################################################################################################
 *
 *  app.js
 *    JS routines for RAD.
 *
 *   Date      Author              Description
 *   --------  -----------------   -----------------------------------------------------
 *   08/2017   Michael Sayer       Designed and coded.
 *
 * ################################################################################################################## */


 $(document).ready(function() {
  var geocoder;
  var map;

  // init map
  initialize();

  //--------------------------------------------------------------------------------------------------------------------
  //       FUNCTIONS
  //--------------------------------------------------------------------------------------------------------------------

  // disable automagical form submissions
  $(function() {
      $("form").submit(function() { return false; });
  });

  //----------------------------------------------------------------------------

  $(document).on('keyup', function (e) {
    var code;
    if (typeof e.keyCode !== 'undefined' && String(e.keyCode) !== 'null') {
      code = e.keyCode;
    } else {
      code = e.which;
    }
    if (parseInt(code, 10) === 27) {
      if ($('.closeIconDiv').is(':visible')) {
        $('.closeIconDiv').trigger('click');
      }
    }
    if (parseInt(code, 10) === 13) {

    }

    e.preventDefault();
    e.stopPropagation();
    return false;

  });

  //----------------------------------------------------------------------------

  function interfaceCall(varFunctionParams, varFunctionName, varCallback) {
    var apiParams;

    apiParams = {
      // apiKey: $('#jsProjectGoApiKey').html(),
      // apiSecureKey: $('#jsProjectGoApiSecretKey').html(),
      apiFunctionName: varFunctionName,
      apiFunctionParams: JSON.stringify(varFunctionParams)
    };

    $.ajax({
      type: 'POST',
      // url: $('#constHostPrefix').html() + 'classes/app_client.php',
      url: 'http://localhost/rad/classes/app_client.php',
      data: apiParams,
      success: function(res) {

        console.log('[internalAPI : ] ' + res);
        var jsonData = jQuery.parseJSON(res);

        //redirect to call back with the response - decoded
        if (typeof varCallback !== 'undefined' && varCallback !== null) {
            varCallback(jsonData);
        }//end if

      },
      error: function(res){
        console.log(res);
      }

    });

   } //end function

  //----------------------------------------------------------------------------

  function notifyUser(varCode, varText) {
    var appendHTML, notificationClass;

    // decide what color to use
    switch (varCode) {

      // Good - Green
      case 1:
        notificationClass = 'alertGood';
        break;

      // Cautionary - Yellow
      case 2:
        notificationClass = 'alertCaution';
        break;

      // Bad - Red
      case 3:
        notificationClass = 'alertBad';
        break;

      // Informational - Blue
      case 4:
        notificationClass = 'alertInfo';
        break;

    }

    // build a notification to display at the top of the screen
    appendHTML = '<div class="dropDownNotification hide ' + notificationClass + '">' +
                     '<div class="messageText">' + varText + '</div>' +
                 '</div>';

    // slideDown the notification
    $('.pageVariables').after(appendHTML);

    $('.dropDownNotification').slideDown('300', function() {

      $('.dropDownNotification').removeClass('hide');

      setTimeout(function() {
        $('.dropDownNotification').slideUp('300', function() {
          $('.dropDownNotification').remove();
        });
      }, 700);
    });

  }

  //----------------------------------------------------------------------------

  function saveRADCallback(varData) {
    console.log(varData);
  }

  //----------------------------------------------------------------------------

  function saveRad() {
    var apiFunctionParams, apiFunctionName;

    apiFunctionParams = {
      'userProfileId': $('#userId').html(),
      'objectProperties' : {
        'id': 0,
        'first_name': $('.settingsInputField[name=first_name]').val(),
        'last_name': $('.settingsInputField[name=last_name]').val(),
        'email': $('.settingsInputField[name=email]').val(),
        'line_one': $('.settingsInputField[name=line_one]').val(),
        'line_two': $('.settingsInputField[name=line_two]').val(),
        'city': $('.settingsInputField[name=city]').val(),
        'postal_code': $('.settingsInputField[name=postal_code]').val()
      }
    };

    apiFunctionName = 'updateUserProfile';

    interfaceCall(apiFunctionParams, apiFunctionName, saveRADCallback);
  }

  //----------------------------------------------------------------------------

  function listCountyOfficeCallback(varData) {
    console.log(varData);
  }

  //----------------------------------------------------------------------------

  function listCountyOffice(varCountyDetails) {
    var apiFunctionParams, apiFunctionName;
    
    apiFunctionParams = {
    };

    apiFunctionName = 'listCountyOffice';

    interfaceCall(apiFunctionParams, apiFunctionName, listCountyOfficeCallback);
  }

  //----------------------------------------------------------------------------
  // MAPS SHIT
  //----------------------------------------------------------------------------

  function initialize() {
    geocoder = new google.maps.Geocoder();
    var latlng = new google.maps.LatLng(43.618881,-116.215019);
    var mapOptions = {
      zoom: 10,
      center: latlng
    }
    map = new google.maps.Map(document.getElementById('map'), mapOptions);
  }

  //----------------------------------------------------------------------------

  function codeAddress() {

    var addressOne = $('#addressOne').val();
    var addressTwo = $('#addressTwo').val();
    var city = $('#mainRadForm').find('.settingsInputField[name=city]').val();
    var zip = $('#mainRadForm').find('.settingsInputField[name=zip]').val();
    var county;

    // check to see if we got both lines
    if (addressTwo === '') {
      address = addressOne + ' ' + city + ' Idaho ' + zip;
    } else {
      address = addressOne + ' ' + addressTwo + ' Idaho ' + city + ' ' + zip;
    }

    geocoder.geocode( { 'address': address}, function(results, status) {
      if (status == 'OK') {
        console.log(status);
        console.log(results);

        // get county
        county = results[0].address_components[3].long_name;

        console.log('Your county is: ', county);

        // pair up with county office from database
        listCountyOffice(county);
        
        map.setCenter(results[0].geometry.location);
        var marker = new google.maps.Marker({
            map: map,
            position: results[0].geometry.location
        });

      } else {
        alert('Geocode was not successful for the following reason: ' + status);
      }
    });
  }

  //--------------------------------------------------------------------------------------------------------------------
  //       CLICK EVENTS
  //--------------------------------------------------------------------------------------------------------------------

  $('#radSubmitButton').on('click', function() {

    // confirm that the required fields are filled out
    // if first name is blank
    if ( $('#mainRadForm').find('.settingsInputField[name=first_name]').val() === '' ) {
      notifyUser(3, 'Please address the fields highlighted in red and then try to save again.');
      $('#mainRadForm').find('.settingsInputField[name=first_name]').addClass('settingsInputAlert');
      return false;
    } else {
      $('#mainRadForm').find('.settingsInputField[name=first_name]').removeClass('settingsInputAlert');
    }

    // if last name is blank
    if ( $('#mainRadForm').find('.settingsInputField[name=last_name]').val() === '' ) {
      notifyUser(3, 'Please address the fields highlighted in red and then try to save again.');
      $('#mainRadForm').find('.settingsInputField[name=last_name]').addClass('settingsInputAlert');
      return false;
    } else {
      $('#mainRadForm').find('.settingsInputField[name=last_name]').removeClass('settingsInputAlert');
    }

    // if email is blank
    if ( $('#mainRadForm').find('.settingsInputField[name=email]').val() === '' ) {
      notifyUser(3, 'Please address the fields highlighted in red and then try to save again.');
      $('#mainRadForm').find('.settingsInputField[name=email]').addClass('settingsInputAlert');
      return false;
    }  else {
      $('#mainRadForm').find('.settingsInputField[name=email]').removeClass('settingsInputAlert');
    }

    // if addressOne is blank
    if ( $('#mainRadForm').find('.settingsInputField[name=addressOne]').val() === '' ) {
      notifyUser(3, 'Please address the fields highlighted in red and then try to save again.');
      $('#mainRadForm').find('.settingsInputField[name=addressOne]').addClass('settingsInputAlert');
      return false;
    }  else {
      $('#mainRadForm').find('.settingsInputField[name=addressOne]').removeClass('settingsInputAlert');
    }

    // if city is blank
    if ( $('#mainRadForm').find('.settingsInputField[name=city]').val() === '' ) {
      notifyUser(3, 'Please address the fields highlighted in red and then try to save again.');
      $('#mainRadForm').find('.settingsInputField[name=city]').addClass('settingsInputAlert');
      return false;
    }  else {
      $('#mainRadForm').find('.settingsInputField[name=city]').removeClass('settingsInputAlert');
    }

    // if zip is blank
    if ( $('#mainRadForm').find('.settingsInputField[name=zip]').val() === '' ) {
      notifyUser(3, 'Please address the fields highlighted in red and then try to save again.');
      $('#mainRadForm').find('.settingsInputField[name=zip]').addClass('settingsInputAlert');
      return false;
    }  else {
      $('#mainRadForm').find('.settingsInputField[name=zip]').removeClass('settingsInputAlert');
    }

    codeAddress();

    // saveRad();

  });

}); // end document ready
