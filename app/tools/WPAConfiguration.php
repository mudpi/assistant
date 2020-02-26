<?php 
namespace Mudpi\Tools;

class WPAConfiguration {
	public $networks;
	public $file_contents;
	public $country = 'US';

	public function __construct($networks = null, $country = 'US') {
		$this->networks = $networks ?? [];
		$this->country = $country ?? $this->country;
	}

	public function loadFromFile($path = MUDPI_CONFIG_WPA_SUPPLICANT) {
		exec(' sudo cat ' . $path, $this->file_contents);
		return $this->parseContentsFromFile($this->file_contents);
	}

	public function saveToFile($path = MUDPI_CONFIG_WPA_SUPPLICANT) {
		if ($file = fopen('/tmp/wpa_supplicant.tmp', 'w')) {
			fwrite($file, 'ctrl_interface=DIR=' . MUDPI_WPA_CTRL_INTERFACE . ' GROUP=netdev' . PHP_EOL);
			fwrite($file, 'update_config=1' . PHP_EOL);
			if(MUDPI_PI_VERSION >= 3) {
				fwrite($file, 'country='. $this->country . PHP_EOL);		
			}

			foreach ($this->networks as $ssid => $network) {
				$this->writeNetworkBlock($file, $network);
			}
			fclose($file);
			system('sudo cp /tmp/wpa_supplicant.tmp ' . $path, $file_copy_errors);

			if ($file_copy_errors == 0) {
				return $file;
			} else {
				//Failed copying the new configuration file (Permisson issues?)
				return false;
			}
		} else {
			//ERROR OPENING CONFIG FILE
			return false;
		}
	}

	private function parseContentsFromFile($configuration) {
		$networks = array();
		$network = null;
		foreach($configuration as $line) {
			if (preg_match('/country\s*=/', $line)) {
				$country = trim(explode('=', $line, 2)[1]);
			}
			//Look for network config block
			if (preg_match('/network\s*=/', $line)) {
				$network = new Network(['configured' => true, 'connected' => false]);
			}  //check if a network block was found and we have now reached the end of the block
			elseif ($network !== null) {
				if (preg_match('/^\s*}\s*$/', $line)) {
					$networks[$network->ssid] = $network;
					$this->networks[$network->ssid] = $network;
					$network = null;
				}  //check if we are inside the network block and reading configured options
				elseif ($config_settings = preg_split('/\s*=\s*/', trim($line))) {
					switch(strtolower($config_settings[0])) {
						case 'ssid':
							$network->ssid = trim($config_settings[1], '"');
							break;
						case 'psk':
							if (!empty($network->passphrase)) {
								break;
							}
						case '#psk':
							$network->protocol = 'WPA';
						case 'wep_key0': // Untested
							$network->passphrase = trim($config_settings[1], '"');
							break;
						case 'key_mgmt':
							if ((empty($network->passphrase)) && $config_settings[1] === 'NONE') {
								$network->protocol = 'Open';
							}
							break;
						case 'priority':
							$network->priority = trim($config_settings[1], '"');
							break;
					}
				}
			}
		}
		return $networks;
	}

	private function writeNetworkBlock($file, $network) {
		if ($network->protocol === 'Open') {
			$this->writeOpenNetworkBlock($file, $network);
		} elseif (strpos($network->protocol, 'WPA') !== false) {
			$this->writeWPANetworkBlock($file, $network);
		} else {
			//Protocol not currently supported...
		}
		return $file;
	}

	private function writeOpenNetworkBlock($file, $network) {
		fwrite($file, "network={".PHP_EOL);
		fwrite($file, "\tssid=\"".$network->ssid."\"".PHP_EOL);
		fwrite($file, "\tkey_mgmt=NONE".PHP_EOL);
		fwrite($file, "\tscan_ssid=1".PHP_EOL);
		if (!empty($network->priority)) {
			fwrite($file, "\tpriority=".$network->priority.PHP_EOL);
		}
		fwrite($file, "}".PHP_EOL);
		return $file;
	}

	private function writeWPANetworkBlock($file, $network) {
		if (strlen($network->passphrase) >=8 && strlen($network->passphrase) <= 63) {
			$wpa_encrypted_passphrase='';
			//generate encrypted passkey
			exec('wpa_passphrase '.escapeshellarg($network->ssid). ' ' . escapeshellarg($network->passphrase), $wpa_encrypted_passphrase);
			foreach ($wpa_encrypted_passphrase as $line) {
				if (preg_match('/^\s*}\s*$/', $line)) {
					//Write the priority before closing bracket if one is set
					if (!empty($network->priority)) {
						fwrite($file, "\tpriority=".$network->priority.PHP_EOL);
					}
					fwrite($file, $line.PHP_EOL);
				} else {
					fwrite($file, $line.PHP_EOL);
				}
			}
			return $file;
		} else {
			//PASSPHRASE MUST BE BETWEEN 8 AND 64 CHARACTERS
			throw new Exception('PASSPHRASE MUST BE BETWEEN 8 AND 64 CHARACTERS');
			return false;
		}
	}
}
