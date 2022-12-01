
## Contruire le contener
```bash
[Windows] docker build -t nano-php:1.0 .
[Linux] sudo docker build -t nano-php:1.0 .
 ```

## Lancer le conteneur
```bash 
[Windows] docker run -it --rm --name "nano-php_1.0" -p 80:80 -p 443:443 -p 5432:5432 -v "%cd%/www":/var/www -v "%cd%/persistant_data":/var/lib/postgresql/data nano-php:1.0
[Linux] sudo docker run -it --rm --name "nano-php_1.0" -p 80:80 -p 443:443 -p 5432:5432 -v "`pwd`/www":/var/www -v "`pwd`/persistant_data":/var/lib/postgresql/data nano-php:1.0
```

# Créer un host local
```bash
[Windows]
	Dans le fichier C:\Windows\System32\drivers\etc\hosts
	127.0.0.1 localhost
[Linux]
	Dans le fichier /etc/hosts
```

# Pour reinitialiser les données de la base de données, videz le dossier "persistant_data"

# Pour acceder a la base de données avec pgadmin4, acceder à 
* localhost	:5432
* user		: postgres
* pwd		: postgres

_N.B: La mise en docker n'a pas pour vocation a aller en production, elle ne sert que d'environnement de demontration au framework PHP Nano_