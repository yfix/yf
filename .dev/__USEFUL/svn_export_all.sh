svn export --revision HEAD --force --quiet --username ph --password pr0st0pa2s http://dev.demiurgy.com/repos/sexy_net/ /var/www/html/
# Clenup source folder contents
rm -fr /var/www/__copy_this_after_export/*
# Dirs we need to copy "__copy_this_after_export" folder contents
cp -r /var/www/html/__copy_this_after_export/* /var/www/__copy_this_after_export
# Force remove that folder inside htdocs
rm -dfr /var/www/html/__copy_this_after_export
# remove .htaccess in root of our dir
rm -f /var/www/__copy_this_after_export/.htaccess
# Now copy all files back to htdocs
cp -r /var/www/__copy_this_after_export/* /var/www/html
# Cleanup cache dirs
rm -fr /var/www/html/core_cache/*.php
rm -fr /var/www/html/site1/pages_cache/*
rm -fr /var/www/html/sites/escortpersonalads.com/pages_cache/*
rm -fr /var/www/html/sites/escortpictures.com/pages_cache/*
rm -fr /var/www/html/sites/escortproviders.com/pages_cache/*
rm -fr /var/www/html/sites/escorttown.com/pages_cache/*
rm -fr /var/www/html/sites/sexyescortads.com/pages_cache/*
rm -fr /var/www/html/sites/sexyescortpersonals.com/pages_cache/*
rm -fr /var/www/html/sites/uk-escort.net/pages_cache/*
rm -fr /var/www/html/sites/us-escort.net/pages_cache/*
