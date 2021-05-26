# Installationsanleitung - MongoDB als Datebase-Server und Apache2 mit PHP 8 als Webserver
### Version: 0.1, Status: Erster Entwurf, Datum: 26.03.2021, Author: Lars Gächter, URL: [https://gitlab.rafisa.org/l.gaechter/arbeitszeitserfassung_wohnen/-/blob/master/doc/readme/time_recording_server.md](doc/readme/time_recording_server.md)
## 1. Kurzfassung
In dieser Installationsanleitung wird beschrieben, wie man auf einem Ubuntu-Server (Ubuntu 20.04 (Focal)) den MongoDB-Server und Apache2-Server und PHP 8 installiert.
## 2. Installation
### 2.1 Installation und Einrichten MongoDB
```bash
echo "10.0.0.11 mongo.rafisa.org" | sudo tee -a /etc/hosts\\
sudo hostnamectl set-hostname mongo.rafisa.org
sudo apt update
sudo apt upgrade
```
Danach wird MongoDB installiert:

Mongo?
mongodb ist keine beleidigung sonder dessen namen stammt der name mongo von humongous ein riese oder gigant ab.
source: https://morpheusdata.com/cloud-blog/how-did-mongodb-get-its-name/
text: The company behind MongoDB, MongoDB was originally developed by MongoDB, Inc., which at the time (2007) was named 10gen. While originally simply dubbed “p”, the database was officially named MongoDB, with “Mongo” being short for the word humongous.

Importieren Sie den vom Paketverwaltungssystem verwendeten öffentlichen Schlüssel.
```bash
wget -qO - https://www.mongodb.org/static/pgp/server-4.4.asc | sudo apt-key add -
```
Die Operation sollte mit einem OK antworten.

Wenn Sie jedoch eine Fehlermeldung erhalten, die besagt, dass gnupg nicht installiert ist, können Sie:
```bash
sudo apt-get install gnupg
wget -qO - https://www.mongodb.org/static/pgp/server-4.4.asc | sudo apt-key add -
```

Erstellen Sie eine Listendatei für MongoDB.
```bash
echo "deb [ arch=amd64,arm64 ] https://repo.mongodb.org/apt/ubuntu focal/mongodb-org/4.4 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-4.4.list
```
Die Operation sollte mit einem deb [ arch=amd64,arm64 ] https://repo.mongodb.org/apt/ubuntu focal/mongodb-org/4.4 multiverse antworten.

Laden Sie die lokale Paketdatenbank neu.
```bash
sudo apt-get update
```
Installieren Sie die MongoDB-Pakete.
```bash
sudo apt-get install -y mongodb-org
```
### Optional
Optional. Obwohl Sie jede verfügbare Version von MongoDB angeben können, aktualisiert apt-get die Pakete, wenn eine neuere Version verfügbar ist. Um unbeabsichtigte Upgrades zu verhindern, können Sie das Paket an der aktuell installierten Version festmachen:
Ich habe diesen Schritt übersprungen
```bash
echo "mongodb-org hold" | sudo dpkg --set-selections
echo "mongodb-org-server hold" | sudo dpkg --set-selections
echo "mongodb-org-shell hold" | sudo dpkg --set-selections
echo "mongodb-org-mongos hold" | sudo dpkg --set-selections
echo "mongodb-org-tools hold" | sudo dpkg --set-selections
```
Starten Sie MongoDB.
```bash
sudo systemctl start mongod
```
Wenn Sie beim Starten von mongod einen Fehler ähnlich dem folgenden erhalten:
```bash
Failed to start mongod.service: Unit mongod.service not found.
```
Führen Sie zunächst den folgenden Befehl aus:
```bash
sudo systemctl daemon-reload
```
Überprüfen Sie, ob MongoDB erfolgreich gestartet wurde.
```bash
sudo systemctl status mongod
```
Sie können optional sicherstellen, dass MongoDB nach einem Systemneustart gestartet wird, indem Sie den folgenden Befehl eingeben:
```bash
sudo systemctl enable mongod
```
Stoppen Sie MongoDB.
```bash
sudo systemctl stop mongod
```
Starten Sie MongoDB neu.
```bash
sudo systemctl restart mongod
```
Beginnen Sie mit der Verwendung von MongoDB.
```bash
mongo
```
Remote zugänglich machen
Auf Server
```bash
sudo nano /etc/mongod.conf
bindIp: 127.0.0.1,10.0.0.11
```
Auf Client
```bash
mongo --host 10.0.0.11
```
Oder auf Client MongoDB Compass als GUI installieren und verbinden
## Apache und PHP 8.0 installieren
### Hinzufügen des Ondřej Surý PPA-Repositorys
Lassen Sie uns Ihre Ubuntu-Systempakete aktualisieren und einige Abhängigkeiten wie gezeigt installieren.
```bash
sudo apt install ca-certificates apt-transport-https software-properties-common
```
Fügen Sie das Ondrej-PPA hinzu.
```bash
sudo add-apt-repository ppa:ondrej/php
```
Wenn Sie dazu aufgefordert werden, drücken Sie ENTER, um mit dem Hinzufügen des Repositorys fortzufahren.
### PHP 8.0 mit Apache auf Ubuntu installieren
Aktualisieren Sie die System-Repositorys, um die Verwendung des PPA zu starten.
```bash
sudo apt update
```
Wenn Sie den Apache-Webserver verwenden, installieren Sie PHP 8.0 mit dem Apache-Modul wie gezeigt.
```bash
sudo apt install php8.0 libapache2-mod-php8.0 
```
Starten Sie den Apache-Webserver neu, um das Modul zu aktivieren.
```bash
sudo systemctl restart apache2
```
Wir benötigen PHP-FPM, führen Sie den folgenden Befehl aus, um die erforderlichen Pakete zu installieren:
```bash
sudo apt install php8.0-fpm libapache2-mod-fcgid
```
Da PHP-FPM nicht standardmäßig aktiviert ist, aktivieren Sie es, indem Sie die folgenden Befehle aufrufen:
```bash
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.0-fpm
```
Starten Sie anschließend den Apache-Webserver neu, damit die Änderungen in Kraft treten können.
```bash
sudo systemctl restart apache2
```
### PHP 8-Erweiterungen in Ubuntu installieren
```bash
sudo apt-get install php-pear
###sudo pecl install mongodb
sudo apt-get install php8.0-mongodb
sudo apt-get install php8.0-ldap
sudo nano /etc/php/8.0/apache2/php.ini
extension=php_ldap.so
extension=mongodb.so
```
### Überprüfen der PHP 8-Installation unter Ubuntu
```bash
php -v
sudo nano /var/www/html/info.php
```
```php
<?php

phpinfo();

?>
```
```text
http://10.0.0.11/info.php
```
### vHost anlegen
Client und Server seitig
```bash
sudo nano /etc/hosts
10.0.0.11 mongo.rafisa.org
127.0.0.1 mongo.rafisa.org
sudo mkdir -p /var/www/mongo.rafisa.org/public
sudo chown -R $USER:$USER /var/www/mongo.rafisa.org/public
###sudo chmod -R 755 /var/www
```
Lege jetzt Sie eine eigene Conf mit folgendem Inhalt an:
```text
sudo nano /etc/apache2/sites-available/mongo.rafisa.org.conf


<VirtualHost mongo.rafisa.org:80>
DocumentRoot /var/www/mongo.rafisa.org/public
ServerName mongo.rafisa.org
ServerAlias mongo.rafisa.org
<Directory "/var/www/mongo.rafisa.org/public">
Options FollowSymLinks Indexes
AllowOverride All
Require all granted
</Directory>
</VirtualHost>
```
Wenn Sie soweit sind, aktivieren Sie die einzelnen vHosts:
```bash
sudo a2ensite mongo.rafisa.org && service apache2 restart
sudo systemctl reload apache2
```
Git
```bash
sudo apt install git-all
cd /var/www
sudo git clone https://gitlab.rafisa.org/l.gaechter/ldap-user-authentication.git
sudo rm -Rf mongo.rafisa.org
sudo mv ldap-user-authentication mongo.rafisa.org
### git pull and save credential
```text
sudo git config --global credential.helper store
sudo git pull
```
###sudo chown -R $USER:$USER /var/www/mongo.rafisa.org/public
```
composer.phar
```bash
sudo php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
sudo php composer-setup.php
sudo php -r "unlink('composer-setup.php');"
```
Composer
```bash
sudo apt install composer
composer -version
cd mongo.rafisa.org
sudo composer i
```
ENV
```bash
sudo cp .env.example .env
```
Node.js and npm
```bash
sudo apt install nodejs
nodejs -v
sudo apt install npm
sudo npm i
sudo npm run css
```
Permissions

chmod in kürze
erste ist sticky bit
zweite ist owner of dir
dritte ist group
vierte ist all others

Da apache2 immer mit permission denied kam haben ich vom root dir an allen dir berechtigungen so erteilt und das rekursiv wie folgt.

-R macht es rekursiv das ab einem angegebenen Verzeichnis alle darunterliegenden kinder oder sub directories diesselbe berechtigung erteilt wird
/ ist das oberste verzeichnis das es in linux gibt und greift somit auf alle Verzeichnisse welche es überhaupt auf dem server gibt.

RootDirectory
Jeder apache request geht an dieses Verzeichnis und an dieses PHP File
public/index.php
```bash
sudo chown -R www-data public

```

Der cache folder ist nicht von anfang an hier und kann manuell erstellt und Berechtigungen gesetzt werden
```bash
sudo mkdir cache
sudo chown -R www-data cache

```
Rafisa autoloader von Stefan Kuhn, dieser generiert für eine Verzeichnistiefe Unique Class Names die includes von Klassen
RIOAutoloader.php
```bash
sudo chown -R www-data source
 
```

Git
Nach jedem commit und push auf den Git-Remoteserver auf GitLab,
muss auf dem Webserver git pull ausgeführt werden um die daten zu updaten,
dabei wichtig ist der Twig Cache bei Template Änderungen zu berücksichtigen.

LDAP mit Kommandokonsole und nicht mit LAM (LDAP Account Manager) oder phpLDAPadmin

```bash
:~$ sudo apt update && sudo apt upgrade -y
:~$ lsb_release -a
:~$ sudo apt install slapd ldap-utils
```
Administrator password: Password1
<Ok>
Confirm password: Password1
<Ok>

Jetzt kommen wir zur Package Konfiguration
```bash
:~$ sudo dpkg-reconfigure slapd
```
<No>
DNS domain name: rafisa.org
<Ok>
Organization name: rafisa
<Ok>
Administrator password: Password1
<Ok>
Confirm password: Password1
<Ok>
Do you want the datsbase to be removed when slapd is purged?
<Yes>
Move old database?
<Yes>

Anpassen der ldap.conf Datei, darin müssen die Zeilen wie folgt einkommentiert und geändert werden.
```bash
:~$ sudo nano /etc/ldap/ldap.conf
Base  dc=rafisa,dc=org
URI   ldap://ldap.rafisa.org
#ldap://ldap-master.example.com:666
```

```bash
:~$ sudo ldapsearch -Q -LLL -Y EXTERNAL -H ldapi:/// -b cn=config dn
```

```bash
:~$ sudo ldapsearch -Q -LLL -Y EXTERNAL -H ldapi:/// -b dc=rafisa,dc=org dn
dn: dc=rafisa,dc=org

dn: cn=admin,dc=rafisa,dc=org
```

Inhalt (content) als ldif Datei erstellen für den user und group Import

```bash
:~$ sudo nano /etc/ldap/add_content.ldif

dn: ou=People,dc=rafisa,dc=org
objectClass: organizationalUnit
ou: People


dn: ou=Groups,dc=rafisa,dc=org
objectClass: organizationalUnit
ou: Groups


dn: cn=Administration,ou=Groups,dc=rafisa,dc=org
objectClass: posixGroup
cn: Administration
gidNumber: 5000


dn: uid=l.gaechter,ou=People,dc=rafisa,dc=org
objectClass: inetOrgPerson
objectClass: posixAccount
objectClass: shadowAccount
uid: Lars
sn: Gaechter
givenName: Lars
cn: Lars Gaechter
displayName: Lars Gaechter
uidNumber: 10000
gidNumber: 5000
userPassword: {CRYPT}x
gecos: Lars Gaechter
loginShell: /bin/bash
homeDirectory: /home/l.gaechter


dn: uid=m.schmid,ou=People,dc=rafisa,dc=org
objectClass: inetOrgPerson
objectClass: posixAccount
objectClass: shadowAccount
uid: Monika
sn: Schmid
givenName: Monika
cn: Monika Schmid
displayName: Monika Schmid
uidNumber: 11000
gidNumber: 5000
userPassword: {CRYPT}x
gecos: Monika Schmid
loginShell: /bin/bash
homeDirectory: /home/m.schmid
```

Überprüfen Sie die Änderungen.

```bash
sudo ldapadd -x -D cn=admin,dc=rafisa,dc=org -h localhost -W -f /etc/ldap/add_content.ldif
adding new entry "ou=People,dc=rafisa,dc=org"
adding new entry "ou=Groups,dc=rafisa,dc=org"
adding new entry "cn=Administration,ou=Groups,dc=rafisa,dc=org"
adding new entry "uid=l.gaechter,ou=People,dc=rafisa,dc=org"
adding new entry "uid=m.schmid,ou=People,dc=rafisa,dc=org"
```

```bash
:~$ ldapsearch -x -LLL -h localhost -b dc=rafisa,dc=org '(uid=l.gaechter)' cn gidNumber
dn: uid=l.gaechter,ou=People,dc=rafisa,dc=org
cn: Lars Gaechter
gidNumber: 5000

:~$ ldapsearch -x -LLL -h localhost -b dc=rafisa,dc=org '(uid=m.schmid)' cn gidNumber
dn: uid=m.schmid,ou=People,dc=rafisa,dc=org
cn: Monika Schmid
gidNumber: 5000

:~$ ldapsearch -x -LLL -h localhost -b dc=rafisa,dc=org '(uid=l.gaechter)' cn homeDirectory
dn: uid=l.gaechter,ou=People,dc=rafisa,dc=org
cn: Lars Gaechter
homeDirectory: /home/l.gaechter

:~$ ldapsearch -x -LLL -h localhost -b dc=rafisa,dc=org '(uid=m.schmid)' cn homeDirectory
dn: uid=m.schmid,ou=People,dc=rafisa,dc=org
cn: Monika Schmid
homeDirectory: /home/m.schmid

:~$ ldapsearch -x -LLL -h localhost -b dc=rafisa,dc=org '(uid=l.gaechter)' cn userPassword
dn: uid=l.gaechter,ou=People,dc=rafisa,dc=org
cn: Lars Gaechter

:~$ ldapsearch -x -LLL -h localhost -b dc=rafisa,dc=org '(uid=m.schmid)' cn userPassword
dn: uid=m.schmid,ou=People,dc=rafisa,dc=org
cn: Monika Schmid

:~$ ldappasswd -x -D cn=admin,dc=rafisa,dc=org -h localhost -W -S uid=l.gaechter,ou=People,dc=rafisa,dc=org
New password: 
Re-enter new password: 
Enter LDAP Password: 

:~$ ldappasswd -x -D cn=admin,dc=rafisa,dc=org -h localhost -W -S uid=m.schmid,ou=People,dc=rafisa,dc=org
New password: 
Re-enter new password: 
Enter LDAP Password: 
```

New password: (user password)
Re-enter new password: (user password)
Enter LDAP Password: Password1 (admin password)

Client MongoDB Compass install on ubuntu 64 Bit
```bash
wget https://downloads.mongodb.com/compass/mongodb-compass_x.x.x_amd64.deb
sudo dpkg -i mongodb-compass_1.26.1_amd64.deb
mongodb-compass
```
Connection
```text
mongodb://mongo.rafisa.org:27017/time
```