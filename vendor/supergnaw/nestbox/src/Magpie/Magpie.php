<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Magpie;

use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\NestboxException;
use Supergnaw\Nestbox\Nestbox;

class Magpie extends Nestbox
{
    // settings variables
    public string $permissionsTable;
    public string $rolesTable;
    public string $permissionAssignmentsTable;
    public string $userColumn;
    public string $userGroup;

    // constructor
    public function __construct(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        // call parent constructor
        parent::__construct($host, $user, $pass, $name);

        // set default variables
        $defaultSettings = [
            "permissiontsTable" => 'permissions',
            "rolesTable" => 'roles',
            "permissionAssignmentsTable" => 'permission_assignments',
            "userColumn" => 'username',
            "userGroup" => 'usergroup'
        ];

        $this->load_settings(package: "lorikeet", defaultSettings: $defaultSettings);

        $this->settingNames = array_keys($defaultSettings);
    }

    public function __invoke(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        $this->__construct($host, $user, $pass, $name);
    }

    public function __destruct()
    {
        // save settings
        $this->save_settings(package: "lorikeet", settings: $this->settingNames);

        // do the thing
        parent::__destruct();
    }

    public function query_execute(string $query, array $params = [], bool $close = false): bool
    {
        try {
            return parent::query_execute($query, $params, $close);
        } catch (InvalidTableException) {
            $this->create_tables();
            return parent::query_execute($query, $params, $close);
        }
    }

    public function create_tables(): void
    {
        $this->create_permissions_table();
        $this->create_roles_table();
        $this->create_permission_assignments_table();
    }

    private function create_permissions_table(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->permissionsTable}` (
                    `permission_id` INT NOT NULL AUTO_INCREMENT ,
                    `permission_name` VARCHAR(63) NOT NULL ,
                    `permission_description` VARCHAR(255) NOT NULL ,
                    PRIMARY KEY (`permission_id`)) ENGINE = InnoDB; 
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";

        return $this->query_execute(query: $sql);
    }

    private function create_roles_table(): void
    {
        $sql = "";
    }

    private function create_permission_assignments_table(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->permissionAssignmentsTable}` (
                    `assignment_id` INT NOT NULL AUTO_INCREMENT ,
                    `permission_id` INT NOT NULL ,
                    `user_id` VARCHAR( 125 ) NOT NULL ,
                    PRIMARY KEY ( `assignment_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";

        return $this->query_execute(query: $sql);
    }

    private function create_role_assignments_table(): void
    {

    }

    public function permission_create(): bool
    {
        // create snake_case id from permission name
        // check no other permission with name exists
        // add permission to permissions table
        return true;
    }

    public function permission_rename(): bool
    {
        // check no other permission with name exists
        // update permission name and id
        // update permission assignments for roles
        // update permission assignments for users
        return true;
    }

    public function permission_delete(): bool
    {
        // delete permission form permissions table
        // delete assigned permissions from users
        // delete grouped permissions from roles
        return true;
    }

    public function role_create(): bool
    {
        // create snake_case id from role name
        // check no other role with name exists
        // add role to role table
        return true;
    }

    public function role_rename(): bool
    {
        // check no other role with name exists
        // update role name and id
        // update role assignment names for users
        return true;
    }

    public function role_delete(): bool
    {
        // delete role from roles table
        // delete assigned roles from users
        return true;
    }

    public function role_add_permission(): bool
    {
        return true;
    }

    public function role_remove_permission(): bool
    {
        return true;
    }

    public function user_add_permission(): bool
    {
        return true;
    }

    public function user_remove_permission(): bool
    {
        return true;
    }

    public function user_add_role(): bool
    {
        return true;
    }

    public function user_remove_role(): bool
    {
        return true;
    }
}
