#!/bin/bash

ffmpeg -loop 1 -i custom/fond_stream.png -y -c:v libx264 -t 15 -pix_fmt yuv420p -vf scale=1280:720 custom/high.mp4

ffmpeg -i custom/high.mp4 -y -vf drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='LE STREAMING LIVE EST': fontcolor=white: fontsize=48: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-t
ext_w)/2: y=((h-text_h)/2)-(text_h/2)-5",drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='EN COURS DE PREPARATION': fontcolor=white: fontsize=48: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-text_w)/2: y=((h-text_h)/2)+(text_h/2)+5"  -vcodec: libx264 custom/high_init.ts

ffmpeg -i custom/high.mp4 -y -vf drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='LE STREAMING LIVE EST': fontcolor=white: fontsize=48: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-t
ext_w)/2: y=((h-text_h)/2)-(text_h/2)-5",drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='EN PAUSE': fontcolor=white: fontsize=48: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-tex
t_w)/2: y=((h-text_h)/2)+(text_h/2)+5"  -vcodec: libx264 custom/high_pause.ts

ffmpeg -i custom/high.mp4 -y -vf drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='LE STREAMING LIVE EST': fontcolor=white: fontsize=48: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-t
ext_w)/2: y=((h-text_h)/2)-(text_h/2)-5",drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='TERMINE': fontcolor=white: fontsize=48: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-text
_w)/2: y=((h-text_h)/2)+(text_h/2)+5"  -vcodec: libx264 custom/high_stop.ts

ffmpeg -loop 1 -i custom/fond_stream.png -y -c:v libx264 -t 15 -pix_fmt yuv420p -vf scale=640:360 custom/low.mp4

ffmpeg -i custom/low.mp4 -y -vf drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='LE STREAMING LIVE EST': fontcolor=white: fontsize=24: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-te
xt_w)/2: y=((h-text_h)/2)-(text_h/2)-5",drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='EN COURS DE PREPARATION': fontcolor=white: fontsize=24: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-text_w)/2: y=((h-text_h)/2)+(text_h/2)+5"  -vcodec: libx264 custom/low_init.ts

ffmpeg -i custom/low.mp4 -y -vf drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='LE STREAMING LIVE EST': fontcolor=white: fontsize=24: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-te
xt_w)/2: y=((h-text_h)/2)-(text_h/2)-5",drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='EN PAUSE': fontcolor=white: fontsize=24: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-text
_w)/2: y=((h-text_h)/2)+(text_h/2)+5"  -vcodec: libx264 custom/low_pause.ts

ffmpeg -i custom/low.mp4 -y -vf drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='LE STREAMING LIVE EST': fontcolor=white: fontsize=24: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-te
xt_w)/2: y=((h-text_h)/2)-(text_h/2)-5",drawtext="fontfile=/Library/Fonts/Lao MN.ttc: text='TERMINE': fontcolor=white: fontsize=24: box=1: boxcolor=black@0.5: boxborderw=5: x=(w-text_
w)/2: y=((h-text_h)/2)+(text_h/2)+5"  -vcodec: libx264 custom/low_stop.ts

cp custom/*_*.ts ./modules/remote_ffmpeg_hls/remote/resources/videos/
cp custom/*_*.ts ./modules/local_ffmpeg_hls/resources/videos/

