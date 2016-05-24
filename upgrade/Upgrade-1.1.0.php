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

function upgrade_module_1_1_0($module)
{
    /** @var MDImageMagick $module */
    $module->removeOverride('AdminProductsController');
    $module->removeOverride('ImageManager');

    // For those who love to upload modules through FTP
    @unlink(_PS_MODULE_DIR_.$module->name.'/override/controllers/admin/AdminProductsController.php');
    @unlink(_PS_MODULE_DIR_.$module->name.'/override/controllers/admin/index.php');
    @rmdir(_PS_MODULE_DIR_.$module->name.'/override/controllers/admin');
    @unlink(_PS_MODULE_DIR_.$module->name.'/override/controllers/index.php');
    @rmdir(_PS_MODULE_DIR_.$module->name.'/override/controllers');

    return $module->installOverrides();
}