# host windows
## C:\Windows\System32\drivers\etc
### bottom last line
### use # on single line to comment out
```
127.0.0.1           local.standortbestimmung.ch
```
# vhost xampp by default dir
## C:\xampp\apache\conf\extra
### example bottom last line
### use ## on every (multiple) line to comment out
### {username}, {projectname} and {public} have to be replaced how your setup is
```
<VirtualHost *:80>
    DocumentRoot "C:/Users/{username}/git/{projectname}/{public}"
    ServerName local.{projectname}.ch
    ServerAlias local.{projectname}.ch
    <Directory "C:/Users/{username}/git/{projectname}/{public}">
        Options FollowSymLinks Indexes
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## XAMPP example configuration php dir
```
<IfModule php_module>
    PHPINIDir "/xampp/php"
</IfModule>

<IfModule php_module>
    PHPINIDir "/php"
</IfModule>
```

## Install MongoDB Community Edition locally on Windows with Compass
### Install MongoDB as a Service and run service as Network Service user
### Windows + S search 'var' add path variable as system variables in the bottom box
### Path by default
```
?:\Program Files\MongoDB\Server\?.?\bin
```
### Windows + S search 'cmd' type in 'mongod'
### Windows + S search 'cmd' type in 'mongo'
### Git account
```
  git config --global user.email "f.lastname@rafisa.ch"
  git config --global user.name "Your Name"
```
### Install Apache Server and Set Up Virtual Hosts on Linux (Ubuntu)

### Symfony Twig Setting up or Fixing File Permissions
https://symfony.com/doc/current/setup/file_permissions.html

### Allow apache2 rewrite .htaccess
```
sudo a2enmod rewrite
sudo systemctl restart apache2
```
### Zend extension XDebug KCachegrind
### LDAP CLI
```shell
:~$ ldapsearch -x -LLL -h localhost -b dc=contoso-lab,dc=com '(uid=john)' cn gidNumber
dn: uid=john,ou=People,dc=contoso-lab,dc=com
cn: John Doe
gidNumber: 5000

:~$ ldapsearch -x -LLL -h localhost -b dc=contoso-lab,dc=com '(uid=john)' cn homeDirectory
dn: uid=john,ou=People,dc=contoso-lab,dc=com
cn: John Doe
homeDirectory: /home/john

:~$ ldapsearch -x -LLL -h localhost -b dc=contoso-lab,dc=com '(uid=john)' cn userPassword
dn: uid=john,ou=People,dc=contoso-lab,dc=com
cn: John Doe

:~$ ldappasswd -x -D cn=admin,dc=contoso-lab,dc=com -h localhost -W -S uid=john,ou=People,dc=contoso-lab,dc=com
New password: 
Re-enter new password: 
Enter LDAP Password: 
```
### compile scss to css
```shell
:/var/www/local.ldap-user-authentication.ch$ npm run css

> ldap-user-authentication@1.0.0 css /var/www/local.ldap-user-authentication.ch
> node-sass source/scss/_light-theme.scss -o public/css

Rendering Complete, saving .css file...
Wrote CSS to /var/www/local.ldap-user-authentication.ch/public/css/main.css



:/var/www/local.ldap-user-authentication.ch$ npm run css:watch

> ldap-user-authentication@1.0.0 css:watch /var/www/local.ldap-user-authentication.ch
> npm run css && node-sass source/scss/_light-theme.scss -wo public/css


> ldap-user-authentication@1.0.0 css /var/www/local.ldap-user-authentication.ch
> node-sass source/scss/_light-theme.scss -o public/css

Rendering Complete, saving .css file...
Wrote CSS to /var/www/local.ldap-user-authentication.ch/public/css/main.css
```
### Linux & VM & LDAP
Passwords if I would somehow forget them
VM start password "22Zahlen_22"
Linux sudo user "haskell" and password "Password1"
LDAP user "admin" password "Password1"