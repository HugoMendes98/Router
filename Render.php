<?php

class Render {
    private $scripts = [];
    private $styles = [];

    private $base_url = '';

    /**
     * @return array[string]
     */
    public function getScripts(): array {
        return $this->scripts;
    }

    /**
     * @return array[string]
     */
    public function getStyles(): array {
        return $this->styles;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string {
        return $this->base_url;
    }


    /**
     * Render constructor.
     * @param string $baseUrl
     */
    public function __construct(string $baseUrl = '') {
        $this->base_url = $baseUrl;
    }

    /**
     * set the base url if isn't an external file (need start with a '/')
     * @param string $file
     * @return string
     */
    private function pathFile(string $file): string {
        if ((!(substr($file, 0, 2) == '//'
            || substr($file, 0, 4) == "http")) && substr($file, 0, 1) == '/')
            $file = $this->getBaseUrl() . $file;
        return $file;
    }

    /**
     * Add a style file
     * @param string $style
     */
    public function addStyle(string $style) {
        if ($style != "")
            $this->styles[] = $this->pathFile($style);
    }

    /**
     * Add many style files
     * @param array[string] $styles
     */
    public function addStyles(array $styles) {
        foreach ($styles as $style) $this->addStyle($style);
    }

    /**
     * load all styles
     * @param array $styles
     */
    public function loadStyles(array $styles = []) {
        $this->addStyles($styles);
        foreach ($this->styles as $style) { ?>
            <link rel="stylesheet" type="text/css" href="<?= $style ?>">
        <?php }
    }

    /**
     * Add a script file
     * @param string $script
     */
    public function addScript(string $script) {
        if ($script != "")
            $this->scripts[] = $this->pathFile($script);
    }

    /**
     * Add many script files
     * @param array[string] $scripts
     */
    public function addScripts(array $scripts) {
        foreach ($scripts as $script) $this->addScript($script);
    }

    /**
     * load all scripts (try to call it one time)
     * @param array $scripts
     * @param array $consts add js consts (["varName" => value])
     */
    public function loadScripts(array $scripts = [], array $consts = []) {
        $this->addScripts($scripts);

        if (!empty($consts)) { ?>
            <script type="text/javascript"><?php
                foreach ($consts as $name => $value) echo "const $name = " . json_encode($value) . ";";
            ?></script>
        <?php }

        foreach ($this->scripts as $script) { ?>
           <script type="text/javascript" src="<?= $script ?>"></script>
        <?php }
    }

    /**
     * Load html meta ["metaname" => "value"]
     * @param array $metas
     */
    public function loadMeta(array $metas) {
        foreach ($metas as $key => $value) { ?>
            <meta name="<?= $key ?>" content="<?= $value ?>">
        <?php }
    }
}