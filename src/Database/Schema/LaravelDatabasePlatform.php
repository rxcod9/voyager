<?php

namespace TCG\Voyager\Database\Schema;

class LaravelDatabasePlatform
{
    public function supportsForeignKeyConstraints()
    {
        return true;
    }

    public function getName()
    {
        return 'mysql';
    }

    public function getDoctrineTypeMapping()
    {
        return array_merge([
            \Doctrine\DBAL\Types\StringType::class => 'string',
            \Doctrine\DBAL\Types\TextType::class => 'text',
            \Doctrine\DBAL\Types\IntegerType::class => 'integer',
            \Doctrine\DBAL\Types\BigIntType::class => 'bigint',
            \Doctrine\DBAL\Types\SmallIntType::class => 'smallint',
            \Doctrine\DBAL\Types\BooleanType::class => 'boolean',
            \Doctrine\DBAL\Types\FloatType::class => 'float',
            \Doctrine\DBAL\Types\DecimalType::class => 'decimal',
            \Doctrine\DBAL\Types\DateTimeType::class => 'datetime',
            \Doctrine\DBAL\Types\DateTimeType::class => 'datetime',
            \Doctrine\DBAL\Types\DateType::class => 'date',
            \Doctrine\DBAL\Types\TimeType::class => 'time',
            \Doctrine\DBAL\Types\JsonType::class => 'json',
            \Doctrine\DBAL\Types\BinaryType::class => 'binary',
            \Doctrine\DBAL\Types\BlobType::class => 'blob',
            // Add other type mappings as needed
        ], self::$doctrineTypeMapping);
    }

    protected static array $doctrineTypeMapping = [];

    public function registerDoctrineTypeMapping($dbType, $name)
    {
        self::$doctrineTypeMapping[$dbType] = $name;
    }
}
