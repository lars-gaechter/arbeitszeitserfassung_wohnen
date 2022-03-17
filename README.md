# 1. Admin-Handbücher

## 1.2 Generelle Installation

### 1.2.1 Vorausgesetzte Installationen

* Ein Operating System wie Linux oder Windows
* PHP 8
* npm 6
* OpenLDAP 2 auf Linux
* Composer 2

### 1.2.2 Installation von Git

Unter https://git-scm.com/ findet man die Git Installation für Windows. Auf Linux wie folgt kann Git installiert werden.

```sh
sudo apt install git-all
```

### 1.2.3 Klonen vom Git-Repository

Dafür muss man auf das Rafisa GitLab zugriff haben.

```sh
git clone https://gitlab.rafisa.org/l.gaechter/arbeitszeitserfassung_wohnen.git
```

### 1.2.4 Einrichten der Umgebungsvariablen

Kopiere die bereits vorhandene .env.example Datei und benenne die neue .env, diese wird von .gitignore ignoriert, da diese für Entwicklung Testzwecke dient oder für den Produktiven Einsatz gilt.

```sh
cp .env.example .env
```

Unten die Umgebungsvariablen in einer Tabelle im Detail 

| Variable         | Beschreibung               | Werte          | Typ          |
| ---------------- | -------------------------- | -------------- | -------------- |
| HOSTNAME         | Domainname der Applikation | example.ch     | string     |
| API_HOSTNAME     | Domainname der API         | example.ch     | string     |
| LDAP_HOST        | IP vom LDAP Server         | 127.0.0.1      | string     |
| LDAP_SEARCH_ROOT | LDAP Verbindung            | ou=\*,dc=\*,dc=\* | string     |
| LDAP_PORT        | LDAP Port                  | 389 \| \*        | string     |
| LDAP_RDN         | LDAP uid                   | uid            | int  |
| HTTPS            | HTTP oder HTTPS            | false / true   | bool  |
| API_HTTPS        | HTTP oder HTTPS von API     | false / true   | bool  |
| DEFAULT_AREA_NAME | RIOAdmin | riomain | string |
| EMPTY_URL_CONTROLLER | example.ch/ | showHomepage | string |
| SESSION_LIFE_TIME | Session Lebenszeit in Sekunden, 28800 sind 8 Stunden | 28800 \| \* | int   |
| DEVELOPMENT_MODE | Twig Cache | false \| true | bool  |
| DEBUG | PHP Errors | false \| true | bool  |
| MAINTENANCE | Seite im Bau, standardmässig nicht | false \|true | bool  |
| LAUNCH_YEAR | Jahr in dem die Applikation in Produktion kommt | 2021\| \* | int   |
| MONGODB | Verbindung zu MongoDB mit Port | mongodb://localhost:27017 | string |
| DB_NAME | Datenbank in MongoDB standardmässig "time" | time | string |

### 1.2.5 Installieren von Composer

Die Installation für Linux und Windows findet man entweder auf https://getcomposer.org/ oder es hat im obersten Verzeichnis eine install_composer.sh Datei, diese muss wie folgt unter Linux ausführbar gemacht werden.

```sh
chmod +x install_composer.sh
```

Danach führt man diese install_composer.sh Datei wir folgt aus.

```sh
./install_composer.sh
```

In der Datei sind schon alle für das Projekt benötigten Paketen hinterlegt so muss nur noch der nachfolgende Befehl ausgeführt werden.

```sh
php composer.phar i
```

### 1.2.5 npm installieren von Paketen

```sh
npm i
```

### 1.2.5 Apache und Berechtigung

```sh
sudo chown -R www-data arbeitszeitserfassung_wohnen
```

## 1.3 Aufsetzen für die Entwicklung

### 1.3.1 Neustes Setup

[PHPStorm, Git, Composer und PHP](doc/readme/development.md)

## 1.4 Aufsetzen für die Produktion

### 1.4.1 Überarbeitung Setup

[Noch in Überarbeitung](doc/readme/production.md)

### 1.4.2 Neustes Setup

[Virtuelle Maschine Linux mit LDAP, MongoDB, PHP und Apache, Client Browser und MongoDB Compass](doc/readme/time_recording_server.md)

### 1.4.3 ???
#### TODO:
- [ ] check for waht 1.4.3 was else decrement to 1.4.2 on
### 1.4.4 Älteres Setup

[XAMPP und MongoDB in Windows und LDAP in Linux](doc/readme/README.md)

### Somehow redirect doesn't work (404) on the ubuntu server, run follow comands, found EMPTY_URL_CONTROLLER in .env but doesn't find login in Main Controller

```shell
root@wohnen:/var/www/wohnen.stiftung.ifa# a2enmod rewrite
Enabling module rewrite.
To activate the new configuration, you need to run:
  systemctl restart apache2
root@wohnen:/var/www/wohnen.stiftung.ifa# service apache2 restart
```

### 1.4.5 htuser und htgroup Setup

```shell
a2enmod authz_groupfile
systemctl restart apache2
```

### 1.4.6 SFTP Setup

```shell
sudo apt update
sudo apt install vsftpd
sudo systemctl start vsftpd
sudo systemctl enable vsftpd
```
Backup copy
```shell
sudo cp /etc/vsftpd.conf  /etc/vsftpd.conf_default
```
Create FTP User
```shell
sudo useradd -m testuser

sudo passwd testuser
```
Configure Firewall to Allow SFTP Traffic
```shell
sudo ufw allow 22/tcp
```
Connect to Ubuntu FTP Server
```shell
sudo ftp ubuntu-ftp
```
or
```shell
ftp
```
Change Default Directory
```shell
sudo usermod -d /var/www ftp
```
Enable authenticated users upload (write)
```shell
sudo nano /etc/vsftpd.conf
```
```txt
write_enable=YES
```
```shell
sudo systemctl restart vsftpd.service
```
Open SSH

```shell
sudo apt install openssh-server
sudo apt install ssh
sudo chown root:root /var
sudo chmod 755 /var
sudo chown testuser:testuser /var/www/
```
Open the SSH server configuration
```shell
sudo nano /etc/ssh/sshd_config
```
End of file append the following configuration
```txt
Port <your_port_number>
Match User sftp_user
ForceCommand internal-sftp
PasswordAuthentication yes
ChrootDirectory /var/sftp/myfolder
PermitTunnel no
AllowAgentForwarding no
AllowTcpForwarding no
X11Forwarding no
```
```shell
sudo systemctl restart sshd
```