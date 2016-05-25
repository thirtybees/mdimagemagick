<?php
/**
 * 2016 Michael Dekker
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
 *  @copyright 2016 Michael Dekker
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MDImageMagick extends Module
{
    const IMAGICK_ENABLED = 'IMAGICK_ENABLED';
    const IMAGICK_PROGRESSIVE_JPEG = 'IMAGICK_PROGRESSIVE_JPEG';
    const IMAGICK_FILTER = 'IMAGICK_FILTER';
    const IMAGICK_BLUR = 'IMAGICK_BLUR';
    const IMAGICK_STRIP_ICC_PROFILE = 'IMAGICK_STRIP_ICC_PROFILE';
    const IMAGICK_TRIM_WHITESPACE = 'IMAGICK_TRIM_WHITESPACE';
    const IMAGICK_FUZZ = 'IMAGICK_FUZZ';
    const IMAGICK_PNG_DATA_ENCODING = 'IMAGICK_PNG_DATA_ENCODING';
    const ORIGINAL_COPY = 'ORIGINAL_COPY';

    const PNG_NONE = 0;
    const PNG_SUB = 1;
    const PNG_UP = 2;
    const PNG_AVERAGE = 3;
    const PNG_PAETH = 4;
    const PNG_ADAPTIVE = 5;

    /**
     * MDImagemagick constructor.
     */
    public function __construct()
    {
        $this->name = 'mdimagemagick';
        $this->tab = 'administration';
        $this->version = '1.1.1';
        $this->author = 'Michael Dekker';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ImageMagick');
        $this->description = $this->l('Add ImageMagick support to PrestaShop');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Install this module
     *
     * @return bool Whether this module was successfully installed
     */
    public function install()
    {
        if (!extension_loaded('imagick')) {
            $this->context->controller->errors[] = $this->l('Unable to install module. Imagick extension not found on system.');

            return false;
        }

        Configuration::updateGlobalValue(self::IMAGICK_ENABLED, true);
        Configuration::updateGlobalValue(self::IMAGICK_PROGRESSIVE_JPEG, true);
        Configuration::updateGlobalValue(self::IMAGICK_TRIM_WHITESPACE, false);
        Configuration::updateGlobalValue(self::IMAGICK_FUZZ, 0);
        Configuration::updateGlobalValue(self::IMAGICK_BLUR, 1);
        Configuration::updateGlobalValue(self::IMAGICK_FILTER, Imagick::FILTER_LANCZOS);
        Configuration::updateGlobalValue(self::IMAGICK_STRIP_ICC_PROFILE, true);
        Configuration::updateGlobalValue(self::IMAGICK_PNG_DATA_ENCODING, self::PNG_ADAPTIVE);
        Configuration::updateGlobalValue(self::ORIGINAL_COPY, false);

        return parent::install();
    }

    /**
     * Uninstall this module
     *
     * @return bool Whether this module was successfully uninstalled
     */
    public function uninstall()
    {
        Configuration::deleteByName(self::IMAGICK_ENABLED);
        Configuration::deleteByName(self::IMAGICK_PROGRESSIVE_JPEG);
        Configuration::deleteByName(self::IMAGICK_TRIM_WHITESPACE);
        Configuration::deleteByName(self::IMAGICK_FUZZ);
        Configuration::deleteByName(self::IMAGICK_BLUR);
        Configuration::deleteByName(self::IMAGICK_FILTER);
        Configuration::deleteByName(self::IMAGICK_STRIP_ICC_PROFILE);
        Configuration::deleteByName(self::IMAGICK_PNG_DATA_ENCODING);
        Configuration::deleteByName(self::ORIGINAL_COPY);

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (!extension_loaded('imagick')) {
            return $this->displayError($this->l('Imagick extension has not been enabled'));
        }

        $output = '';
        foreach ($this->detectBOSettingsErrors() as $error) {
            $output .= $this->displayError($error);
        }
        $output .= $this->postProcess();
        
        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderImagickOptions();
    }

    /**
     * Render Imagick HelperOptions
     *
     * @return string HTML
     */
    protected function renderImagickOptions()
    {
        $helper = new HelperOptions();
        $helper->module = $this;
        $helper->id = 1;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;

        return $helper->generateOptions($this->getImagickOptions());
    }

    /**
     * Get Imagick Options
     *
     * @return array Options array
     */
    protected function getImagickOptions()
    {
        $imagick_filters = array();
        if (defined('Imagick::FILTER_BARTLETT')) {
            $imagick_filters[] = array(
                'name' => 'Bartlett',
                'value' => Imagick::FILTER_BARTLETT,
            );
        }
        if (defined('Imagick::FILTER_BESSEL')) {
            $imagick_filters[] = array(
                'name' => 'Bessel',
                'value' => Imagick::FILTER_BESSEL,
            );
        }
        if (defined('Imagick::FILTER_BLACKMAN')) {
            $imagick_filters[] = array(
                'name' => 'Blackman',
                'value' => Imagick::FILTER_BLACKMAN,
            );
        }
        if (defined('Imagick::FILTER_BOX')) {
            $imagick_filters[] = array(
                'name' => 'Box',
                'value' => Imagick::FILTER_BOX,
            );
        }
        if (defined('Imagick::FILTER_CATROM')) {
            $imagick_filters[] = array(
                'name' => 'Catrom',
                'value' => Imagick::FILTER_CATROM,
            );
        }
        if (defined('Imagick::FILTER_COSINE')) {
            $imagick_filters[] = array(
                'name' => 'Cosine',
                'value' => Imagick::FILTER_COSINE,
            );
        }
        if (defined('Imagick::FILTER_CUBIC')) {
            $imagick_filters[] = array(
                'name' => 'Cubic',
                'value' => Imagick::FILTER_CUBIC,
            );
        }
        if (defined('Imagick::FILTER_GAUSSIAN')) {
            $imagick_filters[] = array(
                'name' => 'Gaussian',
                'value' => Imagick::FILTER_GAUSSIAN,
            );
        }
        if (defined('Imagick::FILTER_HAMMING')) {
            $imagick_filters[] = array(
                'name' => 'Hamming',
                'value' => Imagick::FILTER_HAMMING,
            );
        }
        if (defined('Imagick::FILTER_HANNING')) {
            $imagick_filters[] = array(
                'name' => 'Hanning',
                'value' => Imagick::FILTER_HANNING,
            );
        }
        if (defined('Imagick::FILTER_HERMITE')) {
            $imagick_filters[] = array(
                'name' => 'Hermite',
                'value' => Imagick::FILTER_HERMITE,
            );
        }
        if (defined('Imagick::FILTER_JINC')) {
            $imagick_filters[] = array(
                'name' => 'Jinc',
                'value' => Imagick::FILTER_JINC,
            );
        }
        if (defined('Imagick::FILTER_KAISER')) {
            $imagick_filters[] = array(
                'name' => 'Kaiser',
                'value' => Imagick::FILTER_KAISER,
            );
        }
        if (defined('Imagick::FILTER_LAGRANGE')) {
            $imagick_filters[] = array(
                'name' => 'Lagrange',
                'value' => Imagick::FILTER_LAGRANGE,
            );
        }
        if (defined('Imagick::FILTER_LANCZOS')) {
            $imagick_filters[] = array(
                'name' => 'Lanczos',
                'value' => Imagick::FILTER_LANCZOS,
            );
        }
        if (defined('Imagick::FILTER_LANCZOSSHARP')) {
            $imagick_filters[] = array(
                'name' => 'Lanczos Sharp',
                'value' => Imagick::FILTER_LANCZOSSHARP,
            );
        }
        if (defined('Imagick::FILTER_LANCZOS2')) {
            $imagick_filters[] = array(
                'name' => 'Lanczos2',
                'value' => Imagick::FILTER_LANCZOS2,
            );
        }
        if (defined('Imagick::FILTER_LANCZOS2SHARP')) {
            $imagick_filters[] = array(
                'name' => 'Lanczos2 Sharp',
                'value' => Imagick::FILTER_LANCZOS2SHARP,
            );
        }
        if (defined('Imagick::FILTER_MITCHELL')) {
            $imagick_filters[] = array(
                'name' => 'Mitchell',
                'value' => Imagick::FILTER_MITCHELL,
            );
        }
        if (defined('Imagick::FILTER_PARZEN')) {
            $imagick_filters[] = array(
                'name' => 'Parzen',
                'value' => Imagick::FILTER_PARZEN,
            );
        }
        if (defined('Imagick::FILTER_POINT')) {
            $imagick_filters[] = array(
                'name' => 'Point',
                'value' => Imagick::FILTER_POINT,
            );
        }
        if (defined('Imagick::FILTER_QUADRATIC')) {
            $imagick_filters[] = array(
                'name' => 'Quadratic',
                'value' => Imagick::FILTER_QUADRATIC,
            );
        }
        if (defined('Imagick::FILTER_ROBIDOUX')) {
            $imagick_filters[] = array(
                'name' => 'Robidoux',
                'value' => Imagick::FILTER_ROBIDOUX,
            );
        }
        if (defined('Imagick::FILTER_ROBIDOUXSHARP')) {
            $imagick_filters[] = array(
                'name' => 'Robidoux Sharp',
                'value' => Imagick::FILTER_ROBIDOUXSHARP,
            );
        }
        if (defined('Imagick::FILTER_SINC')) {
            $imagick_filters[] = array(
                'name' => 'Sinc',
                'value' => Imagick::FILTER_SINC,
            );
        }
        if (defined('Imagick::FILTER_BOX')) {
            $imagick_filters[] = array(
                'name' => 'Triangle',
                'value' => Imagick::FILTER_TRIANGLE,
            );
        }
        if (defined('Imagick::FILTER_UNDEFINED')) {
            $imagick_filters[] = array(
                'name' => 'Undefined',
                'value' => Imagick::FILTER_UNDEFINED,
            );
        }

        $png_compression = array(
            array(
                'name' => 'None',
                'value' => self::PNG_NONE,
            ),
            array(
                'name' => 'Sub',
                'value' => self::PNG_SUB,
            ),
            array(
                'name' => 'Up',
                'value' => self::PNG_UP,
            ),
            array(
                'name' => 'Average',
                'value' => self::PNG_AVERAGE,
            ),
            array(
                'name' => 'Paeth',
                'value' => self::PNG_PAETH,
            ),
            array(
                'name' => 'Adaptive',
                'value' => self::PNG_ADAPTIVE,
            ),
        );

        return array(
            'locales' => array(
                'title' => $this->l('ImageMagick Settings'),
                'icon' => 'icon-magic',
                'fields' => array(
                    self::IMAGICK_ENABLED => array(
                        'title' => $this->l('Enable ImageMagick'),
                        'type' => 'bool',
                        'name' => self::IMAGICK_ENABLED,
                        'value' => Configuration::get(self::IMAGICK_ENABLED),
                        'validation' => 'isBool',
                        'cast' => 'boolval',
                    ),
                    self::IMAGICK_PROGRESSIVE_JPEG => array(
                        'title' => $this->l('Use progressive JPEGs'),
                        'type' => 'bool',
                        'name' => self::IMAGICK_PROGRESSIVE_JPEG,
                        'value' => Configuration::get(self::IMAGICK_PROGRESSIVE_JPEG),
                        'validation' => 'isBool',
                        'cast' => 'boolval',
                    ),
                    self::IMAGICK_STRIP_ICC_PROFILE => array(
                        'title' => $this->l('Strip image'),
                        'desc' => $this->l('Convert to sRGB, remove the ICC profile and EXIF data'),
                        'type' => 'bool',
                        'name' => self::IMAGICK_STRIP_ICC_PROFILE,
                        'value' => Configuration::get(self::IMAGICK_STRIP_ICC_PROFILE),
                        'validation' => 'isBool',
                        'cast' => 'boolval',
                    ),
                    self::IMAGICK_FILTER => array(
                        'title' => $this->l('Resize filter type'),
                        'desc' => $this->l('Choose one of the available ImageMagick resize filters'),
                        'type' => 'select',
                        'list' => $imagick_filters,
                        'identifier' => 'value',
                        'name' => self::IMAGICK_FILTER,
                        'value' => Configuration::get(self::IMAGICK_FILTER),
                    ),
                    self::IMAGICK_PNG_DATA_ENCODING => array(
                        'title' => $this->l('PNG data encoding filter'),
                        'desc' => sprintf($this->l('Choose the preferred PNG data encoding filter (before compression). More info: %s'), '<a href="http://www.imagemagick.org/Usage/formats/#png_quality" target="_blank">PNG compression</a>'),
                        'type' => 'select',
                        'list' => $png_compression,
                        'identifier' => 'value',
                        'name' => self::IMAGICK_PNG_DATA_ENCODING,
                        'value' => Configuration::get(self::IMAGICK_PNG_DATA_ENCODING),
                    ),
                    self::IMAGICK_BLUR => array(
                        'title' => $this->l('Blur'),
                        'desc' => sprintf($this->l('Sharpen/Blur the image. More info: %s'), '<a href="http://php.net/manual/en/imagick.resizeimage.php" target="_blank">Imagick resizeImage</a>'),
                        'type' => 'text',
                        'class' => 'fixed-width-lg',
                        'name' => self::IMAGICK_BLUR,
                        'value' => Configuration::get(self::IMAGICK_BLUR),
                        'validation' => 'isInt',
                        'cast' => 'intval',
                    ),
                    self::IMAGICK_TRIM_WHITESPACE => array(
                        'title' => $this->l('Trim whitespace'),
                        'desc' => $this->l('Trim whitespace from images'),
                        'type' => 'bool',
                        'name' => self::IMAGICK_TRIM_WHITESPACE,
                        'value' => Configuration::get(self::IMAGICK_TRIM_WHITESPACE),
                        'validation' => 'isBool',
                        'cast' => 'boolval',
                    ),
                    self::IMAGICK_FUZZ => array(
                        'title' => $this->l('Fuzz'),
                        'desc' => sprintf($this->l('Sets the fuzz parameter (percentage). More info: %s'), '<a href="http://www.imagemagick.org/Usage/bugs/fuzz_distance/" target="_blank">Fuzz distance</a>'),
                        'type' => 'text',
                        'suffix' => '%',
                        'class' => 'fixed-width-lg',
                        'name' => self::IMAGICK_FUZZ,
                        'value' => Configuration::get(self::IMAGICK_FUZZ),
                        'validation' => 'isInt',
                        'cast' => 'intval',
                        'max' => 100,
                        'min' => 0
                    ),
                    self::ORIGINAL_COPY => array(
                        'title' => $this->l('Original copy'),
                        'desc' => $this->l('Keep the original copy on the server. By default PrestaShop encodes the image immediately after uploading and twice when resizing. By enabling this option, encoding right after the upload will be disabled.'),
                        'type' => 'bool',
                        'name' => self::ORIGINAL_COPY,
                        'value' => Configuration::get(self::ORIGINAL_COPY),
                        'validation' => 'isBool',
                        'cast' => 'boolval',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'button'
                ),
            ),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $output = '';

        if (Tools::isSubmit('submitOptionsconfiguration')) {
            $output .= $this->postProcessImagickOptions();
        }

        return $output;
    }

    /**
     * Process ImageMagickOptions
     */
    protected function postProcessImagickOptions()
    {
        $imagick_enabled = (bool)Tools::getValue(self::IMAGICK_ENABLED);
        $imagick_progressive_jpg = (bool)Tools::getValue(self::IMAGICK_PROGRESSIVE_JPEG);
        $imagick_trim_whitespace = (bool)Tools::getValue(self::IMAGICK_TRIM_WHITESPACE);
        $imagick_fuzz = (int)Tools::getValue(self::IMAGICK_FUZZ);
        if ($imagick_fuzz < 0) {
            $imagick_fuzz = 0;
        }
        if ($imagick_fuzz > 100) {
            $imagick_fuzz = 100;

        }
        $_POST[self::IMAGICK_FUZZ] = $imagick_fuzz;
        $imagick_blur = (float)Tools::getValue(self::IMAGICK_BLUR);
        if ($imagick_blur < 0) {
            $imagick_blur = 1;
        }
        $imagick_filter = (int)Tools::getValue(self::IMAGICK_FILTER);
        $imagick_strip_icc_profile = (bool)Tools::getValue(self::IMAGICK_STRIP_ICC_PROFILE);
        $imagick_png_data_encoding = (int)Tools::getValue(self::IMAGICK_PNG_DATA_ENCODING);
        $original_copy = (bool)Tools::getValue(self::ORIGINAL_COPY);

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            if (Shop::getContext() == Shop::CONTEXT_ALL) {
                $this->updateAllValue(self::IMAGICK_ENABLED, $imagick_enabled);
                $this->updateAllValue(self::IMAGICK_PROGRESSIVE_JPEG, $imagick_progressive_jpg);
                $this->updateAllValue(self::IMAGICK_TRIM_WHITESPACE, $imagick_trim_whitespace);
                $this->updateAllValue(self::IMAGICK_FUZZ, $imagick_fuzz);
                $this->updateAllValue(self::IMAGICK_BLUR, $imagick_blur);
                $this->updateAllValue(self::IMAGICK_FILTER, $imagick_filter);
                $this->updateAllValue(self::IMAGICK_STRIP_ICC_PROFILE, $imagick_strip_icc_profile);
                $this->updateAllValue(self::IMAGICK_PNG_DATA_ENCODING, $imagick_png_data_encoding);
                $this->updateAllValue(self::ORIGINAL_COPY, $original_copy);
            } elseif (is_array(Tools::getValue('multishopOverrideOption'))) {
                $id_shop_group = (int)Shop::getGroupFromShop($this->getShopId(), true);
                $multishop_override = Tools::getValue('multishopOverrideOption');
                if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                    foreach (Shop::getShops(false, $this->getShopId()) as $id_shop) {
                        if ($multishop_override[self::IMAGICK_ENABLED]) {
                            Configuration::updateValue(self::IMAGICK_ENABLED, $imagick_enabled, false, $id_shop_group, $id_shop);
                        }
                        if ($multishop_override[self::IMAGICK_PROGRESSIVE_JPEG]) {
                            Configuration::updateValue(self::IMAGICK_PROGRESSIVE_JPEG, $imagick_progressive_jpg, false, $id_shop_group, $id_shop);
                        }
                        if ($multishop_override[self::IMAGICK_TRIM_WHITESPACE]) {
                            Configuration::updateValue(self::IMAGICK_TRIM_WHITESPACE, $imagick_trim_whitespace, false, $id_shop_group, $id_shop);
                        }
                        if ($multishop_override[self::IMAGICK_FUZZ]) {
                            Configuration::updateValue(self::IMAGICK_FUZZ, $imagick_fuzz, false, $id_shop_group, $id_shop);
                        }
                        if ($multishop_override[self::IMAGICK_BLUR]) {
                            Configuration::updateValue(self::IMAGICK_BLUR, $imagick_blur, false, $id_shop_group, $id_shop);
                        }
                        if ($multishop_override[self::IMAGICK_FILTER]) {
                            Configuration::updateValue(self::IMAGICK_FILTER, $imagick_filter, false, $id_shop_group, $id_shop);
                        }
                        if ($multishop_override[self::IMAGICK_STRIP_ICC_PROFILE]) {
                            Configuration::updateValue(self::IMAGICK_STRIP_ICC_PROFILE, $imagick_strip_icc_profile, false, $id_shop_group, $id_shop);
                        }
                        if ($multishop_override[self::IMAGICK_PNG_DATA_ENCODING]) {
                            Configuration::updateValue(self::IMAGICK_PNG_DATA_ENCODING, $imagick_png_data_encoding, false, $id_shop_group, $id_shop);
                        }
                        if ($multishop_override[self::ORIGINAL_COPY]) {
                            Configuration::updateValue(self::ORIGINAL_COPY, $original_copy, false, $id_shop_group, $id_shop);
                        }
                    }
                } else {
                    $id_shop = (int)$this->getShopId();
                    if ($multishop_override[self::IMAGICK_ENABLED]) {
                        Configuration::updateValue(self::IMAGICK_ENABLED, $imagick_enabled, false, $id_shop_group, $id_shop);
                    }
                    if ($multishop_override[self::IMAGICK_PROGRESSIVE_JPEG]) {
                        Configuration::updateValue(self::IMAGICK_PROGRESSIVE_JPEG, $imagick_progressive_jpg, false, $id_shop_group, $id_shop);
                    }
                    if ($multishop_override[self::IMAGICK_TRIM_WHITESPACE]) {
                        Configuration::updateValue(self::IMAGICK_TRIM_WHITESPACE, $imagick_trim_whitespace, false, $id_shop_group, $id_shop);
                    }
                    if ($multishop_override[self::IMAGICK_FUZZ]) {
                        Configuration::updateValue(self::IMAGICK_FUZZ, $imagick_fuzz, false, $id_shop_group, $id_shop);
                    }
                    if ($multishop_override[self::IMAGICK_BLUR]) {
                        Configuration::updateValue(self::IMAGICK_BLUR, $imagick_blur, false, $id_shop_group, $id_shop);
                    }
                    if ($multishop_override[self::IMAGICK_FILTER]) {
                        Configuration::updateValue(self::IMAGICK_FILTER, $imagick_filter, false, $id_shop_group, $id_shop);
                    }
                    if ($multishop_override[self::IMAGICK_STRIP_ICC_PROFILE]) {
                        Configuration::updateValue(self::IMAGICK_STRIP_ICC_PROFILE, $imagick_strip_icc_profile, false, $id_shop_group, $id_shop);
                    }
                    if ($multishop_override[self::IMAGICK_PNG_DATA_ENCODING]) {
                        Configuration::updateValue(self::IMAGICK_PNG_DATA_ENCODING, $imagick_png_data_encoding, false, $id_shop_group, $id_shop);
                    }
                    if ($multishop_override[self::ORIGINAL_COPY]) {
                        Configuration::updateValue(self::ORIGINAL_COPY, $original_copy, false, $id_shop_group, $id_shop);
                    }
                }
            }
        } else {
            Configuration::updateValue(self::IMAGICK_ENABLED, $imagick_enabled);
            Configuration::updateValue(self::IMAGICK_PROGRESSIVE_JPEG, $imagick_progressive_jpg);
            Configuration::updateValue(self::IMAGICK_TRIM_WHITESPACE, $imagick_trim_whitespace);
            Configuration::updateValue(self::IMAGICK_FUZZ, $imagick_fuzz);
            Configuration::updateValue(self::IMAGICK_BLUR, $imagick_blur);
            Configuration::updateValue(self::IMAGICK_FILTER, $imagick_filter);
            Configuration::updateValue(self::IMAGICK_STRIP_ICC_PROFILE, $imagick_strip_icc_profile);
            Configuration::updateValue(self::IMAGICK_PNG_DATA_ENCODING, $imagick_png_data_encoding);
            Configuration::updateValue(self::ORIGINAL_COPY, $original_copy);
        }
    }

    /**
     * Update configuration value in ALL contexts
     *
     * @param string $key Configuration key
     * @param mixed $values Configuration values, can be string or array with id_lang as key
     * @param bool $html Contains HTML
     */
    public function updateAllValue($key, $values, $html = false)
    {
        foreach (Shop::getShops() as $shop) {
            Configuration::updateValue($key, $values, $html, $shop['id_shop_group'], $shop['id_shop']);
        }
        Configuration::updateGlobalValue($key, $values, $html);
    }

    /**
     * Get the Shop ID of the current context
     * Retrieves the Shop ID from the cookie
     *
     * @return int Shop ID
     */
    public function getShopId()
    {
        $cookie = Context::getContext()->cookie->getFamily('shopContext');

        return (int)Tools::substr($cookie['shopContext'], 2, count($cookie['shopContext']));
    }

    /**
     * Add all methods in a module override to the override class
     *
     * @param string $classname
     * @return bool
     * @throws Exception
     */
    public function addOverride($classname)
    {
        $orig_path = $path = PrestaShopAutoload::getInstance()->getClassPath($classname.'Core');
        if (!$path) {
            $path = 'modules'.DIRECTORY_SEPARATOR.$classname.DIRECTORY_SEPARATOR.$classname.'.php';
        }
        $path_override = $this->getLocalPath().'override'.DIRECTORY_SEPARATOR.$path;
        if (!file_exists($path_override)) {
            return false;
        } else {
            file_put_contents($path_override, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($path_override)));
        }
        $pattern_escape_com = '#(^\s*?\/\/.*?\n|\/\*(?!\n\s+\* module:.*?\* date:.*?\* version:.*?\*\/).*?\*\/)#ism';
        // Check if there is already an override file, if not, we just need to copy the file
        if ($file = PrestaShopAutoload::getInstance()->getClassPath($classname)) {
            // Check if override file is writable
            $override_path = _PS_ROOT_DIR_.'/'.$file;
            if ((!file_exists($override_path) && !is_writable(dirname($override_path))) || (file_exists($override_path) && !is_writable($override_path))) {
                throw new Exception(sprintf(Tools::displayError('file (%s) not writable'), $override_path));
            }
            // Get a uniq id for the class, because you can override a class (or remove the override) twice in the same session and we need to avoid redeclaration
            do {
                $uniq = uniqid();
            } while (class_exists($classname.'OverrideOriginal_remove', false));
            // Make a reflection of the override class and the module override class
            $override_file = file($override_path);
            $override_file = array_diff($override_file, array("\n"));
            eval(preg_replace(array('#^\s*<\?(?:php)?#', '#class\s+'.$classname.'\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?#i'), array(' ', 'class '.$classname.'OverrideOriginal'.$uniq), implode('', $override_file)));
            $override_class = new ReflectionClass($classname.'OverrideOriginal'.$uniq);
            $module_file = file($path_override);
            $module_file = array_diff($module_file, array("\n"));
            eval(preg_replace(array('#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'), array(' ', 'class '.$classname.'Override'.$uniq), implode('', $module_file)));
            $module_class = new ReflectionClass($classname.'Override'.$uniq);
            // Check if none of the methods already exists in the override class
            foreach ($module_class->getMethods() as $method) {
                if ($override_class->hasMethod($method->getName())) {
                    $method_override = $override_class->getMethod($method->getName());
                    if (preg_match('/module: (.*)/ism', $override_file[$method_override->getStartLine() - 5], $name) && preg_match('/date: (.*)/ism', $override_file[$method_override->getStartLine() - 4], $date) && preg_match('/version: ([0-9.]+)/ism', $override_file[$method_override->getStartLine() - 3], $version)) {
                        throw new Exception(sprintf(Tools::displayError('The method %1$s in the class %2$s is already overridden by the module %3$s version %4$s at %5$s.'), $method->getName(), $classname, $name[1], $version[1], $date[1]));
                    }
                    throw new Exception(sprintf(Tools::displayError('The method %1$s in the class %2$s is already overridden.'), $method->getName(), $classname));
                }
                $module_file = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b'.$method->getName().'\b))/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override method %1$s in class %2$s.'), $method->getName(), $classname));
                }
            }
            // Check if none of the properties already exists in the override class
            foreach ($module_class->getProperties() as $property) {
                if ($override_class->hasProperty($property->getName())) {
                    throw new Exception(sprintf(Tools::displayError('The property %1$s in the class %2$s is already defined.'), $property->getName(), $classname));
                }
                $module_file = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b'.$property->getName().'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2$3", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override property %1$s in class %2$s.'), $property->getName(), $classname));
                }
            }
            foreach ($module_class->getConstants() as $constant => $value) {
                if ($override_class->hasConstant($constant)) {
                    throw new Exception(sprintf(Tools::displayError('The constant %1$s in the class %2$s is already defined.'), $constant, $classname));
                }
                $module_file = preg_replace('/(const\s)\s*(\b'.$constant.'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                }
            }
            // Insert the methods from module override in override
            $copy_from = array_slice($module_file, $module_class->getStartLine() + 1, $module_class->getEndLine() - $module_class->getStartLine() - 2);
            array_splice($override_file, $override_class->getEndLine() - 1, 0, $copy_from);
            $code = implode('', $override_file);
            file_put_contents($override_path, preg_replace($pattern_escape_com, '', $code));
        } else {
            $override_src = $path_override;
            $override_dest = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'override'.DIRECTORY_SEPARATOR.$path;
            $dir_name = dirname($override_dest);
            if (!$orig_path && !is_dir($dir_name)) {
                $oldumask = umask(0000);
                @mkdir($dir_name, 0777);
                umask($oldumask);
            }
            if (!is_writable($dir_name)) {
                throw new Exception(sprintf(Tools::displayError('directory (%s) not writable'), $dir_name));
            }
            $module_file = file($override_src);
            $module_file = array_diff($module_file, array("\n"));
            if ($orig_path) {
                do {
                    $uniq = uniqid();
                } while (class_exists($classname.'OverrideOriginal_remove', false));
                eval(preg_replace(array('#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'), array(' ', 'class '.$classname.'Override'.$uniq), implode('', $module_file)));
                $module_class = new ReflectionClass($classname.'Override'.$uniq);
                // For each method found in the override, prepend a comment with the module name and version
                foreach ($module_class->getMethods() as $method) {
                    $module_file = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b'.$method->getName().'\b))/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override method %1$s in class %2$s.'), $method->getName(), $classname));
                    }
                }
                // Same loop for properties
                foreach ($module_class->getProperties() as $property) {
                    $module_file = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b'.$property->getName().'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2$3", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override property %1$s in class %2$s.'), $property->getName(), $classname));
                    }
                }
                // Same loop for constants
                foreach ($module_class->getConstants() as $constant => $value) {
                    $module_file = preg_replace('/(const\s)\s*(\b'.$constant.'\b)/ism', "/*\n    * module: ".$this->name."\n    * date: ".date('Y-m-d H:i:s')."\n    * version: ".$this->version."\n    */\n    $1$2", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                    }
                }
            }
            file_put_contents($override_dest, preg_replace($pattern_escape_com, '', $module_file));
            // Re-generate the class index
            Tools::generateIndex();
        }
        return true;
    }

    /**
     * Remove all methods in a module override from the override class
     *
     * @param string $classname
     * @return bool
     */
    public function removeOverride($classname)
    {
        $orig_path = $path = PrestaShopAutoload::getInstance()->getClassPath($classname.'Core');
        if ($orig_path && !$file = PrestaShopAutoload::getInstance()->getClassPath($classname)) {
            return true;
        } elseif (!$orig_path && Module::getModuleIdByName($classname)) {
            $path = 'modules'.DIRECTORY_SEPARATOR.$classname.DIRECTORY_SEPARATOR.$classname.'.php';
        }
        // Check if override file is writable
        if ($orig_path) {
            $override_path = _PS_ROOT_DIR_.'/'.$file;
        } else {
            $override_path = _PS_OVERRIDE_DIR_.$path;
        }
        if (!is_file($override_path) || !is_writable($override_path)) {
            return false;
        }
        file_put_contents($override_path, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($override_path)));
        if ($orig_path) {
            // Get a uniq id for the class, because you can override a class (or remove the override) twice in the same session and we need to avoid redeclaration
            do {
                $uniq = uniqid();
            } while (class_exists($classname.'OverrideOriginal_remove', false));
            // Make a reflection of the override class and the module override class
            $override_file = file($override_path);
            eval(preg_replace(array('#^\s*<\?(?:php)?#', '#class\s+'.$classname.'\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?#i'), array(' ', 'class '.$classname.'OverrideOriginal_remove'.$uniq), implode('', $override_file)));
            $override_class = new ReflectionClass($classname.'OverrideOriginal_remove'.$uniq);
            $module_file = file($this->getLocalPath().'override/'.$path);
            eval(preg_replace(array('#^\s*<\?(?:php)?#', '#class\s+'.$classname.'(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'), array(' ', 'class '.$classname.'Override_remove'.$uniq), implode('', $module_file)));
            $module_class = new ReflectionClass($classname.'Override_remove'.$uniq);
            // Remove methods from override file
            foreach ($module_class->getMethods() as $method) {
                if (!$override_class->hasMethod($method->getName())) {
                    continue;
                }
                $method = $override_class->getMethod($method->getName());
                $length = $method->getEndLine() - $method->getStartLine() + 1;
                $module_method = $module_class->getMethod($method->getName());
                $module_length = $module_method->getEndLine() - $module_method->getStartLine() + 1;
                $override_file_orig = $override_file;
                $orig_content = preg_replace('/\s/', '', implode('', array_splice($override_file, $method->getStartLine() - 1, $length, array_pad(array(), $length, '#--remove--#'))));
                $module_content = preg_replace('/\s/', '', implode('', array_splice($module_file, $module_method->getStartLine() - 1, $length, array_pad(array(), $length, '#--remove--#'))));
                $replace = true;
                if (preg_match('/\* module: ('.$this->name.')/ism', $override_file[$method->getStartLine() - 5])) {
                    $override_file[$method->getStartLine() - 6] = $override_file[$method->getStartLine() - 5] = $override_file[$method->getStartLine() - 4] = $override_file[$method->getStartLine() - 3] = $override_file[$method->getStartLine() - 2] = '#--remove--#';
                    $replace = false;
                }
                if (md5($module_content) != md5($orig_content) && $replace) {
                    $override_file = $override_file_orig;
                }
            }
            // Remove properties from override file
            foreach ($module_class->getProperties() as $property) {
                if (!$override_class->hasProperty($property->getName())) {
                    continue;
                }
                // Replace the declaration line by #--remove--#
                foreach ($override_file as $line_number => &$line_content) {
                    if (preg_match('/(public|private|protected)\s+(static\s+)?(\$)?'.$property->getName().'/i', $line_content)) {
                        if (preg_match('/\* module: ('.$this->name.')/ism', $override_file[$line_number - 4])) {
                            $override_file[$line_number - 5] = $override_file[$line_number - 4] = $override_file[$line_number - 3] = $override_file[$line_number - 2] = $override_file[$line_number - 1] = '#--remove--#';
                        }
                        $line_content = '#--remove--#';
                        break;
                    }
                }
            }
            // Remove properties from override file
            foreach ($module_class->getConstants() as $constant => $value) {
                if (!$override_class->hasConstant($constant)) {
                    continue;
                }
                // Replace the declaration line by #--remove--#
                foreach ($override_file as $line_number => &$line_content) {
                    if (preg_match('/(const)\s+(static\s+)?(\$)?'.$constant.'/i', $line_content)) {
                        if (preg_match('/\* module: ('.$this->name.')/ism', $override_file[$line_number - 4])) {
                            $override_file[$line_number - 5] = $override_file[$line_number - 4] = $override_file[$line_number - 3] = $override_file[$line_number - 2] = $override_file[$line_number - 1] = '#--remove--#';
                        }
                        $line_content = '#--remove--#';
                        break;
                    }
                }
            }
            $count = count($override_file);
            for ($i = 0; $i < $count; ++$i) {
                if (preg_match('/(^\s*\/\/.*)/i', $override_file[$i])) {
                    $override_file[$i] = '#--remove--#';
                } elseif (preg_match('/(^\s*\/\*)/i', $override_file[$i])) {
                    if (!preg_match('/(^\s*\* module:)/i', $override_file[$i + 1])
                        && !preg_match('/(^\s*\* date:)/i', $override_file[$i + 2])
                        && !preg_match('/(^\s*\* version:)/i', $override_file[$i + 3])
                        && !preg_match('/(^\s*\*\/)/i', $override_file[$i + 4])) {
                        for (; $override_file[$i] && !preg_match('/(.*?\*\/)/i', $override_file[$i]); ++$i) {
                            $override_file[$i] = '#--remove--#';
                        }
                        $override_file[$i] = '#--remove--#';
                    }
                }
            }
            // Rewrite nice code
            $code = '';
            foreach ($override_file as $line) {
                if ($line == '#--remove--#') {
                    continue;
                }
                $code .= $line;
            }
            $to_delete = preg_match('/<\?(?:php)?\s+(?:abstract|interface)?\s*?class\s+'.$classname.'\s+extends\s+'.$classname.'Core\s*?[{]\s*?[}]/ism', $code);
        }
        if (!isset($to_delete) || $to_delete) {
            unlink($override_path);
        } else {
            file_put_contents($override_path, $code);
        }
        // Re-generate the class index
        Tools::generateIndex();
        return true;
    }

    /**
     * Detect Back Office settings
     *
     * @return array Array with error message strings
     */
    protected function detectBOSettingsErrors()
    {
        $lang_id = Context::getContext()->language->id;
        $output = array();
        if (Configuration::get('PS_DISABLE_NON_NATIVE_MODULE')) {
            $output[] = $this->l('Non native modules such as this one are disabled. Go to').' "'.
                $this->getTabName('AdminParentPreferences', $lang_id).
                ' > '.
                $this->getTabName('AdminPerformance', $lang_id).
                '" '.$this->l('and make sure that the option').' "'.
                Translate::getAdminTranslation('Disable non PrestaShop modules', 'AdminPerformance').
                '" '.$this->l('is set to').' "'.
                Translate::getAdminTranslation('No', 'AdminPerformance').
                '"'.$this->l('.').'<br />';
        }
        if (Configuration::get('PS_DISABLE_OVERRIDES')) {
            $output[] = $this->l('Overrides are disabled. Go to').' "'.
                $this->getTabName('AdminParentPreferences', $lang_id).
                ' > '.
                $this->getTabName('AdminPerformance', $lang_id).
                '" '.$this->l('and make sure that the option').' "'.
                Translate::getAdminTranslation('Disable non PrestaShop modules', 'AdminPerformance').
                '" '.$this->l('is set to').' "'.
                Translate::getAdminTranslation('No', 'AdminPerformance').
                '"'.$this->l('.').'<br />';
        }
        return $output;
    }

    /**
     * Get Tab name from database
     * @param $class_name string Class name of tab
     * @param $id_lang int Language id
     *
     * @return string Returns the localized tab name
     */
    protected function getTabName($class_name, $id_lang)
    {
        if ($class_name == null || $id_lang == null) {
            return '';
        }

        $sql = new DbQuery();
        $sql->select('tl.`name`');
        $sql->from('tab_lang', 'tl');
        $sql->innerJoin('tab', 't', 't.`id_tab` = tl.`id_tab`');
        $sql->where('t.`class_name` = \''.pSQL($class_name).'\'');
        $sql->where('tl.`id_lang` = '.(int)$id_lang);

        try {
            return (string)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        } catch (Exception $e) {
            return $this->l('Unknown');
        }
    }
}
