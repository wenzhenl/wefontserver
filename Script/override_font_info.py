#!/usr/bin/env python

# -*- coding: utf8 -*-
__author__ = "Wenzheng Li"

#////////////////////////////////////////////////////////
#////////////////// VERSION 1.0.0 /////////////////////////
#//////////////// PART OF ALYSSA PROJECT ////////////////
#//// INITILIZE FONT WITH USER PROVIDED /////////////////
#//// FONTNAME, COPYRIGHT AND VERSION  ///////////////////
#////////////////////////////////////////////////////////

import sys
import fontforge
import string
import argparse
import os

#******************* COMMAND LINE OPTIONS *******************************#
parser = argparse.ArgumentParser(description="initilize font with fontname, \
        copyright and version info")
parser.add_argument("fontpath", help="input font file full path we want to initilize")
parser.add_argument("name", help="the fullname of the font")
parser.add_argument("copyright", help="the copyright of the font")
parser.add_argument("version", help="the version of the font")
args = parser.parse_args()

font = fontforge.open(args.fontpath)

font.fontname = args.name
font.familyname = args.name
font.fullname = args.name
font.copyright = "Copyright(c) " + args.copyright
print font.copyright
font.version = args.version

dirname = os.path.dirname(args.fontpath)
temp_font_name = '/'.join((dirname, "temporaryFont.ttf"))
print temp_font_name

font.generate(temp_font_name)
# os.rename(temp_font_name, args.fontpath)
