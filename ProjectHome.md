# trufflepig is a search engine for SMB shares on local networks #

## Network scanner ##
  * Developed in Python, with a couple of Linux/Mac OSX/UNIX specific commands, for now
  * Walks around the network to generate list of files and file information
  * Data stored in mysql database (locally or on remote server)
  * Self cleaning - old files intelligently removed

## Web frontend ##
  * Developed in php
  * Reads mysql database created by the network scanner
  * Provides familiar interface to search through files shared, filtering by type supported
  * Produces a list of sharing computers on the network
  * Wall for posting of comments to search page