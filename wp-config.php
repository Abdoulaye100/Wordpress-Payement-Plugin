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
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define( 'AUTH_KEY',         'Or&lE}|{P01N*]4?d5D_-aEsbi&HW2IK$rm^smYH B$GOLoj*1tjaO ;i(OUPtG2' );
define( 'SECURE_AUTH_KEY',  'Kp3h8U~XE;M,+d/eM{5xYVu6O*VwNURTn8qn;9nJO3;Gd@)GHo3IG*G;-S|6!iuC' );
define( 'LOGGED_IN_KEY',    '}s1]$tk@Lj:Mt qKR}<3[`ZcwXec{#@#+0J~~QX{[Y-rr[,l/[MgKx8nhYs,Si,b' );
define( 'NONCE_KEY',        'b^F1q%Zl8lC)[>L!GlUHe[I~[p~K8;&f[? ^qy ,f1q`J<b3*O.uOsm~u<!oobDB' );
define( 'AUTH_SALT',        'HyvrG+@Et3CofQ@T;$#j0^1$t`Uekr2rhLpZX8)heP[eR,D()C=TQh?wxZ_:R}T4' );
define( 'SECURE_AUTH_SALT', 'Z%O@`QoUp*Ja?ER|jBtG/Dn(A#Xko&(GWb0+2Q25*+`D?0&V`I<aD3KH-l6W92~W' );
define( 'LOGGED_IN_SALT',   '/MHtuN,m:E0SArj]5:*JHj6Hmn[Mr=G{zU)=(6f&%G!jDh0=yEH[:>9hEZj9?4ZA' );
define( 'NONCE_SALT',       '$wsp_SKONIo}E%@4yM]zTyn]Poks&ernyN,*7Gvi~/LV9??gYN-56ws_Xh!t`i[,' );

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
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
