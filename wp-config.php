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
define('DB_NAME', 'conference');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

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
define('AUTH_KEY',         'p!zcuquiCF&wl$r5i<VS}RGa7p8Se[lTF.AnQ8HS5E20:Xjy@1E(xSA1O^z?^>{x');
define('SECURE_AUTH_KEY',  'OKAZ?VeMf]%:xVB~KX1t;J^Vgx`STD/&V3yMlZ7FWE_gE%usunh$ECSK,NWH]x9.');
define('LOGGED_IN_KEY',    'C^V._Xh6FnKmA7hQ%L5_XrY:P%>P+:Q]IT0f=?D)L<vZ@s);,>Z}S(TUG0&N,m+g');
define('NONCE_KEY',        'et? V$D@I_>a1[+QQw5WYhDNMvvcBKBjbjnh2V[= =8#9PPv;1O,&%`9;xJ3-s7@');
define('AUTH_SALT',        'b=yAuNG|zR^O{ya]Z1IRlBDG[qrV@h4E~A5Vw-BYh]}Xwgz+*!il}C<}wu2Ljd4W');
define('SECURE_AUTH_SALT', 'a~9S`r._<m=Ox|m$em2!.P?im* I4>#`!aox6f~])l/^#?p?hnKtYODt)*{,Xk$f');
define('LOGGED_IN_SALT',   '.1z_tR<3Z1IpdrEv9m5:~sRqlE7o^v46I>-N=L+KmDwb[FoC`(zPX)*L3SiJojF,');
define('NONCE_SALT',       '_7p|glb)58EriD(|8D&Nax*,s~B}oWF8q1!U8%^gOuA-df??;dw$@YPq(h$!iSb_');

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
define('FS_METHOD', 'direct');
