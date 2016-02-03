<?php

require 'lib/aws-sdk/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
define('TABLE', 'Users');

function CustomDynamoDbConnection() {
  $sdk = new \Aws\DynamoDb\DynamoDbClient([
    'credentials' => array(
      'key' => 'key',
      'secret' => 'secret'
    ),
    'region' => 'region',
    'version' => 'latest'
  ]);

  return $sdk;
}

Class FinditDynamoDbUser {

  public $sdk;

  public function __construct() {
    $this->sdk = CustomDynamoDbConnection();
  }

  /**
   * 
   * @param type $mail
   * @return type
   * Return user count based on email.
   */
  public function getUserCountByMail($mail) {
    $result = $this->sdk->scan(array(
      'TableName' => TABLE,
      'Select' => 'COUNT',
      'ScanFilter' => array(
        'email' => array(
          'AttributeValueList' => array(
            array('S' => $mail)
          ),
          'ComparisonOperator' => 'EQ'
        )
      )
    ));

    return !empty($result) ? $result['Count'] : 0;
  }

  /**
   * 
   * @param type $mail
   * @return type
   * Get user account array based on email id.
   */
  public function getUserRecordByMail($mail) {
    $result = $this->sdk->scan(array(
      'TableName' => TABLE,
      'ScanFilter' => array(
        'email' => array(
          'AttributeValueList' => array(
            array('S' => $mail)
          ),
          'ComparisonOperator' => 'EQ'
        )
      )
    ));

    return !empty($result) ? $result['Items'][0] : 0;
  }

  /**
   * 
   * @param type $mail
   * @return type
   * Get user account array based on email id.
   */
  public function getUserRecordById($id) {
    $result = $this->sdk->scan(array(
      'TableName' => TABLE,
      'ScanFilter' => array(
        'id' => array(
          'AttributeValueList' => array(
            array('S' => $id)
          ),
          'ComparisonOperator' => 'EQ'
        )
      )
    ));

    return !empty($result) ? $result['Items'][0] : 0;
  }

  /**
   * 
   * @param type $password
   * @param type $unique_salt
   * @return type
   * Generate password hash.
   */
  public function hash($password, $unique_salt) {
    return crypt($password, '$5$' . $unique_salt);
  }

  /**
   * 
   * @return type
   * Unique salt for password.
   */
  public function unique_salt() {
    return substr(sha1(mt_rand()), 0, 22);
  }

  /**
   * Password reset token.
   */
  public function generatePasswordToken($data) {
    $time = time();
    $id = $data['id']['S'];
    $token = base64_encode($time . '/' . $id . '/' . hash_hmac('sha256', rand(10, 100), TRUE));
    return $token;
  }

  /**
   * Password validate token.
   */
  public function validatePasswordToken($token) {
    $response = array();
    $tokeninfo = base64_decode($token);
    $current = time();
    $timeout = 84000;
    if (isset($tokeninfo)) {
      $tokenarray = explode('/', $tokeninfo);
      $timestamp = $tokenarray[0];
      if ($timestamp <= $current) {
        if ($current - $timestamp > $timeout) {
          $response['error'] = 1;
          $response['message'] = 'You have tried to use a one-time login link that has expired.';
        }
        else {
          $response['error'] = 0;
          $response['message'] = '';
          $response['id'] = $tokenarray[1];
        }
      }
      else {
        $response['error'] = 1;
        $response['message'] = 'You have tried to use a one-time login link that has expired.';
      }
    }
    return $response;
  }

  /**
   * 
   * @param type $data
   * @return type
   * Create and save user account to dynamodb.
   */
  public function createUserProfile($data) {
    $salt = $this->unique_salt();
    $hashpassword = $this->hash($data['password'], $unique_salt);
    $time = (string) time();
    $registeredFrom = 'Findit';
    $other_data = $this->otherUserData($data);
    $result = $this->sdk->putItem(array(
      'TableName' => TABLE,
      'Item' => array(
        'id' => array('S' => base64_encode($data['email'])),
        'name' => array('S' => $data['name']),
        'password' => array('S' => $hashpassword),
        'passwordsalt' => array('S' => $salt),
        'email' => array('S' => $data['email']),
        'created' => array('S' => $time),
        'updated' => array('S' => $time),
        'status' => array('BOOL' => TRUE),
        'data' => array('S' => $other_data),
        'registeredfrom' => array('S' => $registeredFrom)
      )
    ));
    if ($result['@metadata']['statusCode'] == 200) {
      $response = $this->getUserRecordByMail($data['email']);
      return $response;
    }
  }

  /**
   * 
   * @param type $id
   * @param type $token
   * @return type
   * Update user profile information.
   */
  public function updateUserProfile($id, $token) {
    $time = (string) time();
    $result = $this->sdk->updateItem(array(
      'TableName' => TABLE,
      'Key' => [
        'id' => ['S' => $id]
      ],
      'ExpressionAttributeValues' => [
        ':token' => ['S' => $token],
        ':time' => ['S' => $time]
      ],
      'UpdateExpression' => 'set authtoken = :token, updated = :time',
      'ReturnValues' => 'ALL_NEW'
    ));
    if ($result['@metadata']['statusCode'] == 200) {
      return $result['Attributes'];
    }
  }

  /**
   * 
   * @param type $id
   * @param type $password
   * @return type
   * Update user password.
   */
  public function updateUserPassword($id, $password) {
    $salt = $this->unique_salt();
    $hashpassword = $this->hash($password, $salt);
    $result = $this->sdk->updateItem(array(
      'TableName' => TABLE,
      'Key' => [
        'id' => ['S' => $id]
      ],
      'ExpressionAttributeValues' => [
        ':password' => ['S' => $hashpassword],
        ':salt' => ['S' => $salt]
      ],
      'UpdateExpression' => 'set password = :password, passwordsalt = :salt',
      'ReturnValues' => 'ALL_NEW'
    ));
    if ($result['@metadata']['statusCode'] == 200) {
      return $result['Attributes'];
    }
  }

  /**
   * 
   * @param type $data
   * @return type
   * Get Addtional User data.
   */
  public function otherUserData($data) {
    unset($data['email']);
    unset($data['password']);
    unset($data['name']);
    return json_encode($data);
  }

  /**
   * 
   * @param type $mail
   * @param type $password
   * @return string
   * Validate user account.
   */
  public function validateuseraccount($mail, $password) {
    $user = $this->getUserRecordByMail($mail);
    $currenthash = $user['password']['S'];
    $salt = substr($currenthash, 0, 25);
    $entered = $this->hash($password, $salt);
    $new_hash = crypt($password, $salt);
    if ($currenthash == $new_hash) {
      $user['status'] = 0;
      return $user;
    }
    else {
      $result = array();
      $result['status'] = 1;
      $result['message'] = "Invalid Password";
      return $result;
    }
  }

  public function createJwtToken($data, $headers) {
    $tokenId = base64_encode(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
    $issuedAt = time();
    $notBefore = $issuedAt + 100;             //Adding 10 seconds
    $expire = $notBefore + 500;            // Adding 60 seconds
    $serverName = 'finditserver.com'; // Retrieve the server name from config file
    $jwtkey = 'findsso';
    /*
     * Create the token as an array
     */
    $data = [
      'iat' => $issuedAt, // Issued at: time when the token was generated
      'jti' => $tokenId, // Json Token Id: an unique identifier for the token
      'iss' => $serverName, // Issuer
      //'nbf' => $notBefore, // Not before
      //'exp' => $expire, // Expire
      'data' => [// Data related to the signer user
        'userId' => $data['id']['S'], // userid from the users table
        'email' => $data['email']['S'],
        'name' => $data['name']['S'],
        'userIdentifier' => !empty($headers['HTTP_DEVICEID']) ? base64_encode($headers['HTTP_DEVICEID'][0]) : base64_encode($headers['Host'][0])
      ]
    ];

    //$secretKey = base64_decode($jwtkey);
    $jwt = \Firebase\JWT\JWT::encode($data, $jwtkey, 'HS512');
    return $jwt;
  }

  public function validateJwtToken($headers) {
    $authcode = $headers['HTTP_AUTHCODE'][0];
    $identifier = !empty($headers['HTTP_DEVICEID']) ? base64_encode($headers['HTTP_DEVICEID'][0]) : base64_encode($headers['Host']);
    $output = FALSE;
    $token_data = \Firebase\JWT\JWT::decode($authcode, 'findsso', array('HS512'));
    return $token_data->data;
  }

}
