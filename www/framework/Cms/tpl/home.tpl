<div class="edit layout layout-left">
    <header class="info">
        <h1><?php self::e(_("Dashboard")); ?></h1>
    </header>
    <div class="scrollable-content dashboard">
        <?php self::e($this->content); ?>

        <div class="box-help">
            <div class="content">
                <h2><?php self::t(_("Help")); ?></h2>
                <p><a href="<?php self::t($this->helpUrl); ?>" class="help" target="_blank"><?php self::t(_("Open online documentation")); ?></a></p>
            </div>
        </div>
    </div>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
