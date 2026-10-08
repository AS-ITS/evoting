<?php
/**
 * Custom configuration for Functional Tests.
 * Merges the main test config with necessary aliases and request configuration.
 */

// Debug: logging (disabled)
// file_put_contents('php://stderr', "Loading functional_config.php\n");

// Robustly Ensure argv is present for Console Request (used by EnvPathConfigTrait)
if (!isset($_SERVER['argv'])) {
    $_SERVER['argv'] = [];
}
// IMPORTANT: The EnvPathConfigTrait extracts the app name from the script path.
// determinePartitionByPath() reads $_SERVER['argv'][0] via yii\console\Request::getScriptFile(),
// then strips excluded dirs (web/public/yii/html/www/htdocs) to find the app folder name.
// Using __DIR__-based path keeps this portable regardless of the server's actual location.
$_SERVER['argv'][0] = dirname(__DIR__) . '/yii';

// Ensure Web vars require by Web Request if needed
if (!isset($_SERVER['SCRIPT_FILENAME'])) {
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/_bootstrap.php';
}
if (!isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['SCRIPT_NAME'] = '/tests/functional/_bootstrap.php';
}

$config = require __DIR__ . '/../config/test.php';

// Ensure @tests alias is defined for fixtures
if (!isset($config['aliases']['@tests'])) {
    $config['aliases']['@tests'] = dirname(__DIR__); // points to /tests/
}

// Configure Request component for Codeception
// Logic: "Unable to determine the path info" happens when Yii Request cannot match scriptUrl/baseUrl against requestUri.
// Setting scriptFile/Url explicitly usually fixes this.
$config['components']['request'] = array_merge(
    isset($config['components']['request']) ? $config['components']['request'] : [],
    [
        'class' => \yii\web\Request::class,
        'cookieValidationKey' => 'test-key-for-codeception',
        'scriptFile' => __DIR__ . '/../web/index.php',
        'scriptUrl' => '/index.php',
        'baseUrl' => '',
        'enableCsrfValidation' => false,
    ]
);

// Ensure UrlManager is configured to handle the requests
// Important: enablePrettyUrl=false means Yii uses ?r= parameter for routing
// But Codeception's internal request handling sets pathInfo directly from the URL path
// Solution: Override the UrlManager to properly handle both cases
// Keep enablePrettyUrl=true (from test.php) as Codeception works better with pretty URLs
// Tests should use pretty URL format: /controller/action?param=value instead of /?r=controller/action&param=value

// Disable RBAC cache for functional tests to avoid caching issues
$config['components']['authManager']['cache'] = null;

return $config;
