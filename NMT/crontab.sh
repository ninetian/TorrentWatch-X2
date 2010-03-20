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

${PHPCGI} -q rss_dl.php -D >/dev/null 2>&1
${PHPCGI} -q transmission_queue.php >/dev/null 2>&1
