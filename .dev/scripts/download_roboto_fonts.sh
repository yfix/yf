mkdir -p /tmp/roboto
cd /tmp/roboto
wget "http://fonts.googleapis.com/css?family=Roboto:300italic,400italic,700italic,400,700,300&subset=cyrillic-ext,latin-ext" -O roboto.css
egrep -o "url\([^\)]+\)" roboto.css | awk -F '(' '{print $2}' | awk -F ')' '{print $1}' | xargs wget
mv -v dtpHsbgPEm2lVWciJZ0P-A.ttf Roboto-Light.ttf
mv -v W5F8_SL0XFawnjxHGsZjJA.ttf Roboto-Regular.ttf
mv -v bdHGHleUa-ndQCOrdpfxfw.ttf Roboto-Bold.ttf
mv -v iE8HhaRzdhPxC93dOdA056CWcynf_cDxXwCLxiixG1c.ttf Roboto-LightItalic.ttf
mv -v hcKoSgxdnKlbH5dlTwKbow.ttf Roboto-Italic.ttf
mv -v owYYXKukxFDFjr0ZO8NXh6CWcynf_cDxXwCLxiixG1c.ttf Roboto-BoldItalic.ttf
mkdir -p /usr/share/fonts/truetype/roboto/
mv -v Roboto-*.ttf /usr/share/fonts/truetype/roboto/