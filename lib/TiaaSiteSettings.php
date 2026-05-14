<?php
/**
 * Class TiaaSiteSettings
 *
 * Adds a "Site Settings" tab to the tiaa-wpplugin options screen and
 * registers all global site configuration values that were previously
 * hardcoded or missing.
 *
 * ADMIN SCREEN
 * ------------
 * WordPress admin → Settings → TIAA WP Plugin → Site Settings tab
 *
 * FIELDS REGISTERED
 * -----------------
 * tiaa_cookie_domain    Cookie domain shared by WP + Discourse subdomains.
 *                       Overridden by TIAA_COOKIE_DOMAIN constant in
 *                       wp-config.php (dev environments use this).
 * tiaa_contact_email    Site contact email (replaces hardcoded value).
 * tiaa_funding_level    Reserve level: red/yellow/green/blue. Drives the
 *                       Contribute button colour in the Discourse brand header.
 * tiaa_stat_members     Member count displayed on homepage stats card.
 * tiaa_stat_topics      Topic count displayed on homepage stats card.
 * tiaa_stat_posts       Post count displayed on homepage stats card.
 *
 * SHORTCODES
 * ----------
 * [tiaa_stat field="members"]  →  value of tiaa_stat_members
 * [tiaa_stat field="topics"]   →  value of tiaa_stat_topics
 * [tiaa_stat field="posts"]    →  value of tiaa_stat_posts
 *
 * Drop these into any Elementor text or heading widget. Update the numbers
 * in the admin screen and the front end updates automatically — no code change.
 *
 * INTEGRATION WITH OPTIONS-PAGE.PHP
 * ----------------------------------
 * See "Edit admin/options-page.php" section in the handoff document for the
 * two changes needed to wire this class into the existing tab navigation.
 *
 * @package TIAAPlugin
 * @subpackage TIAAPlugin\lib
 * @since 0.0.5
 */

namespace TIAAPlugin\lib;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TiaaSiteSettings {

	const OPTION_GROUP  = 'tiaa_site_settings';
	const OPTIONS_PAGE  = 'tiaa_wpplugin_options';
	const TAB_SLUG      = 'site_settings';
	const SECTION_SLUG  = 'tiaa_site_settings_section';

	const KEY_COOKIE_DOMAIN = 'tiaa_cookie_domain';
	const KEY_CONTACT_EMAIL = 'tiaa_contact_email';
	const KEY_FUNDING_LEVEL = 'tiaa_funding_level';
	const KEY_STAT_MEMBERS  = 'tiaa_stat_members';
	const KEY_STAT_TOPICS   = 'tiaa_stat_topics';
	const KEY_STAT_POSTS    = 'tiaa_stat_posts';

	const SHORTCODE_STAT = 'tiaa_stat';

	/** WP-Discourse options key — Discourse URL is owned by that plugin, not us. */
	const WPDC_OPTIONS_KEY = 'discourse_connect';

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_shortcode( self::SHORTCODE_STAT, [ $this, 'render_stat_shortcode' ] );
		// Output GO TO FORUM button script on all front-end pages.
		add_action( 'wp_footer', [ $this, 'output_forum_button_script' ] );
	}

	public function register_settings(): void {
		register_setting( self::OPTION_GROUP, self::KEY_COOKIE_DOMAIN, [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '.tiaa-forum.org' ] );
		register_setting( self::OPTION_GROUP, self::KEY_CONTACT_EMAIL, [ 'sanitize_callback' => 'sanitize_email',       'default' => '' ] );
		register_setting( self::OPTION_GROUP, self::KEY_FUNDING_LEVEL, [ 'sanitize_callback' => [ $this, 'sanitize_funding_level' ], 'default' => 'green' ] );
		register_setting( self::OPTION_GROUP, self::KEY_STAT_MEMBERS,  [ 'sanitize_callback' => 'absint', 'default' => 0 ] );
		register_setting( self::OPTION_GROUP, self::KEY_STAT_TOPICS,   [ 'sanitize_callback' => 'absint', 'default' => 0 ] );
		register_setting( self::OPTION_GROUP, self::KEY_STAT_POSTS,    [ 'sanitize_callback' => 'absint', 'default' => 0 ] );

		$page = self::OPTIONS_PAGE . '_' . self::TAB_SLUG;

		add_settings_section( self::SECTION_SLUG, '', '__return_false', $page );

		add_settings_field( self::KEY_COOKIE_DOMAIN, 'Cookie Domain',    [ $this, 'render_cookie_domain_field' ], $page, self::SECTION_SLUG );
		add_settings_field( self::KEY_CONTACT_EMAIL, 'Contact Email',    [ $this, 'render_contact_email_field' ], $page, self::SECTION_SLUG );
		add_settings_field( self::KEY_FUNDING_LEVEL, 'Funding Level',    [ $this, 'render_funding_level_field' ], $page, self::SECTION_SLUG );
		add_settings_field( 'tiaa_discourse_url_display', 'Discourse URL', [ $this, 'render_discourse_url_field' ], $page, self::SECTION_SLUG );
		add_settings_field( 'tiaa_stats_group',      'Forum Statistics', [ $this, 'render_stats_fields' ],        $page, self::SECTION_SLUG );
	}

	public function render_cookie_domain_field(): void {
		$value    = get_option( self::KEY_COOKIE_DOMAIN, '.tiaa-forum.org' );
		$override = defined( 'TIAA_COOKIE_DOMAIN' );
		?>
		<input type="text"
		       name="<?php echo esc_attr( self::KEY_COOKIE_DOMAIN ); ?>"
		       value="<?php echo esc_attr( $override ? TIAA_COOKIE_DOMAIN : $value ); ?>"
		       class="regular-text"
		       <?php echo $override ? 'disabled' : ''; ?>>
		<p class="description">
			<?php if ( $override ) : ?>
				<strong>Overridden by <code>TIAA_COOKIE_DOMAIN</code> in
				<code>wp-config.php</code></strong> — value is
				<code><?php echo esc_html( TIAA_COOKIE_DOMAIN ); ?></code>.
				Remove the constant to use this field instead.
			<?php else : ?>
				Domain shared by WordPress and Discourse for cross-subdomain cookies.
				Must begin with a dot: <code>.tiaa-forum.org</code> (production),
				<code>.test.tiaa-forum.org</code> (staging), <code>.local</code> (dev).<br>
				<em>Tip: set <code>define('TIAA_COOKIE_DOMAIN', '.local');</code> in
				<code>wp-config.php</code> to lock this per environment without
				touching the database.</em>
			<?php endif; ?>
		</p>
		<?php
	}

	public function render_contact_email_field(): void {
		$value = get_option( self::KEY_CONTACT_EMAIL, '' );
		?>
		<input type="email"
		       name="<?php echo esc_attr( self::KEY_CONTACT_EMAIL ); ?>"
		       value="<?php echo esc_attr( $value ); ?>"
		       class="regular-text">
		<p class="description">Site contact email used in automated messages and the Contact page.</p>
		<?php
	}

	public function render_funding_level_field(): void {
		$value   = get_option( self::KEY_FUNDING_LEVEL, 'green' );
		$choices = [
			'green'  => 'Green — healthy reserves',
			'yellow' => 'Yellow — watch needed',
			'red'    => 'Red — critical',
			'blue'   => 'Blue — special campaign',
		];
		?>
		<select name="<?php echo esc_attr( self::KEY_FUNDING_LEVEL ); ?>">
			<?php foreach ( $choices as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"
				        <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			Controls the Contribute button colour in the Discourse brand header.
			Set by the Treasurer based on current reserve level.
		</p>
		<?php
	}

	public function render_stats_fields(): void {
		$members = get_option( self::KEY_STAT_MEMBERS, 0 );
		$topics  = get_option( self::KEY_STAT_TOPICS,  0 );
		$posts   = get_option( self::KEY_STAT_POSTS,   0 );
		?>
		<table class="form-table" style="margin:0;padding:0">
			<tr>
				<th style="padding-left:0;font-weight:normal">Members</th>
				<td><input type="number" min="0" name="<?php echo esc_attr( self::KEY_STAT_MEMBERS ); ?>" value="<?php echo esc_attr( $members ); ?>" class="small-text"></td>
			</tr>
			<tr>
				<th style="padding-left:0;font-weight:normal">Topics</th>
				<td><input type="number" min="0" name="<?php echo esc_attr( self::KEY_STAT_TOPICS ); ?>" value="<?php echo esc_attr( $topics ); ?>" class="small-text"></td>
			</tr>
			<tr>
				<th style="padding-left:0;font-weight:normal">Posts</th>
				<td><input type="number" min="0" name="<?php echo esc_attr( self::KEY_STAT_POSTS ); ?>" value="<?php echo esc_attr( $posts ); ?>" class="small-text"></td>
			</tr>
		</table>
		<p class="description">
			Displayed on the homepage statistics card. Update manually when Discourse
			reports change. Use in Elementor:
			<code>[tiaa_stat field="members"]</code>
			<code>[tiaa_stat field="topics"]</code>
			<code>[tiaa_stat field="posts"]</code>
		</p>
		<?php
	}

	/**
	 * Renders the full Site Settings tab content.
	 * Called from options-page.php when tab=site_settings.
	 */
	public function render_tab(): void {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( self::OPTION_GROUP );
			do_settings_sections( self::OPTIONS_PAGE . '_' . self::TAB_SLUG );
			submit_button( 'Save Site Settings' );
			?>
		</form>
		<?php
	}

	/**
	 * [tiaa_stat field="members|topics|posts"]
	 */
	public function render_stat_shortcode( array $atts ): string {
		$atts = shortcode_atts( [ 'field' => '' ], $atts, self::SHORTCODE_STAT );
		$map  = [
			'members' => self::KEY_STAT_MEMBERS,
			'topics'  => self::KEY_STAT_TOPICS,
			'posts'   => self::KEY_STAT_POSTS,
		];
		$key = $map[ $atts['field'] ] ?? null;
		if ( ! $key ) {
			return '';
		}
		return esc_html( (string) (int) get_option( $key, 0 ) );
	}

	public function sanitize_funding_level( string $value ): string {
		return in_array( $value, [ 'red', 'yellow', 'green', 'blue' ], true ) ? $value : 'green';
	}

	/**
	 * Displays the Discourse URL from WP-Discourse settings (read-only).
	 * We don't store this ourselves — WP-Discourse owns it.
	 */
	public function render_discourse_url_field(): void {
		$url = self::get_discourse_url();
		?>
		<code><?php echo esc_html( $url ?: '(not set)' ); ?></code>
		<p class="description">
			Read from WP-Discourse plugin settings. To change it, go to
			<strong>Settings → WP-Discourse → Connection → Discourse URL</strong>.<br>
			This value is used as the destination for the GO TO FORUM button
			and as the redirect target after SSO login.
		</p>
		<?php
	}

	/**
	 * Outputs a small inline script that sets the href of all .tiaa-go-to-forum
	 * elements to the Discourse home URL. Fires on all front-end pages.
	 *
	 * Targets both logged-in and logged-out states: the GO TO FORUM button is
	 * only visible to logged-in members (Elementor display condition), but the
	 * script is harmless when no matching elements are present.
	 *
	 * ELEMENTOR STEP: Add CSS class 'tiaa-go-to-forum' to the GO TO FORUM
	 * button widget in the Header template (Advanced tab → CSS Classes).
	 */
	public function output_forum_button_script(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		$url = self::get_discourse_url();
		if ( empty( $url ) ) {
			return;
		}
		?>
		<script id="tiaa-forum-url">
		(function () {
			'use strict';
			var url = '<?php echo esc_js( $url ); ?>';
			document.querySelectorAll( '.tiaa-go-to-forum' ).forEach( function ( el ) {
				el.href = url;
			} );
		})();
		</script>
		<?php
	}

	/**
	 * Returns the Discourse home URL from WP-Discourse plugin options.
	 * WP-Discourse is the single source of truth for this value.
	 * Falls back to empty string if WP-Discourse is not configured.
	 *
	 * Called by output_forum_button_script.
	 *
	 * @return string Absolute URL with trailing slash, or empty string.
	 */
	public static function get_discourse_url(): string {
		$wpdc_options = get_option( self::WPDC_OPTIONS_KEY, [] );
		$url          = $wpdc_options['url'] ?? '';
		return $url ? trailingslashit( esc_url_raw( $url ) ) : '';
	}

	/**
	 * Returns the effective cookie domain.
	 * wp-config.php constant takes priority over the admin screen value.
	 * Called by TiaaReturnUrlCookie and TiaaMemberCookie.
	 */
	public static function get_cookie_domain(): string {
		if ( defined( 'TIAA_COOKIE_DOMAIN' ) ) {
			return TIAA_COOKIE_DOMAIN;
		}
		return get_option( self::KEY_COOKIE_DOMAIN, '.tiaa-forum.org' );
	}
}
