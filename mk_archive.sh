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
hg archive -r $1 -X '.hg*' -X 'mk_archive.sh' -X 'robots.txt' -X 'php/config.php' -X 'wiki' -X 'NMT' -p TorrentWatchX -t tar /var/www/torrentwatch-x/releases/TorrentWatchX-NMT.tar
mkdir -p TorrentWatchX/docs

cat <<EOF> TorrentWatchX/appinfo.json
{   
    appinfo_format="1",
    name="TorrentWatchX",
    version="NMT-$1",
    enabled="1",
    daemon_script="daemon.sh",
    webui_path="#PATH#"
}
EOF

for i in wiki/*.wiki ; do cp $i TorrentWatchX/docs/ ; done
cp -r NMT/* TorrentWatchX/
tar uf /var/www/torrentwatch-x/releases/TorrentWatchX-NMT.tar TorrentWatchX/
rm -rf TorrentWatchX/
cd releases
zip TorrentWatchX-NMT-$1.zip TorrentWatchX-NMT.tar
rm TorrentWatchX-NMT.tar

