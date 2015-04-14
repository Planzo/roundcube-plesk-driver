# Add parameters to roundcubes configfile "config/main.inc.php" to add the plesk password driver

The database user must be able to write within the database "psa". i.e. the plesk admin user.

`
// Password Plugin options
// -----------------------
// A driver to use for password change. Default: "sql".
// See README file for list of supported driver names.
$rcmail_config['password_driver'] = 'plesk';

// Determine whether current password is required to change password.
// Default: false.
$rcmail_config['password_confirm_current'] = true;

// Require the new password to be a certain length.
// set to blank to allow passwords of any length
$rcmail_config['password_minimum_length'] = 5;

// Require the new password to contain a letter and punctuation character
// Change to false to remove this check.
$rcmail_config['password_require_nonalpha'] = false;

// Enables logging of password changes into logs/password
$rcmail_config['password_log'] = false;

// Comma-separated list of login exceptions for which password change
// will be not available (no Password tab in Settings)
$rcmail_config['password_login_exceptions'] = null;

// Array of hosts that support password changing. Default is NULL.
// Listed hosts will feature a Password option in Settings; others will not.
// Example:
//$rcmail_config['password_hosts'] = array('mail.example.com', 'mail2.example.org');
$rcmail_config['password_hosts'] = null;

// Enables saving the new password even if it matches the old password. Useful
// for upgrading the stored passwords after the encryption scheme has changed.
$rcmail_config['password_force_save'] = false;


//Plesk DB

$rcmail_config['plesk_db_host'] = 'localhost'; //psa database host
$rcmail_config['plesk_db_admin'] = ''; //psa database user
$rcmail_config['plesk_db_pass'] = ''; //psa db user password
`

# Put the password driver into the drivers directory
  * put plesk.php to plugins/password/drivers/





