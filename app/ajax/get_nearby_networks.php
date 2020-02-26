<?php
namespace Mudpi\Ajax;

use Mudpi\Tools\WPASupplicant;

require '../bootstrap.php';

begin_session();
handle_csrf();

// Get all the networks within range
$wpa_cli = new WPASupplicant();
// $wpa_cli->refreshNetworkList();
$wpa_cli->scanNearbyHotspots();

echo json_encode($wpa_cli->networks);
?>

