<?php
/**
 * 2016-2017 Michael Dekker and Robert Andersson
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Michael Dekker <michael@thirtybees.com>
 *  @author    Robert Andersson <robert@manillusion.no>
 *  @copyright 2016-2017 Michael Dekker
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class ImageManager extends ImageManagerCore
{
    public static function thumbnail($image, $cacheImage, $size, $imageType = 'jpg', $disableCache = true, $regenerate = false)
    {
        if (!self::isModuleEnabled() || !self::isImagickEnabled()) {
            return parent::thumbnail($image, $cacheImage, $size, $imageType, $disableCache, $regenerate);
        }

        self::statCacheClear($image);

        if (!file_exists($image)) {
            return '';
        }

        if ($regenerate && file_exists(_PS_TMP_IMG_DIR_.$cacheImage)) {
            @unlink(_PS_TMP_IMG_DIR_.$cacheImage);
        }

        if ($regenerate || !file_exists(_PS_TMP_IMG_DIR_.$cacheImage)) {
            if (!parent::checkImageMemoryLimit($image)) {
                return '';
            }

            $srcImage = new Imagick($image);
            self::trimImage($srcImage);
            $x = $srcImage->getImageWidth();
            $y = $srcImage->getImageHeight();

            $maxX = $size * 3;
            $ratioX = $x / ($y / $size);
            if ($ratioX > $maxX) {
                $ratioX = $maxX;
                $size = $y / ($x / $maxX);
            }

            self::resize2($image, _PS_TMP_IMG_DIR_.$cacheImage, $srcImage, $ratioX, $size, $imageType);
        }

        // Relative link will always work, whatever the base uri set in the admin
        if (Context::getContext()->controller->controller_type == 'admin') {
            return '<img src="../img/tmp/'.$cacheImage.($disableCache ? '?time='.time() : '').'" alt="" class="imgm img-thumbnail" />';
        } else {
            return '<img src="'._PS_TMP_IMG_.$cacheImage.($disableCache ? '?time='.time() : '').'" alt="" class="imgm img-thumbnail" />';
        }
    }

    public static function resize($srcFile, $dstFile, $dstWidth = null, $dstHeight = null, $fileType = 'jpg',
        $forceType = false, &$error = 0, &$tgtWidth = null, &$tgtHeight = null, $quality = 5,
        &$srcWidth = null, &$srcHeight = null)
    {
        if (!self::isModuleEnabled()) {
            return parent::resize($srcFile, $dstFile, $dstWidth, $dstHeight, $fileType,
                $forceType, $error, $tgtWidth , $tgtHeight, $quality, $srcWidth, $srcHeight);
        }

        if (Configuration::get(MDImageMagick::ORIGINAL_COPY)) {
            // Check if we should just copy the file instead
            $relativeDstFile = str_replace(_PS_IMG_DIR_, '', $dstFile);
            $relativeDstParts = explode(DIRECTORY_SEPARATOR, $relativeDstFile);
            $dstFileOnly = end($relativeDstParts);
            list($filename, $extension) = explode('.', $dstFileOnly);

            if (is_numeric($filename) && preg_match('/^(c|p|m|su|st)'.preg_quote(DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, str_split($filename)).DIRECTORY_SEPARATOR.$filename.'.'.$extension, '/').'$/', $relativeDstFile)) {
                return @copy($srcFile, $dstFile);
            }
        }

        if (!self::isImagickEnabled()) {
            return parent::resize($srcFile, $dstFile, $dstWidth, $dstHeight, $fileType,
                $forceType, $error, $tgtWidth , $tgtHeight, $quality, $srcWidth, $srcHeight);
        }

        self::statCacheClear($srcFile);

        if (!file_exists($srcFile) || !filesize($srcFile)) {
            return !($error = self::ERROR_FILE_NOT_EXIST);
        }

        if (!parent::checkImageMemoryLimit($srcFile)) {
            return !($error = self::ERROR_MEMORY_LIMIT);
        }

        $srcImage = new Imagick($srcFile);
        $srcWidth = $srcImage->getImageWidth();
        $srcHeight = $srcImage->getImageHeight();

        self::trimImage($srcImage);
        $trimWidth = $srcImage->getImageWidth();
        $trimHeight = $srcImage->getImageHeight();
        if (!$dstWidth) {
            $dstWidth = $trimWidth;
        }
        if (!$dstHeight) {
            $dstHeight = $trimHeight;
        }

        $ps_image_generation_method = Configuration::get('PS_IMAGE_GENERATION_METHOD');
        if ($ps_image_generation_method && ($trimWidth > $dstWidth || $trimHeight > $dstHeight)) {
            if ($ps_image_generation_method == 2) {
                $dstWidth = (int)(round(($trimWidth * $dstHeight) / $trimHeight));
            } else {
                $dstHeight = (int)(round(($trimHeight * $dstWidth) / $trimWidth));
            }
        }

        $tgtWidth  = $dstWidth;
        $tgtHeight = $dstHeight;

        return self::resize2($srcFile, $dstFile, $srcImage, $dstWidth, $dstHeight, $fileType, $forceType);
    }

    private static function resize2($srcFile, $dstFile, $srcImage, $dstWidth, $dstHeight, $fileType = 'jpg', $force_type = false) {
        // ArgyllCMS sRGB.icm
        // See http://ninedegreesbelow.com/photography/srgb-profile-comparison.html
        // Binary content
        $srbgProfile = null;
        // Base64 content
        $srbgProfileBase64 = '
AAAMjGFyZ2wCIAAAbW50clJHQiBYWVogB90ABwAfABMAEAAnYWNzcE1TRlQAAAAASUVDIHNSR0IA
AAAAAAAAAAAAAAAAAPbWAAEAAAAA0y1hcmdsAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAARZGVzYwAAAVAAAACZY3BydAAAAewAAABnZG1uZAAAAlQAAABwZG1k
ZAAAAsQAAACIdGVjaAAAA0wAAAAMdnVlZAAAA1gAAABndmlldwAAA8AAAAAkbHVtaQAAA+QAAAAU
bWVhcwAAA/gAAAAkd3RwdAAABBwAAAAUYmtwdAAABDAAAAAUclhZWgAABEQAAAAUZ1hZWgAABFgA
AAAUYlhZWgAABGwAAAAUclRSQwAABIAAAAgMZ1RSQwAABIAAAAgMYlRSQwAABIAAAAgMZGVzYwAA
AAAAAAA/c1JHQiBJRUM2MTk2Ni0yLjEgKEVxdWl2YWxlbnQgdG8gd3d3LnNyZ2IuY29tIDE5OTgg
SFAgcHJvZmlsZSkAAAAAAAAAAAAAAD9zUkdCIElFQzYxOTY2LTIuMSAoRXF1aXZhbGVudCB0byB3
d3cuc3JnYi5jb20gMTk5OCBIUCBwcm9maWxlKQAAAAAAAAAAdGV4dAAAAABDcmVhdGVkIGJ5IEdy
YWVtZSBXLiBHaWxsLiBSZWxlYXNlZCBpbnRvIHRoZSBwdWJsaWMgZG9tYWluLiBObyBXYXJyYW50
eSwgVXNlIGF0IHlvdXIgb3duIHJpc2suAABkZXNjAAAAAAAAABZJRUMgaHR0cDovL3d3dy5pZWMu
Y2gAAAAAAAAAAAAAABZJRUMgaHR0cDovL3d3dy5pZWMuY2gAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZGVzYwAAAAAAAAAuSUVDIDYxOTY2LTIuMSBEZWZhdWx0
IFJHQiBjb2xvdXIgc3BhY2UgLSBzUkdCAAAAAAAAAAAAAAAuSUVDIDYxOTY2LTIuMSBEZWZhdWx0
IFJHQiBjb2xvdXIgc3BhY2UgLSBzUkdCAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHNpZyAAAAAAQ1JU
IGRlc2MAAAAAAAAADUlFQzYxOTY2LTIuMQAAAAAAAAAAAAAADUlFQzYxOTY2LTIuMQAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAdmlldwAAAAAA
E6R8ABRfMAAQzgIAA+2yAAQTCgADXGcAAAABWFlaIAAAAAAATAo9AFAAAABXHrhtZWFzAAAAAAAA
AAEAAAAAAAAAAAAAAAAAAAAAAAACjwAAAAJYWVogAAAAAAAA81EAAQAAAAEWzFhZWiAAAAAAAAAA
AAAAAAAAAAAAWFlaIAAAAAAAAG+gAAA49QAAA5BYWVogAAAAAAAAYpcAALeHAAAY2VhZWiAAAAAA
AAAknwAAD4QAALbDY3VydgAAAAAAAAQAAAAABQAKAA8AFAAZAB4AIwAoAC0AMgA3ADsAQABFAEoA
TwBUAFkAXgBjAGgAbQByAHcAfACBAIYAiwCQAJUAmgCfAKQAqQCuALIAtwC8AMEAxgDLANAA1QDb
AOAA5QDrAPAA9gD7AQEBBwENARMBGQEfASUBKwEyATgBPgFFAUwBUgFZAWABZwFuAXUBfAGDAYsB
kgGaAaEBqQGxAbkBwQHJAdEB2QHhAekB8gH6AgMCDAIUAh0CJgIvAjgCQQJLAlQCXQJnAnECegKE
Ao4CmAKiAqwCtgLBAssC1QLgAusC9QMAAwsDFgMhAy0DOANDA08DWgNmA3IDfgOKA5YDogOuA7oD
xwPTA+AD7AP5BAYEEwQgBC0EOwRIBFUEYwRxBH4EjASaBKgEtgTEBNME4QTwBP4FDQUcBSsFOgVJ
BVgFZwV3BYYFlgWmBbUFxQXVBeUF9gYGBhYGJwY3BkgGWQZqBnsGjAadBq8GwAbRBuMG9QcHBxkH
Kwc9B08HYQd0B4YHmQesB78H0gflB/gICwgfCDIIRghaCG4IggiWCKoIvgjSCOcI+wkQCSUJOglP
CWQJeQmPCaQJugnPCeUJ+woRCicKPQpUCmoKgQqYCq4KxQrcCvMLCwsiCzkLUQtpC4ALmAuwC8gL
4Qv5DBIMKgxDDFwMdQyODKcMwAzZDPMNDQ0mDUANWg10DY4NqQ3DDd4N+A4TDi4OSQ5kDn8Omw62
DtIO7g8JDyUPQQ9eD3oPlg+zD88P7BAJECYQQxBhEH4QmxC5ENcQ9RETETERTxFtEYwRqhHJEegS
BxImEkUSZBKEEqMSwxLjEwMTIxNDE2MTgxOkE8UT5RQGFCcUSRRqFIsUrRTOFPAVEhU0FVYVeBWb
Fb0V4BYDFiYWSRZsFo8WshbWFvoXHRdBF2UXiReuF9IX9xgbGEAYZRiKGK8Y1Rj6GSAZRRlrGZEZ
txndGgQaKhpRGncanhrFGuwbFBs7G2MbihuyG9ocAhwqHFIcexyjHMwc9R0eHUcdcB2ZHcMd7B4W
HkAeah6UHr4e6R8THz4faR+UH78f6iAVIEEgbCCYIMQg8CEcIUghdSGhIc4h+yInIlUigiKvIt0j
CiM4I2YjlCPCI/AkHyRNJHwkqyTaJQklOCVoJZclxyX3JicmVyaHJrcm6CcYJ0kneierJ9woDSg/
KHEooijUKQYpOClrKZ0p0CoCKjUqaCqbKs8rAis2K2krnSvRLAUsOSxuLKIs1y0MLUEtdi2rLeEu
Fi5MLoIuty7uLyQvWi+RL8cv/jA1MGwwpDDbMRIxSjGCMbox8jIqMmMymzLUMw0zRjN/M7gz8TQr
NGU0njTYNRM1TTWHNcI1/TY3NnI2rjbpNyQ3YDecN9c4FDhQOIw4yDkFOUI5fzm8Ofk6Njp0OrI6
7zstO2s7qjvoPCc8ZTykPOM9Ij1hPaE94D4gPmA+oD7gPyE/YT+iP+JAI0BkQKZA50EpQWpBrEHu
QjBCckK1QvdDOkN9Q8BEA0RHRIpEzkUSRVVFmkXeRiJGZ0arRvBHNUd7R8BIBUhLSJFI10kdSWNJ
qUnwSjdKfUrESwxLU0uaS+JMKkxyTLpNAk1KTZNN3E4lTm5Ot08AT0lPk0/dUCdQcVC7UQZRUFGb
UeZSMVJ8UsdTE1NfU6pT9lRCVI9U21UoVXVVwlYPVlxWqVb3V0RXklfgWC9YfVjLWRpZaVm4Wgda
VlqmWvVbRVuVW+VcNVyGXNZdJ114XcleGl5sXr1fD19hX7NgBWBXYKpg/GFPYaJh9WJJYpxi8GND
Y5dj62RAZJRk6WU9ZZJl52Y9ZpJm6Gc9Z5Nn6Wg/aJZo7GlDaZpp8WpIap9q92tPa6dr/2xXbK9t
CG1gbbluEm5rbsRvHm94b9FwK3CGcOBxOnGVcfByS3KmcwFzXXO4dBR0cHTMdSh1hXXhdj52m3b4
d1Z3s3gReG54zHkqeYl553pGeqV7BHtje8J8IXyBfOF9QX2hfgF+Yn7CfyN/hH/lgEeAqIEKgWuB
zYIwgpKC9INXg7qEHYSAhOOFR4Wrhg6GcobXhzuHn4gEiGmIzokziZmJ/opkisqLMIuWi/yMY4zK
jTGNmI3/jmaOzo82j56QBpBukNaRP5GokhGSepLjk02TtpQglIqU9JVflcmWNJaflwqXdZfgmEyY
uJkkmZCZ/JpomtWbQpuvnByciZz3nWSd0p5Anq6fHZ+Ln/qgaaDYoUehtqImopajBqN2o+akVqTH
pTilqaYapoum/adup+CoUqjEqTepqaocqo+rAqt1q+msXKzQrUStuK4trqGvFq+LsACwdbDqsWCx
1rJLssKzOLOutCW0nLUTtYq2AbZ5tvC3aLfguFm40blKucK6O7q1uy67p7whvJu9Fb2Pvgq+hL7/
v3q/9cBwwOzBZ8Hjwl/C28NYw9TEUcTOxUvFyMZGxsPHQce/yD3IvMk6ybnKOMq3yzbLtsw1zLXN
Nc21zjbOts83z7jQOdC60TzRvtI/0sHTRNPG1EnUy9VO1dHWVdbY11zX4Nhk2OjZbNnx2nba+9uA
3AXcit0Q3ZbeHN6i3ynfr+A24L3hROHM4lPi2+Nj4+vkc+T85YTmDeaW5x/nqegy6LzpRunQ6lvq
5etw6/vshu0R7ZzuKO6070DvzPBY8OXxcvH/8ozzGfOn9DT0wvVQ9d72bfb794r4Gfio+Tj5x/pX
+uf7d/wH/Jj9Kf26/kv+3P9t//8=';

        // If PS_IMAGE_QUALITY is activated, the generated image will be a PNG with .jpg as a file extension.
        // This allow for higher quality and for transparency. JPG source files will also benefit from a higher quality
        // because JPG reencoding, even with max quality setting, degrades the image.
        /** @var Imagick $srcImage $type */
        $type = $srcImage->getImageFormat();
        if (Configuration::get('PS_IMAGE_QUALITY') == 'png_all'
            || (Configuration::get('PS_IMAGE_QUALITY') == 'png' && $type == 'PNG') && !$force_type) {
            $fileType = 'png';
        }

        if ($fileType == 'png') {
            // PNG is basically no more than lossless gzip
            $srcImage->setImageCompression(Imagick::COMPRESSION_LZW);
            $srcImage->setImageFormat('png');
            $srcImage->setImageCompressionQuality((int)Configuration::get('PS_PNG_QUALITY') * 10 + (int)Configuration::get(MDImageMagick::IMAGICK_PNG_DATA_ENCODING));
            $destTypeFile = 'png:'.$dstFile;
        } else {
            $srcImage->setImageCompression(Imagick::COMPRESSION_JPEG);
            $srcImage->setImageFormat('jpeg');
            $srcImage->setImageCompressionQuality((int)Configuration::get('PS_JPEG_QUALITY'));
            if (Configuration::get(MDImageMagick::IMAGICK_PROGRESSIVE_JPEG)) {
                $srcImage->setInterlaceScheme(Imagick::INTERLACE_PLANE);
            } else {
                $srcImage->setInterlaceScheme(Imagick::INTERLACE_NO);
            }
            $destTypeFile = 'jpeg:'.$dstFile;
        }

        // If image is a PNG and the output is PNG, fill with transparency. Else fill with white background.
        if ($fileType == 'png' && $type == 'PNG') {
            $srcImage->setImageBackgroundColor('none');
        } else {
            $srcImage->setImageBackgroundColor('white');
        }

        Hook::exec(
            'actionChangeImagickSettings',
            [
                'imagick'    => &$srcImage,
                'src_file'   => $srcFile,
                'dst_file'   => $dstFile,
                'dst_width'  => $dstWidth,
                'dst_height' => $dstHeight,
                'file_type'  => $fileType,
            ]
        );

        if (Configuration::get(MDImageMagick::IMAGICK_STRIP_ICC_PROFILE)) {
            $iccModel = $srcImage->getImageProperty('icc:model');
            $colorSpace = $srcImage->getImageColorspace();
            if ($iccModel) {
                // Contains an ICC profile, do profile conversion if not already sRGB
                // c2 is Facebook's sRGB hack, treat it like sRGB
                // But don't touch some incorrectly tagged images seen in real life
                if (strpos($iccModel, 'sRGB') === false
                    && strpos($iccModel, 'c2') === false
                    && !($colorSpace == Imagick::COLORSPACE_SRGB && strpos($iccModel, 'SWOP') !== false)) {
                    if ($srbgProfile == null) {
                        // Do once, cache result
                        $srbgProfile = base64_decode($srbgProfileBase64);
                    }
                    // Transform to sRGB
                    $srcImage->profileImage('icc', $srbgProfile);
                }
            } else {
                // Does not contain an ICC profile, do simplistic colorspace conversion if needed
                // Should arguably try to guess the input ICC profile for stuff like untagged
                // CMYK images, but the complexity gets overly high for all these fringe cases
                if ($colorSpace != Imagick::COLORSPACE_GRAY && $colorSpace != Imagick::COLORSPACE_SRGB) {
                    // Transform to sRGB
                    $srcImage->transformImageColorspace(Imagick::COLORSPACE_SRGB);
                }
            }
            // Strip ICC profiles, comments and exif data
            $srcImage->stripImage();
        }

        $x = $srcImage->getImageWidth();
        $y = $srcImage->getImageHeight();

        // Do we even need to resize?
        if ($x != $dstWidth || $y != $dstHeight) {
            $srcImage->resizeImage($dstWidth, $dstHeight, Configuration::get(MDImageMagick::IMAGICK_FILTER), (float) Configuration::get(MDImageMagick::IMAGICK_BLUR), true);
            $x = $srcImage->getImageWidth();
            $y = $srcImage->getImageHeight();
            // If the image dimensions differ from the target, add whitespace
            if ($x != $dstWidth || $y != $dstHeight) {
                $srcImage->extentImage($dstWidth, $dstHeight, ($x - $dstWidth) / 2, ($y - $dstHeight) / 2);
            }
        }

        $writeFile = $srcImage->writeImage($destTypeFile);

        Hook::exec('actionOnImageResizeAfter', ['dst_file' => $dstFile, 'file_type' => $fileType]);

        return $writeFile;
    }

    private static function isModuleEnabled() {
        if (!Module::isEnabled('mdimagemagick')) {
            return false;
        }
        if (!class_exists('MDImageMagick')) {
            require_once _PS_MODULE_DIR_.'mdimagemagick/mdimagemagick.php';
        }

        return true;
    }

    private static function isImagickEnabled() {
        $imagickEnabled = (bool)Configuration::get(MDImageMagick::IMAGICK_ENABLED);
        if ($imagickEnabled && !extension_loaded('imagick')) {
            Db::getInstance()->update('configuration', ['name' => MDImageMagick::IMAGICK_ENABLED, 'value' => false], 'name = \''.MDImageMagick::IMAGICK_ENABLED.'\'');
            $imagickEnabled = false;
        }

        return $imagickEnabled;
    }

    private static function statCacheClear($image) {
        if (PHP_VERSION_ID < 50300) {
            clearstatcache();
        } else {
            clearstatcache(true, $image);
        }
    }

    private static function trimImage($src_image) {
        /** @var Imagick $src_image */
        if (Configuration::get(MDImageMagick::IMAGICK_TRIM_WHITESPACE)) {
            $fuzz = (int) Configuration::get(MDImageMagick::IMAGICK_FUZZ, 0);
            if ($fuzz && method_exists('Imagick', 'getQuantum')) {
                // From percentage to 0 - getQuantum
                $fuzz = Imagick::getQuantum() / (100 / $fuzz);
            }
            // Trim whitespace
            $src_image->trimImage($fuzz);
        }
    }
}
