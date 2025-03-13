<?php
/**
 * TIAA-WPPlugin - Manage Screened Emails
 *
 * This template provides an interface to manage "screened emails."
 * It includes features for:
 * - Adding individual screened email entries with notes.
 * - Importing screened emails from a CSV file.
 * - Exporting screened emails to a CSV file.
 * - Displaying a paginated table of screened emails with options for deletion.
 *
 * @package TIAA-WPPlugin
 * @subpackage Admin_Interface
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @license GPL-2.0-or-later
 */
?>

<div class="wrap">
    <hr style="border-width: 8px;">
    <!-- Heading for the Screened Email Management Section -->
    <div id="tiaaManageScreenedEmails">
        <h3>Manage Screened Emails</h3>
    </div>

    <div id="tiaaScreenedEmailsDiv">
        <!-- Add Email Form -->
        <form method="post">
            <label for="tiaa-screened-email">Email to be screened: </label>
            <input type="email" name="email" width="15em" id="tiaa-screened-email" required>
            <label for="tiaa-screened-email-notes">Notes: </label>
            <input type="text" name="notes" width="25em" id="tiaa-screened-email-notes" class="regular-text">

			<?php
			// Submit button for adding a screened email.
			submit_button(
				'Add Screen Email',
				'primary',
				'submit_email',
				false,
				array('class' => 'button primary-button')
			);
			?>
        </form>

        <hr>

        <!-- Import CSV Form -->
        <form method="post" enctype="multipart/form-data">
            <label for="tiaa_csv_file">Import CSV: </label>
            <input type="file" name="tiaa_csv_file" id="tiaa_csv_file" required>
			<?php
			submit_button(
				'Import CSV',
				'primary',
				'import_csv',
				false,
				array('class' => 'button primary-button')
			);
			?>
        </form>

        <hr>

        <!-- Export CSV Form -->
        <div class="wrap">
            <form method="post" action="">
                <!-- Security nonce for validation -->
				<?php wp_nonce_field('export_emails_csv', '_wpnonce_export_csv'); ?>

                <!-- File Name Input -->
                <label for="export_file_name">
					<?php esc_html_e('Enter File Name (CSV)', 'tiaa-plugin'); ?>
                </label>
                <input type="text" name="export_file_name" id="export_file_name" />

                <label for="column_labels">
					<?php esc_html_e('Include Column Labels', 'tiaa-plugin'); ?>
                </label>
                <input type="checkbox" name="column_labels" value="on" />

				<?php
				submit_button(
					'Export CSV',
					'primary',
					'export_csv',
					false,
					array('class' => 'button primary-button')
				);
				?>
            </form>
        </div>
        <hr>

        <!-- Display Emails Table -->
        <table class="wp-list-table widefat fixed striped table-view-list" style="width: 80vw;">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 15%;">
                <col style="width: 10%;">
                <col style="width: 20%;">
                <col style="width: 20%;">
                <col style="width: 30%;">
            </colgroup>
            <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Hit Count</th>
                <th>Date Added</th>
                <th>Last Accessed</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
			<?php if (empty($emails)) : ?>
                <tr>
                    <td colspan="7">No emails found.</td>
                </tr>
			<?php else : ?>
				<?php foreach ($emails as $email) : ?>
                    <tr>
                        <td><?php echo esc_html($email->ID); ?></td>
                        <td><?php echo esc_html($email->email); ?></td>
                        <td><?php echo esc_html($email->hit_count); ?></td>
                        <td><?php echo esc_html($email->date_time_added); ?></td>
                        <td><?php echo esc_html($email->date_time_last_access); ?></td>
                        <td><?php echo esc_html($email->notes); ?></td>
                        <!-- Delete Email Button -->
                        <td>
                            <form method="post">
                                <input type="hidden" name="delete_email_id" value="<?php echo esc_attr($email->ID); ?>">
                                <button type="submit" class="button button-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
				<?php endforeach; ?>
			<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>