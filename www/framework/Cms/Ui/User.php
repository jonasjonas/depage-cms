<?php
/**
 * @file    framework/Cms/Ui/User.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class User extends Base
{
    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        $this->userName = $this->urlSubArgs[0];

        if (empty($this->userName)) {
            throw new \Depage\Auth\Exceptions\User("no user given");
        } else if ($this->userName == "+") {
            $this->user = new \Depage\Cms\Auth\DefaultUser($this->pdo);
        } else {
            $this->user = \Depage\Auth\User::loadByUsername($this->pdo, $this->userName);
        }

    }
    // }}}

    // {{{ index()
    function index() {
        if ($this->userName == "+") {
            return $this->edit();
        } else {
            return $this->edit();
        }
    }
    // }}}
    // {{{ edit()
    /**
     * @brief edit
     *
     * @param mixed
     * @return void
     **/
    protected function edit()
    {
        $form = new \Depage\Cms\Forms\User("edit-user-" . $this->user->id, array(
            "user" => $this->user,
            "authUser" => $this->authUser,
        ));
        $form->process();

        if ($form->validate()) {
            $values = $form->getValues();

            foreach ($values as $key => $val) {
                $this->user->$key = $val;
            }
            if ($values['password1'] !== "" && $values['password1'] == $values['password2']) {
                $pass = new \Depage\Auth\Password($this->auth->realm, $this->auth->digestCompat);
                $this->user->passwordhash = $pass->hash($user->name, $values['password1']);
            };

            $this->user->save();
            $form->clearSession();

            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        }

        if ($this->user->id != null) {
            $title = sprintf(_("Edit user '%s'"), $this->user->fullname);
        } else {
            $title = _("Add new User");
        }
        $h = new Html("box.tpl", array(
            'id' => "user",
            'icon' => "framework/Cms/images/icon_users.gif",
            'class' => "first",
            'title' => $title,
            'content' => array(
                $this->toolbar(),
                $form,
            ),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
