<?php

/**
 * @package Compressor
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2018, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace gplcart\modules\compressor\helpers;

use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Aggregates and minifies CSS/JS files
 * Inspired by Drupal's aggregator
 */
class Compressor
{

    /**
     * Base URL path
     * @var string
     */
    protected $base;

    /**
     * Base path for CSS url() attribute
     * @var string
     */
    protected $base_css;

    /**
     * Whether to optimize a CSS file
     * @var bool
     */
    protected $optimize_css;

    /**
     * Sets base URL path
     * @param $url
     * @return $this
     */
    public function setBase($url)
    {
        $this->base = $url;
        return $this;
    }

    /**
     * Aggregate an array of scripts into one compressed file
     * @param array $files
     * @param string $directory
     * @return string
     */
    public function getJs(array $files, $directory)
    {
        $key = $this->getKey($files);

        $filename = "js_$key.js";
        $uri = "$directory/$filename";

        if (file_exists($uri)) {
            return $uri;
        }

        $data = '';

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $contents .= ";\n";
            $data .= $contents;
        }

        $this->write($directory, $filename, $data);
        return $uri;
    }

    /**
     * Aggregate an array of stylesheets into one compressed file
     * @param array $files
     * @param string $directory
     * @return string
     * @throws InvalidArgumentException
     */
    public function getCss(array $files, $directory)
    {
        $key = $this->getKey($files);
        $filename = "css_$key.css";
        $uri = "$directory/$filename";

        if (file_exists($uri)) {
            return $uri;
        }

        if (!isset($this->base)) {
            throw new InvalidArgumentException('Base URL is not set');
        }

        $data = '';

        foreach ($files as $file) {

            $contents = $this->loadCss($file, true);

            // Build the base URL of this CSS file: start with the full URL.
            $url = "{$this->base}$file";

            // Move to the parent.
            $url = substr($url, 0, strrpos($url, '/'));

            $this->buildPathCss(null, "$url/");

            // Anchor all paths in the CSS with its base URL, ignoring external and absolute paths.
            $pattern = '/url\(\s*[\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\s*\)/i';
            $data .= preg_replace_callback($pattern, array($this, 'buildPathCss'), $contents);
        }

        // Per the W3C specification at http://www.w3.org/TR/REC-CSS2/cascade.html#at-import,
        // @import rules must proceed any other style, so we move those to the top.
        $regexp = '/@import[^;]+;/i';
        preg_match_all($regexp, $data, $matches);

        $data = preg_replace($regexp, '', $data);
        $data = implode('', $matches[0]) . $data;

        $this->write($directory, $filename, $data);
        return $uri;
    }

    /**
     * Processes the contents of a stylesheet for aggregation.
     * @param string $contents
     * @param bool $optimize
     * @return string
     * @todo remove second argument
     */
    protected function processCss($contents, $optimize = false)
    {
        // Remove multiple charset declarations for standards compliance (and fixing Safari problems).
        $contents = preg_replace('/^@charset\s+[\'"](\S*?)\b[\'"];/i', '', $contents);

        if ($optimize) {
            $contents = $this->optimizeCss($contents);
        }

        // Replaces @import commands with the actual stylesheet content.
        // This happens recursively but omits external files.
        $pattern = '/@import\s*(?:url\(\s*)?[\'"]?(?![a-z]+:)(?!\/\/)([^\'"\()]+)[\'"]?\s*\)?\s*;/';
        return preg_replace_callback($pattern, array($this, 'prepareCss'), $contents);
    }

    /**
     * Loads stylesheets recursively and returns contents with corrected paths.
     * @param array $matches
     * @return string
     */
    protected function prepareCss($matches)
    {
        $filename = $matches[1];

        // Load the imported stylesheet and replace @import commands in there as well.
        $file = $this->loadCss($filename, null, false);

        // Determine the file's directory.
        $directory = dirname($filename);

        // If the file is in the current directory, make sure '.' doesn't appear in
        // the url() path.
        $directory = $directory == '.' ? '' : $directory . '/';

        // Alter all internal url() paths. Leave external paths alone. We don't need
        // to normalize absolute paths here (i.e. remove folder/... segments) because
        // that will be done later.
        $pattern = '/url\(\s*([\'"]?)(?![a-z]+:|\/+)([^\'")]+)([\'"]?)\s*\)/i';

        return preg_replace($pattern, 'url(\1' . $directory . '\2\3)', $file);
    }

    /**
     * Loads the stylesheet and resolves all @import commands.
     * @param string $file
     * @param null|boolean $optimize
     * @param bool $reset_basepath
     * @return string
     * @throws UnexpectedValueException
     */
    public function loadCss($file, $optimize = null, $reset_basepath = true)
    {
        if ($reset_basepath) {
            $this->base_css = '';
        }

        // Store the value of $optimize_css for preg_replace_callback with nested
        // @import loops.
        if (isset($optimize)) {
            $this->optimize_css = $optimize;
        }

        // Stylesheets are relative one to each other. Start by adding a base path
        // prefix provided by the parent stylesheet (if necessary).
        if ($this->base_css && strpos($file, '://') === false) {
            $file = "{$this->base_css}/$file";
        }

        // Store the parent base path to restore it later.
        $parent_base_path = $this->base_css;

        // Set the current base path to process possible child imports.
        $this->base_css = dirname($file);

        // Load the CSS stylesheet
        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new UnexpectedValueException("Failed to read file $file");
        }

        $result = $this->processCss($contents, $this->optimize_css);

        // Restore the parent base path as the file and its childen are processed.
        $this->base_css = $parent_base_path;
        return $result;
    }

    /**
     * Performs some safe CSS optimizations
     * @param string $contents
     * @return string
     */
    public function optimizeCss($contents)
    {
        // Regexp to match comment blocks.
        $comment = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';

        // Regexp to match double quoted strings.
        $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';

        // Regexp to match single quoted strings.
        $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";

        // Strip all comment blocks, but keep double/single quoted strings.
        $contents = preg_replace(
            "<($double_quot|$single_quot)|$comment>Ss", "$1", $contents
        );

        // Remove certain whitespace.
        // There are different conditions for removing leading and trailing
        // whitespace.
        // @see http://php.net/manual/regexp.reference.subpatterns.php
        $contents = preg_replace('<
                # Strip leading and trailing whitespace.
                \s*([@{};,])\s*
                # Strip only leading whitespace from:
                # - Closing parenthesis: Retain "@media (bar) and foo".
                | \s+([\)])
                # Strip only trailing whitespace from:
                # - Opening parenthesis: Retain "@media (bar) and foo".
                # - Colon: Retain :pseudo-selectors.
                | ([\(:])\s+
                >xS',
            // Only one of the three capturing groups will match, so its reference
            // will contain the wanted value and the references for the
            // two non-matching groups will be replaced with empty strings.
            '$1$2$3', $contents
        );

        // End the file with a new line.
        $contents = trim($contents);
        $contents .= "\n";

        return $contents;
    }

    /**
     * Prefixes all paths within a CSS file
     * @param array|null $matches
     * @param null|string $basepath
     * @return string
     */
    protected function buildPathCss($matches, $basepath = null)
    {
        // Store base path for preg_replace_callback.
        if (isset($basepath)) {
            $this->base_css = $basepath;
        }

        // Prefix with base and remove '../' segments where possible.
        $path = $this->base_css . $matches[1];

        $last = '';

        while ($path != $last) {
            $last = $path;
            $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
        }

        return 'url(' . $path . ')';
    }

    /**
     * Creates a unique key from an array of files
     * @param array $files
     * @return string
     */
    protected function getKey(array $files)
    {
        return md5(json_encode($files));
    }

    /**
     * Writes an aggregated data to a file
     * @param string $directory
     * @param string $filename
     * @param string $data
     * @throws RuntimeException
     */
    protected function write($directory, $filename, $data)
    {
        if (!file_exists($directory) && !mkdir($directory, 0775, true)) {
            throw new RuntimeException("Failed to create directory $directory");
        }

        $file = "$directory/$filename";

        if (file_put_contents($file, $data) === false) {
            throw new RuntimeException("Failed to write to file $file");
        }
    }

}
