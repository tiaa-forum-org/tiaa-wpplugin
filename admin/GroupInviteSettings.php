<?php
/**
 * TIAA Forum Group Invite Settings
 *
 * Handles the administration settings for group-specific TIAA Forum invitations,
 * providing configuration options for different user groups and their respective
 * Discourse forum settings.
 *
 * @package    TIAAPlugin
 * @subpackage Admin
 * @author     Lew Grothe, TIAA Admin Platform sub-team
 * @license    GPL-2.0-or-later
 * @link       https://tiaa-forum.org/contact
 * @email      info@tiaa-forum.org
 *
 * @since      0.0.3
 */

namespace TIAAPlugin\Admin;

use TIAAPlugin\lib\PluginUtil;

/**
 * Manages group-specific invitation settings for the TIAA Forum.
 *
 * Handles the creation and management of settings pages for different user groups,
 * allowing specific configuration of API keys, URLs, and other settings per group.
 *
 * @since 0.0.3
 */

class GroupInviteSettings {
	use PluginUtil;
	/**
	 * Form helper instance for creating and managing form elements.
	 *
	 * @since  0.0.3
	 * @access protected
	 * @var    FormHelper Instance of the form helper class.
	 */
	protected FormHelper $form_helper;
	/**
	 * Stored group invite options from the WordPress options table.
	 *
	 * @since  0.0.3
	 * @access protected
	 * @var    array|null The stored group invite configuration options.
	 */
	protected ?array $invite_options;

	/**
	 * GroupInviteSettings constructor.
	 *
	 * Initializes properties and actions.
	 *
	 * @param FormHelper $form_helper Form helper object.
	 */
	public function __construct(FormHelper $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_group_invite_settings' ) );
	}

	/**
	 * Adds settings sections and fields, and registers the settings.
	 *
	 * This function is hooked to 'admin_init'.
	 */
	public function register_group_invite_settings(): void {
		$this->invite_options = $this->get_options_by_group( TIAA_GROUP_LIST_GROUP );

		add_settings_section(
			'group_list_section',
			'List of Groups for Group Invite',
			array(
				$this,
				'group_list_section',
			),
			TIAA_GROUP_LIST_GROUP
		);
		add_settings_field(
			'group_list',
			'List of groups',
			array(
				$this,
				'group_list_setting'
			),
			TIAA_GROUP_LIST_GROUP,
			'group_list_section'
		);

		register_setting(
			TIAA_GROUP_LIST_GROUP,
			TIAA_GROUP_LIST_GROUP,
			array(
				$this->form_helper,
				'validate_options',
			));

		$group_list = $this->get_options_by_group(TIAA_GROUP_LIST_GROUP);
		if ($group_list) {
			foreach ($group_list as $group_array) {
				foreach( $group_array as $group) {
					$this->group_invite_settings($group);

				}
			}
		}


	}

	/**
	 * Prints the group list section on the settings page.
	 *
	 * This function is set as a callback in the add_settings_section call.
	 */
	public function group_list_section() {
		?>
		<p class="group_invite_forum_section_tab">
			This is a list of groups that can receive a "special invite" to the forum.
            See documentation here.
		</p>
		<?php
	}

	/**
	 * Sets the value of the group list setting.
	 *
	 * This function is set as a callback in the add_settings_field call.
	 */
	public function group_list_setting() : void {
		$this->form_helper->input(
			'group_list',
			TIAA_GROUP_LIST_GROUP,
			'comma separated list of group slugs',
            'array',
            null,
			array("style" => 'width: 20em;text-align: left;')
		);
	}

	/**
	 * Adds settings fields and register the settings for a given group invite.
	 *
	 * @param string $group_name The name of the group which is concatenated
     *      into the options group name.
	 */
    public function group_invite_settings(string $group_name) : void {
        $option_group_name = TIAA_GROUP_INVITE_GROUP . $group_name;
        $section_name = 'invite_settings_section-' . $group_name;
	    add_settings_section(
                $section_name,
		    'Options for TIAA Forum Signup page for ' . $group_name . ' group' ,
		    array(
			    $this,
			    'invite_to_forum',
		    ),
		    $option_group_name,
            array('group_name' => $group_name)
	    );
	    add_settings_field(
		    'url',
		    'Discourse URL',
		    array(
			    $this,
			    'url_input'
		    ),
		    $option_group_name,
		    $section_name,
            array('option_group_name' => $option_group_name)
	    );

	    add_settings_field(
		    'api_key',
		    'API Key',
		    array(
			    $this,
			    'api_key_input'
		    ),
		    $option_group_name,
		    $section_name,
		    array('option_group_name' => $option_group_name,
		          'group_name' => $group_name)
	    );
	    add_settings_field(
		    'username',
		    'Discourse login name',
		    array(
			    $this,
			    'username_input'
		    ),
		    $option_group_name,
		    $section_name,
		    array('option_group_name' => $option_group_name,
		          'group_name' => $group_name)
	    );
	    add_settings_field(
		    'post_id',
		    'Discourse message post ID',
		    array(
			    $this,
			    'post_id_input'
		    ),
		    $option_group_name,
		    $section_name,
		    array('option_group_name' => $option_group_name,
                'group_name' => $group_name)
	    );
	    register_setting(
		    $option_group_name,
		    $option_group_name,
		    array(
			    $this->form_helper,
			    'validate_options_blank_ok',
		    ));
    }

	/**
	 * Prints the forum invitation section on the settings page.
	 *
	 * This function is set as a callback in the add_settings_section call.
	 */
	public function invite_to_forum(array $args) : void {
		?>
        <p class="invite_to_forum_section_tab">
            These settings determine how our invitation system works for the <?php echo $args['group_name']; ?> group.
        </p>
		<?php
	}
	/**
	 * Creates URL input field for group settings.
	 *
	 * @since  0.0.3
	 * @access public
	 * @param  array $args {
	 *     Array of field arguments.
	 *
	 *     @type string $option_group_name The name of the option group.
	 * }
	 * @return void
	 */
	public function url_input(array $args) : void {
        $option_group_name = $args['option_group_name'];
		$this->form_helper->input(
			'url',
			$option_group_name,
			'URL - leave blank to use connection default',
			'url'
		);
	}
	/**
	 * Creates API key input field for group settings.
	 *
	 * @since  0.0.3
	 * @access public
	 * @param  array $args {
	 *     Array of field arguments.
	 *
	 *     @type string $option_group_name The name of the option group.
	 *     @type string $group_name        The name of the group.
	 * }
	 * @return void
	 */
	public function api_key_input(array $args) : void {
		$option_group_name = $args['option_group_name'];
		$this->form_helper->input(
			'api_key',
			$option_group_name,
			'API Key - leave blank to use the connection default'
		);
	}
	/**
	 * Creates username input field for group settings.
	 *
	 * @since  0.0.3
	 * @access public
	 * @param  array $args {
	 *     Array of field arguments.
	 *
	 *     @type string $option_group_name The name of the option group.
	 * }
	 * @return void
	 */
	public function username_input(array $args) : void {
		$option_group_name = $args['option_group_name'];
    	$this->form_helper->input(
			'username',
			$option_group_name,
			'Username - leave blank to use connection default',
            'username',
            null,
            array("style" => 'width: 10em;')
		);
        $hook_url = site_url("/wp-json/tiaa_wpplugin/v1/tiaa_discourse_ping?option_group=$option_group_name");
		?>
        <div class="wrap tiaa-ping-discourse-class" id="tiaa-ping-<?php echo $args['group_name'];?>">
            <a href="<?php echo $hook_url ?>"  id="tiaa-ping-<?php echo $args['group_name'];?>-a" >Ping test</a>
            <div id="tiaa-ping-<?php echo $args['group_name'];?>-results" class="tiaa-ping-results">ping results</div>
        </div>
        <?php
	}

	/**
	 * Creates post ID input field for group settings.
	 *
	 * @since  0.0.3
	 * @access public
	 * @param  array $args {
	 *     Array of field arguments.
	 *
	 *     @type string $option_group_name The name of the option group.
	 *     @type string $group_name        The name of the group.
	 * }
	 * @return void
	 */
	public function post_id_input(array $args) : void {
		$group_name = $args['group_name'];
		$option_group_name = $args['option_group_name'];
        $this->form_helper->input(
			'post_id',
			$option_group_name,
			'Invitation Post Message ID',
			'number',
			null,
	        array("style" => 'width: 5em;')
        );

		$options = self::get_options_by_group($option_group_name);
        // only display options if post_id has been set
		if (isset($options['post_id']) && $options['post_id'] > 10) {
			$hook_url = site_url("/wp-json/tiaa_wpplugin/v1/get_discourse_post/" .
                    "?post_id={$options['post_id']}&option_group=$option_group_name");
			?>
            <div class="wrap tiaa-message-discourse-class" id="tiaa-message-<?php echo $group_name;?>">
                <a href='<?php echo $hook_url ?>' id="tiaa-message-<?php echo $group_name;?>-a" >Get Message</a>
                <div id="tiaa-message-<?php echo $group_name;?>-results" class="tiaa-message-results-off">Message div</div>
            </div>
			<?php
		} //  if ($options['post_id'] > 10)
	}
}
