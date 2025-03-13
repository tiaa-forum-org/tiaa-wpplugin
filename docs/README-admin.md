## Admin layout
The plugin admin is comprised of a number of sub-systems (e.g. connections, invites, messaging, logging, etc.) which are presented as sub-menus and-or tabs.

Basic design and execution closely follows what the WP Discourse plugin did as it was presumed that anyone who was supporting this plugin had probably at least installed and may maintain that plugin as well.

The sub-systems all show up as tabs on the options-page. The options page route has an argument of tab=[service name slug]  which is then used to call the rendering of the that page.

For some services/pages (screening emails, logging, welcome) the admin page is split between regular WP settings and a separate section that deals with the associated database table and misc. non-setting related operations.