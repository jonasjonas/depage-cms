<div id="toolbarmain" class="toolbar">
    <menu class="left">
        <li><a <?php self::attr([
                "href" => " ",
                "class" => "home icon-depagecms",
                "data-live-help" => _("Go to dashboard"),
            ]); ?>>depage-cms</a></li>
    </menu>
    <menu class="preview">
        <!-- empty placeholder - content is added with javascript -->
    </menu>
    <menu class="right">
        <!-- add submenu for project -->
        <li><a <?php self::attr([
                "href" => "",
                "class" => "button icon-projects",
                "data-live-help" => _("Change current project"),
            ]); ?>>
                <?php if(!empty($this->project)) {
                    self::t($this->project->fullname);
                } else {
                    self::t(_("Projects"));
                } ?>
            </a>
            <menu class="popup">
                <?php if(!empty($this->project)) { ?>
                    <ul class="project-shortcuts">
                        <li>
                            <?php if (file_exists("projects/{$this->project->name}/lib/global/favicon.png")) { ?>
                                <img class="thumb" src="projects/<?php self::t($this->project->name); ?>/lib/global/favicon.png">
                            <?php } ?>
                            <?php self::t($this->project->fullname); ?>
                        </li>
                        <li>
                            <a href="project/<?php self::t($this->project->name); ?>/">
                                <?php self::t(_("Edit")); ?>
                            </a>
                        </li>
                        <li>
                            <a href="project/<?php self::t($this->project->name); ?>/library/"><?php self::t(_("Library")); ?></a>
                        </li>
                        <li>
                            <a href="project/<?php self::t($this->project->name); ?>/colors/"><?php self::t(_("Color Schemes")); ?></a>
                        </li>
                        <li>
                            <a href="project/<?php self::t($this->project->name); ?>/publish/"><?php self::t(_("Publish")); ?></a>
                        </li>
                        <li>
                            <a href="project/<?php self::t($this->project->name); ?>/settings/"><?php self::t(_("Settings")); ?></a>
                        </li>
                    </ul>
                <?php } ?>
                <ul class="projects">
                    <?php foreach($this->projects as $project) { ?>
                        <li class="project">
                            <a href="project/<?php self::t($project->name); ?>/">
                                <?php if (file_exists("projects/$project->name/lib/global/favicon.png")) { ?>
                                    <img class="thumb" src="projects/<?php self::t($project->name); ?>/lib/global/favicon.png">
                                <?php } ?>
                                <?php self::t($project->fullname); ?>
                            </a>
                            <a href="project/<?php self::t($project->name); ?>/settings/" class="right icon-settings"><?php self::t(_("Settings")); ?></a>
                        </li>
                    <?php } ?>
                </ul>
            </menu>
        </li>
        <!-- add submenu for user -->
        <li><a <?php self::attr([
                "href" => "user/{$this->user->name}/",
                "class" => "button icon-user",
                "data-live-help" => _("Change user settings or logout"),
            ]); ?>><?php self::t($this->user->fullname); ?></a>
            <menu class="popup">
                <ul>
                    <li><a href="user/<?php self::t($this->user->name); ?>/"><?php self::t(_("Account settings")); ?></a></li>
                </ul>
                <ul>
                    <li><a href="logout/" id="logout">logout</a></li>
                </ul>
            </menu>
        </li>
        <li><a <?php self::attr([
                "id" => "help",
                "class" => "button icon-help",
                "data-live-help" => _("Help"),
            ]); ?>>?</a></li>
    </menu>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
