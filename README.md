# gafia-tool
Vote counting tool for the Mafia community on the online forum NeoGAF (we now moved on to ResetERA).

Crawls a specified thread from the forum and searches for relevant voting keywords. The current votecount is written into a HTML file. Also includes a copy-paste-able version for posting on NeoGAF.

I will not include major new features in this version since it is rather confusing already. Instead the mid-term plan is to write a new version in python or java that is easily expandable. Feel free to download and modify the tool yourself, though.

To use it, set up a php-capable webserver and access the tool via /tool.php?t=threadnumber, where the threadnumber can be found in the link to the NeoGAF thread. For example the thread http://www.neogaf.com/forum/showthread.php?t=1412319 has the threadnumber 1412319 and the correct address for the tool would be /tool.php?t=1412319. The tool will then automatically create the files 1412319.data (storage file for all previous votes so we only have to process new posts since the last update) and 1412319.html (the file that you see in your browser) in its root directory.

The tool currently works ONLY on NeoGAF since it relies on certain keywords in the html generated by the forum software that seem to vary from forum to forum, even when the same forum software is used.
