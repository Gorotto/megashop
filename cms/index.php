<?

//ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once( $_SERVER['DOCUMENT_ROOT'] . "/includes/loader.php" );

Builder::init();

$app = new CmsApplication();
$app->run();
