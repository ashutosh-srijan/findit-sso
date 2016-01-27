<?php

require '../lib/aws-sdk/vendor/autoload.php';
//$sdk = new \Aws\DynamoDb\DynamoDbClient([
//    'credentials' => array(
//        'key' => '12345',
//        'secret' => '12334456'
//    ),
//    'region' => 'ap-southeast-1',
//    'version' => 'latest',
//    //'endpoint' => 'http://localhost:8000'
//        ]);

$sdk = new \Aws\DynamoDb\DynamoDbClient([
    'credentials' => array(
        'key' => 'AKIAI5FPXTJJEXEF56VA',
        'secret' => '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'
    ),
    'region' => 'ap-southeast-1',
    'version' => 'latest',
        //'endpoint' => 'http://localhost:8000'
        ]);

$result = $sdk->scan(array(
    'TableName' => 'Users',
    'Select' => 'COUNT',
    'ScanFilter' => array(
        'name' => array(
            'AttributeValueList' => array(
                array('S' => 'ashutosh')
            ),
            'ComparisonOperator' => 'EQ'
        )
    )
        ));

$numOrders = $result['Count'];
print_r($numOrders);
exit;

$info = $sdk->getItem(array(
    'ConsistentRead' => true,
    'TableName' => 'Users',
    'Key' => array(
        'name' => array('S' => 'ashutosh')
    )
        ));
print_r($info);
exit;
//$result = $sdk->putItem(array(
//    'TableName' => 'Users',
//    'Item' => array(
//        'id' => array('S' => '1'),
//        'name' => array('S' => 'ashutosh'),
//        'password' => array('S' => 'ashutosh'))
//        ));

/**
  $result = $sdk->describeTable(array(
  'TableName' => 'Users'
  ));
 * */
//$sdk->updateTable(array(
//    'TableName' => 'Users',
//    'ProvisionedThroughput' => array(
//        'ReadCapacityUnits' => 20,
//        'WriteCapacityUnits' => 35
//    ),
//    'GlobalSecondaryIndexes' => array(
//        [
//            'IndexName' => 'name',
//            'KeySchema' => [
//                [ 'AttributeName' => 'name', 'KeyType' => 'String']
//            ],
//            'Projection' => [ 'ProjectionType' => 'ALL']
//        ]
//    )
//));

//print_r($result);
//exit;


////create table
//$tableName = 'user';
//
//echo "# Creating table $tableName...\n";
//
//$result = $sdk->createTable([
//    'TableName' => $tableName,
//    'AttributeDefinitions' => [
//        [ 'AttributeName' => 'Id', 'AttributeType' => 'N']
//    ],
//    'KeySchema' => [
//        [ 'AttributeName' => 'Id', 'KeyType' => 'HASH']
//    ],
//    'ProvisionedThroughput' => [
//        'ReadCapacityUnits' => 10,
//        'WriteCapacityUnits' => 15
//    ]
//        ]);
//
//print_r($result->getPath('TableDescription'));


//Update Table



