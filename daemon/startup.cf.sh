#!/bin/sh

LOGFILE=/www/cf88.com/inf.cf88.com/log/$(basename $0 .sh).log
PATTERN="synchronous.cf.php"
RECOVERY="php -d error_log=/www/cf88.com/inf.cf88.com/log/php_errors.log -c /srv/php/etc/php-cli.ini /www/cf88.com/inf.cf88.com/daemon/synchronous.cf.php restart"

while true
do
    TIMEPOINT=$(date -d "today" +"%Y-%m-%d_%H:%M:%S")
    PROC=$(pgrep -o -f ${PATTERN})
    #echo ${PROC}
    if [ -z "${PROC}" ]; then
		${RECOVERY} >> $LOGFILE
		echo "[${TIMEPOINT}] ${PATTERN} ${RECOVERY}" >> $LOGFILE
    #else
        #echo "[${TIMEPOINT}] ${PATTERN} ${PROC}" >> $LOGFILE
    fi
sleep 5
done &
