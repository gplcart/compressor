<?php

/**
 * @package Compressor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2018, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace gplcart\modules\compressor;

use gplcart\core\Container;
use gplcart\core\helpers\Request;
use gplcart\core\Module;

/**
 * Main class for Compressor module
 */
class Main
{
    /**
     * Module class instance
     * @var \gplcart\core\Module;
     */
    protected $module;

    /**
     * Request class instance
     * @var \gplcart\core\helpers\Request;
     */
    protected $request;

    /**
     * Main constructor.
     * @param Module $module
     * @param Request $request
     */
    public function __construct(Module $module, Request $request)
    {
        $this->module = $module;
        $this->request = $request;
    }

    /**
     * Implements hook "template.data"
     * @param array $data
     */
    public function hookTemplateData(array &$data)
    {
        $settings = $this->module->getSettings('compressor');

        if (!empty($settings['status_css'])) {
            gplcart_array_sort($data['_css']);
            $data['_css'] = $this->compress($data['_css'], 'css');
        }

        if (!empty($settings['status_js'])) {
            foreach (array('top', 'bottom') as $position) {
                gplcart_array_sort($data["_js_$position"]);
                $data["_js_$position"] = $this->compress($data["_js_$position"], 'js');
            }
        }
    }

    /**
     * Implements hook "route.list"
     * @param array $routes
     */
    public function hookRouteList(array &$routes)
    {
        $routes['admin/module/settings/compressor'] = array(
            'access' => 'module_edit',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\compressor\\controllers\\Settings', 'editSettings')
            )
        );
    }

    /**
     * Compress and aggregate an array of assets
     * @param array $assets
     * @param string $type
     * @return array
     */
    public function compress(array $assets, $type)
    {
        $group = 0;
        $groups = $results = array();
        $directory = GC_DIR_ASSET . "/compiled/$type";

        foreach ($assets as $key => $asset) {

            if ($this->isExcludedAsset($asset)) {
                $groups["__$group"] = $asset;
                $group++;
                continue;
            }

            if (!empty($asset['asset'])) {
                $groups[$group][$key] = $asset['asset'];
            }
        }

        $asset_helper = $this->getAssetHelper();
        $compressor_helper = $this->getCompressor()->setBase($this->request->base());

        foreach ($groups as $group => $contents) {

            if (strpos($group, '__') === 0) {
                $results[$group] = $contents;
                continue;
            }

            if ($type === 'js') {
                $aggregated = $compressor_helper->getJs($contents, $directory);
            } else if ($type === 'css') {
                $aggregated = $compressor_helper->getCss($contents, $directory);
            }

            if (!empty($aggregated)) {
                $asset = $asset_helper->build(array('asset' => $aggregated, 'version' => false));
                $results[$asset['key']] = $asset;
            }
        }

        return $results;
    }

    /**
     * Whether the asset is excluded from aggregation
     * @param array $asset
     * @return bool
     */
    protected function isExcludedAsset(array $asset)
    {
        if (!empty($asset['text']) || (isset($asset['type']) && $asset['type'] === 'external')) {
            return true;
        }

        $paths = array(
            'files/assets/system/js/common.js',
            'files/assets/vendor/jquery/jquery'
        );

        foreach ($paths as $path) {
            if (strpos($asset['asset'], $path) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Asset helper class instance
     * @return \gplcart\core\helpers\Asset
     */
    protected function getAssetHelper()
    {
        /** @var \gplcart\core\helpers\Asset $instance */
        $instance = Container::get('gplcart\\core\\helpers\\Asset');
        return $instance;
    }

    /**
     * Returns Compressor class instance
     * @return \gplcart\modules\compressor\helpers\Compressor
     */
    public function getCompressor()
    {
        /** @var \gplcart\modules\compressor\helpers\Compressor $instance */
        $instance = Container::get('gplcart\\modules\\compressor\\helpers\\Compressor');
        return $instance;
    }
}
