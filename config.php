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
   * @param type $mail
   * @return type
   * Generate unique salt for each user.
   */
  public function generateSalt($mail) {
    $code = md5(base64_encode($mail));
    return $code;
  }

  /**
   * 
   * @param type $data
   * @return type
   * Create and save user account to dynamodb.
   */
  public function createUserProfile($data) {
    $time = (string) time();
    $registeredFrom = 'Findit';
    $salt = $this->generateSalt($data['email']);
    $hashpassword = hash_pbkdf2("sha256", $data['password'], $salt, 4096, 128);
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
        'status' => array('S' => 1),
        'data' => array('S' => $other_data),
        'registeredfrom' => array('S' => $registeredFrom)
      )
    ));
    if ($result['@metadata']['statusCode'] == 200) {
      $response = $this->getUserRecordByMail($data['email']);
      return $response;
    }
  }

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
    $error = array();
    $salt = $this->generateSalt($mail);
    $hashpassword = hash_pbkdf2("sha256", $password, $salt, 4096, 128);
    $result = $this->sdk->scan(array(
      'TableName' => 'Users',
      'ScanFilter' => array(
        'email' => array(
          'AttributeValueList' => array(
            array('S' => $mail)
          ),
          'ComparisonOperator' => 'EQ'
        ),
        'password' => array(
          'AttributeValueList' => array(
            array('S' => $hashpassword)
          ),
          'ComparisonOperator' => 'EQ'
        )
      )
    ));
    if ($result['Count']) {
      $result['Items'][0]['status'] = 1;
      return $result['Items'][0];
    }
    else {
      $result = array();
      $result['status'] = 0;
      return $result;
    }
  }

  public function createJwtToken($data, $headers) {
    $tokenId = base64_encode(mcrypt_create_iv(32));
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
        'userIdentifier' => !empty($headers['HTTP_DEVICEID']) ? base64_encode($headers['HTTP_DEVICEID'][0]) : base64_encode($headers['Host'])
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
//    $verifier = $token_data->data->userAgent;
//    $identifier = $token_data->data->userId;
//    $receivedToken = base64_encode($data['Host'][0]);
//    if ($verifier == $receivedToken) {
//      $output = $identifier;
//    }
//    return $output;
  }

}
