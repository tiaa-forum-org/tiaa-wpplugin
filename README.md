# TIAA WordPress Plugin

## Overview

The **TIAA WordPress Plugin** (from tiaa-forum.org) is designed to provide essential functionality for the [TIAA-Forum.org](https://tiaa-forum.org) community, integrating key features to enhance user experience, streamline administrative processes, and improve interactions with external the Discourse server.

This plugin consolidates various functions previously handled by Google Apps Scripts and third-party WordPress plugins, aiming for a more maintainable and WordPress-native solution.

## Features

### 1. **User Invitation Management**
- Replaces the existing Google Apps Script-based invitation system.
- Provides a form-based invite process within WordPress.
- Integrates with Discourse to automate invitation processing.
- Allows invitation to a particular Discourse group to allow for special onboarding or to allow changing the home group for members of a particular primary group
- Detects duplicate email addresses and directs users to the password reset process.

### 2. **Welcome Message Automation**
- Sends personalized Discourse messages to new users.
- Helps onboard members by explaining key features of the forum.
- Ensures engagement and retention by encouraging participation.

### 3. **Web Hook & API Integrations**
- Uses webhooks for real-time processing of forms.
- Custom PHP handlers improve error handling and user feedback.
- Designed to work independently of Elementor but offers integration where needed.

### 4. **Admin & Plugin Configuration**
- Provides a centralized admin panel for managing plugin settings.
- Supports configurable parameters for integration with Discourse.
- Implements WordPress cron jobs for scheduled tasks.

## Installation

1. **Upload the Plugin:**
    - Download the ZIP file from GitHub or your WordPress admin panel.
    - Navigate to `Plugins > Add New` and upload the ZIP file.
    - Click **Activate**.

2. **Configure Settings:**
    - Go to `Settings > TIAA Plugin` in the WordPress admin panel.
    - Enter the required API keys for Discourse.
    - Adjust settings for invite processing, welcome messages, and webhook responses.

3. **Use the Plugin:**
    - The plugin will automatically handle invites, welcome messages, and other functions based on the settings configured.

## Future Development

Planned enhancements include:
- More robust error handling for Discourse API calls.
- Additional admin tools for managing invitations.

## Contributing

If you'd like to contribute to this plugin, you can:
- Submit bug reports and feature requests on the [GitHub repository](https://github.com/tiaa-forum-org).
- Fork the repository and submit pull requests with improvements.
- Provide feedback and suggestions via the [TIAA Forum](https://discourse.tiaa-forum.org).

## License

This plugin is open-source and licensed under the [MIT License](LICENSE).
