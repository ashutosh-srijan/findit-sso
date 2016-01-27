<?php

/**
 * 
 * @param Array $requestdata
 * Callback function for creating user account.
 */
function createuseraccount($requestdata) {
    if (empty($requestdata)) {
        exit('Invalid Request.');
    }

    if (!isset($requestdata['email'])) {
        exit('Email id is required.');
    }

    if (!isset($requestdata['password'])) {
        exit('Password is required.');
    }

    if (validateemail($requestdata['email'])) {
        $dd = new FinditDynamoDbUser();
        $result = $dd->createUserProfile($requestdata);
        return $result;
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
    if ($count) {
        $output = FALSE;
        exit('Email already exist.');
    }

    if (filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
        $output = FALSE;
        exit('Invalid email id.');
    }
    return $output;
}

/**
 * 
 * @param type $data
 * @return type
 * Validate username and password before assigning token.
 */
function loginuser($data) {
    $mail = $data['email'];
    $password = $data['password'];
    $dd = new FinditDynamoDbUser();
    if (filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
        $output = FALSE;
        exit('Invalid email id.');
    }
    $user = $dd->validateuseraccount($mail, $password);
    return $user;
}
