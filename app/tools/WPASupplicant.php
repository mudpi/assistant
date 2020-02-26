<?php 
namespace Mudpi\Tools;

//Useful constants for wpa scan results parsing

class WPASupplicant {
	public $networks = [];
	public $config = null;

	private $wpa_config_raw = null;
	private $scan_results_raw = null;

	//Constants for scan results return order in array
	const WPA_MAC = 0;
	const WPA_FREQUENCY = 1;
	const WPA_RSSI = 2;
	const WPA_SECURITY = 3;
	const WPA_SSID = 4;

	public function __construct() {
		//Load any networks from the current configuration
		$this->networks = $this->loadSavedConfig()->networks;
	}

	public function getNearbyNetworks($raw = false) {
		exec( 'sudo wpa_cli -i ' . MUDPI_WIFI_INTERFACE . ' scan' );
		sleep(4); //Sleep to wait for scan to complete
		exec( 'sudo wpa_cli -i ' . MUDPI_WIFI_INTERFACE . ' scan_results', $this->scan_results_raw );
		array_shift($this->scan_results_raw);
		return $raw ? $this->scan_results_raw : $this->parseNetworkScanResults($this->scan_results_raw);
	}

	public function getConnectedNetworks() {
		exec( 'iwconfig ' . MUDPI_WIFI_INTERFACE, $iwconfig_return );
		foreach ($iwconfig_return as $line) {
			if ( preg_match( '/ESSID:\"([^"]+)\"/i',$line,$iwconfig_ssid ) && isset($this->networks[$iwconfig_ssid[1]]) ) {
				$this->networks[$iwconfig_ssid[1]]->connected = true;
			}
		}
		return $iwconfig_return;
	}

	public function loadSavedConfig($path = MUDPI_CONFIG_WPA_SUPPLICANT) {
		$this->config = new WPAConfiguration();
		$this->config->loadFromFile($path);
		return $this->config;
	}

	//Switch between mulitple configured networks. 
	public function switchNetwork($network_index) {
		$result = 0;
		exec('sudo wpa_cli -i ' . MUDPI_WPA_CTRL_INTERFACE . ' select_network '.strval($network_index), $output, $result);
		return $result;
	}

	public function reconfigure() {
		$return_result = 0;
		exec('sudo wpa_cli -i ' . MUDPI_WIFI_INTERFACE . ' reconfigure', $output, $return_result);
		return $return_result;
	}

	public function renewLease() {
		$return_result;
		exec('sudo dhclient '.MUDPI_WIFI_INTERFACE);
	}

	public function refreshNetworkList($hardreset = false) {
		if($hardreset) { $this->networks = []; }
		$this->getNearbyNetworks();
		$this->getConnectedNetworks();
		return $this->networks;
	}

	//Private Functions
	private function parseNetworkScanResults($scan_results) {
		$network_list = [];
		$network = null;
		foreach( $scan_results as $scan_result ) {
			//Split the network line on tabs to get just the data
			$scan_data = preg_split("/[\t]+/",$scan_result);
			if (!array_key_exists(self::WPA_SSID, $scan_data)) {
				continue;
			}
			//Check for exisiting network in the list
			if(array_key_exists($scan_data[self::WPA_SSID], $this->networks)) {
				$network = $this->networks[$scan_data[self::WPA_SSID]];
			}
			else {
				$network = new Network();
			}
			$network->macAddress = array_key_exists(self::WPA_MAC, $scan_data) ? $scan_data[self::WPA_MAC] : null;
			$network->frequency = array_key_exists(self::WPA_FREQUENCY, $scan_data) ? $scan_data[self::WPA_FREQUENCY] : null;
			$network->rssi = array_key_exists(self::WPA_RSSI, $scan_data) ? $scan_data[self::WPA_RSSI] : null;
			$network->protocol = array_key_exists(self::WPA_SECURITY, $scan_data) ? $this->parseSecurityString($scan_data[self::WPA_SECURITY]) : null;
			$network->ssid = array_key_exists(self::WPA_SSID, $scan_data) ? $scan_data[self::WPA_SSID] : null;

			$network_list[$network->ssid] = $network;
			$this->networks[$network->ssid] = $network;
		}
		return $network_list;
	}

	//Another method to scan for networks while in Hotspot mode
	public function scanNearbyHotspots() {
		exec('sudo /sbin/iw dev '.MUDPI_WIFI_INTERFACE.' scan ap-force | egrep "WEP:|^BSS|SSID:|signal:|freq:|capability:|WPA:|RSN:"', $scan_results);

		$network_list = [];
		$network =  new Network();
		$new_network = [];

		foreach( $scan_results as $scan_result ) {
			//Split the network line on ':' to get just the data
			$scan_data = preg_split("/:+/", $scan_result, 2);
			$key = strtolower(trim($scan_data[0]));
			$value = trim($scan_data[1]);

			//Check for 'BSS' which is mac address aka start of scan block
			if (strpos($key, 'bss') !== false) {
				$network = new Network();
				$new_network = [];
				$new_network['macAddress'] = substr($key, -2).':'.preg_replace('/\(([^\)]+)\)/', '', $value);
				$new_network['protocol'] = 'Open';
			}

			if ($key == 'rsn') {
				$new_network['protocol'] = 'WPA2';
			}

			if ($key == 'wpa') {
				$new_network['protocol'] = 'WPA';
			}

			//Check for exisiting network in the list
			if(!array_key_exists($key, $new_network)) {
				$new_network[$key] = $value;
			}

			//If the key is 'ssid' then end of scan block
			if ($key == 'ssid') {
				//End of block operations
				$network->macAddress = array_key_exists('macAddress', $new_network) ? $new_network['macAddress'] : null;
				$network->frequency = array_key_exists('freq', $new_network) ? $new_network['freq'] : null;
				$network->rssi = array_key_exists('signal', $new_network) ? preg_replace('/[^0-9\.,]/', '', $new_network['signal']) : null;
				$network->protocol = array_key_exists('protocol', $new_network) ? $new_network['protocol'] : null;
				$network->ssid = array_key_exists('ssid', $new_network) ? $new_network['ssid'] : null;

				if(array_key_exists('ssid', $new_network)) {
					$network_list[$new_network['ssid']] = $new_network;
					if(!array_key_exists($new_network['ssid'], $this->networks)) {
						$this->networks[$new_network['ssid']] = $network;
					}
				}	
			}

		}
		return $network_list;
	}

	private function parseSecurityString($security_string) {
		$options = array();
		//Match all the flags from the scan [WPA][FLAG2]
		preg_match_all('/\[([^\]]+)\]/s', $security_string, $matches);
		foreach ($matches[1] as $match) {
			//Determine if its WPA flag to determine security protocol
			if (preg_match('/^(WPA\d?)/', $match, $protocol_match)) {
				//Will be WPA or WPA2
				$protocol = $protocol_match[1];
				//Explode the flags by '-' to get other options like 'PSK' or "CCMP"
				$wpa_options = explode('-', $match);
				if (count($wpa_options) > 2) {
					$options[] = $protocol . ' ('.  $wpa_options[2] . ')';
				} else {
					$options[] = $protocol;
				}
			}
		}
		//No flags are set means it must be WEP or Open
		if (count($options) === 0) {
			return 'Open';
		} else {
			return implode(' / ', $options);
		}
	}
}