<?php

/**
 * @package Compressor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2018, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace gplcart\modules\compressor\controllers;

use gplcart\core\controllers\backend\Controller;

/**
 * Handles incoming requests and outputs data related to Compressor module
 */
class Settings extends Controller
{

    /**
     * Route page callback to display the module settings page
     */
    public function editSettings()
    {
        $this->setTitleEditSettings();
        $this->setBreadcrumbEditSettings();

        $this->setData('settings', $this->getModuleSettings('compressor'));

        $this->submitSettings();
        $this->outputEditSettings();
    }

    /**
     * Set title on the module settings page
     */
    protected function setTitleEditSettings()
    {
        $title = $this->text('Edit %name settings', array('%name' => $this->text('Compressor')));
        $this->setTitle($title);
    }

    /**
     * Set breadcrumbs on the module settings page
     */
    protected function setBreadcrumbEditSettings()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'text' => $this->text('Dashboard'),
            'url' => $this->url('admin')
        );

        $breadcrumbs[] = array(
            'text' => $this->text('Modules'),
            'url' => $this->url('admin/module/list')
        );

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Saves the submitted settings
     */
    protected function submitSettings()
    {
        if ($this->isPosted('clear_cache')) {
            $this->clearCacheSettings();
        } else if ($this->isPosted('save') && $this->validateSettings()) {
            $this->updateSettings();
        }
    }

    /**
     * Validate submitted module settings
     * @return bool
     */
    protected function validateSettings()
    {
        $this->setSubmitted('settings');
        $this->setSubmittedBool('status_js');
        $this->setSubmittedBool('status_css');

        return !$this->hasErrors();
    }

    /**
     * Update module settings
     */
    protected function updateSettings()
    {
        $this->controlAccess('module_edit');
        $this->module->setSettings('compressor', $this->getSubmitted());
        $this->redirect('', $this->text('Settings have been updated'), 'success');
    }

    /**
     * Deletes all aggregated assets
     */
    protected function clearCacheSettings()
    {
        gplcart_file_delete_recursive(GC_DIR_ASSET . '/compiled');

        $this->redirect('', $this->text('Cache has been deleted'), 'success');
    }

    /**
     * Render and output the module settings page
     */
    protected function outputEditSettings()
    {
        $this->output('compressor|settings');
    }
}
