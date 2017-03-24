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

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class MDImageMagick
 *
 * @since 1.0.0
 */
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
        $this->version = '1.3.0';
        $this->author = 'Michael Dekker & Robert Andersson';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ImageMagick');
        $this->description = $this->l('Add ImageMagick support to thirty bees');

        $this->tb_versions_compliancy = '^1.0.0';
    }

    /**
     * Install this module
     *
     * @return bool Whether this module was successfully installed
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
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
     *
     * @return string
     *
     * @since 1.0.0
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
     * Get the Shop ID of the current context
     * Retrieves the Shop ID from the cookie
     *
     * @return int Shop ID
     */
    public function getShopId()
    {
        $cookie = Context::getContext()->cookie->getFamily('shopContext');

        return (int) Tools::substr($cookie['shopContext'], 2, count($cookie['shopContext']));
    }

    /**
     * Update configuration value in ALL contexts
     *
     * @param string $key    Configuration key
     * @param mixed  $values Configuration values, can be string or array with id_lang as key
     * @param bool   $html   Contains HTML
     */
    public function updateAllValue($key, $values, $html = false)
    {
        foreach (Shop::getShops() as $shop) {
            Configuration::updateValue($key, $values, $html, $shop['id_shop_group'], $shop['id_shop']);
        }
        Configuration::updateGlobalValue($key, $values, $html);
    }

    /**
     * Get Tab name from database
     *
     * @param $className string Class name of tab
     * @param $idLang    int Language id
     *
     * @return string Returns the localized tab name
     */
    protected function getTabName($className, $idLang)
    {
        if ($className == null || $idLang == null) {
            return '';
        }

        $sql = new DbQuery();
        $sql->select('tl.`name`');
        $sql->from('tab_lang', 'tl');
        $sql->innerJoin('tab', 't', 't.`id_tab` = tl.`id_tab`');
        $sql->where('t.`class_name` = \''.pSQL($className).'\'');
        $sql->where('tl.`id_lang` = '.(int) $idLang);

        try {
            return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        } catch (Exception $e) {
            return $this->l('Unknown');
        }
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
        $imagickEnabled = (bool) Tools::getValue(self::IMAGICK_ENABLED);
        $imagickProgressiveJpg = (bool) Tools::getValue(self::IMAGICK_PROGRESSIVE_JPEG);
        $imagickTrimWhitespace = (bool) Tools::getValue(self::IMAGICK_TRIM_WHITESPACE);
        $imagickFuzz = (int) Tools::getValue(self::IMAGICK_FUZZ);
        if ($imagickFuzz < 0) {
            $imagickFuzz = 0;
        }
        if ($imagickFuzz > 100) {
            $imagickFuzz = 100;

        }
        $_POST[self::IMAGICK_FUZZ] = $imagickFuzz;
        $imagickBlur = (float) Tools::getValue(self::IMAGICK_BLUR);
        if ($imagickBlur < 0) {
            $imagickBlur = 1;
        }
        $imagickFilter = (int) Tools::getValue(self::IMAGICK_FILTER);
        $imagickStripIccProfile = (bool) Tools::getValue(self::IMAGICK_STRIP_ICC_PROFILE);
        $imagickPngDataEncoding = (int) Tools::getValue(self::IMAGICK_PNG_DATA_ENCODING);
        $originalCopy = (bool) Tools::getValue(self::ORIGINAL_COPY);

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            if (Shop::getContext() == Shop::CONTEXT_ALL) {
                $this->updateAllValue(self::IMAGICK_ENABLED, $imagickEnabled);
                $this->updateAllValue(self::IMAGICK_PROGRESSIVE_JPEG, $imagickProgressiveJpg);
                $this->updateAllValue(self::IMAGICK_TRIM_WHITESPACE, $imagickTrimWhitespace);
                $this->updateAllValue(self::IMAGICK_FUZZ, $imagickFuzz);
                $this->updateAllValue(self::IMAGICK_BLUR, $imagickBlur);
                $this->updateAllValue(self::IMAGICK_FILTER, $imagickFilter);
                $this->updateAllValue(self::IMAGICK_STRIP_ICC_PROFILE, $imagickStripIccProfile);
                $this->updateAllValue(self::IMAGICK_PNG_DATA_ENCODING, $imagickPngDataEncoding);
                $this->updateAllValue(self::ORIGINAL_COPY, $originalCopy);
            } elseif (is_array(Tools::getValue('multishopOverrideOption'))) {
                $idShopGroup = (int) Shop::getGroupFromShop($this->getShopId(), true);
                $multishopOverride = Tools::getValue('multishopOverrideOption');
                if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                    foreach (Shop::getShops(false, $this->getShopId()) as $idShop) {
                        if ($multishopOverride[self::IMAGICK_ENABLED]) {
                            Configuration::updateValue(self::IMAGICK_ENABLED, $imagickEnabled, false, $idShopGroup, $idShop);
                        }
                        if ($multishopOverride[self::IMAGICK_PROGRESSIVE_JPEG]) {
                            Configuration::updateValue(self::IMAGICK_PROGRESSIVE_JPEG, $imagickProgressiveJpg, false, $idShopGroup, $idShop);
                        }
                        if ($multishopOverride[self::IMAGICK_TRIM_WHITESPACE]) {
                            Configuration::updateValue(self::IMAGICK_TRIM_WHITESPACE, $imagickTrimWhitespace, false, $idShopGroup, $idShop);
                        }
                        if ($multishopOverride[self::IMAGICK_FUZZ]) {
                            Configuration::updateValue(self::IMAGICK_FUZZ, $imagickFuzz, false, $idShopGroup, $idShop);
                        }
                        if ($multishopOverride[self::IMAGICK_BLUR]) {
                            Configuration::updateValue(self::IMAGICK_BLUR, $imagickBlur, false, $idShopGroup, $idShop);
                        }
                        if ($multishopOverride[self::IMAGICK_FILTER]) {
                            Configuration::updateValue(self::IMAGICK_FILTER, $imagickFilter, false, $idShopGroup, $idShop);
                        }
                        if ($multishopOverride[self::IMAGICK_STRIP_ICC_PROFILE]) {
                            Configuration::updateValue(self::IMAGICK_STRIP_ICC_PROFILE, $imagickStripIccProfile, false, $idShopGroup, $idShop);
                        }
                        if ($multishopOverride[self::IMAGICK_PNG_DATA_ENCODING]) {
                            Configuration::updateValue(self::IMAGICK_PNG_DATA_ENCODING, $imagickPngDataEncoding, false, $idShopGroup, $idShop);
                        }
                        if ($multishopOverride[self::ORIGINAL_COPY]) {
                            Configuration::updateValue(self::ORIGINAL_COPY, $originalCopy, false, $idShopGroup, $idShop);
                        }
                    }
                } else {
                    $idShop = (int) $this->getShopId();
                    if ($multishopOverride[self::IMAGICK_ENABLED]) {
                        Configuration::updateValue(self::IMAGICK_ENABLED, $imagickEnabled, false, $idShopGroup, $idShop);
                    }
                    if ($multishopOverride[self::IMAGICK_PROGRESSIVE_JPEG]) {
                        Configuration::updateValue(self::IMAGICK_PROGRESSIVE_JPEG, $imagickProgressiveJpg, false, $idShopGroup, $idShop);
                    }
                    if ($multishopOverride[self::IMAGICK_TRIM_WHITESPACE]) {
                        Configuration::updateValue(self::IMAGICK_TRIM_WHITESPACE, $imagickTrimWhitespace, false, $idShopGroup, $idShop);
                    }
                    if ($multishopOverride[self::IMAGICK_FUZZ]) {
                        Configuration::updateValue(self::IMAGICK_FUZZ, $imagickFuzz, false, $idShopGroup, $idShop);
                    }
                    if ($multishopOverride[self::IMAGICK_BLUR]) {
                        Configuration::updateValue(self::IMAGICK_BLUR, $imagickBlur, false, $idShopGroup, $idShop);
                    }
                    if ($multishopOverride[self::IMAGICK_FILTER]) {
                        Configuration::updateValue(self::IMAGICK_FILTER, $imagickFilter, false, $idShopGroup, $idShop);
                    }
                    if ($multishopOverride[self::IMAGICK_STRIP_ICC_PROFILE]) {
                        Configuration::updateValue(self::IMAGICK_STRIP_ICC_PROFILE, $imagickStripIccProfile, false, $idShopGroup, $idShop);
                    }
                    if ($multishopOverride[self::IMAGICK_PNG_DATA_ENCODING]) {
                        Configuration::updateValue(self::IMAGICK_PNG_DATA_ENCODING, $imagickPngDataEncoding, false, $idShopGroup, $idShop);
                    }
                    if ($multishopOverride[self::ORIGINAL_COPY]) {
                        Configuration::updateValue(self::ORIGINAL_COPY, $originalCopy, false, $idShopGroup, $idShop);
                    }
                }
            }
        } else {
            Configuration::updateValue(self::IMAGICK_ENABLED, $imagickEnabled);
            Configuration::updateValue(self::IMAGICK_PROGRESSIVE_JPEG, $imagickProgressiveJpg);
            Configuration::updateValue(self::IMAGICK_TRIM_WHITESPACE, $imagickTrimWhitespace);
            Configuration::updateValue(self::IMAGICK_FUZZ, $imagickFuzz);
            Configuration::updateValue(self::IMAGICK_BLUR, $imagickBlur);
            Configuration::updateValue(self::IMAGICK_FILTER, $imagickFilter);
            Configuration::updateValue(self::IMAGICK_STRIP_ICC_PROFILE, $imagickStripIccProfile);
            Configuration::updateValue(self::IMAGICK_PNG_DATA_ENCODING, $imagickPngDataEncoding);
            Configuration::updateValue(self::ORIGINAL_COPY, $originalCopy);
        }
    }

    /**
     * Render Imagick HelperOptions
     *
     * @return string HTML
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
     */
    protected function getImagickOptions()
    {
        $imagickFilters = [];
        if (defined('Imagick::FILTER_BARTLETT')) {
            $imagickFilters[] = [
                'name'  => 'Bartlett',
                'value' => Imagick::FILTER_BARTLETT,
            ];
        }
        if (defined('Imagick::FILTER_BESSEL')) {
            $imagickFilters[] = [
                'name'  => 'Bessel',
                'value' => Imagick::FILTER_BESSEL,
            ];
        }
        if (defined('Imagick::FILTER_BLACKMAN')) {
            $imagickFilters[] = [
                'name'  => 'Blackman',
                'value' => Imagick::FILTER_BLACKMAN,
            ];
        }
        if (defined('Imagick::FILTER_BOX')) {
            $imagickFilters[] = [
                'name'  => 'Box',
                'value' => Imagick::FILTER_BOX,
            ];
        }
        if (defined('Imagick::FILTER_CATROM')) {
            $imagickFilters[] = [
                'name'  => 'Catrom',
                'value' => Imagick::FILTER_CATROM,
            ];
        }
        if (defined('Imagick::FILTER_COSINE')) {
            $imagickFilters[] = [
                'name'  => 'Cosine',
                'value' => Imagick::FILTER_COSINE,
            ];
        }
        if (defined('Imagick::FILTER_CUBIC')) {
            $imagickFilters[] = [
                'name'  => 'Cubic',
                'value' => Imagick::FILTER_CUBIC,
            ];
        }
        if (defined('Imagick::FILTER_GAUSSIAN')) {
            $imagickFilters[] = [
                'name'  => 'Gaussian',
                'value' => Imagick::FILTER_GAUSSIAN,
            ];
        }
        if (defined('Imagick::FILTER_HAMMING')) {
            $imagickFilters[] = [
                'name'  => 'Hamming',
                'value' => Imagick::FILTER_HAMMING,
            ];
        }
        if (defined('Imagick::FILTER_HANNING')) {
            $imagickFilters[] = [
                'name'  => 'Hanning',
                'value' => Imagick::FILTER_HANNING,
            ];
        }
        if (defined('Imagick::FILTER_HERMITE')) {
            $imagickFilters[] = [
                'name'  => 'Hermite',
                'value' => Imagick::FILTER_HERMITE,
            ];
        }
        if (defined('Imagick::FILTER_JINC')) {
            $imagickFilters[] = [
                'name'  => 'Jinc',
                'value' => Imagick::FILTER_JINC,
            ];
        }
        if (defined('Imagick::FILTER_KAISER')) {
            $imagickFilters[] = [
                'name'  => 'Kaiser',
                'value' => Imagick::FILTER_KAISER,
            ];
        }
        if (defined('Imagick::FILTER_LAGRANGE')) {
            $imagickFilters[] = [
                'name'  => 'Lagrange',
                'value' => Imagick::FILTER_LAGRANGE,
            ];
        }
        if (defined('Imagick::FILTER_LANCZOS')) {
            $imagickFilters[] = [
                'name'  => 'Lanczos',
                'value' => Imagick::FILTER_LANCZOS,
            ];
        }
        if (defined('Imagick::FILTER_LANCZOSSHARP')) {
            $imagickFilters[] = [
                'name'  => 'Lanczos Sharp',
                'value' => Imagick::FILTER_LANCZOSSHARP,
            ];
        }
        if (defined('Imagick::FILTER_LANCZOS2')) {
            $imagickFilters[] = [
                'name'  => 'Lanczos2',
                'value' => Imagick::FILTER_LANCZOS2,
            ];
        }
        if (defined('Imagick::FILTER_LANCZOS2SHARP')) {
            $imagickFilters[] = [
                'name'  => 'Lanczos2 Sharp',
                'value' => Imagick::FILTER_LANCZOS2SHARP,
            ];
        }
        if (defined('Imagick::FILTER_MITCHELL')) {
            $imagickFilters[] = [
                'name'  => 'Mitchell',
                'value' => Imagick::FILTER_MITCHELL,
            ];
        }
        if (defined('Imagick::FILTER_PARZEN')) {
            $imagickFilters[] = [
                'name'  => 'Parzen',
                'value' => Imagick::FILTER_PARZEN,
            ];
        }
        if (defined('Imagick::FILTER_POINT')) {
            $imagickFilters[] = [
                'name'  => 'Point',
                'value' => Imagick::FILTER_POINT,
            ];
        }
        if (defined('Imagick::FILTER_QUADRATIC')) {
            $imagickFilters[] = [
                'name'  => 'Quadratic',
                'value' => Imagick::FILTER_QUADRATIC,
            ];
        }
        if (defined('Imagick::FILTER_ROBIDOUX')) {
            $imagickFilters[] = [
                'name'  => 'Robidoux',
                'value' => Imagick::FILTER_ROBIDOUX,
            ];
        }
        if (defined('Imagick::FILTER_ROBIDOUXSHARP')) {
            $imagickFilters[] = [
                'name'  => 'Robidoux Sharp',
                'value' => Imagick::FILTER_ROBIDOUXSHARP,
            ];
        }
        if (defined('Imagick::FILTER_SINC')) {
            $imagickFilters[] = [
                'name'  => 'Sinc',
                'value' => Imagick::FILTER_SINC,
            ];
        }
        if (defined('Imagick::FILTER_BOX')) {
            $imagickFilters[] = [
                'name'  => 'Triangle',
                'value' => Imagick::FILTER_TRIANGLE,
            ];
        }
        if (defined('Imagick::FILTER_UNDEFINED')) {
            $imagickFilters[] = [
                'name'  => 'Undefined',
                'value' => Imagick::FILTER_UNDEFINED,
            ];
        }

        $pngCompression = [
            [
                'name'  => 'None',
                'value' => self::PNG_NONE,
            ],
            [
                'name'  => 'Sub',
                'value' => self::PNG_SUB,
            ],
            [
                'name'  => 'Up',
                'value' => self::PNG_UP,
            ],
            [
                'name'  => 'Average',
                'value' => self::PNG_AVERAGE,
            ],
            [
                'name'  => 'Paeth',
                'value' => self::PNG_PAETH,
            ],
            [
                'name'  => 'Adaptive',
                'value' => self::PNG_ADAPTIVE,
            ],
        ];

        return [
            'locales' => [
                'title'  => $this->l('ImageMagick Settings'),
                'icon'   => 'icon-magic',
                'fields' => [
                    self::IMAGICK_ENABLED           => [
                        'title'      => $this->l('Enable ImageMagick'),
                        'type'       => 'bool',
                        'name'       => self::IMAGICK_ENABLED,
                        'value'      => Configuration::get(self::IMAGICK_ENABLED),
                        'validation' => 'isBool',
                        'cast'       => 'boolval',
                    ],
                    self::IMAGICK_PROGRESSIVE_JPEG  => [
                        'title'      => $this->l('Use progressive JPEGs'),
                        'type'       => 'bool',
                        'name'       => self::IMAGICK_PROGRESSIVE_JPEG,
                        'value'      => Configuration::get(self::IMAGICK_PROGRESSIVE_JPEG),
                        'validation' => 'isBool',
                        'cast'       => 'boolval',
                    ],
                    self::IMAGICK_STRIP_ICC_PROFILE => [
                        'title'      => $this->l('Strip image'),
                        'desc'       => $this->l('Convert to sRGB, remove the ICC profile and EXIF data'),
                        'type'       => 'bool',
                        'name'       => self::IMAGICK_STRIP_ICC_PROFILE,
                        'value'      => Configuration::get(self::IMAGICK_STRIP_ICC_PROFILE),
                        'validation' => 'isBool',
                        'cast'       => 'boolval',
                    ],
                    self::IMAGICK_FILTER            => [
                        'title'      => $this->l('Resize filter type'),
                        'desc'       => $this->l('Choose one of the available ImageMagick resize filters'),
                        'type'       => 'select',
                        'list'       => $imagickFilters,
                        'identifier' => 'value',
                        'name'       => self::IMAGICK_FILTER,
                        'value'      => Configuration::get(self::IMAGICK_FILTER),
                    ],
                    self::IMAGICK_PNG_DATA_ENCODING => [
                        'title'      => $this->l('PNG data encoding filter'),
                        'desc'       => sprintf($this->l('Choose the preferred PNG data encoding filter (before compression). More info: %s'), '<a href="http://www.imagemagick.org/Usage/formats/#png_quality" target="_blank">PNG compression</a>'),
                        'type'       => 'select',
                        'list'       => $pngCompression,
                        'identifier' => 'value',
                        'name'       => self::IMAGICK_PNG_DATA_ENCODING,
                        'value'      => Configuration::get(self::IMAGICK_PNG_DATA_ENCODING),
                    ],
                    self::IMAGICK_BLUR              => [
                        'title'      => $this->l('Blur'),
                        'desc'       => sprintf($this->l('Sharpen/Blur the image. More info: %s'), '<a href="http://php.net/manual/en/imagick.resizeimage.php" target="_blank">Imagick resizeImage</a>'),
                        'type'       => 'text',
                        'class'      => 'fixed-width-lg',
                        'name'       => self::IMAGICK_BLUR,
                        'value'      => Configuration::get(self::IMAGICK_BLUR),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                    ],
                    self::IMAGICK_TRIM_WHITESPACE   => [
                        'title'      => $this->l('Trim whitespace'),
                        'desc'       => $this->l('Trim whitespace from images'),
                        'type'       => 'bool',
                        'name'       => self::IMAGICK_TRIM_WHITESPACE,
                        'value'      => Configuration::get(self::IMAGICK_TRIM_WHITESPACE),
                        'validation' => 'isBool',
                        'cast'       => 'boolval',
                    ],
                    self::IMAGICK_FUZZ              => [
                        'title'      => $this->l('Fuzz'),
                        'desc'       => sprintf($this->l('Sets the fuzz parameter (percentage). More info: %s'), '<a href="http://www.imagemagick.org/Usage/bugs/fuzz_distance/" target="_blank">Fuzz distance</a>'),
                        'type'       => 'text',
                        'suffix'     => '%',
                        'class'      => 'fixed-width-lg',
                        'name'       => self::IMAGICK_FUZZ,
                        'value'      => Configuration::get(self::IMAGICK_FUZZ),
                        'validation' => 'isInt',
                        'cast'       => 'intval',
                        'max'        => 100,
                        'min'        => 0,
                    ],
                    self::ORIGINAL_COPY             => [
                        'title'      => $this->l('Original copy'),
                        'desc'       => $this->l('Keep the original copy on the server. By default thirty bees encodes the image immediately after uploading and twice when resizing. By enabling this option, encoding right after the upload will be disabled.'),
                        'type'       => 'bool',
                        'name'       => self::ORIGINAL_COPY,
                        'value'      => Configuration::get(self::ORIGINAL_COPY),
                        'validation' => 'isBool',
                        'cast'       => 'boolval',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button',
                ],
            ],
        ];
    }

    /**
     * Detect Back Office settings
     *
     * @return array Array with error message strings
     */
    protected function detectBOSettingsErrors()
    {
        $idLang = Context::getContext()->language->id;
        $output = [];
        if (Configuration::get('PS_DISABLE_NON_NATIVE_MODULE')) {
            $output[] = $this->l('Non native modules such as this one are disabled. Go to').' "'.$this->getTabName('AdminParentPreferences', $idLang).' > '.$this->getTabName('AdminPerformance', $idLang).'" '.$this->l('and make sure that the option').' "'.Translate::getAdminTranslation('Disable non thirty bees modules', 'AdminPerformance').'" '.$this->l('is set to').' "'.Translate::getAdminTranslation('No', 'AdminPerformance').'"'.$this->l('.').'<br />';
        }
        if (Configuration::get('PS_DISABLE_OVERRIDES')) {
            $output[] = $this->l('Overrides are disabled. Go to').' "'.$this->getTabName('AdminParentPreferences', $idLang).' > '.$this->getTabName('AdminPerformance', $idLang).'" '.$this->l('and make sure that the option').' "'.Translate::getAdminTranslation('Disable non thirty bees modules', 'AdminPerformance').'" '.$this->l('is set to').' "'.Translate::getAdminTranslation('No', 'AdminPerformance').'"'.$this->l('.').'<br />';
        }

        return $output;
    }
}
