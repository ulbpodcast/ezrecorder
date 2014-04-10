#!/bin/sh
mplayer tv:// -tv driver=v4l:width=640:height=480:outfmt=i420:input=2 -vc rawi420
