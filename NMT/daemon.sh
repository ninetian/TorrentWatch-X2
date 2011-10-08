#!/bin/sh

install()
{
		if [ ! -f /share/Apps/Torrentwatchx/php/config.php ] ; then 
			cp /share/Apps/Torrentwatchx/php/config.php.dist /share/Apps/Torrentwatchx/php/config.php
		fi
		if [ ! -f /share/Apps/Torrentwatchx/etc/torrentwatch.config ] ; then 
			cp /share/Apps/Torrentwatchx/etc/torrentwatch.config.dist /share/Apps/Torrentwatchx/etc/torrentwatch.config
		fi
                chmod 777 /share/Apps/Torrentwatchx/tmp
                chmod 777 /share/Apps/Torrentwatchx/etc
                chmod 666 /share/Apps/Torrentwatchx/etc/torrentwatch.config
                chmod 777 /share/Apps/Torrentwatchx/rss_cache
                chmod 777 /share/Apps/Torrentwatchx/tvdb_cache
		chmod 777 /share/Apps/Torrentwatchx/twx-poller.sh
}

start()
{
                #crontab -l >/tmp/tw.cron.tmp
                #if ! grep -q "Torrentwatchx" "/tmp/tw.cron.tmp" ; then
                #    echo "*/15 * * * * /share/Apps/Torrentwatchx/crontab.sh >/dev/null 2>&1" >> /tmp/tw.cron.tmp
                #    crontab /tmp/tw.cron.tmp
                #fi
		if ! ps ax | grep twx-poller.sh ; then
			nohup /share/Apps/Torrentwatchx/twx-poller.sh &
		fi
}

 #########################################################################
#
# Main function begins here
#
#########################################################################


case "$1" in
	start)
		start;;
	install)
		install;;
esac
