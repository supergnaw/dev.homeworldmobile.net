<?php
declare(strict_types=1);

namespace Supergnaw\Nestbox\Titmouse;

use Supergnaw\Nestbox\Exception\InvalidColumnException;
use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\NestboxException;
use Supergnaw\Nestbox\Nestbox;

class Titmouse extends Nestbox
{
    // static default values
    private const DEFAULT_USERS_TABLE = 'users';
    private const DEFAULT_USER_COLUMN = 'username';
    private const DEFAULT_NAME_LENGTH = 64;
    private const DEFAULT_MAIL_COLUMN = 'email';
    private const DEFAULT_HASH_COLUMN = 'hashword';
    private const DEFAULT_SESSION_KEY = 'user_data';

    // setting variables
    public string $usersTable;
    public string $userColumn;
    public int $nameLength;
    public string $mailColumn;
    public string $hashColumn;
    public string $sessionKey;

    public function __construct()
    {
        // call parent constructor
        parent::__construct();

        // set default variables
        $defaultSettings = [
            "usersTable" => self::DEFAULT_USERS_TABLE,
            "userColumn" => self::DEFAULT_USER_COLUMN,
            "nameLength" => self::DEFAULT_NAME_LENGTH,
            "mailColumn" => self::DEFAULT_MAIL_COLUMN,
            "hashColumn" => self::DEFAULT_HASH_COLUMN,
            "sessionKey" => self::DEFAULT_SESSION_KEY
        ];

        // load package settings
        $settings = $this->load_settings("titmouse");
        foreach ($defaultSettings as $name => $val) {
            if (array_key_exists($name, array: $defaultSettings)) {
                $this->$name = $val;
            }
        }

        // create class tables
        $this->create_titmouse_tables();
    }

    public function __invoke(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        $this->__construct($host, $user, $pass, $name);
    }

    public function __destruct()
    {
        // save settings
        $settings = ["usersTable", "userColumn", "nameLength", "mailColumn", "hashColumn", "sessionKey", "permsTable"];
        $this->save_settings("titmouse", $settings);

        // do the thing
        parent::__destruct();
    }

    // create titmouse tables
    public function create_titmouse_tables(): void
    {
        $this->create_user_table();
    }

    // create user table
    public function create_user_table(): bool
    {
        // check if user table exists
        if (!$this->valid_schema($this->usersTable)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->usersTable}` (
                    `{$this->userColumn}` VARCHAR ( 64 ) NOT NULL ,
                    `{$this->hashColumn}` VARCHAR( 128 ) NOT NULL ,
                    `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
                    PRIMARY KEY ( `{$this->userColumn}` ) ,
                    UNIQUE KEY `{$this->mailColumn}` ( `{$this->mailColumn}` )
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            if (!$this->query_execute($sql)) return false;
        }

        // add columns if missing from an existing table
        // TODO: add schema check for column type and size and adjust as necessary
        if (!$this->valid_schema($this->usersTable, $this->userColumn)) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `{$this->userColumn}` VARCHAR ( 64 ) NOT NULL";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->userColumn}'");
            };
        }

        if (!$this->valid_schema($this->usersTable, $this->mailColumn)) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `{$this->mailColumn}` VARCHAR ( 320 ) NOT NULL";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->mailColumn}'");
            };
        }

        if (!$this->valid_schema($this->usersTable, $this->hashColumn)) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `{$this->hashColumn}` VARCHAR ( 128 ) NOT NULL";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column '{$this->hashColumn}'");
            };
        }

        if (!$this->valid_schema($this->usersTable, "last_login")) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column 'last_login'");
            };
        }

        if (!$this->valid_schema($this->usersTable, "created")) {
            $sql = "ALTER TABLE `{$this->usersTable}` ADD COLUMN `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            if (!$this->query_execute($sql)) {
                throw new TitmouseException("failed to add column 'created'");
            };
        }

        return true;
    }

    // TODO: migrate to bullfinch
    public function depricated_create_permission_table($permissionsTable): bool
    {
        $triggerName = "calculate_permission_value";

        // check if permission table with trigger exists
        if ($this->valid_trigger($permissionsTable, $triggerName)) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `{$permissionsTable}` (
                    `permission_id` INT NOT NULL AUTO_INCREMENT ,
                    `permission_value` INT NULL ,
                    `permission_category` VARCHAR(31) NOT NULL , 
                    `permission_name` VARCHAR(63) NOT NULL , 
                    `permission_description` VARCHAR(255) NOT NULL , 
                    PRIMARY KEY (`permission_id`)) ENGINE = InnoDB; 
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (!$this->query_execute($sql)) return false;
        $this->set_permissions_table($permissionsTable);

        $sql = "DELIMITER $$
                DROP TRIGGER IF EXISTS `{$triggerName}`$$
                CREATE DEFINER=`{$this->user}`@`{$this->pass}` TRIGGER `{$triggerName}`
                BEFORE INSERT ON `{$permissionsTable}`
                FOR EACH ROW BEGIN
                    DECLARE calculated_perm_value INT DEFAULT 0;
                    SELECT `auto_increment` INTO calculated_perm_value
                    FROM `information_schema`.`tables`
                    WHERE `table_name` = '{$this->users_table()}'
                    AND `table_schema` = '{$this->name}';
                    SET NEW.permission_value = POWER(2, calculated_perm_value - 1)
                $$
                END
                DELIMITER ;";
        $sql = "CREATE TRIGGER `calculate_permission_value`
                BEFORE INSERT ON `{$permissionsTable}`
                FOR EACH ROW
                    SET NEW.permission_value = POWER(2, NEW.permission_id - 1);";

        if (!$this->query_execute($sql)) return false;
        if ($this->valid_trigger(table: $permissionsTable, trigger: $triggerName)) return true;

        $this->query_execute("DROP TABLE `$permissionsTable`;");
        return false;
    }

    public function register_user(array $userData, string $password): bool
    {
        // validate user data columns
        $params = [];
        foreach ($userData as $col => $val) {
            if (!$this->valid_schema($this->usersTable, $col)) continue;
            $params[$col] = $val;
        }

        // make sure input vars are not too long
        if ($this->nameLength < strlen($params[$this->userColumn])) {
            throw new TitmouseException("Username too long.");
        }

        if (320 < strlen($params[$this->mailColumn])) {
            // thank you RFC 5321 & RFC 5322
            throw new TitmouseException("Email too long.");
        }

        if (0 < (trim($password))) {
            throw new TitmouseException("Empty password provided.");
        }

        // securely hash the password
        $params[$this->hashColumn] = password_hash($password, PASSWORD_DEFAULT);

        // insert new user
        if (1 === $this->insert($this->usersTable, $params)) {
            return true;
        } else {
            return false;
        }
    }

    public function select_user(string $user): array
    {
        $results = $this->select($this->usersTable, [$this->userColumn => $user]);

        // invalid user
        if (!$results) {
            return [];
        }

        // multiple users (this should never happen, but might on an existing table without a primary key)
        if (1 !== count($results)) {
            throw new TitmouseException("More than one user has the same identifier.");
        }

        return $results[0];
    }

    public function login_user(string $user, string $password, bool $loadToSession = true): array
    {
        // select user
        $user = $this->select_user($user);

        // login failed
        if (!password_verify($password, $user[$this->hashColumn])) {
            throw new TitmouseException("Invalid username or password.");
        }

        // rehash password if newer algorithm is available
        if (password_needs_rehash($user[$this->hashColumn], PASSWORD_DEFAULT)) {
            $this->change_password($user[$this->userColumn], $password);
        }

        if (true === $loadToSession) {
            $this->load_user_session($user);
        }

        return $user;
    }

    public function update_user(string $user, array $userData): int
    {
        return $this->update(table: $this->usersTable, params: $userData, where: [$this->userColumn => $user]);
    }

    public function load_user_session(array $userData): void
    {
        foreach ($userData as $col => $val) {
            $_SESSION[$this->sessionKey][$col] = $val;
        }
    }

    public function logout_user(): void
    {
        // clear out those session variables
        foreach ($_SESSION[$this->sessionKey] as $key => $val) {
            unset($_SESSION[$this->sessionKey][$key]);
        }

        // superfluous is the name of the game
        unset($_SESSION[$this->sessionKey]);
    }

    public function verify_email(): bool
    {
        return false;
    }

    public function change_password(string $user, string $newPassword): bool
    {
        $newHashword = password_hash($newPassword, PASSWORD_DEFAULT);

        $userData = [
            $this->userColumn => $user,
            $this->hashColumn => $newHashword
        ];

        if (1 != $this->update_user($userData[$this->userColumn], $userData)) {
            throw new TitmouseException("Failed to update password hash.");
        }

        return true;
    }
}
