### Known bugs or incomplete investigations
#### 1. Problem with saving optins as an array
The WelcomeSettings.php file uses the `validate_options` for registering settings instead of `validate_options_blank_ok`. Reason for this is that, for some reason, the saving of the array `group_list` in the WelcomeOptions is converted to a string on validation when using `validate_options_blank_ok`.

After a bit of investigation and creating a special function which should have handled it - giving up for now which means that you must enter Discourse credentials in the WelcomeSettings.

We meed to spend some time testing and validating the validator() method in FormHandler class.
#### 2. Logger handling
The file TIAAFile.php is buried in the \Analog area but should be moved to the plugin library and generalized. 

As a result, the logging is not reliable because it seems like it doesn't get initialized for all the calls. This needs to be audited so that the calls from \PluginUtil are reliable. 