mediacore-ffmpeg-bot
====================

mediacore-ffmpeg-bot is a simple php daemon. It watches mediacore media tables and loops over uploaded media.
When an unencoded one is found it invokes ffmpeg to encode it in an h264 one. then it creates the needed media_files
in mediacore db and set the original media as encoded.

Install:
* save bot file in /opt ( if you want to change this remember to change hardcoded paths )
* save .conf file in /etc/init/ ( this also has an hardcoded path so remember to review it )
* start ffmpeg bot service with "start ffmpeg-bot"


Features:
* fill media_file metadata as size and bitrate
* send email via php mail to media author when encoding is done
* upstart script for ubuntu/debian distros

Many todos:
* multiple encoding profiles
* script parametrization ( remove all hardcoded paths )
* ...
