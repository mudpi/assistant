<?php
namespace Mudpi\Ajax;

use Mudpi\Tools\WPAConfiguration;
use Mudpi\Tools\Network;

require '../bootstrap.php';

begin_session();
handle_csrf();

if (!isset($_POST['network']) && empty($_POST['network'])) {
	response_error('Network was not set or invalid.');
}

$network = new Network(json_decode($_POST['network']));

if (!isset($network->ssid) || empty($network->ssid) || empty($_POST['ssid'])) {
	response_error('Network SSID not set or invalid.');
}

if ((!isset($_POST['passphrase']) || empty($_POST['passphrase'])) && $network->protocol === 'Open') {
	response_error('Network PASSPHRASE not set or invalid and is required for secure network.');
}
elseif (isset($_POST['passphrase']) && (strlen($_POST['passphrase']) < 8 || strlen($_POST['passphrase']) > 63)) {
	response_error('Network PASSPHRASE must be between 8 and 64 characters.');
}
else {
	$network->passphrase = $_POST['passphrase'];
}

$network->ssid = $_POST['ssid'];

$config = new WPAConfiguration( array($network) );

if($config->saveToFile(MUDPI_CONFIG.'/tmp/wpa_supplicant.conf')) {
	echo json_encode(['status' => 'OK', 'message' => 'Succsfully Saved Netowrk Config to '.MUDPI_CONFIG_NETWORKING.'/wpa_supplicant.conf']);
}
else {
	response_error('Problem Saving the Network File');
}

?>

