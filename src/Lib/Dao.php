<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;

class Dao extends Creator
{
    const VERSION = '1.0.0';

    public function create($table)
    {
        $f = "<?php\n"
            . "namespace {$table['namespace']}\\Dao;\n"
            . "\n"
            . '// ' . __CLASS__ . ' v' . self::VERSION . ' ' . App::now() . "\n"
            . "\n"
            . "class {$table['class']} extends \\MonitoLib\\Database\\Dao\\{$table['dbms']}\n"
            . "{\n"
            . "    const VERSION = '1.0.0';\n"
            . "    /**\n"
            . "     * 1.0.0 - " . date('Y-m-d') . "\n"
            . "     * initial release\n"
            . "     */\n";

        if (!is_null($table['connection'])) {
            $f .= "    public function __construct()\n"
                . "    {\n"
                . "        \$connector = \MonitoLib\Database\Connector::getInstance();\n"
                . "        \$connector->setConnectionName('{$table['connection']}');\n"
                . "        parent::__construct();\n"
                . "    }\n";
        }

        $f .= '}';

        // echo "$f\n";
        $this->createFile($table, 'dao.php', $f);
    }
}
