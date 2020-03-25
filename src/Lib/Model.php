<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;
use MonitoLib\Functions;

class Model extends Creator
{
    const VERSION = '1.0.0';

    public function create($table)
    {
        // \MonitoLib\Dev::pre($table);
        $output = '';
        $keys = '';

        foreach ($table['columns'] as $object => $column) {
            $cl = strlen($object);
            $ci = $cl;//$bi + $cl;
            $it = floor($ci / 4);
            $is = $ci % 4;
            $li = "            ";//$util->indent($it, $is);

            $output .= "        '" . $object . "' => [\n";
            $output .= "$li'name'      => '{$column['name']}',\n";

            if ($column['auto']) {
                $output .= "$li'auto'      => true,\n";

                if ($column['autoSource'] !== 'auto') {
                    $output .= "$li'source'    => '{$column['autoSource']}',\n";
                }
            }

            // if ($column['getType']() == 'char') {
            //     if ($column['getCharset']() != $modelDefault->getDefaults('charset')) {
            //         $output .= "$li'charset'   => '{$column['getCharset']()}',\n";
            //     }
            //     if ($column['getCollation']() != $modelDefault->getDefaults('collation')) {
            //         $output .= "$li'collation' => '{$column['getCollation']()}',\n";
            //     }
            // }
            if ($column['type'] !== 'string') {
                $output .= "$li'type'      => '{$column['type']}',\n";
            }
            if (!is_null($column['format'])) {
                $output .= "$li'format'    => '{$column['format']}',\n";
            }
            if (!is_null($column['default'])) {
                $output .= "$li'default'   => '{$column['default']}',\n";
            }
            if (!is_null($column['label']) && $column['label'] !== '') {
                $output .= "$li'label'     => '{$column['label']}',\n";
            }
            if (!is_null($column['maxLength']) && $column['maxLength'] > 0) {
                $output .= "$li'maxLength' => {$column['maxLength']},\n";
            }
            if ($column['primary']) {
                $keys .= "'" . $column['name'] . "',";
                $output .= "$li'primary'   => true,\n";
            }
            if ($column['required']) {
                $output .= "$li'required'  => true,\n";
            }
            // if ($modelDefault->getDefaults('type')) {
            // if ($modelDefault->getDefaults('type') != $column['datatype']) {
            // }
            // if ($modelDefault->getDefaults('unique') != $column['getIsUnique']()) {
            //     $output .= "$li'unique' => {$column['getIsUnique']()},\n";
            // }

            if (in_array($column['type'], ['int', 'double']) && $column['unsigned']) {
                $output .= "$li'unsigned'  => true,\n";
            }


        //'maxValue'         => 0,
        //'minValue'         => 0,
        //'numericPrecision' => null,
        //'numericScale'     => null,

            $output .= "        ],\n";
        }

        $keys = substr($keys, 0, -1);

        $constraints = '';

        // Constraints
        if (isset($table['constraints']) && !empty($table['constraints'])) {
            // \MonitoLib\Dev::pre($table['constraints']);
            // foreach ($table['constraints'] as $constraint) {

            // }
            $constraints =  "\n    protected \$constraints = [\n";

            $gambiarraTemporaria = 0;

            foreach ($table['constraints'] as $ck => $cv) {
                // \MonitoLib\Dev::pre($ck);
                if ($ck === 'unique') {
                    $key = key($cv);
                    $constraints .= "        'unique' => [\n";
                    $constraints .= "            '$key' => [\n";
                    foreach ($cv->$key as $ck => $c) {
                        // \MonitoLib\Dev::pre($c);
                        $constraints .= "                '" . Functions::toLowerCamelCase($c) . "',\n";
                    }
                    $constraints .= "             ]\n";
                    $constraints .= "         ],\n";

                    $gambiarraTemporaria++;
                }
            }

            $constraints .=  "    ];\n";

            if ($gambiarraTemporaria === 0) {
                $constraints = '';
            }
        }

        $f = "<?php\n"
            // . $this->renderComments()
            . "\n"
            . "namespace {$table['namespace']}\\Model;\n"
            . "\n"
            . '// ' . __CLASS__ . ' v' . self::VERSION . ' ' . App::now() . "\n"
            . "\n"
            . "class {$table['class']} extends \\MonitoLib\\Database\\Model\n"
            . "{\n"
            . "    const VERSION = '1.0.0';\n"
            . "\n"
            . "    protected \$tableName = '" . $table['name'] . "';\n"
            . "\n"
            . "    protected \$fields = [\n"
            . $output
            . "    ];\n"
            . "\n"
            . "    protected \$keys = [$keys];\n"
            . $constraints
            . "}"
            ;
        // echo "$f\n";
        // return $f;
        $this->createFile($table, 'model.php', $f);
    }
}
