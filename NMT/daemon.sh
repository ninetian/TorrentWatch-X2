#!/bin/sh

install()
{
		if [ ! -f /share/Apps/Torrentwatchx/web/php/config.php ] ; then 
			cp /share/Apps/Torrentwatchx/web/php/config.php.dist /share/Apps/Torrentwatchx/web/php/config.php
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
		if ! ps | grep twx-poller.sh | grep -v grep ; then
			/share/Apps/Torrentwatchx/twx-poller.sh &
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
