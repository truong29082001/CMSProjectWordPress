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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '2weJRf@y,J}FyUeyY,-xc%l[W|)eUqy)K=|C;VE5j_r{$@z@_hl;`|+V*$weSuQI' );
define( 'SECURE_AUTH_KEY',  ';M`wMn(*bZI7p/J_Hg.d425pfa5UKT$q!%O6 !.Tj*Wpi^I3wyNW~  75r(WI|Bu' );
define( 'LOGGED_IN_KEY',    'Gc.W>A:/]Np8sMPJbW p5g*+nkJKS:5^S^?d+hwHKo3uR5.%6nU 0oNn15z[mmI=' );
define( 'NONCE_KEY',        '^:bEB:%]8WhkGB /;w2|E5$cIMT1!(hAei$&Q!jrMqO34!btFKHQ(1H$]oo]%$~q' );
define( 'AUTH_SALT',        '0.]koLWXu7O[+;JGR2v9,AcKF,nz%@pQ<cX0)kPW1VjHL2Tu9r=!O214wDwlF|Mi' );
define( 'SECURE_AUTH_SALT', 'MDQ,<W*nk k5cflIlsC$f{HL>`ItxU?Q&-r9%.);?E%XfZ^?K6d$(?Xyy>ukr=ve' );
define( 'LOGGED_IN_SALT',   'S~rv;bwB)WREp~v@ZW<O#.=|AFEY0aA%1/!%|&8y%M>&B=Ku$$+U bI4rE9#>rf5' );
define( 'NONCE_SALT',       'OYrh:K&-|N9Cmd#m]@8JD}NQ%(=ObNagUzPz!4uzK89gS457G %vSLzTyVQ7kYL[' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
