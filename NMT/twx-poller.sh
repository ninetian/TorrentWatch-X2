#!/bin/sh
cd $(echo $0 | sed 's,[^/]*$,,')

if [ -e "/usr/bin/php-cgi" ];
then
PHPCGI="/usr/bin/php-cgi"
fi
 
if [ -e "/nmt/apps/server/php5-cgi" ];
then
PHPCGI="/nmt/apps/server/php5-cgi"
fi
 
if [ -e "/mnt/syb8634/server/php5-cgi" ];
then
PHPCGI="/mnt/syb8634/server/php5-cgi"
fi

if [ -e "/share/Apps/local/bin/php-fcgi" ];
then
PHPCGI="/share/Apps/local/bin/php-fcgi";
fi

if [ -e "/share/Apps/lighttpd/bin/php-cgi" ];
then
PHPCGI="/share/Apps/lighttpd/bin/php-cgi";
fi

while true; do
	${PHPCGI} -q /share/Apps/Torrentwatchx/rss_dl.php -D >/dev/null 2>&1
	sleep 900
done
