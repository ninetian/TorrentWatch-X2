#!/bin/sh
if [ -z $1 ] ; then
  echo "Usage: $0 <release nr>"
  exit
fi
hg archive -r $1 -X '.hg*' -X 'mk_archive.sh' -X 'robots.txt' -X 'php/config.php' -X 'wiki' -X 'NMT' -t tar /var/www/torrentwatch-x/releases/TorrentWatchX-$1.tar
mkdir -p TorrentWatchX-$1/docs
for i in wiki/*.wiki ; do cp $i TorrentWatchX-$1/docs/ ; done
tar uf /var/www/torrentwatch-x/releases/TorrentWatchX-$1.tar TorrentWatchX-$1/docs/
rm -rf TorrentWAtchX-$1/
gzip /var/www/torrentwatch-x/releases/TorrentWatchX-$1.tar

#NMT Package

echo "Building NMT-Package..."
hg archive -r $1 -X '.hg*' -X 'mk_archive.sh' -X 'robots.txt' -X 'php/config.php' -X 'wiki' -X 'NMT' -p . -t tar /var/www/torrentwatch-x/releases/Torrentwatchx.tar
mkdir -p Torrentwatchx/docs

cat <<EOF> Torrentwatchx/appinfo.json
{   
    appinfo_format="1",
    name="Torrentwatchx",
    version="NMT-$1",
    enabled="1",
    daemon_script="daemon.sh",
    webui_path="#PATH#"
    setup_script="daemon.sh"
}
EOF

for i in wiki/*.wiki ; do cp $i Torrentwatchx/docs/ ; done
cp -pr NMT/* Torrentwatchx/
cd Torrentwatchx/
sudo tar upf /var/www/torrentwatch-x/releases/Torrentwatchx.tar .
cd ..
rm -rf TorrentWatchX-$1/
cd releases
zip TorrentWatchX-NMT-$1.zip Torrentwatchx.tar
rm Torrentwatchx.tar

