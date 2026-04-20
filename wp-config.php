<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
// define( 'DB_USER', 'root' );
define( 'DB_USER', 'rfiddev1' );

/** Database password */
// define( 'DB_PASSWORD', '' );
define( 'DB_PASSWORD', 'cRdl018gu432Hezk8q5' );

/** Database hostname */
define( 'DB_HOST', 'azurelateliers.mysql.database.azure.com' );
// define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'd4tNMFI$/yyx*=;8Cp)*M!-7fla-TQK{8qC#[~sg3HH.I&ISWAH8O|dd3#%{G=kn' );
define( 'SECURE_AUTH_KEY',  'DoAPKq{`fqQGRtQ|iFqX4-{gh+YCQwV|jT6h%RyiI`PHqchUhAk=Q)4ih}PJ#=t!' );
define( 'LOGGED_IN_KEY',    'Y|8#]#0w,wT%Z)<M*~z1R@jW2z{@OW(_y?Pvkf`C9BCIe`9Vx4f0d!8B_IK@0u!f' );
define( 'NONCE_KEY',        'FbEE#4Lz#6WebW}oB80!<mN.RS<kh,_q#cT,qGQ:ah2.*3&[N)ZvEBxc< 3zjh&C' );
define( 'AUTH_SALT',        '$YZmM$9~2Z?8/b3NW /WHPf<bmDaace}2EdUv_/ua-TsG?tWb-=[cNCx(~tBqKT`' );
define( 'SECURE_AUTH_SALT', 'D#Z~Mpz~Y2X6PpvS7.X >r{s70iU[4HQilA[9n,zcuGbs>{2i7UHy`5 ay`DTcA%' );
define( 'LOGGED_IN_SALT',   'VH_> `pIX6A?4*OQJc|ixqXB-8Y:nf?~S&EY$M,Z:okXSB-;X~+HHec/dl8`yK3/' );
define( 'NONCE_SALT',       '31EdPALg}i UlM3p0fcSrK2oQ(7HUBfI)x%ppAb*s-N pWNR1uwL!t~zf,hZp)6d' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
