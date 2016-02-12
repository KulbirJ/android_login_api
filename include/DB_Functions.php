<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);


class DB_Functions {

    private $conn;

    // constructor
    function __construct() {
        require_once 'DB_Connect.php';
        // connecting to database
        $db = new Db_Connect();
        $this->conn = $db->connect();
    }

    // destructor
    function __destruct() {

    }

    /**
     * Storing new user
     * returns user details
     */
    public function storeUser($fname, $lname, $email, $uname, $password) {
        $uuid = uniqid('', true);
       $hash = $this->hashSSHA($password);
        $encrypted_password = $hash; // encrypted password

       $salt = $hash; // salt2
        $stmt = $this->conn->prepare("INSERT INTO auth_user(password, username, first_name, last_name, email,unique_id, date_joined) VALUES(?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param("ssssss", $encrypted_password, $uname, $fname, $lname, $email, $uuid);
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->conn->prepare("SELECT * FROM auth_user WHERE username = ?");
            $stmt->bind_param("s", $uname);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $user;
        } else {
            return false;
        }
    }

    /**
     * Get user by email and password
     */

	   public function getUserByEmailAndPassword($username, $password) {

        $stmt = $this->conn->prepare("SELECT * FROM auth_user WHERE username = ?");

        $stmt->bind_param("s", $username);
        if ($stmt->execute()){
            $user = $stmt->get_result()->fetch_assoc();
            $dbpwd = $user["password"];

            $stmt->close();

            if($this->checkhashSSHA($dbpwd, $password))
            {
              return $user;
            }
            else {
              return NULL;
            }

            }
            else {
            return NULL;
        }


    }





    /**
     * Check user is existed or not
     */
    public function isUserExisted($uname) {
        $stmt = $this->conn->prepare("SELECT email from users WHERE uname = ?");

        $stmt->bind_param("s", $uname);

        $stmt->execute();

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // user existed
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }

    /**
     * Encrypting password
     * @param password
     * returns salt and encrypted password
     */
	 public function hashSSHA($password) {
    $algorithm = "pbkdf2_sha256";
    $iterations = 10000;

    $newSalt = mcrypt_create_iv(8, MCRYPT_DEV_URANDOM);
    $newSalt = base64_encode($newSalt);

    $hash = hash_pbkdf2("SHA256", $password, $newSalt, $iterations, 0, true);
    $toDBStr = $algorithm ."$". $iterations ."$". $newSalt ."$". base64_encode($hash);

    // This string is to be saved into DB, just like what Django generate.
    return $toDBStr;
} /*


    /**
     * Decrypting password
     * @param salt, password
     * returns hash string
     */

	public function checkhashSSHA($dbString, $password) {
    $pieces = explode("$", $dbString);
    $algorithm = $pieces[0];
    $iterations = $pieces[1];
    $salt = $pieces[2];
    $oldhash = $pieces[3];

    $hash = hash_pbkdf2("SHA256", $password, $salt, $iterations, 0, true);
   $hash = base64_encode($hash);

    if ($hash == $oldhash) {
       // login ok.
       return true;
    }
    else {
       //login fail
       return false;
    }
    //return $toDBStr;
}
	 
}

?>
