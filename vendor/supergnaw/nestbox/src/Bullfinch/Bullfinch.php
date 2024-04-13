<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Titmouse;

use Supergnaw\Nestbox\Exception\InvalidTableException;
use Supergnaw\Nestbox\Exception\NestboxException;
use Supergnaw\Nestbox\Nestbox;

class Bullfinch extends Nestbox
{
    // settings variables
    public string $permissionsTable;
    public string $rolesTable;
    public string $permissionAssignmentsTable;
    public string $userColumn;
    public string $userGroup;

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

        // load package settings
        $this->load_settings(package: "bullfinch", defaultSettings: $defaultSettings);
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

    }
}