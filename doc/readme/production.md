#
##
### Clear Twig cache
```sh
cd cache/twig
shopt -s extglob
rm cache/twig -rf -v !(".gitkeep")
shopt -u extglob
```