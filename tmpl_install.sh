#!/bin/bash

# EZCAST EZrecorder 
# Copyright (C) 2016 Université libre de Bruxelles
#
# Written by Michel Jansens <mjansens@ulb.ac.be>
# 		    Arnaud Wijns <awijns@ulb.ac.be>
#
# This software is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 3 of the License, or (at your option) any later version.
#
# This software is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this software; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

#NAME:          tmpl_install.sh
#DESCRIPTION:   Generate EZrecorder's templates in all available languages
#AUTHOR:        Université libre de Bruxelles

G='\033[32m\033[1m'
R='\033[31m\033[1m'
N='\033[0m'


current_dir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd)";

source_folder="tmpl_sources";
dest_folder="tmpl";

echo "*******************************************************************";
echo "*                     EZRECORDER TEMPLATES                        *";
echo "*******************************************************************";
echo "This script will now compile the templates in all available languages";
echo " ";


echo -e "Source folder: ${G}$source_folder${N}";

mkdir $dest_folder;
echo -e "Destination folder: ${G}$dest_folder${N}";
# echo " ";

# echo "Compiling files ..."
php $current_dir/cli_template_generate.php $source_folder fr $dest_folder;
php $current_dir/cli_template_generate.php $source_folder en $dest_folder;
echo "Compilation complete. Don't forget to edit global_config.inc to your own needs";

