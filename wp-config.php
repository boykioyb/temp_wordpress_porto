<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'hoadenhat');

/** MySQL database username */
define('DB_USER', 'full');

/** MySQL database password */
define('DB_PASSWORD', '123456');

/** MySQL hostname */
define('DB_HOST', 'host.docker.internal');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'j_zZ!.HY3+?:IOI;*K+F[k_?*aUJ6RB~2wLF|b:d`q/6E1pIblUH9Km{FHSSyGW$');
define('SECURE_AUTH_KEY',  ',}&1}*8,s kZd}Nfbu.y )acd|@c}k)2$kQQ.FAC/!G Tr^4MxpM/Z~/S!DGQyC_');
define('LOGGED_IN_KEY',    '=na j&{IW7~yA],HZ1yVz(+`T8H*g@+$,%RBZs$o3A^&nV;ZWWEb{&`>73A*HwX;');
define('NONCE_KEY',        'N,X/`MW%C_T5|@2Zs<@[!~A@BX$qhfo hHQ$ypqsH3GS68w/vlGUIJ!CHqd[8XGI');
define('AUTH_SALT',        '@gbZX_(QS46!$8 E)x+HQEn&ea7(WA9L4P=(8lTXR<!7,<s`R/r6;~djA6TW0vKz');
define('SECURE_AUTH_SALT', 'vouWBS_|rWqcCtXvM8&}fwT%XwNlB|&^kH*bCDx GThLJvFbBbkHrYOd`oz/2uH]');
define('LOGGED_IN_SALT',   '^Zlu?j$~f&:}Qppds`I,ljT~$pi=+]9z$@g|DRJ]e]}>] @_D(LfG8u8;l/jJtWd');
define('NONCE_SALT',       '3(~UV/AQ-}05:V*mW{X-GyH]E1R@y(5|W*;~?mWmJa*lR(Uq[~<&4<pb2Ts=RVF<');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
