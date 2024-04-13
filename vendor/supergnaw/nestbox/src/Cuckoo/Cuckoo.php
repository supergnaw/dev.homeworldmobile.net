<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Titmouse;

use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\NestboxException;
use Supergnaw\Nestbox\Nestbox;

class Bullfinch extends Nestbox
{
    // settings variables
    public array $setting;

    public function __construct(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        // call parent constructor
        parent::__construct($host, $user, $pass, $name);

        // set default variables
        $defaultSettings = [
            "setting" => 'setting'
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

    private function create_tables()
    {
        $this->create_encryption_schema();
    }

    private function create_encryption_schema(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `cuckoo_encryption_schema` (
                    `schema_id` INT NOT NULL AUTO_INCREMENT ,
                    `table_name` VARCHAR( 128 ) NOT NULL ,
                    `column_name` VARCHAR( 128 ) NOT NULL ,
                    `encryption` VARCHAR( 16 ) NOT NULL ,
                    PRIMARY KEY ( `schema_id` ) ,
                    UNIQUE KEY `schema_key` ( `table_name`, `column_name` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";
        return $this->query_execute($sql);
    }

//    public function query_execute(string $query, array $params = [], bool $close = false): bool
//    {
//        try {
//            return parent::query_execute($query, $params, $close);
//        } catch (InvalidTableException) {
//            $this->create_tables();
//            return parent::query_execute($query, $params, $close);
//        }
//    }

//    public function create_tables(): void
//    {
//        $this->create_permissions_table();
//        $this->create_permission_assignments_table();
//    }

//    public function create_permissions_table($permissionsTable): bool
//    {
//        $sql = "CREATE TABLE IF NOT EXISTS `{$permissionsTable}` (
//                    `permission_id` INT NOT NULL AUTO_INCREMENT ,
//                    `permission_name` VARCHAR(63) NOT NULL ,
//                    `permission_description` VARCHAR(255) NOT NULL ,
//                    `permission_group` VARCHAR(31) NOT NULL ,
//                    PRIMARY KEY (`permission_id`)) ENGINE = InnoDB;
//                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";
//
//        return $this->query_execute(query: $sql);
//    }

//    public function create_permission_assignments_table(): bool
//    {
//        $sql = "CREATE TABLE IF NOT EXISTS `{$this->permissionAssignmentsTable}` (
//                    `assignment_id` INT NOT NULL AUTO_INCREMENT ,
//                    `permission_id` INT NOT NULL ,
//                    `user_id` VARCHAR( 125 ) NOT NULL ,
//                    PRIMARY KEY ( `assignment_id` )
//                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8_unicode_ci;";
//
//        return $this->query_execute(query: $sql);
//    }
}