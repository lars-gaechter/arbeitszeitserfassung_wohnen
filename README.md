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

| Variable         | Beschreibung               | Werte          |
| ---------------- | -------------------------- | -------------- |
| HOSTNAME         | Domainname der Applikation | example.ch     |
| API_HOSTNAME     | Domainname der API         | example.ch     |
| LDAP_HOST        | IP vom LDAP Server         | 127.0.0.1      |
| LDAP_SEARCH_ROOT | LDAP Verbindung            | ou=\*,dc=\*,dc=\* |
| LDAP_PORT        | LDAP Port                  | 389 \| \*        |
| LDAP_RDN         | LDAP uid                   | uid            |
| HTTPS            | HTTP oder HTTPS            | false / true   |
| API_HTTPS        | HTTP oder HTTPS von API     | false / true   |
| DEFAULT_AREA_NAME | RIOAdmin | riomain |
| EMPTY_URL_CONTROLLER | example.ch/ | showHomepage |
| SESSION_LIFE_TIME | In Sekunden, 28800 sind 8 Stunden | 28800 \| \* |
| DEVELOPMENT_MODE | Twig Cache | false \| true |
| DEBUG | PHP Errors | false \| true |
| SESSION_LIFE_TIME | In Sekunden, 28800 sind 8 Stunden | \* |
| MAINTENANCE | Seite im Bau, standardmässig nicht | false \|true |
| LAUNCH_YEAR | Jahr in dem die Applikation in Produktion kommt | 2021\| \* |
| MONGODB | Verbindung zu MongoDB mit Port | mongodb://localhost:27017 |
| DB_NAME | Datenbank in MongoDB standardmässig "time" | time |

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

### 1.4.4 Älteres Setup

[XAMPP und MongoDB in Windows und LDAP in Linux](doc/readme/README.md)