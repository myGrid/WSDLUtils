# BioCatalogue: WSDLUtils Service
#
# Copyright (c) 2009-2011, University of Manchester, The European Bioinformatics 
# Institute (EMBL-EBI) and the University of Southampton.
# See license.txt for details


Introduction
------------
The WSDLUtils Service was developed by Dan Mowbray for the EMBRACE web services registry and is now being used in the BioCatalogue web services registry.


Functions
---------
The WDSDLUtils Service has two main functions:
 - parse a WDSL file into a format that is consumable by the BioCatalogue registry
 - track changes in a WSDL document


Installation
------------
The utilities in this Service are written in PHP and hence would run on a web server with PHP enabled.

The following PHP libraries are required to be installed:
- php-xml
- php-xml-parser 
- php-xml-serializer 
- php-xml-util 

To deploy the code, run the following in document root of the your web server (e.g.: /var/www/. 

  [sudo] git clone https://github.com/myGrid/WSDLUtils.git

The library will reside in a folder called "WSDLUtils".

To test your installation, call the parse utility as follow:
 
http://<my server root>/WSDLUtils/WSDLUtils.php?method=parse&wsdl_uri="my test wsdl uri"
 
 
Using the Service in BioCatalogue for Parsing
---------------------------------------------
 
Set the WSDLUtils parser location in  "config/initializers/biocat_local.rb"

Example
WSDLUTILS_BASE_URI = 'http://test.biocatalogue.org/WSDLUtils/WSDLUtils.php'

Use the Service in the application in the following way:

BioCatalogue::WSDLUtils::WSDLParser.parse("wsdl_url")

where "wsdl_url" is the wsdl you want to parse. You could test this in the rails console as well.


Resources
---------
Complete documentation by the author is available at
http://www.biocatalogue.org/wiki/doku.php?id=development:wsdl_parsing


TODO
----
Extend this README for the WSDL tracking function.
