<?php
/**
 * @link http://www.atomframework.net/
 * @copyright Copyright (c) 2017 Safarov Alisher
 * @license https://github.com/atomwares/atom-view/blob/master/LICENSE (MIT License)
 */

namespace Atom\View;

use RuntimeException;

/**
 * Class Renderer
 *
 * @package Atom\View
 */
class Renderer
{
    /**
     * @var string
     */
    protected $templatePath;
    /**
     * @var string
     */
    protected $templateExtension;
    /**
     * @var array
     */
    protected $attributes = [];
    /**
     * @var array
     */
    protected $blocks = [];
    /**
     * @var array
     */
    protected $inherits = [];
    /**
     * @var
     */
    protected $registerScriptFile;

    /**
     * Renderer constructor.
     *
     * @param string $templatePath
     * @param string $templateExtension
     * @param array $attributes
     */
    public function __construct(
        $templatePath = '',
        $templateExtension = 'php',
        array $attributes = []
    ) {
        $this->setPath($templatePath);
        $this->setTemplateExtension($templateExtension);
        $this->setAttributes($attributes);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->templatePath;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->templatePath = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateExtension()
    {
        return $this->templateExtension;
    }

    /**
     * @param string $extension
     *
     * @return $this
     */
    public function setTemplateExtension($extension)
    {
        $this->templateExtension = $extension;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getAttribute($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function removeAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }

        return $this;
    }

    /**
     * @param string $template
     */
    public function inherits($template)
    {
        $this->inherits[] = $template;
    }

    /**
     * @param string $name
     */
    public function beginBlock($name)
    {
        ob_start(function ($buffer) use ($name) {
            if (! isset($this->blocks[$name])) {
                $this->blocks[$name] = $buffer;
            }

            return $this->blocks[$name];
        });
    }

    /**
     *
     */
    public function endBlock()
    {
        ob_end_flush();
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return mixed|null
     */
    public function block($name, $default = null)
    {
        return isset($this->blocks[$name]) ? $this->blocks[$name] : $default;
    }

    /**
     * @param string $template
     * @param array $data
     *
     * @return string
     */
    public function render($template, array $data = [])
    {
        ob_start();
        $this->requireTemplate($template, $data);

        while ($extend = array_pop($this->inherits)) {
            ob_clean();
            $this->requireTemplate($extend, $data);
        }

        return ob_get_clean();
    }

    /**
     * @param string $template
     * @param array $data
     */
    protected function requireTemplate($template, $data = [])
    {
        $path = $this->templatePath !== '' ? $this->templatePath . DIRECTORY_SEPARATOR : '';
        $filename = "{$path}{$template}.{$this->templateExtension}";

        if (! is_file($filename)) {
            throw new RuntimeException("View cannot render `$template` because the template does not exist");
        }

        extract(array_merge($this->attributes, $data));

        require $filename;

        $this->attributes = array_merge(
            $this->attributes,
            array_diff_key(get_defined_vars(), [
                'template' => false,
                'data'     => false,
                'filename' => false,
            ])
        );
    }
}
