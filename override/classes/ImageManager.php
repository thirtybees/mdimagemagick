<?php
/**
 * 2016 Michael Dekker and Robert Andersson
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@michaeldekker.com so we can send you a copy immediately.
 *
 *  @author    Michael Dekker <prestashop@michaeldekker.com>
 *  @author    Robert Andersson <robert@manillusion.no>
 *  @copyright 2016 Michael Dekker
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class ImageManager extends ImageManagerCore
{
    // ArgyllCMS sRGB.icm
    // See http://ninedegreesbelow.com/photography/srgb-profile-comparison.html
    // Binary content
    private static $srbgProfile = null;
    // Base64 content
    private static $srbgProfileBase64 = '
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

    public static function thumbnail($image, $cache_image, $size, $image_type = 'jpg', $disable_cache = true, $regenerate = false)
    {
        if (!self::isModuleEnabled() || !self::isImagickEnabled()) {
            return parent::thumbnail($image, $cache_image, $size, $image_type, $disable_cache, $regenerate);
        }

        self::statCacheClear($image);

        if (!file_exists($image)) {
            return '';
        }

        if ($regenerate && file_exists(_PS_TMP_IMG_DIR_.$cache_image)) {
            @unlink(_PS_TMP_IMG_DIR_.$cache_image);
        }

        if ($regenerate || !file_exists(_PS_TMP_IMG_DIR_.$cache_image)) {
            if (!parent::checkImageMemoryLimit($image)) {
                return '';
            }

            $src_image = new Imagick($image);
            self::trimImage($src_image);
            $x = $src_image->getImageWidth();
            $y = $src_image->getImageHeight();

            $max_x = $size * 3;
            $ratio_x = $x / ($y / $size);
            if ($ratio_x > $max_x) {
                $ratio_x = $max_x;
                $size = $y / ($x / $max_x);
            }

            self::resize2($image, _PS_TMP_IMG_DIR_.$cache_image, $src_image, $ratio_x, $size, $image_type);
        }

        // Relative link will always work, whatever the base uri set in the admin
        if (Context::getContext()->controller->controller_type == 'admin') {
            return '<img src="../img/tmp/'.$cache_image.($disable_cache ? '?time='.time() : '').'" alt="" class="imgm img-thumbnail" />';
        } else {
            return '<img src="'._PS_TMP_IMG_.$cache_image.($disable_cache ? '?time='.time() : '').'" alt="" class="imgm img-thumbnail" />';
        }
    }

    public static function resize($src_file, $dst_file, $dst_width = null, $dst_height = null, $file_type = 'jpg',
        $force_type = false, &$error = 0, &$tgt_width = null, &$tgt_height = null, $quality = 5,
        &$src_width = null, &$src_height = null)
    {
        if (!self::isModuleEnabled()) {
            return parent::resize($src_file, $dst_file, $dst_width, $dst_height, $file_type,
                $force_type, $error, $tgt_width , $tgt_height, $quality, $src_width, $src_height);
        }

        if (Configuration::get(MDImageMagick::ORIGINAL_COPY)) {
            // Check if we should just copy the file instead
            $relative_dst_file = str_replace(_PS_IMG_DIR_, '', $dst_file);
            $relative_dst_parts = explode(DIRECTORY_SEPARATOR, $relative_dst_file);
            $dst_file_only = end($relative_dst_parts);
            list($filename, $extension) = explode('.', $dst_file_only);

            if (is_numeric($filename) && preg_match('/^(c|p|m|su|st)'.preg_quote(DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, str_split($filename)).DIRECTORY_SEPARATOR.$filename.'.'.$extension, '/').'$/', $relative_dst_file)) {
                return @copy($src_file, $dst_file);
            }
        }

        if (!self::isImagickEnabled()) {
            return parent::resize($src_file, $dst_file, $dst_width, $dst_height, $file_type,
                $force_type, $error, $tgt_width , $tgt_height, $quality, $src_width, $src_height);
        }

        self::statCacheClear($src_file);

        if (!file_exists($src_file) || !filesize($src_file)) {
            return !($error = self::ERROR_FILE_NOT_EXIST);
        }

        if (!parent::checkImageMemoryLimit($src_file)) {
            return !($error = self::ERROR_MEMORY_LIMIT);
        }

        $src_image = new Imagick($src_file);
        $src_width = $src_image->getImageWidth();
        $src_height = $src_image->getImageHeight();

        self::trimImage($src_image);
        $trim_width = $src_image->getImageWidth();
        $trim_height = $src_image->getImageHeight();
        if (!$dst_width) {
            $dst_width = $trim_width;
        }
        if (!$dst_height) {
            $dst_height = $trim_height;
        }

        $ps_image_generation_method = Configuration::get('PS_IMAGE_GENERATION_METHOD');
        if ($ps_image_generation_method && ($trim_width > $dst_width || $trim_height > $dst_height)) {
            if ($ps_image_generation_method == 2) {
                $dst_width = (int)(round(($trim_width * $dst_height) / $trim_height));
            } else {
                $dst_height = (int)(round(($trim_height * $dst_width) / $trim_width));
            }
        }

        $tgt_width  = $dst_width;
        $tgt_height = $dst_height;

        return self::resize2($src_file, $dst_file, $src_image, $dst_width, $dst_height, $file_type, $force_type);
    }

    private static function resize2($src_file, $dst_file, $src_image, $dst_width, $dst_height, $file_type = 'jpg', $force_type = false) {
        // If PS_IMAGE_QUALITY is activated, the generated image will be a PNG with .jpg as a file extension.
        // This allow for higher quality and for transparency. JPG source files will also benefit from a higher quality
        // because JPG reencoding, even with max quality setting, degrades the image.
        $type = $src_image->getImageFormat();
        if (Configuration::get('PS_IMAGE_QUALITY') == 'png_all'
            || (Configuration::get('PS_IMAGE_QUALITY') == 'png' && $type == 'PNG') && !$force_type) {
            $file_type = 'png';
        }

        if ($file_type == 'png') {
            // PNG is basically no more than lossless gzip
            $src_image->setImageCompression(Imagick::COMPRESSION_LZW);
            $src_image->setImageFormat('png');
            $src_image->setImageCompressionQuality((int)Configuration::get('PS_PNG_QUALITY') * 10 + (int)Configuration::get(MDImageMagick::IMAGICK_PNG_DATA_ENCODING));
            $dest_type_file = 'png:'.$dst_file;
        } else {
            $src_image->setImageCompression(Imagick::COMPRESSION_JPEG);
            $src_image->setImageFormat('jpeg');
            $src_image->setImageCompressionQuality((int)Configuration::get('PS_JPEG_QUALITY'));
            if (Configuration::get(MDImageMagick::IMAGICK_PROGRESSIVE_JPEG)) {
                $src_image->setInterlaceScheme(Imagick::INTERLACE_PLANE);
            } else {
                $src_image->setInterlaceScheme(Imagick::INTERLACE_NO);
            }
            $dest_type_file = 'jpeg:'.$dst_file;
        }

        // If image is a PNG and the output is PNG, fill with transparency. Else fill with white background.
        if ($file_type == 'png' && $type == 'PNG') {
            $src_image->setImageBackgroundColor('none');
        } else {
            $src_image->setImageBackgroundColor('white');
        }

        Hook::exec(
            'actionChangeImagickSettings',
            array(
                'imagick' => &$src_image,
                'src_file' => $src_file,
                'dst_file' => $dst_file,
                'dst_width' => $dst_width,
                'dst_height' => $dst_height,
                'file_type' => $file_type,
            )
        );

        if (Configuration::get(MDImageMagick::IMAGICK_STRIP_ICC_PROFILE)) {
            $iccModel = $src_image->getImageProperty('icc:model');
            $colorSpace = $src_image->getImageColorSpace();
            if ($iccModel) {
                // Contains an ICC profile, do profile conversion if not already sRGB
                // c2 is Facebook's sRGB hack, treat it like sRGB
                // But don't touch some incorrectly tagged images seen in real life
                if (strpos($iccModel, 'sRGB') === false
                    && strpos($iccModel, 'c2') === false
                    && !($colorSpace == imagick::COLORSPACE_SRGB && strpos($iccModel, 'SWOP') !== false)) {
                    if (self::$srbgProfile == null) {
                        // Do once, cache result
                        self::$srbgProfile = base64_decode(self::$srbgProfileBase64);
                    }
                    // Transform to sRGB
                    $src_image->profileImage('icc', self::$srbgProfile);
                }
            } else {
                // Does not contain an ICC profile, do simplistic colorspace conversion if needed
                // Should arguably try to guess the input ICC profile for stuff like untagged
                // CMYK images, but the complexity gets overly high for all these fringe cases
                if ($colorSpace != imagick::COLORSPACE_GRAY && $colorSpace != imagick::COLORSPACE_SRGB) {
                    // Transform to sRGB
                    $src_image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
                }
            }
            // Strip ICC profiles, comments and exif data
            $src_image->stripImage();
        }

        $x = $src_image->getImageWidth();
        $y = $src_image->getImageHeight();
        // Do we even need to resize?
        if ($x != $dst_width || $y != $dst_heigth) {
            $src_image->resizeImage($dst_width, $dst_height, Configuration::get(MDImageMagick::IMAGICK_FILTER), (float)Configuration::get(MDImageMagick::IMAGICK_BLUR), true);
            $x = $src_image->getImageWidth();
            $y = $src_image->getImageHeight();
            // If the image dimensions differ from the target, add whitespace
            if ($x != $dst_width || $y != $dst_height) {
                $src_image->extentImage($dst_width, $dst_height, ($x - $dst_width) / 2, ($y - $dst_height) / 2);
            }
        }

        $write_file = $src_image->writeImage($dest_type_file);

        Hook::exec('actionOnImageResizeAfter', array('dst_file' => $dst_file, 'file_type' => $file_type));

        return $write_file;
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
        $imagick_enabled = (bool)Configuration::get(MDImageMagick::IMAGICK_ENABLED);
        if ($imagick_enabled && !extension_loaded('imagick')) {
            Db::getInstance()->update('configuration', array('name' => MDImageMagick::IMAGICK_ENABLED, 'value' => false), 'name = \''.MDImageMagick::IMAGICK_ENABLED.'\'');
            $imagick_enabled = false;
        }
        return $imagick_enabled;
    }

    private static function statCacheClear($image) {
        if (PHP_VERSION_ID < 50300) {
            clearstatcache();
        } else {
            clearstatcache(true, $image);
        }
    }

    private static function trimImage($src_image) {
        if (Configuration::get(MDImageMagick::IMAGICK_TRIM_WHITESPACE)) {
            $fuzz = (int)Configuration::get(MDImageMagick::IMAGICK_FUZZ, 0);
            if ($fuzz) {
                // From percentage to 0 - getQuantum
                $fuzz = Imagick::getQuantum() / (100 / $fuzz);
            }
            // Trim whitespace
            $src_image->trimImage($fuzz);
        }
    }
}
