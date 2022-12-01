#!/bin/bash
chgrp www-data 	-R /var/www
chmod g+s  		-R /var/www

# Force init cluster
chown postgres -R /var/lib/postgresql/data
chmod 0700  	  /var/lib/postgresql/data
chmod g+s  		  /var/lib/postgresql/data

if [ -d "/var/lib/postgresql/data/base" ]
then	
	echo ""
	echo "### Postgres cluster already exists"	
	service postgresql restart
else
	rm -rf /var/lib/postgresql/data;
	echo ""
	echo "### Create postgres cluster"	
	su -c "/usr/lib/postgresql/13/bin/initdb  --pgdata=/var/lib/postgresql/data --encoding=UTF-8 --locale=C --username=postgres" -s /bin/sh postgres
	service postgresql restart
fi

# Check for database update
if [ -f /var/www/data/create.sql ] 
then
	if [ ! -f /var/lib/postgresql/data/.deploy ] 
	then
		echo ""
		echo "### Initialize postgres data"
		psql -U "postgres" -h "localhost" -p 5433 -f /var/www/data/create.sql
	else
		if [ $(stat  /var/www/data/create.sql --format="%X") -gt $(stat /var/lib/postgresql/data/.deploy --format="%X") ] 
		then
			echo ""
			echo "### Update data $(stat /var/lib/postgresql/data/.deploy --format="%X") to $(stat /var/www/data/create.sql --format="%X")"
			psql -U "postgres" -h "localhost" -p 5433 -f /var/www/data/create.sql
		else
			echo ""
			echo "### Database up-to-date"
		fi
	fi
fi
touch /var/lib/postgresql/data/.deploy

# Apache2 - Force active vhosts
(for d in `ls /etc/apache2/sites-available`; do a2ensite $d; done); 
service apache2 restart

# Start bridger
echo "Restart Postgresql Bridger"
node /etc/postgresql/13/bridger.js