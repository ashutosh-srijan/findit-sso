<?php

/**
 * 
 * @param Array $requestdata
 * Callback function for creating user account.
 */
function createuseraccount($requestdata, $headers) {
  $response = array();
  if (empty($requestdata)) {
    $response['error'] = 1;
    $response['system_msg'] = '';
    $response['dispaly_msg'] = 'Invalid Request.';
    return json_encode($response);
  }

  if (empty($requestdata['email'])) {
    $response['error'] = 1;
    $response['system_msg'] = '';
    $response['dispaly_msg'] = 'Email id is required.';
    return json_encode($response);
  }

  if (empty($requestdata['password'])) {
    $response['error'] = 1;
    $response['system_msg'] = '';
    $response['dispaly_msg'] = 'Password is required.';
    return json_encode($response);
  }

  $validate = validateemail($requestdata['email']);
  if ($validate == 1) {
    $dd = new FinditDynamoDbUser();
    $result = $dd->createUserProfile($requestdata);
    createUserOnApp($result);
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
  if (!$user['status']) {
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
    $response['dispaly_msg'] = $user['message'];
    return json_encode($response);
  }
}

function updateUserPassword($data) {
  $url = 'https://login.finditlabs.com/newPassword.php';
  $response = array();
  $response['error'] = 1;
  if (empty($data) || !array_key_exists('loginID', $data) || !array_key_exists('redirectkey', $data)) {
    $response['message'] = 'Invalid Request';
    return $response;
  }

  if (filter_var($data['loginID'], FILTER_VALIDATE_EMAIL) === false) {
    $response['message'] = 'Invalid Email Id';
    return $response;
  }

  $dynamo = new FinditDynamoDbUser();
  $user = $dynamo->getUserRecordByMail($data['loginID']);
  if (empty($user)) {
    $response['message'] = 'User not exist';
    return $response;
  }
  $resetPasswordToken = $dynamo->generatePasswordToken($user);
  $response['error'] = 0;
  $response['message'] = 'Password reset details send to your email address';
  $url .= '?code=' . $resetPasswordToken;
  mail('ashutoshsngh67@gmail.com', 'Password update link', $url);
  return $response;
}

function updateResetPassword($data) {
  $response = array();
  if (empty($data)) {
    $response['message'] = 'Invalid Request';
    return $response;
  }

  if (array_key_exists('code', $data)) {
    $dynamo = new FinditDynamoDbUser();
    $validationresponse = $dynamo->validatePasswordToken($data['code']);
    if (!$validationresponse['error']) {
      $id = $validationresponse['id'];
      $newpassword = $data['password'];
      $pp = $dynamo->updateUserPassword($id, $newpassword);
      return $pp;
    }
    else {
      return $validationresponse;
    }
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

function createUserOnApp($data) {
  $dd = new FinditDynamoDbUser();
  //Create user on marketplace.
  $url = 'http://localhost/findit/api_market/register';
  $response = $dd->curlRequest($url, 'POST', $data);

  //Create user on merchant.
  //$url = 'http://localhost/findit-merchant/api_market/register';
  //$response = $dd->curlRequest($url, 'POST', $data);
  //Create user on cakephp.
  //@ToDO
}
