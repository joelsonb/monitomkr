<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;
use MonitoLib\Functions;

class Router extends Creator
{
    const VERSION = '1.0.0';

    public function create($table)
    {
        \MonitoLib\Dev::pre($table);
        $routesFile = $table['output'] . 'config/routes.php';

        $useNamespace = true;

        $lines = [];

        if (file_exists($routesFile)) {
            $lines = file($routesFile, FILE_IGNORE_NEW_LINES);
            // \MonitoLib\Dev::pre($lines);
        }

        // $routes = [];

        // $routes[$baseRoute][] = [
        //     'verb' => 'get',
        //     'params' => ':{[0-9]{1,}}',
        //     'class' => "\\{$table->namespace}\\controller\\{$table->class}",
        //     'method' => 'get',
        //     'secure' => false,
        // ];

        $routes[] = "Router::get('/{$table['url']}/:{[0-9]{1,}}', '\\{$table['namespace']}\\controller\\{$table['class']}@get', false);";
        $routes[] = "Router::get('/{$table['url']}', '\\{$table['namespace']}\\controller\\{$table['class']}@list', false);";

        if ($table['type'] === 'table') {
            $routes[] = "Router::post('/{$table['url']}', '\\{$table['namespace']}\\controller\\{$table['class']}@create', false);";
            $routes[] = "Router::put('/{$table['url']}/:{[0-9]{1,}}', '\\{$table['namespace']}\\controller\\{$table['class']}@update', false);";
            $routes[] = "Router::delete('/{$table['url']}/:{[0-9]{1,}}', '\\{$table['namespace']}\\controller\\{$table['class']}@delete', false);";
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
        // return $f;
        file_put_contents($routesFile, $f);
        // exit;
    }
}
