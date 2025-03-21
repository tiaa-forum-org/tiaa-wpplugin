/**
 * Executes once the DOM is fully loaded, attaching event handlers to download buttons.
 *
 * This function binds click events to all buttons with the attribute `[data-download]`.
 * When clicked, it:
 * - Prevents the default navigation behavior.
 * - Retrieves the `href` attribute as the download URL.
 * - Displays a progress indicator, if available.
 * - Initiates the download by setting `window.location.href` to the URL.
 * - Hides the progress indicator upon success or failure.
 * TODO - as of version 0.0.4, standard browser download is used so no progress is implemented
 * Errors during the initiation process are logged via `console.error`, and the user
 * is alerted with a failure message.
 *
 * @since 0.0.4
 *
 * @return void
 */
document.addEventListener('DOMContentLoaded', () => {
    // Select all elements with the data attribute `data-download`
    const downloadButtons = document.querySelectorAll('[data-download]');

    /**
     * Loop through each `data-download` button, attaching individual event listeners.
     */
    downloadButtons.forEach(button => {
        /**
         * Handles the click event for downloading.
         *
         * - Prevents default browser navigation.
         * - Retrieves the download URL from the `href` attribute.
         * - Displays or hides a progress indicator.
         * - Uses `window.location.href` to initiate the browser download.
         * - Handles success and failure states with alerts and logs.
         *
         * @param {MouseEvent} e The click event object.
         *
         * @return {Promise<void>} A promise that resolves after all operations.
         */
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const downloadUrl = button.getAttribute('href');

            // Progress indicator container (optional feature)
            const progressEl = document.getElementById(`${button.dataset.download}-progress`);
            if (progressEl) {
                progressEl.style.display = 'block';
            }

            try {
                // Use the browser's native behavior to download the file
                window.location.href = downloadUrl;

                // Notify the user of download initiation
                alert('Download initiated. Check your browser downloads.');
                // Hide the progress indicator
                if (progressEl) progressEl.style.display = 'none';
            } catch (error) {
                // Log errors to the console
                console.error('Download failed:', error);

                // Notify the user of the failure
                alert('Failed to download file.');
                // Hide the progress indicator
                if (progressEl) progressEl.style.display = 'none';
            }
        });
    });
});