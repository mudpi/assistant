<?php
namespace Mudpi\Tools;

/*
-30 dBm 	Amazing
-67 dBm 	Very Good
-70 dBm 	Okay
-80 dBm 	Not Good
-90 dBm 	Unusable
*/

class Network {
	public $ssid = '';
	public $rssi = 0;
	public $channel = 0;
	public $passphrase = null;
	public $protocol = 'Open';
	public $macAddress = '';
	public $connected = false;
	public $configured = false;
	public $priority = null;

	public function __construct($config=null) {
		if(!empty($config)) {
			if(is_array($config) || is_object($config)) {
				foreach($config as $option => $value) {
					if(in_array(strtolower($option), ['ssid', 'rssi', 'frequency', 'macAddress', 'passphrase', 'connected', 'configured', 'protocol', 'channel', 'priority'])) {
						$this->$option = $value;
					}
				}
			} else {
				//Asssume a ssid was provided if no config array is
				$this->ssid = $config;
			}
		}
	}

	function __set($name, $value) {
		if($name == 'frequency') {
			$this->channel = $this->convertFrequencyToChannel($value);
		}
	}

	private function convertFrequencyToChannel($freq) {
		$channel = 0;
		if ($freq >= 2412 && $freq <= 2484) {
			$channel = ($freq - 2407)/5;
		} elseif ($freq >= 4915 && $freq <= 4980) {
			$channel = ($freq - 4910)/5 + 182;
		} elseif ($freq >= 5035 && $freq <= 5865) {
			$channel = ($freq - 5030)/5 + 6;
		} else {
			$channel = -1;
		}

		if ($channel >= 1 && $channel <= 196) {
			return $channel;
		} else {
			return 0;
		}
	}
}