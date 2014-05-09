<?php 
/**
 * @file    auth.php
 *
 * User and Session Handling Library
 *
 * This file contains classes for session
 * handling. 
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * @todo look into http://www.openwall.com/articles/PHP-Users-Passwords
 */

namespace depage\Auth;

/**
 * contains functions for handling user authentication
 * and session handling.
 */
abstract class Auth {
    // {{{ variables
    public $realm = "depage::cms";
    public $sid, $uid;
    public $valid = false;
    public $sessionLifetime = 10800; // in seconds
    public $privateKey = "private Key";
    public $user = null;
    public $justLoggedOut = false;

    public $loginUrl = "login/";
    public $logoutUrl = "logout/";
    // }}}
    
    // {{{ factory()
    /**
     * factory method
     *
     * @public
     *
     * @param       depage\DB\PDO  $pdo        depage\DB\PDO object for database access
     * @param       string  $realm      realm to use for http-basic and http-digest auth
     * @param       domain  $domain     domain to use for cookie and auth validity
     *
     * @return      void
     */
    public static function factory($pdo, $realm, $domain, $method) {
        if ($method == "http_digest") {
            return new Methods\HttpDigest($pdo, $realm, $domain);
        } elseif ($method == "http_basic") {
            return new Methods\HttpBasic($pdo, $realm, $domain);
        } else {
            return new Methods\HttpCookie($pdo, $realm, $domain);
        }
    }
    // }}}
    
    // {{{ constructor()
    /**
     * constructor
     *
     * @public
     *
     * @param       depage\DB\PDO  $pdo        depage\DB\PDO object for database access
     * @param       string  $realm      realm to use for http-basic and http-digest auth
     * @param       domain  $domain     domain to use for cookie and auth validity
     *
     * @return      void
     */
    public function __construct($pdo, $realm, $domain) {
        $this->pdo = $pdo;
        $this->realm = $realm;
        $this->domain = $domain;

        if (class_exists("\\depage\\log\\log")) {
            $this->log = new \depage\log\log();
        }
    }
    // }}}
    // {{{ enforce()
    /**
     * enforces authentication 
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    abstract public function enforce();
    // }}}
    // {{{ enforceLazy()
    /**
     * enforces authentication 
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    abstract public function enforceLazy();
    // }}}
    // {{{ enforceLogout()
    /**
     * enforces authentication 
     *
     * @public
     *
     * @param       string  $method     method to use for authentication. Can be http
     * @return      void
     */
    abstract public function enforceLogout();
    // }}}
    
    // {{{ isValidSid()
    protected function isValidSid($sid) {
        $this->logoutTimedoutUsers();

        // test for validity
        $session_query = $this->pdo->prepare(
            "SELECT 
                sid, userid
            FROM 
                {$this->pdo->prefix}_auth_sessions
            WHERE
                sid = :sid AND
                ip = :ip
            LIMIT 1"
        );
        $session_query->execute(array(
            ':sid' => $sid,
            ':ip' => $_SERVER['REMOTE_ADDR'],
        ));
        $result = $session_query->fetchAll();

        if (count($result) > 0) {
            // set new timestamp
            $timestamp_query = $this->pdo->prepare(
                "UPDATE
                    {$this->pdo->prefix}_auth_sessions
                SET
                    last_update = NOW()
                WHERE
                    sid = :sid AND
                    ip = :ip"
            );
            $timestamp_query->execute(array(
                ':sid' => $sid,
                ':ip' => $_SERVER['REMOTE_ADDR'],
            ));

            $this->uid = $result[0]['userid'];
            $this->valid = true;

            return $this->uid;
        } else {
            $this->valid = false;

            return false;
        }
    }
    // }}}
    // {{{ setSid()
    function setSid($sid) {
        $this->sid = $sid;

        return $sid;
    }
    // }}}
    // {{{ getSid()
    protected function getSid() {
        if (!$this->valid) {
            if (!$this->isValidSid($this->sid)) {
                $this->registerSession();
            }
        }
        return $this->sid;
    }
    // }}}
    // {{{ getNewSid()
    protected function getNewSid() {
        $this->sid = md5(uniqid(dechex(mt_rand(256, 4095))));

        return $this->sid;
    }
    // }}}
    // {{{ uniqid16()
    /**
     * generates a uniqid, used for sessions.
     *
     * @public
     *
     * @return    $id (string) new id
     */
    protected function uniqid16() {
        return uniqid(dechex(mt_rand(256, 4095)));
    }
    // }}}
    // {{{ registerSession()
    protected function registerSession($uid = null, $sid = null) {
        if (is_null($sid)) {
            $this->sid = $this->getNewSid();
        } else {
            $this->sid = $sid;
        }
        if (is_null($uid)) {
            $update_query = $this->pdo->prepare(
                "REPLACE INTO
                    {$this->pdo->prefix}_auth_sessions
                SET
                    sid = :sid,
                    last_update = NOW(),
                    ip = :ip,
                    useragent = :useragent"
            )->execute(array(
                ':sid' => $this->sid,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
            ));
        } else {
            $this->uid = $uid;
            $update_query = $this->pdo->prepare(
                "REPLACE INTO
                    {$this->pdo->prefix}_auth_sessions
                SET
                    sid = :sid,
                    userid = :uid,
                    time_login = NOW(),
                    last_update = NOW(),
                    ip = :ip,
                    useragent = :useragent"
            )->execute(array(
                ':sid' => $this->sid,
                ':uid' => $this->uid,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
            ));


            // update time of last login in user-table
            $update_query = $this->pdo->prepare(
                "UPDATE 
                    {$this->pdo->prefix}_auth_user
                SET
                    date_lastlogin = NOW()
                WHERE
                    id = :uid"
            )->execute(array(
                ':uid' => $this->uid,
            ));
        }

        $this->valid = true;

        return $sid;
    }
    // }}}
    
    // {{{ getActiveUsers()
    function getActiveUsers() {
        $users = array();

        $this->logoutTimedoutUsers();

        // get logged in users
        $user_query = $this->pdo->prepare(
            "SELECT 
                user.id AS id,
                user.name as name,
                user.name_full as fullname,
                user.pass as passwordhash,
                user.email as email,
                user.settings as settings,
                user.level as level,
                sessions.project AS project, 
                sessions.ip AS ip, 
                sessions.last_update AS last_update, 
                sessions.useragent AS useragent
            FROM 
                {$this->pdo->prefix}_auth_user AS user, 
                {$this->pdo->prefix}_auth_sessions AS sessions
            WHERE 
                user.id=sessions.userid"
        );

        $user_query->execute();
        while ($user = $user_query->fetchObject("auth_user", array($this->pdo))) {
            $users[] = $user;
        }
        return $users;
    }
    // }}}
    
    // {{{ logoutTimedoutUsers()
    protected function logoutTimedoutUsers() {
        // remove users which login is outdated
        $outdated_query = $this->pdo->query(
            "SELECT 
                sid 
            FROM 
                {$this->pdo->prefix}_auth_sessions
            WHERE 
                last_update < DATE_SUB(NOW(), INTERVAL $this->sessionLifetime SECOND)"
        );
        $result = $outdated_query->fetchAll();

        foreach ($result as $s) {
            $this->logout($s['sid']);
        }
    }
    // }}}
    // {{{ logout()
    /**
     * logs user out
     *
     * @public
     *
     * @param    $sid (string) session id
     */
    public function logout($sid = null) {
        if ($sid == null) {
            $sid = $this->sid;

            //remove session
            $this->destroy_session();
        }

        // get user object for info
        $user = User::loadById($this->pdo, $sid);
        if ($user) {
            $user->logout($sid);
            $this->log->log("'{$user->name}' has logged out with $sid", "auth");
        }

        // delete session data for sid
        $delete_query = $this->pdo->prepare(
            "DELETE FROM 
                {$this->pdo->prefix}_auth_sessions
            WHERE 
                sid = :sid"
        );
        $delete_query->execute(array(
            ':sid' => $sid,
        ));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */