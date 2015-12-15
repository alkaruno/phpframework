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

        foreach (array_diff($this->getMigrationsFiles(), $versions) as $fileName) {
            Db::transaction(function () use ($fileName) {
                if (String::endsWith($fileName, '.php')) {
                    $migration = $this->getMigration($fileName);
                    $migration->migrate();
                } elseif (String::endsWith($fileName, '.sql')) {
                    Db::query(file_get_contents("app/migrations/$fileName"));
                }
                Db::insert('migration', ['version' => $fileName, 'apply_time' => time()]);
            });
            print 'Applied migration: ' . $fileName . PHP_EOL;
        }

        print 'Database updated' . PHP_EOL;
    }

    private function getMigrationsFiles()
    {
        $files = array_map(function ($file) {
            return pathinfo($file, PATHINFO_BASENAME);
        }, glob('app/migrations/*.{php,sql}', GLOB_BRACE));

        sort($files);

        return $files;
    }

    /**
     * @param $fileName
     * @return Migration
     */
    private function getMigration($fileName)
    {
        $classes = get_declared_classes();
        include "app/migrations/$fileName";
        $class = array_shift(array_diff(get_declared_classes(), $classes));

        return new $class;
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
