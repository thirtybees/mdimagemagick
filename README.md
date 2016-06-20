# ImageMagick ![ImageMagick](/logo.gif)
![ImageMagick](/logo.png)

Add ImageMagick support to PrestaShop

##Installation
###Installation on Ubuntu 14.04/16.04
Make sure you use Ondřej Surý's PHP PPA (`apt-add-repository ppa:ondrej/php`). It's kept up to date and at the time of writing comes with PHP versions 5.5, 5.6 and 7.0.
 
To install the PHP extension, issue the command:  
```shell
$ apt-get install php-imagick
```  
(yes, that's without the `php7.0` prefix)
 
Unfortunately, the version of ImageMagick that is bundled with Ubuntu (or any version below 7.0.2) is vulnerable: https://imagetragick.com/  
In order to install a safe version on production, we will have to compile it from source.
First, make sure you have the necessary tools to build:  
```shell
$ apt-get install build-essential git
```
 
Then download the source with git:  
```shell
$ git clone https://github.com/ImageMagick/ImageMagick
```
 
Change directory:  
```shell
$ cd ImageMagick
```
 
Configure:  
```shell
$ ./configure
```  
(This step might show some missing dependencies, try to locate the Ubuntu package and install with `apt-get`).
 
Compile and install:  
```shell
$ make && sudo make install
```
 
You will now have the latest version available. Don't forget to update from time to time.
 
###Module installation
- Upload the module through FTP or your Back Office
- Install the module
- Check if there are any errors and correct them if necessary
- Profit!

## Compatibility
This module has been tested with versions:  
- `1.6.1.0` - `1.6.1.6`

## Minimum requirements
- PHP imagick extension 3.4.0
- ImageMagick 7.0.2 (anything below is vulnerable and not supported!)
- PHP 5.4
- PrestaShop 1.6.1.0

## License
Academic Free License 3.0