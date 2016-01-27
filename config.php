<?php

require 'lib/aws-sdk/vendor/autoload.php';
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
    $salt = $this->generateSalt($data['email']);
    $hashpassword = hash_pbkdf2("sha256", $data['password'], $salt, 4096, 128);
    $other_data = $this->otherUserData($data);
    $result = $this->sdk->putItem(array(
      'TableName' => TABLE,
      'Item' => array(
        'id' => array('S' => base64_encode($data['email'])),
        'name' => array('S' => $data['name']),
        'password' => array('S' => $hashpassword),
        'email' => array('S' => $data['email']),
        'data' => array('S' => $other_data)
      )
    ));
    if ($result['@metadata']['statusCode'] == 200) {
      $response = $this->getUserRecordByMail($data['email']);
      return $response;
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

}
