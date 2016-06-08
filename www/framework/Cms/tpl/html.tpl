<!DOCTYPE html>
<html>
    <head>
        <title><?php
            if ($this->subtitle != null) {
                self::t($this->subtitle . " / ");
            }
            self::t($this->title);
        ?></title>

        <base href="<?php self::base(); ?>">

        <?php $this->includeJs("interface", [
            "framework/Cms/js/interface.js",
            "framework/shared/jquery.cookie.js",
            "framework/HtmlForm/lib/js/effect.js",
            //"framework/shared/jquery.hotkeys.js",
            //"framework/Cms/js/jstree.js",
        ]); ?>
        <?php $this->includeCss("interface", [
            "framework/HtmlForm/lib/css/depage-forms.css",
            //"framework/Cms/css/jstree.css",
            "framework/Cms/css/interface.css",
        ]); ?>
    </head>
    <body>
        <?php self::e($this->content); ?>
    </body>
</html>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
