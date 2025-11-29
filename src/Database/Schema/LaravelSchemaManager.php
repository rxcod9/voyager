<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;

class LaravelSchemaManager
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function listTableNames()
    {
        return array_map(fn($item) => $item['name'], Schema::getTables());
    }

    public function listTableColumns($table)
    {
        return Schema::getColumns($table);
    }

    public function listTableIndexes($table)
    {
        return Schema::getIndexes($table);
    }

    public function listTableForeignKeys($table)
    {
        return Schema::getForeignKeys($table);
    }

    public static function tableExists($table)
    {
        foreach ($table as $t) {
            if (!Schema::hasTable($t)) {
                return false;
            }
        }

        return true;
    }
}
