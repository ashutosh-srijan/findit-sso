<?php

require '../config.php';
if ($_GET['op'] == 'register') {
  echo '<html>
    <body>
        <form name="register" action="#" method="POST">
            <table>
                <tr>
                    <td>Email</td>
                    <td><input name="email" type="email" id="email" size="20"></td>
                </tr>
                <tr>
                    <td>Name</td>
                    <td><input name="name" type="text" id="name" size="20"></td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td><input name="password" type="password" id="password" size="20"></td>
                </tr>
                <tr>
                    <td>Verify Password</td>
                    <td><input name="password1" type="password" id="verifyPassword" size="20"></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input name ="submit" type="submit" value="submit">
                    </td>
                </tr>
                <input type="hidden" name="op" value="' . $_GET['op'] . '">
                <input type="hidden" name="redirect_uri" value="' . $_GET['redirecturl'] . '">
            </table>
        </form>
    </body>
</html>';
}

if ($_GET['op'] == 'login') {
  echo '<html>
    <body>
        <form name="login" action="#" method="POST">
            <table>
                <tr>
                    <td>Email</td>
                    <td><input name="email" type="email" id="email" size="20"></td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td><input name="password" type="password" id="password" size="20"></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input name ="submit" type="submit" value="submit">
                    </td>
                </tr>
                <input type="hidden" name="op" value="' . $_GET['op'] . '">
                <input type="hidden" name="redirect_uri" value="' . $_GET['redirecturl'] . '">    
            </table>
        </form>
    </body>
</html>';
}
if (!empty($_POST)) {
  $data = $_POST;
  $user = new FinditDynamoDbUser();
  if ($data['op'] == 'login') {
    $result = $user->validateuseraccount($_POST['email'], $_POST['password']);
    if ($result['status']) {
      $data = array();
      $data['email'] = $result['email']['S'];
      $data['name'] = $result['name']['S'];
      $data['id'] = $result['id']['S'];
      $cookie = base64_encode(serialize($data));
      setcookie('Findit_user', $cookie, 0, '/', '.findit.com');
      $location = $_POST['redirect_uri'];
      header("Location: $location");
    }
    else {
      echo 'Somrthing went wrong.Please try again.';
    }
  }
  else {
    $count = $user->getUserCountByMail($_POST['email']);
    if ($count) {
      echo 'Used with this email id already exist';
    }
    else {
      $result = $user->createUserProfile($data);
      $data = array();
      $data['email'] = $result['email']['S'];
      $data['name'] = $result['name']['S'];
      $data['id'] = $result['id']['S'];
      $cookie = base64_encode(serialize($data));
      setcookie('Findit_user', $cookie, 0, '/', '.findit.com');
      $location = $_POST['redirect_uri'];
      header("Location: $location");
    }
  }
}


