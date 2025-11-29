<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Types\Type;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\Type as DoctrineType;

abstract class SchemaManager
{
    // todo: trim parameters

    public static function __callStatic($method, $args)
    {
        return static::manager()->$method(...$args);
    }

    public static function manager()
    {
        return new LaravelSchemaManager(DB::connection());
    }

    public static function getDatabasePlatform()
    {
        return new LaravelDatabasePlatform();
    }

    public static function getDatabaseConnection()
    {
        return DB::connection()->getPdo();
    }

    public static function tableExists($table)
    {
        if (!is_array($table)) {
            $table = [$table];
        }

        return static::manager()->tablesExist($table);
    }

    public static function listTables()
    {
        $tables = [];

        foreach (static::manager()->listTableNames() as $tableName) {
            $tables[$tableName] = static::listTableDetails($tableName);
        }

        return $tables;
    }

    /**
     * @param string $tableName
     *
     * @return \TCG\Voyager\Database\Schema\Table
     */
    public static function listTableDetails($tableName)
    {
        $laravelColumns = static::manager()->listTableColumns($tableName);
        $laravelIndexes = static::manager()->listTableIndexes($tableName);
        $laravelForeignKeys = static::manager()->listTableForeignKeys($tableName);

        // 2) Transform Laravel → Doctrine objects
        $columns = [];
        foreach ($laravelColumns as $col) {
            $columns[] = new Column(
                $col['name'],
                DoctrineType::getType(self::laravelToDoctrineType($col['type'])),
                [
                    'notnull' => !($col['nullable'] ?? false),
                    'length'  => $col['length'] ?? null,
                    'default' => $col['default'] ?? null,
                    'unsigned' => $col['unsigned'] ?? false,
                ]
            );
        }

        $indexes = [];
        foreach ($laravelIndexes as $idx) {
            $indexes[] = new Index(
                $idx['name'],
                $idx['columns'],
                strtolower($idx['type']) === 'unique',
                strtolower($idx['type']) === 'primary'
            );
        }

        $foreignKeys = [];
        foreach ($laravelForeignKeys as $fk) {
            $foreignKeys[] = new ForeignKeyConstraint(
                $fk['columns'],                     // local columns
                $fk['foreign_table'],               // foreign table name
                $fk['foreign_columns'],             // foreign columns
                $fk['name'] ?? null,                // constraint name
                [
                    'onDelete' => $fk['on_delete'] ?? null,
                    'onUpdate' => $fk['on_update'] ?? null,
                ]
            );
        }

        return new Table($tableName, $columns, $indexes, [], $foreignKeys, []);
    }

    protected static function laravelToDoctrineType(string $type): string
    {
        return match (strtolower($type)) {
            'bigint'      => 'bigint',
            'int', 'integer' => 'integer',
            'smallint'   => 'smallint',
            'tinyint'    => 'boolean',
            'varchar'    => 'string',
            'char'       => 'string',
            'text'       => 'text',
            'datetime'   => 'datetime',
            'timestamp'  => 'datetime',
            'date'       => 'date',
            'json'       => 'json',
            'float'      => 'float',
            'double'     => 'float',
            'decimal'    => 'decimal',
            'boolean'    => 'boolean',
            default      => 'string',
        };
    }

    /**
     * Resolve the type name from a Doctrine Type instance.
     *
     * @param \Doctrine\DBAL\Types\Type $type
     * @return string
     */
    public static function resolveTypeName(DoctrineType $type): string
    {
        // DBAL 4: types are represented by class name
        // Return short name to maintain Voyager compatibility

        $class = get_class($type);

        // e.g. Doctrine\DBAL\Types\StringType → string
        if (str_starts_with($class, 'Doctrine\\DBAL\\Types\\')) {
            return strtolower(str_replace('Type', '', substr($class, strrpos($class, '\\') + 1)));
        }

        // Custom type: fall back to class name
        return $class;
    }


    /**
     * Describes given table.
     *
     * @param string $tableName
     *
     * @return \Illuminate\Support\Collection
     */
    public static function describeTable($tableName)
    {
        Type::registerCustomPlatformTypes();

        $table = static::listTableDetails($tableName);

        return collect($table->columns)->map(function ($column) use ($table) {
            $columnArr = $column->toArray();

            $columnArr['field'] = $columnArr['name'];
            $columnArr['type'] = self::resolveTypeName($columnArr['type']);

            // Set the indexes and key
            $columnArr['indexes'] = [];
            $columnArr['key'] = null;
            if ($columnArr['indexes'] = $table->getColumnsIndexes($columnArr['name'], true)) {
                // Convert indexes to Array
                // foreach ($columnArr['indexes'] as $name => $index) {
                //     $columnArr['indexes'][$name] = $index->getIndexedColumns();
                // }

                // If there are multiple indexes for the column
                // the Key will be one with highest priority
                $indexType = array_values($columnArr['indexes'])[0]->getType()->name;
                // $columnArr['key'] = substr($indexType, 0, 3);
                $columnArr['key'] = $indexType;
            }

            return $columnArr;
        });
    }

    public static function listTableColumnNames($tableName)
    {
        Type::registerCustomPlatformTypes();

        $columnNames = [];

        foreach (static::manager()->listTableColumns($tableName) as $column) {
            $columnNames[] = $column->getName();
        }

        return $columnNames;
    }

    public static function createTable($table)
    {
        throw new \RuntimeException('Table creation is not supported in Laravel-native SchemaManager');
    }

    public static function getDoctrineTable($table)
    {
        throw new \RuntimeException('Doctrine API removed. Use listTableDetails instead.');
    }

    public static function getDoctrineColumn($table, $column)
    {
        throw new \RuntimeException('Doctrine API removed. Use listTableDetails instead.');
    }
}
