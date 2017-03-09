#!/bin/sh

cd $(dirname $0)
touch ./var/status
chown !USER *
chgrp !WEB_USER *
chown -R !WEB_USER ./var
#chmod +a "!USER allow list,add_file,search,add_subdirectory,delete_child,file_inherit,directory_inherit" ./var
#chmod +a "!WEB_USER allow list,add_file,search,add_subdirectory,delete_child,file_inherit,directory_inherit" ./var
chgrp -R !WEB_USER ./var
chmod ug=rwx ./var
#chmod +a "!USER allow read,write,execute,append" ./var/status
#chmod +a "!WEB_USER allow read,write,execute,append" ./var/status
chmod ug=rwx ./var/status
