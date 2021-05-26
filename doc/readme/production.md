#
##
### Clear Twig cache
```sh
cd cache/twig
shopt -s extglob
rm -rf -v !(".gitkeep")
shopt -u extglob
```