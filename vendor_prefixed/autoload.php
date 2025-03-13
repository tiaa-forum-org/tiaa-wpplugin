<?php
/* manually generated autoload in order to deal with namespace prefix */

require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Analog.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Apprise.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/LevelName.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Threshold.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/WPMail.php';
//require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Null.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/IFTTT.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Amon.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/ChromeLogger.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Mail.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Multi.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Syslog.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Mongo.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Buffer/Destructor.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Variable.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Post.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Buffer.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Redis.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/FirePHP.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Slackbot.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Stderr.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/GELF.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/LevelBuffer.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/File.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/PDO.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/Ignore.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/EchoConsole.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/psr/log/src/LoggerInterface.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Logger.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/ChromePhp.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/psr/log/src/LoggerTrait.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/psr/log/src/AbstractLogger.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/psr/log/src/LogLevel.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/psr/log/src/NullLogger.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/psr/log/src/LoggerAwareInterface.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/psr/log/src/InvalidArgumentException.php';
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/psr/log/src/LoggerAwareTrait.php';

// added for TIAAPlugin to allow for changing level to name rather
// than just a number
require_once TIAA_PLUGIN_PATH . 'vendor_prefixed/analog/analog/lib/Analog/Handler/TIAAFile.php';
