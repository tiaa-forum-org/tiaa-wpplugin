<?php
/**
 * TIAA-WPPlugin - Cron Status Dashboard
 *
 * This template provides an interface for managing cron tasks
 * associated with the TIAA-WPPlugin. It allows starting, stopping,
 * firing the cron manually, and viewing its current status. Additionally,
 * it fetches and displays recent log entries for debugging and monitoring purposes.
 *
 * @package TIAA-WPPlugin
 * @subpackage Admin_Interface
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @license GPL-2.0-or-later
 */

// Check if $cron_status is set and validate method availability.
if (!isset($cron_status)) {
	die('Error: $cron_status is not set.');
}

if (!isset($this) || !isset($this->Util) || !method_exists($this->Util, 'get_recent_log_entries')) {
	die('Error: `get_recent_log_entries()` method is not accessible.');
}

?>
<div class="wrap tiaa-welcome-class">
    <hr>
    <!-- Cron action buttons -->
    <div class="tiaa-flex-container">
        <div>
            <form method="post">
				<?php submit_button('Start Cron', 'primary', 'cron_start', false); ?>
            </form>
        </div>
        <div>
            <form method="post">
				<?php submit_button('Stop Cron', 'primary', 'cron_stop', false); ?>
            </form>
        </div>
        <div>
            <form method="post">
				<?php submit_button('Fire Cron Once', 'primary', 'cron_do_run', false); ?>
            </form>
        </div>
        <div>
            <form method="post">
				<?php submit_button('Get Cron Status', 'primary', 'get_cron_status', false); ?>
            </form>
        </div>
    </div>

    <!-- Current cron status -->
    <div>
        <p>At <?php echo date('Y-m-d H:i:s', time()); ?> cron status is:
			<?php echo esc_html($cron_status); ?>
        </p>
    </div>

    <hr>
	<?php // Create a URL for the secure_file action
	$download_url = add_query_arg(
		[
			'action'  => 'tiaa_secure_file',
			'_wpnonce' => wp_create_nonce( 'admin_post_tiaa_secure_file' ),
			'type'    => 'csv',
			'table'   => 'tiaa_welcome_log',
		],
		admin_url( 'admin-post.php' )
	);
	// Output the URL (e.g. on a download button) ?>
    <a href="<?php echo esc_url( $download_url ); ?>"
       class="button button-primary"
       data-download="csv">Download CSV</a>
    <hr>

    <!-- Log entries table -->
	<?php
	// Fetch the most recent log entries (default limit: 10 entries).
	$log_entries = $this->Util->get_recent_log_entries(10);
	?>
    <h3>Latest <?php echo count($log_entries); ?> entries</h3>
    <table class="wp-list-table widefat fixed striped table-view-list" style="width: 70vw;">
        <colgroup>
            <col style="width: 12%;">
            <col style="width: 15%;">
            <col style="width: 20%;">
            <col style="width: 15%;">
            <col style="width: 20%;">
            <col style="width: 20%;">
            <col style="width: 10%;">
        </colgroup>
        <thead>
        <tr>
            <th>Member ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Group Name</th>
            <th>Date Created</th>
            <th>Date Processed</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
		<?php if (empty($log_entries)) : ?>
            <tr>
                <td colspan="8">No log entries found.</td>
            </tr>
		<?php else : ?>
			<?php foreach ($log_entries as $log) : ?>
                <tr>
                    <td><?php echo esc_html($log->member_id); ?></td>
                    <td><?php echo esc_html($log->username); ?></td>
                    <td><?php echo esc_html($log->email); ?></td>
                    <td><?php echo esc_html($log->group_name); ?></td>
                    <td><?php echo esc_html($log->date_created); ?></td>
                    <td><?php echo esc_html($log->date_processed); ?></td>
                    <td><?php echo esc_html($log->status); ?></td>
                </tr>
			<?php endforeach; ?>
		<?php endif; ?>
        </tbody>
    </table>
</div>