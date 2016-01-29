<?php

/**
 * 
 * @param Array $requestdata
 * Callback function for creating user account.
 */
function createuseraccount($requestdata, $headers) {
  $response = array();
  if (empty($requestdata)) {
    exit('Invalid Request.');
  }

  if (!isset($requestdata['email'])) {
    exit('Email id is required.');
  }

  if (!isset($requestdata['password'])) {
    exit('Password is required.');
  }

  $validate = validateemail($requestdata['email']);
  if ($validate == 1) {
    $dd = new FinditDynamoDbUser();
    $result = $dd->createUserProfile($requestdata);
    if (!empty($result)) {
      $token = $dd->createJwtToken($result, $headers);
      if (isset($token)) {
        $profile = $dd->updateUserProfile($result['id']['S'], $token);
        if (!empty($profile)) {
          $response['error'] = 0;
          $response['authcode'] = $token;
          $response['system_msg'] = 'Authenticated';
          return json_encode($response);
        }
      }
    }
    else {
      $response['error'] = 1;
      $response['system_msg'] = '';
      $response['dispaly_msg'] = 'Something went wrong please try agian.';
      return json_encode($response);
    }
  }
  else {
    return $validate;
  }
}

/**
 * 
 * @param type $mail
 * @return boolean
 * Validate email before creating user account.
 */
function validateemail($mail) {
  $output = TRUE;
  $dd = new FinditDynamoDbUser();
  $count = $dd->getUserCountByMail($mail);
  $response = array();
  if ($count) {
    $output = FALSE;
    $response['error'] = 1;
    $response['system_msg'] = '';
    $response['dispaly_msg'] = 'Email already exist.';
    return json_encode($response);
  }

  if (filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
    $output = FALSE;
    $response['error'] = 1;
    $response['system_msg'] = '';
    $response['dispaly_msg'] = 'Invalid email id.';
    return json_encode($response);
  }
  return $output;
}

/**
 * 
 * @param type $data
 * @return type
 * Validate username and password before assigning token.
 */
function loginuser($data, $headers) {
  $response = array();
  $mail = $data['email'];
  $password = $data['password'];
  $dd = new FinditDynamoDbUser();
  if (filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
    $output = FALSE;
    $response['error'] = 1;
    $response['system_msg'] = '';
    $response['dispaly_msg'] = 'Invalid email id.';
    return json_encode($response);
  }
  $user = $dd->validateuseraccount($mail, $password);
  if (!empty($user)) {
    $token = $dd->createJwtToken($user, $headers);
    if (isset($token)) {
      $profile = $dd->updateUserProfile($user['id']['S'], $token);
      if (!empty($profile)) {
        $response['error'] = 0;
        $response['authcode'] = $token;
        $response['system_msg'] = 'Authenticated';
        return json_encode($response);
      }
    }
  }
  else {
    $output = FALSE;
    $response['error'] = 1;
    $response['system_msg'] = '';
    $response['dispaly_msg'] = 'Something went wrong please try agian.';
    return json_encode($response);
  }
}

function user_identity($id, $headers) {
  $dd = new FinditDynamoDbUser();
  $validate = $dd->validateJwtToken($headers);
  $data = array();
  if (!empty($validate)) {
    return json_encode($validate);
//    $response = $dd->getUserRecordById($validate);
//    $data['email'] = $response['email']['S'];
//    $data['name'] = $response['name']['S'];
//    $data['id'] = $response['id']['S'];
//    return base64_encode(serialize($data));
  }
}
