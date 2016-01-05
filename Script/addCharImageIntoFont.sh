#!/bin/bash
CHAR=$1
IMAGE=$2
FONT=$3
IMAGENAME=${IMAGE%.*}

convert $IMAGE ${IMAGENAME}.bmp
potrace -s -W 100 -o ${IMAGENAME}.svg ${IMAGENAME}.bmp
python replace_char_image_font.py $FONT $CHAR ${IMAGENAME}.svg
