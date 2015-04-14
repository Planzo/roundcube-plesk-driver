<?php
// Put this file into plugins/password/drivers/

// The value 'INSERT-YOUR-VALUE' is the base64-encoded data of the file /etc/psa/private/secret_key.
// This is necessary because the file is not and should not readable to other users exept root.

// My forked driver is tested with Plesk version 11.5.30-debian7.0.build115130819.13.
// The original driver is made by the user activeindex of the the roundcubeforum.net-Forum.
// Threat http://www.roundcubeforum.net/index.php/topic,7910.0.html

// uncommend to debug in case of errors
// ini_set('display_errors','On');

class rcube_plesk_password
{
    function __construct($params = array())
    {
        $this->_params['host'] = 'localhost';
        $this->_params['port'] = 106;
    }
	
	
    function format_error_result($code, $line)
    {
        if (preg_match('/^\d\d\d\s+(\S.*)\s*$/', $line, $matches)) {
            return array('code' => $code, 'message' => $matches[1]);
        } else {
            return $code;
        }
    }
	
	function save($curpass, $passwd) {

        $rcmail = rcmail::get_instance();

        $link = mysql_connect($rcmail->config->get('plesk_db_host'), $rcmail->config->get('plesk_db_admin'), $rcmail->config->get('plesk_db_pass'));

        if (!$link)
                return PASSWORD_ERROR;
        mysql_select_db("psa");

        $sql = "SELECT a.id,a.password,m.mail_name,d.name
                FROM domains d, mail m, accounts a
                WHERE
                        d.name='" . $rcmail->user->get_username('domain') . "' AND
                        m.mail_name='" . $rcmail->user->get_username('local'). "' AND
                        m.dom_id=d.id AND a.id=m.account_id";
        $res = mysql_query($sql);
        if ($row = mysql_fetch_assoc($res)) {

                //$driver = new Passwd_Driver_poppassd(array('host'=>'localhost', 'port'=> '106'));
                $return = $this->changePassword($_SESSION['username'], $curpass, $passwd);

				$decryptedPasswordFromDatabase = $this->aes_decrypt($row["password"]);
				$encryptedPasswordForDatabase = $this->aes_encrypt($passwd);
				
                if ($return[1] == "Success") {
                        if ($decryptedPasswordFromDatabase != $curpass) {
                                return PASSWORD_ERROR;

						} elseif (mysql_query("UPDATE accounts set password='" . $encryptedPasswordForDatabase . "' where id='" . $row["id"] . "' LIMIT 1")) {
                                return PASSWORD_SUCCESS;

						} else {
                                return PASSWORD_ERROR;
						}

                } else {
                        return PASSWORD_ERROR;
				}

        } else {
			return PASSWORD_ERROR;
		}
}
	
    function _connect()
    {
        $this->_fp = fsockopen($this->_params['host'], $this->_params['port'], $errno, $errstr, 30);
        if (!$this->_fp) {
            return array(false, $errstr);
        } else {
            $res = $this->_getPrompt();
            return $res;
        }
    }
    function _disconnect()
    {
        if (isset($this->_fp)) {
            fputs($this->_fp, "quit\n");
            fclose($this->_fp);
        }
    }
    function _getPrompt()
    {
        $prompt = fgets($this->_fp, 4096);
        if (!$prompt) {
            return array(false, "No prompt returned from server.");
        }
        if (preg_match('/^[1-5][0-9][0-9]/', $prompt)) {
            $rc = substr($prompt, 0, 3);
            /* This should probably be a regex match for 2?0 or 3?0, no? */
            if ($rc == '200' || $rc == '220' || $rc == '250' || $rc == '300' ) {
                return array(true, "Success");
            } else {
                return array(false, $prompt);
            }
        } else {
            return array(true, "Success");
        }
    }

    function _sendCommand($cmd, $arg)
    {
        $line = $cmd . ' ' . $arg . "\n";
        $res_fputs = fputs($this->_fp, $line);
        if (!$res_fputs) {
            return array(false, "Cannot send command to server.");
        }
        $res = $this->_getPrompt();
        return $res;
    }

    function changePassword($username, $old_password, $new_password)
    {
        $res = $this->_connect();
        if (!$res[0]) {
            return $res;
        }

        $res = $this->_sendCommand('user', $username);
        if (!$res[0]) {
            $this->_disconnect();
            return array(false, "User not found");
        }

        $res = $this->_sendCommand('pass', $old_password);
        if (!$res[0]) {
            $this->_disconnect();
            return array(false, "Incorrect Password");
        }



        $res = $this->_sendCommand('newpass', $new_password);
        $this->_disconnect();
        if (!$res[0]) {
            return $res;
        }

        return array(true, "Success");
    }

	function aes_encrypt($passwd) {
		$plaintext = $passwd;

		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		//$key = file_get_contents("/etc/psa/private/secret_key");
		$key = base64_decode('INSERT-YOUR-VALUE'); // INSERT-YOUR-VALUE is the output from "base64 /etc/psa/private/secret_key" on linux console

		$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plaintext, MCRYPT_MODE_CBC, $iv);
		$ciphertext = $ciphertext;
		$ciphertext_base64 = base64_encode($ciphertext);
		$ivtext_base64 = base64_encode($iv);

		return '$AES-128-CBC$'.$ivtext_base64. '$' .$ciphertext_base64;
	}

	function aes_decrypt($passwd) {
		$encryptedPass = $passwd;
		$key = base64_decode('INSERT-YOUR-VALUE'); // INSERT-YOUR-VALUE is the output from "base64 /etc/psa/private/secret_key" on linux console

		$hash = explode('$', $encryptedPass);
		$i = base64_decode($hash[2]);
		$k = base64_decode($hash[3]);

		return str_replace("\0", "", mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $k , MCRYPT_MODE_CBC, $i));
	}
}
