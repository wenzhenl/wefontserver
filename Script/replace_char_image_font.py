#!/usr/bin/env python

# -*- coding: utf8 -*-
__author__ = "Wenzheng Li"

#////////////////////////////////////////////////////////
#////////////////// VERSION 1.0.0 /////////////////////////
#//////////////// PART OF ALYSSA PROJECT ////////////////
#///////////////ADD USER UPLOADED GLYPH TO FONT //////////
#////////////////////////////////////////////////////////

import sys
import fontforge
import string
import argparse
import os

#******************* COMMAND LINE OPTIONS *******************************#
parser = argparse.ArgumentParser(description="replace glyph in font with new one")
parser.add_argument("fontpath", help="input font file full path we want to initilize")
parser.add_argument("charname", help="charname of the glyph")
parser.add_argument("glyph", help="svg file of the glyph")
args = parser.parse_args()

font = fontforge.open(args.fontpath)

unicode_char = "{:04x}".format(ord(args.charname.decode("utf-8")))
unicode_char = "uni" + unicode_char.upper()
unicode_char = unicode_char
print "unicode of " + args.charname + ":" + unicode_char

dirname = os.path.dirname(args.glyph)
unicode_svg = '/'.join((dirname, unicode_char + ".svg"))
print "unicode svg :" + unicode_svg
os.rename(args.glyph, unicode_svg)

font[unicode_char].clear()
font[unicode_char].importOutlines(unicode_svg)

dirname = os.path.dirname(args.fontpath)
temp_font_name = '/'.join((dirname, "temporaryFont.ttf"))
print temp_font_name

font.generate(temp_font_name)
os.rename(temp_font_name, args.fontpath)
