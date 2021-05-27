# Produktion
## CSS und JS kompilieren
### CSS
Das CSS wir kompilieren und komprimiert.
```sh
npm run css:prod
```
### JS
Das JS wir kompilieren und komprimiert.
```sh
npm run js:prod
```
### Clear Twig cache
```sh
cd cache/twig
shopt -s extglob
rm -rf -v !(".gitkeep")
shopt -u extglob
```