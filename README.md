# svg2json

svg2json is a PHP library for converting SVG to JSON format and was largely inspired by Aaron Lindsay's <a href="http://sourceforge.net/projects/svgtodojoxgfx/">SVG to dojox.gfx Convertor</a>.  The JSON format is agnostic but works best with <a href="http://www.dojotoolkit.org/reference-guide/dojox/gfx.html">dojox.gfx</a> (e.g. <a href="http://archive.dojotoolkit.org/nightly/dojotoolkit/dojox/gfx/demos/inspector.html">inspector demo</a>).

**WARNING:** This code is alpha quality and is not meant for general production use. For example, most SVG tests fail or produce partial results. See Q&A below for other SVG/Canvas libraries that are more robust or stable. Otherwise, feel free to fork and improve this code!

## Requirements

1. A web server with PHP enabled *(tested on version 5.3.6 but other versions may work)*

2. <a href="http://mrbluecoat.blogspot.com/2011/12/installing-phantomjs-on-ubuntu-for-use.html">PhantomJS</a> *(used for complex bounding box calculations)*

3. *optional* <a href="https://github.com/stela5/Dojox.gfx-Plugins">Dojox.gfx Plugins</a> *(for blur and shadow effects)*

## Usage

See *demo.htm* and *test.htm* in the src directory.

## FAQ

**Q:** Why convert SVG to JSON?  
**A:** The JSON format is more compact and can be used in conjunction with various JavaScript libraries to display SVG content on older versions of IE and other platforms that only support Canvas.

**Q:** Why do I get errors when converting a specific SVG file?  
**A:** Most likely because:
* The SVG file uses advanced <a href="http://www.w3.org/TR/SVG/filters.html">filters</a> (only blur and shadow are currently supported)
* The SVG file uses <a href="http://help.adobe.com/en_US/illustrator/cs/using/WS714a382cdf7d304e7e07d0100196cbc5f-61e4a.html">non-standard effects</a> or <a href="http://wiki.inkscape.org/wiki/index.php/TextOutputDev">non-standard attributes/elements</a>
* The SVG file does not use the <a href="http://www.w3.org/TR/SVG/">SVG 1.1 Second Edition</a> standard
* The SVG file is corrupt (open in <a href="http://xmlgraphics.apache.org/batik/tools/browser.html">Squiggle</a> or another SVG-compliant browser to verify)
* Your chosen JSON renderer encountered a bug (for example, Dojox.GFX does not currently support applying a transform to a gradient)
* svg2json encountered a bug (please report here or fork)

**Q:** Why is it so slow?  Why is the code so ugly?  Are we there yet?  ...etc.  
**A:** This project is experimental.  If speed, performance, and robustness are your top priority, consider using <a href="http://xmlgraphics.apache.org/batik/">Batik</a>, <a href="http://www.amplesdk.com/">Ample SDK</a>, <a href="https://github.com/kangax/fabric.js/">FabricJS</a>, or <a href="http://readysetraphael.com/">Ready Set Raphael</a>.  Also, feel free to fork this code and improve it!

## Dual-Licensed

Copyright (c) 2011, Stela 5

* <a href="http://www.opensource.org/licenses/mit-license.php">MIT</a>
* <a href="http://www.opensource.org/licenses/GPL-2.0">GPL v2 (or later)</a>

