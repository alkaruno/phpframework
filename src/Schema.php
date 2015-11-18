<?php

namespace Xplosio\PhpFramework;

class Schema
{
    public function migrate()
    {
        $config = include 'app/config/env.php';
        Db::__init($config['db']);

        $this->createMigrationsTableIfNotExists();

        $versions = array_map(function ($row) {
            return $row['version'];
        }, Db::getRows('SELECT version FROM migration'));

        $fileNames = $this->getMigrationsFiles();

        foreach (array_diff($fileNames, $versions) as $fileName) {
            $migration = $this->getMigration($fileName);
            Db::transaction(function () use ($migration, $fileName) {
                $migration->migrate();
                Db::insert('migration', ['version' => $fileName, 'apply_time' => time()]);
            });
            print 'Applied migration: ' . $fileName . PHP_EOL;
        }

        print 'Database updated' . PHP_EOL;
    }

    private function getMigrationsFiles()
    {
        $files = array_map(function ($file) {
            return str_replace('.php', '', basename($file));
        }, glob('app/migrations/*.php'));

        sort($files);

        return $files;
    }

    /**
     * @param $fileName
     * @return Migration
     */
    private function getMigration($fileName)
    {
        include "app/migrations/$fileName.php";
        $className = $this->getClassName($fileName);

        return new $className;
    }

    private function getClassName($fileName)
    {
        Assert::check(count($strings = explode('__', $fileName)) === 2, 'Illegal migration filename');

        return String::toCamelCase($strings[1], true) . 'Migration';
    }

    private function createMigrationsTableIfNotExists()
    {
        Db::query('
            CREATE TABLE IF NOT EXISTS `migration` (
              `version` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
              `apply_time` INT(10) UNSIGNED NOT NULL,
              PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');
    }
}

abstract class Migration
{
    abstract public function migrate();
}
