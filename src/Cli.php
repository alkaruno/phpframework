<?php

namespace Xplosio\PhpFramework;

class Cli
{
    public function __construct($args)
    {
        array_shift($args);

        $command = array_shift($args);
        if (is_callable([$this, $command])) {
            try {
                call_user_func([$this, $command], $args);
            } catch (\Exception $e) {
                print $e->getMessage() . PHP_EOL;
                print $e->getTraceAsString() . PHP_EOL;
            }
        } else {
            print 'Invalid command!' . PHP_EOL;
        }
    }

    private function migration($args)
    {
        if (count($args) === 0) {
            (new Schema())->migrate();
            return;
        }

        if ($args[0] === 'create' && count($args) === 2) {

            $className = String::toCamelCase($args[1], true) . 'Migration';
            $fileName = sprintf('%s__%s.php', date('Ymd_His'), String::toSnakeCase($args[1]));

            $this->createFile('app/migrations/' . $fileName, 'migration.txt', [
                '{{className}}' => $className
            ]);

            printf('Migration %s created in file %s%s', $className, $fileName, PHP_EOL);
        }
    }

    private function createFile($path, $template, $params)
    {
        file_put_contents($path, str_replace(
            array_keys($params),
            array_values($params),
            file_get_contents(dirname(__DIR__) . '/views/' . $template)
        ));
    }
}
