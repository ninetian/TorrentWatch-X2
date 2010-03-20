#!/bin/sh
install()
{
		cp /share/Apps/TorrentWatchX/php/config.php.dist /share/Apps/TorrentWatchX/php/config.php
                chmod 777 /share/Apps/TorrentWatchX/etc
                chmod 777 /share/Apps/TorrentWatchX/rss_cache
}

start()
{
                crontab -l >/tmp/tw.cron.tmp
                if ! grep -q "TorrentWatchX" "/tmp/tw.cron.tmp" ; then
                    echo "*/15 * * * * /share/Apps/TorrentWatchX/crontab.sh >/dev/null 2>&1" >> /tmp/tw.cron.tmp
                    crontab /tmp/tw.cron.tmp
                fi
}

 #########################################################################
#
# Main function begins here
#
#########################################################################


case "$1" in
	install)
                install;;

	uninstall)
		;;
	start)
		start;;

esac
