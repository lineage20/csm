#!/bin/sh

LOGFILE=/www/hx9999.com/inf.hx9999.com/log/$(basename $0 .sh).log
PATTERN="synchronous.gwpm.php"
RECOVERY="php -d error_log=/www/hx9999.com/inf.hx9999.com/log/php_errors.log -c /srv/php/etc/php-cli.ini /www/hx9999.com/inf.hx9999.com/daemon/synchronous.gwpm.php restart"

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
