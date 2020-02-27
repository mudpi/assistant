<img alt="MudPi Smart Garden" title="MudPi Smart Garden" src="https://mudpi.app/img/mudPI_LOGO_small_flat.png" width="100px">

# MudPi Setup Assistant
> A web application with setup scripts to make first time configurations in MudPi.

MudPi Assistant is a lightweight php application that helps with first time configurations such as connecting to Wifi. Typically this app will be used for initial configurations and then be removed in place of [MudPi UI](https://github.com/mudpi/ui) after finishing setup. Assistant makes use of `wpa_supplicant` to help configure your network settings.

## Installation
Clone the repo into your web server i.e. nginx
```
cd /var/www/html/mudpi_assistant
git clone https://github.com/mudpi/assistant.git
```

Check and copy the provided server config file to nginx. Make sure the root is correct and proper php-fpm version is selected.
```
sudo cp /var/www/html/mudpi_assistant/configs/mudpi_assistant.conf /etc/nginx/sites-enabled
sudo nginx -t
sudo service nginx restart
```

Update your sudoers files with visudo and add the following
```
www-data ALL=(ALL) NOPASSWD:/sbin/shutdown -h now
www-data ALL=(ALL) NOPASSWD:/sbin/reboot
www-data ALL=(ALL) NOPASSWD:/sbin/ifdown
www-data ALL=(ALL) NOPASSWD:/sbin/ifup
www-data ALL=(ALL) NOPASSWD:/sbin/dhclient
www-data ALL=(ALL) NOPASSWD:/bin/cat /etc/wpa_supplicant/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/bin/cat /etc/wpa_supplicant/wpa_supplicant-wlan[0-9].conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/wpa_supplicant.tmp /etc/wpa_supplicant/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/wpa_supplicant.tmp /etc/wpa_supplicant/wpa_supplicant-wlan[0-9].conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/wpa_supplicant.tmp /etc/mudpi/tmp/wpa_supplicant.conf
www-data ALL=(ALL) NOPASSWD:/bin/rm /tmp/wpa_supplicant.tmp
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan[0-9] scan_results
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan[0-9] scan
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan[0-9] reconfigure
www-data ALL=(ALL) NOPASSWD:/sbin/wpa_cli -i wlan[0-9] select_network
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/hostapddata /etc/hostapd/hostapd.conf
www-data ALL=(ALL) NOPASSWD:/bin/systemctl start hostapd.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl stop hostapd.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl enable hostapd.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl disable hostapd.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl start dnsmasq.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl enable dnsmasq.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl disable dnsmasq.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl stop dnsmasq.service
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/dnsmasqdata /etc/dnsmasq.conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /tmp/dhcpddata /etc/dhcpcd.conf
www-data ALL=(ALL) NOPASSWD:/bin/cp /etc/mudpi/networking/dhcpcd.conf /etc/dhcpcd.conf
www-data ALL=(ALL) NOPASSWD:/sbin/ip link set wlan[0-9] down
www-data ALL=(ALL) NOPASSWD:/sbin/ip link set wlan[0-9] up
www-data ALL=(ALL) NOPASSWD:/sbin/ip -s a f label wlan[0-9]
www-data ALL=(ALL) NOPASSWD:/sbin/iw dev wlan0 scan ap-force
www-data ALL=(ALL) NOPASSWD:/usr/bin/auto_hotspot
www-data ALL=(ALL) NOPASSWD:/usr/bin/start_hotspot
www-data ALL=(ALL) NOPASSWD:/usr/bin/stop_hotspot
```

## Troubleshooting
Make sure to check folder permissions and that the proper commands have been added to your sudoers file for the web server user.

## Documentation
For full documentation visit [mudpi.app](https://mudpi.app/docs/setup-assistant)


## Versioning
Breaking.Major.Minor


## Authors
* Eric Davisson  - [Website](http://ericdavisson.com)
* [Twitter.com/theDavisson](https://twitter.com/theDavisson)

## Community
* Discord  - [Join](https://discord.gg/daWg2YH)
* [Twitter.com/MudpiApp](https://twitter.com/mudpiapp)

## Devices Tested On
* [Raspberry Pi 2 Model B+](https://www.raspberrypi.org/products/raspberry-pi-2-model-b/)
* [Raspberry Pi 3 Model B](https://www.raspberrypi.org/products/raspberry-pi-3-model-b/)
* [Raspberry Pi 3 Model B+](https://www.raspberrypi.org/products/raspberry-pi-3-model-b/)
* [Raspberry Pi Zero](https://www.raspberrypi.org/products/raspberry-pi-zero/)

Let me know if you are able to confirm tests on any other devices

## License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details


<img alt="MudPi Smart Garden" title="MudPi Smart Garden" src="https://mudpi.app/img/mudPI_LOGO_small_flat.png" width="50px">

