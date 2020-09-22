<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;
use MonitoLib\Functions;

class Route
{
    const VERSION = '1.0.0';

    public function create($table)
    {
        // \MonitoLib\Dev::pre($table);
        $routesFile = str_replace('\\', '_', strtolower($table['namespace'])) . '.php';

        $useNamespace = true;

        $lines = [];

        if (file_exists($routesFile)) {
            $lines = file($routesFile, FILE_IGNORE_NEW_LINES);
        }

        // $routes = [];

        // $routes[$baseRoute][] = [
        //     'verb' => 'get',
        //     'params' => ':{[0-9]{1,}}',
        //     'class' => "\\{$table->namespace}\\controller\\{$table->class}",
        //     'method' => 'get',
        //     'secure' => false,
        // ];

        $routes[] = "Router::get('/{$table['url']}', '\\{$table['namespace']}\\Controller\\{$table['class']}@get');";
        $routes[] = "Router::get('/{$table['url']}/:{[0-9]{1,}}', '\\{$table['namespace']}\\Controller\\{$table['class']}@get');";

        if ($table['type'] === 'table') {
            $routes[] = "Router::post('/{$table['url']}', '\\{$table['namespace']}\\Controller\\{$table['class']}@create');";
            $routes[] = "Router::put('/{$table['url']}/:{[0-9]{1,}}', '\\{$table['namespace']}\\Controller\\{$table['class']}@update');";
            $routes[] = "Router::delete('/{$table['url']}', '\\{$table['namespace']}\\Controller\\{$table['class']}@delete');";
            $routes[] = "Router::delete('/{$table['url']}/:{[0-9]{1,}}', '\\{$table['namespace']}\\Controller\\{$table['class']}@delete');";
        }

        foreach ($routes as $route) {
            if (!in_array($route, $lines)) {
                $lines[] = $route;
            }
        }

        // \MonitoLib\Dev::pre($lines);

        $f = "<?php\n"
            . "use \MonitoLib\Router;\n"
            . "\n"
            . '// ' . __CLASS__ . ' v' . self::VERSION . ' ' . App::now() . "\n"
            . "\n";

        foreach ($lines as $line) {
            if (substr($line, 0, 6) === 'Router') {
                $f .= "$line\n";
            }
        }

        // echo "$f\n";
        return $f;
        file_put_contents($routesFile, $f);
        // exit;
    }
}
