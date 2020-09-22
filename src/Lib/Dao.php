<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;

class Dao
{
    const VERSION = '1.0.0';

    public function create($table)
    {
        $f = "<?php\n"
            . "namespace {$table['namespace']}\\Dao;\n"
            . "\n"
            . "class {$table['class']} extends \\MonitoLib\\Database\\Dao\\{$table['dbms']}\n"
            . "{\n"
            . "    const VERSION = '1.0.0';\n"
            . "    /**\n"
            . "     * 1.0.0 - " . date('Y-m-d') . "\n"
            . "     * initial release\n"
            . "     *\n"
            . '     * ' . __CLASS__ . ' v' . self::VERSION . ' ' . App::now() . "\n"
            . "     */\n"
            . "\n";

        if (!is_null($table['connection'])) {
            $f .= "    public function __construct()\n"
                . "    {\n"
                . "        \\MonitoLib\Database\Connector::setConnectionName('{$table['connection']}');\n"
                . "        parent::__construct();\n"
                . "    }\n";
        }

        $f .= '}';

        return $f;
    }
}
