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

if [ -e "/mnt/syb8634/server/php5-cgi" ];
then
PHPCGI="/share/Apps/lighttpd/bin/php-cgi";
fi

${PHPCGI} -q rss_dl.php -D >/dev/null 2>&1
