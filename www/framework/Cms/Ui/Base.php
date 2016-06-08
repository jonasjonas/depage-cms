<?php
/**
 * @file    framework/Cns/Ui/Base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class Base extends \Depage\Depage\Ui\Base
{
    protected $htmlOptions = [];
    protected $basetitle = "";
    protected $autoEnforceAuth = true;
    protected $authUser;
    protected $pdo;
    public $defaults = [
        'cache' => [
            'xmldb' => [
                'disposition' => "file",
                'host' => "",
            ],
        ],
    ];

    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        if (empty($this->pdo)) {
            // get database instance
            $this->pdo = new \Depage\Db\Pdo (
                $this->options->db->dsn, // dsn
                $this->options->db->user, // user
                $this->options->db->password, // password
                [
                    'prefix' => $this->options->db->prefix, // database prefix
                ]
            );
        }

        // register session handler
        \Depage\Session\SessionHandler::register($this->pdo);

        // get auth object
        $this->auth = \Depage\Auth\Auth::factory(
            $this->pdo, // db_pdo
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method, // method
            $this->options->auth->digestCompat // should we digest compatibility
        );

        // get cache object for xmldb
        $this->xmldbCache = \Depage\Cache\Cache::factory("xmldb", [
            'disposition' => $this->options->cache->xmldb->disposition,
            'host' => $this->options->cache->xmldb->host,
        ]);

        // set html-options
        $this->htmlOptions = [
            'template_path' => __DIR__ . "/../tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        ];
        $this->basetitle = \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion();

        // establish if the user is logged in
        if (empty($this->authUser)) {
            if ($this->autoEnforceAuth) {
                $this->authUser = $this->auth->enforce();
            } else {
                $this->authUser = $this->auth->enforceLazy();
            }
        }
    }
    // }}}
    // {{{ _package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function _package($output) {
        $output = parent::_package($output);

        // pack into base-html if output is html-object
        if (!isset($_REQUEST['ajax']) && is_object($output) && is_a($output, "Depage\Html\Html")) {
            // pack into body html
            $output = new Html("html.tpl", [
                'title' => $this->basetitle,
                'subtitle' => $output->title,
                'content' => [
                    $this->toolbar(),
                    $output,
                ]
            ], $this->htmlOptions);
        }

        return $output;
    }
    // }}}

    // {{{ getProject()
    /**
     * @brief getProject
     *
     * @param mixed $
     * @return void
     **/
    protected function getProject($projectName)
    {
        if ($projectName != "+") {
            return \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $this->projectName);
        } else {
            return "";
        }
    }
    // }}}

    // {{{ toolbar
    protected function toolbar() {
        if ($this->user = $this->auth->enforceLazy()) {
            $projectDisplayName = _("projects");
            if (!empty($this->projectName)) {
                $project = $this->getProject($this->projectName);
                $projectDisplayName = $project->fullname;
            }
            $h = new Html("toolbar_main.tpl", [
                'title' => $this->basetitle,
                'user' => $this->user,
                'projects' => \Depage\Cms\Project::loadByUser($this->pdo, $this->xmldbCache, $this->user),
                'projectname' => $projectDisplayName,
            ], $this->htmlOptions);
        } else {
            $h = new Html("toolbar_plain.tpl", [
                'title' => $this->basetitle,
            ], $this->htmlOptions);
        }

        return $h;
    }
    // }}}

    // {{{ notfound
    /**
     * function to call if action/function is not defined
     *
     * @return  null
     */
    public function notfound($function = "") {
        parent::notfound();

        $h = new Html("box.tpl", [
            'id' => "error",
            'class' => "first",
            'title' => "Error",
            'content' => new Html([
                'content' => 'url not found' . $function,
            ]),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ error
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function error($error, $env) {
        $content = parent::error($error, $env);

        $h = new Html("box.tpl", [
            'id' => "error",
            'class' => "first",
            'content' => new Html([
                'content' => $content,
            ]),
        ], $this->htmlOptions);

        return $this->_package($h);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
