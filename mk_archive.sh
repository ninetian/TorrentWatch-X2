#!/bin/sh
if [ -z $1 ] ; then
  echo "Usage: $0 <release nr>"
  exit
fi
hg archive -r $1 -X '.hg*' -X 'mk_archive.sh' -X 'robots.txt' -X 'php/config.php' -X 'wiki' -X 'NMT' -t tar /var/www/torrentwatch-x/releases/torrentwatch-$1.tar
mkdir -p torrentwatch-$1/docs
for i in wiki/*.wiki ; do cp $i torrentwatch-$1/docs/ ; done
tar uf /var/www/torrentwatch-x/releases/torrentwatch-$1.tar torrentwatch-$1/docs/
rm -rf torrentwatch-$1/
gzip /var/www/torrentwatch-x/releases/torrentwatch-$1.tar

#NMT Package

echo "Building NMT-Package..."
hg archive -r $1 -X '.hg*' -X 'mk_archive.sh' -X 'robots.txt' -X 'php/config.php' -X 'wiki' -X 'NMT' -p . -t tar /var/www/torrentwatch-x/releases/torrentwatchx.tar
mkdir -p torrentwatchx/docs

cat <<EOF> torrentwatchx/appinfo.json
{   
    appinfo_format="1",
    name="torrentwatchx",
    version="NMT-$1",
    enabled="1",
    daemon_script="daemon.sh",
    webui_path="#PATH#"
}
EOF

for i in wiki/*.wiki ; do cp $i torrentwatchx/docs/ ; done
cp -r NMT/* torrentwatchx/
cd torrentwatchx/
tar uf /var/www/torrentwatch-x/releases/torrentwatchx.tar .
cd ..
rm -rf torrentwatchx/
cd releases
zip TorrentWatchX-NMT-$1.zip torrentwatchx.tar
rm torrentwatchx.tar

