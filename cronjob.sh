#!/bin/bash
# At minute 10 past every 24th hour, next at 20??-??-?? 00:10:00
#10 */24 * * *
#* * * * *  /usr/bin/php /var/www/mongo.rafisa.org/cron.php &> /dev/nul
/usr/bin/php /var/www/mongo.rafisa.org/cron.php &> /dev/nul