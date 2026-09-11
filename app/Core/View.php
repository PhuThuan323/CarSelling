<?php

namespace App\Core;

use Smarty;

/**
 * View Handler with Smarty Template Engine
 */
class View {
    private $smarty;

    public function __construct() {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir(__DIR__ . '/../../templates');
        $this->smarty->setCompileDir(__DIR__ . '/../../storage/templates_c');
        $this->smarty->setCacheDir(__DIR__ . '/../../storage/cache');
    }

    public function assign($key, $value) {
        $this->smarty->assign($key, $value);
    }

    public function display($template) {
        try {
            $this->smarty->display($template . '.tpl');
        } catch (\Exception $e) {
            die("Template Error: " . $e->getMessage());
        }
    }

    public function render($template) {
        try {
            return $this->smarty->fetch($template . '.tpl');
        } catch (\Exception $e) {
            return "Template Error: " . $e->getMessage();
        }
    }

    public function getSmarty() {
        return $this->smarty;
    }
}
