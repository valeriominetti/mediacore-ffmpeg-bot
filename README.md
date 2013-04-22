mediacore-ffmpeg-bot
====================

mediacore-ffmpeg-bot is a simple php daemon. It watches mediacore media tables and loops over uploaded media.
When an unencoded one is found it invokes ffmpeg to encode it in an h264 one. then it creates the needed media_files
in mediacore db and set the original media as encoded.

Many todos:
* multiple encoding profiles
* script parametrization ( remove all hardcoded paths )
* send email to author when encoding is done
* ...
