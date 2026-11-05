<?php
/**
 * Plugin Name:       {{APP_NAME}} – app
 * Description:       Serverer webappen {{APP_NAME}} (installérbar PWA, virker offline) på en adresse under dette websted. Rører ikke temaet eller andre sider.
 * Version:           {{VERSION}}
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            {{PUBLISHER}}
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       om-teknik-app
 *
 * @package OmTeknikApp
 */

defined( 'ABSPATH' ) || exit;

define( 'OM_TEKNIK_APP_VERSION', '{{VERSION}}' );
define( 'OM_TEKNIK_APP_NAME', '{{APP_NAME_PHP}}' );
define( 'OM_TEKNIK_APP_DEFAULT_BASE', '{{BASE_SLUG}}' );
define( 'OM_TEKNIK_APP_OPTION', 'om_teknik_app_base' );
define( 'OM_TEKNIK_APP_FLUSH_FLAG', 'om_teknik_app_flush_rules' );
define( 'OM_TEKNIK_APP_DIR', __DIR__ . '/app' );

/**
 * Den aktuelle basePath uden skråstreger, fx "om-teknik".
 *
 * @return string
 */
function om_teknik_app_base() {
	$base = get_option( OM_TEKNIK_APP_OPTION, OM_TEKNIK_APP_DEFAULT_BASE );
	$base = om_teknik_app_clean_base( $base );
	return '' === $base ? OM_TEKNIK_APP_DEFAULT_BASE : $base;
}

/**
 * Normaliserer en sti til "segment/segment" med tilladte tegn.
 *
 * @param mixed $value Rå værdi.
 * @return string
 */
function om_teknik_app_clean_base( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '#[^a-z0-9/_-]#', '', $value );
	$value = preg_replace( '#/+#', '/', $value );
	return trim( $value, '/' );
}

/**
 * Fuld URL til appen med afsluttende skråstreg.
 *
 * @return string
 */
function om_teknik_app_url() {
	return home_url( '/' . om_teknik_app_base() . '/' );
}

/**
 * Regex til rewrite-reglen.
 *
 * @param string $base Basesti.
 * @return string
 */
function om_teknik_app_rule_regex( $base ) {
	return '^' . preg_quote( $base, '#' ) . '(?:/(.*))?/?$';
}

/* ---------------------------------------------------------------------------
 * Rewrite-regel og query vars
 * ------------------------------------------------------------------------- */

add_action( 'init', 'om_teknik_app_register_rewrite' );
function om_teknik_app_register_rewrite() {
	add_rewrite_rule(
		om_teknik_app_rule_regex( om_teknik_app_base() ),
		'index.php?om_teknik_app=1&om_teknik_app_path=$matches[1]',
		'top'
	);
}

// Skyl reglerne én gang efter at basePath er ændret (reglen er da registreret med den nye værdi).
add_action( 'init', 'om_teknik_app_maybe_flush', 99 );
function om_teknik_app_maybe_flush() {
	if ( get_option( OM_TEKNIK_APP_FLUSH_FLAG ) ) {
		delete_option( OM_TEKNIK_APP_FLUSH_FLAG );
		flush_rewrite_rules( false );
	}
}

add_filter( 'query_vars', 'om_teknik_app_query_vars' );
function om_teknik_app_query_vars( $vars ) {
	$vars[] = 'om_teknik_app';
	$vars[] = 'om_teknik_app_path';
	return $vars;
}

register_activation_hook( __FILE__, 'om_teknik_app_activate' );
function om_teknik_app_activate() {
	if ( false === get_option( OM_TEKNIK_APP_OPTION ) ) {
		add_option( OM_TEKNIK_APP_OPTION, OM_TEKNIK_APP_DEFAULT_BASE );
	}
	om_teknik_app_register_rewrite();
	flush_rewrite_rules( false );
}

register_deactivation_hook( __FILE__, 'om_teknik_app_deactivate' );
function om_teknik_app_deactivate() {
	global $wp_rewrite;
	$regex = om_teknik_app_rule_regex( om_teknik_app_base() );
	if ( isset( $wp_rewrite->extra_rules_top[ $regex ] ) ) {
		unset( $wp_rewrite->extra_rules_top[ $regex ] );
	}
	flush_rewrite_rules( false );
}

/* ---------------------------------------------------------------------------
 * Servering af appens filer
 * ------------------------------------------------------------------------- */

add_action( 'parse_request', 'om_teknik_app_parse_request', 1 );
function om_teknik_app_parse_request( $wp ) {
	if ( empty( $wp->query_vars['om_teknik_app'] ) ) {
		return;
	}
	$rel = isset( $wp->query_vars['om_teknik_app_path'] ) ? (string) $wp->query_vars['om_teknik_app_path'] : '';
	if ( ! om_teknik_app_serve( $rel ) ) {
		// Ukendt fil: lad WordPress vise sin almindelige 404-side.
		unset( $wp->query_vars['om_teknik_app'], $wp->query_vars['om_teknik_app_path'] );
		$wp->query_vars['error'] = '404';
	}
}

/**
 * Tilladte filtyper og deres MIME-typer. Andre filer serveres ikke.
 *
 * @return array<string,string>
 */
function om_teknik_app_mime_types() {
	return array(
		'html'        => 'text/html; charset=utf-8',
		'css'         => 'text/css; charset=utf-8',
		'js'          => 'text/javascript; charset=utf-8',
		'json'        => 'application/json; charset=utf-8',
		'webmanifest' => 'application/manifest+json; charset=utf-8',
		'svg'         => 'image/svg+xml',
		'webp'        => 'image/webp',
		'avif'        => 'image/avif',
		'jpg'         => 'image/jpeg',
		'jpeg'        => 'image/jpeg',
		'png'         => 'image/png',
		'gif'         => 'image/gif',
		'ico'         => 'image/x-icon',
		'pdf'         => 'application/pdf',
		'txt'         => 'text/plain; charset=utf-8',
	);
}

/**
 * Serverer én fil fra app-mappen. Returnerer false, hvis filen ikke findes/ikke er tilladt.
 *
 * @param string $rel Sti relativt til app-mappen.
 * @return bool
 */
function om_teknik_app_serve( $rel ) {
	$rel = str_replace( '\\', '/', rawurldecode( $rel ) );
	if ( false !== strpos( $rel, "\0" ) ) {
		return false;
	}
	// Afvis "..", "." og skjulte filer i alle stisegmenter.
	foreach ( explode( '/', $rel ) as $segment ) {
		if ( '' !== $segment && '.' === $segment[0] ) {
			return false;
		}
	}

	$root = realpath( OM_TEKNIK_APP_DIR );
	if ( false === $root ) {
		return false;
	}
	$path = $root . '/' . ltrim( $rel, '/' );

	if ( is_dir( $path ) ) {
		// Mapper kræver afsluttende skråstreg, så relative links virker.
		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- kun sammenlignet, ikke udskrevet.
		if ( '/' !== substr( $request_path, -1 ) ) {
			$target = om_teknik_app_url() . ( '' === trim( $rel, '/' ) ? '' : trim( $rel, '/' ) . '/' );
			if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
				$target .= '?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
			}
			wp_safe_redirect( $target, 301 );
			exit;
		}
		$path = rtrim( $path, '/' ) . '/index.html';
	}

	$real = realpath( $path );
	if ( false === $real || 0 !== strpos( $real, $root . DIRECTORY_SEPARATOR ) || ! is_file( $real ) || ! is_readable( $real ) ) {
		return false;
	}

	$ext   = strtolower( pathinfo( $real, PATHINFO_EXTENSION ) );
	$types = om_teknik_app_mime_types();
	if ( ! isset( $types[ $ext ] ) ) {
		return false;
	}

	$relative = substr( $real, strlen( $root ) + 1 );
	$mtime    = (int) filemtime( $real );
	$size     = (int) filesize( $real );
	$etag     = '"' . md5( $relative . '|' . $mtime . '|' . $size . '|' . OM_TEKNIK_APP_VERSION ) . '"';

	// Fortæl cache-plugins, at dette svar ikke må gemmes som side.
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	status_header( 200 );
	header( 'Content-Type: ' . $types[ $ext ] );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Cache-Control: ' . om_teknik_app_cache_control( $relative, $ext ) );
	header( 'ETag: ' . $etag );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $mtime ) . ' GMT' );
	if ( 'sw.js' === $relative ) {
		header( 'Service-Worker-Allowed: ' . wp_parse_url( om_teknik_app_url(), PHP_URL_PATH ) );
	}

	$if_none_match = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) ) : '';
	if ( '' !== $if_none_match && $if_none_match === $etag ) {
		status_header( 304 );
		exit;
	}

	header( 'Content-Length: ' . $size );
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
	if ( 'HEAD' !== $method ) {
		readfile( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- statisk fil inde i pluginets app-mappe.
	}
	exit;
}

/**
 * Cache-Control pr. filtype.
 *
 * @param string $relative Sti relativt til app-mappen.
 * @param string $ext      Filendelse.
 * @return string
 */
function om_teknik_app_cache_control( $relative, $ext ) {
	if ( 'sw.js' === $relative ) {
		return 'no-cache, no-store, must-revalidate';
	}
	if ( in_array( $ext, array( 'html', 'json', 'webmanifest', 'css', 'js' ), true ) ) {
		return 'no-cache';
	}
	if ( 0 === strpos( $relative, 'media/' ) ) {
		// Filnavne indeholder en indholds-hash.
		return 'public, max-age=31536000, immutable';
	}
	return 'public, max-age=604800';
}

/* ---------------------------------------------------------------------------
 * Indstillingsside
 * ------------------------------------------------------------------------- */

add_action( 'admin_init', 'om_teknik_app_register_settings' );
function om_teknik_app_register_settings() {
	register_setting(
		'om_teknik_app',
		OM_TEKNIK_APP_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'om_teknik_app_sanitize_base',
			'default'           => OM_TEKNIK_APP_DEFAULT_BASE,
		)
	);
}

/**
 * Validerer basePath fra indstillingssiden.
 *
 * @param mixed $value Indsendt værdi.
 * @return string
 */
function om_teknik_app_sanitize_base( $value ) {
	$old   = om_teknik_app_base();
	$clean = om_teknik_app_clean_base( $value );
	$first = strtok( $clean, '/' );
	$bad   = array( 'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'wp-login.php', 'feed', 'comments', 'xmlrpc.php', 'wp-sitemap.xml' );

	if ( '' === $clean ) {
		add_settings_error( OM_TEKNIK_APP_OPTION, 'empty', __( 'Adressen må ikke være tom.', 'om-teknik-app' ) );
		return $old;
	}
	if ( in_array( $first, $bad, true ) ) {
		add_settings_error( OM_TEKNIK_APP_OPTION, 'reserved', __( 'Adressen er reserveret af WordPress. Vælg en anden.', 'om-teknik-app' ) );
		return $old;
	}
	if ( $clean !== $old ) {
		update_option( OM_TEKNIK_APP_FLUSH_FLAG, 1 );
	}
	return $clean;
}

add_action( 'admin_menu', 'om_teknik_app_admin_menu' );
function om_teknik_app_admin_menu() {
	add_options_page(
		OM_TEKNIK_APP_NAME,
		OM_TEKNIK_APP_NAME,
		'manage_options',
		'om-teknik-app',
		'om_teknik_app_settings_page'
	);
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'om_teknik_app_action_links' );
function om_teknik_app_action_links( $links ) {
	$url = admin_url( 'options-general.php?page=om-teknik-app' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Indstillinger', 'om-teknik-app' ) . '</a>' );
	return $links;
}

/**
 * Oplysninger fra build.json (version, byggetidspunkt).
 *
 * @return array
 */
function om_teknik_app_build_info() {
	$file = OM_TEKNIK_APP_DIR . '/build.json';
	if ( ! is_readable( $file ) ) {
		return array();
	}
	$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lokal fil.
	return is_array( $data ) ? $data : array();
}

function om_teknik_app_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$base     = om_teknik_app_base();
	$url      = om_teknik_app_url();
	$info     = om_teknik_app_build_info();
	$conflict = get_page_by_path( $base );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( OM_TEKNIK_APP_NAME ); ?></h1>

		<p>
			<?php esc_html_e( 'Appen ligger på:', 'om-teknik-app' ); ?>
			<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $url ); ?></a>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( sprintf( /* translators: %s: appens navn */ __( 'Åbn %s', 'om-teknik-app' ), OM_TEKNIK_APP_NAME ) ); ?></a>
			<a class="button" href="<?php echo esc_url( $url . '{{INSTALL_SLUG}}/' ); ?>"><?php esc_html_e( 'Vis installationsvejledning og QR-kode', 'om-teknik-app' ); ?></a>
		</p>

		<?php if ( $conflict ) : ?>
			<div class="notice notice-warning inline"><p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: sidetitel */
						__( 'Der findes også en WordPress-side med samme adresse ("%s"). Appen vises i stedet for siden. Vælg en anden adresse, hvis det ikke er meningen.', 'om-teknik-app' ),
						get_the_title( $conflict )
					)
				);
				?>
			</p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'om_teknik_app' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="om-teknik-app-base"><?php esc_html_e( 'Adresse (basePath)', 'om-teknik-app' ); ?></label></th>
					<td>
						<code><?php echo esc_html( home_url( '/' ) ); ?></code>
						<input name="<?php echo esc_attr( OM_TEKNIK_APP_OPTION ); ?>" id="om-teknik-app-base" type="text" class="regular-text" value="<?php echo esc_attr( $base ); ?>" aria-describedby="om-teknik-app-base-help" pattern="[a-z0-9/_\-]+" required>
						<code>/</code>
						<p class="description" id="om-teknik-app-base-help">
							<?php esc_html_e( 'Kun små bogstaver, tal, bindestreg og skråstreg. Bemærk: Ændrer du adressen, skal brugere, der har installeret appen, installere den igen fra den nye adresse.', 'om-teknik-app' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Gem ændringer', 'om-teknik-app' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Link fra en side', 'om-teknik-app' ); ?></h2>
		<p><?php esc_html_e( 'Indsæt denne kortkode på en almindelig side eller et indlæg for at vise en knap til appen:', 'om-teknik-app' ); ?></p>
		<p><code>[om_teknik_link]</code> <?php esc_html_e( 'eller', 'om-teknik-app' ); ?> <code>[om_teknik_link tekst="Åbn guiden"]</code></p>

		<h2><?php esc_html_e( 'Cache-plugins og CDN', 'om-teknik-app' ); ?></h2>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: sti */
					__( 'Undtag stien %s (og især sw.js) fra sidecache i cache-plugins og CDN, ellers får brugerne ikke nye versioner.', 'om-teknik-app' ),
					wp_parse_url( $url, PHP_URL_PATH )
				)
			);
			?>
		</p>

		<h2><?php esc_html_e( 'Version', 'om-teknik-app' ); ?></h2>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: version, 2: build-id, 3: tidspunkt */
					__( 'Plugin %1$s · indhold %2$s · bygget %3$s', 'om-teknik-app' ),
					OM_TEKNIK_APP_VERSION,
					isset( $info['build'] ) ? $info['build'] : '?',
					isset( $info['builtAt'] ) ? $info['builtAt'] : '?'
				)
			);
			?>
		</p>
		<p><?php esc_html_e( 'Opdatér indholdet ved at uploade en ny om-teknik-app.zip under Plugins → Tilføj nyt → Upload plugin og vælge "Erstat nuværende med uploadet".', 'om-teknik-app' ); ?></p>
	</div>
	<?php
}

/* ---------------------------------------------------------------------------
 * Kortkode [om_teknik_link]
 * ------------------------------------------------------------------------- */

add_shortcode( 'om_teknik_link', 'om_teknik_app_shortcode' );
function om_teknik_app_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			/* translators: %s: appens navn */
			'tekst' => sprintf( __( 'Åbn %s', 'om-teknik-app' ), OM_TEKNIK_APP_NAME ),
			'text'  => '',
		),
		$atts,
		'om_teknik_link'
	);
	$label = '' !== $atts['text'] ? $atts['text'] : $atts['tekst'];

	wp_register_style( 'om-teknik-app-link', false, array(), OM_TEKNIK_APP_VERSION );
	wp_enqueue_style( 'om-teknik-app-link' );
	wp_add_inline_style( 'om-teknik-app-link', '.om-teknik-app-link{display:inline-flex;align-items:center;min-height:44px;min-width:44px;padding:.5em 1.1em;text-decoration:none}' );

	return '<a class="wp-element-button om-teknik-app-link" href="' . esc_url( om_teknik_app_url() ) . '">' . esc_html( $label ) . '</a>';
}
