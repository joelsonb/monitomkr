<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;
use MonitoLib\Functions;

class Controller
{
    const VERSION = '1.0.1';

    public function create($options, $table)
    {
        // \MonitoLib\Dev::pre($table);
        $primaryKeys = [];

        foreach ($table['columns'] as $key => $column) {
            if ($column['primary']) {
                $primaryKeys[$column['name']] = $key;
            }
        }

        // \MonitoLib\Dev::pre($primaryKeys);
        // \MonitoLib\Dev::pre($table['columns']);

        if ($table['type'] === 'table' && empty($primaryKeys)) {
            throw new \MonitoLib\Exception\BadRequest("Não existe chave primária na tabela {$table['class']}!");
        }

        // Payload métodos
        $payload = '';
        $equals  = '';

        $i = 0;

        foreach ($primaryKeys as $keyName => $keyObject) {
            if ($i > 0) {
                $equals .= '            ';
            }

            $payload .= '$' . $keyObject . ', ';
            $equals  .= "->andEqual('$keyName', \$$keyObject)\n";
            $i++;
        }

        $use = '';

        // \MonitoLib\Dev::pre($payload);

        $payload = substr($payload, 0, -2);

        $objectName  = $table['object'];
        $className   = $table['class'];
        $namespace   = $table['namespace'];
        $objectDao   = $objectName . 'Dao';
        $objectDto   = $objectName . 'Dto';
        $objectModel = $objectName . 'Model';

        // Create function
        $create = "    public function create(\$mix = null)\n"
            . "    {\n"
            . "        if (is_null(\$mix)) {\n"
            . "            \$json[] = Request::getJson();\n"
            . "        } else {\n"
            . "            if (is_array(\$mix)) {\n"
            . "                \$json = \$mix;\n"
            . "            } else {\n"
            . "                \$json[] = \$mix;\n"
            . "            }\n"
            . "        }\n"
            . "\n"
            . "        \$$objectDao = new \\{$namespace}\\Dao\\{$className};\n"
            . "\n"
            . "        foreach (\$json as \$j) {\n"
            . "            \$$objectDto = \$this->jsonToDto(new \\{$namespace}\\Dto\\{$className}, \$j);\n"
            . "            \${$objectDao}->insert(\$$objectDto);\n"
            . "        }\n"
            . "\n"
            . "        Response::setHttpResponseCode(201);\n"
            . "    }\n";

        // Delete function
        $delete = "    public function delete(...\$keys)\n"
            . "    {\n"
            . "        if (empty(\$keys)) {\n"
            . "            throw new BadRequest('Não é possível deletar sem parâmetros!');\n"
            . "        }\n"
            . "\n"
            . "        \$$objectDao   = new \\{$namespace}\\Dao\\{$className};\n"
            . "        \$$objectModel = new \\{$namespace}\\Model\\{$className};\n"
            . "\n"
            . "        \$primaryKeys = \${$objectModel}->getPrimaryKeys();\n"
            . "\n"
            . "        \$i = 0;\n"
            . "\n"
            . "        foreach (\$primaryKeys as \$field) {\n"
            . "            \${$objectDao}->andEqual(\$field, \$keys[\$i], \$$objectDao::FIXED_QUERY);\n"
            . "            \$i++;\n"
            . "        }\n"
            . "\n"
            . "        \${$objectDao}->delete();\n"
            . "\n"
            . "        Response::setHttpResponseCode(204);\n"
            . "    }\n";

        // Get method
        $get = "    public function get(...\$keys)\n"
            . "    {\n"
            . "        \$$objectDao   = new \\{$namespace}\\Dao\\{$className};\n"
            . "        \$$objectModel = new \\{$namespace}\\Model\\{$className};\n"
            . "\n"
            . "        if (!empty(\$keys)) {\n"
            . "            \$primaryKeys = \${$objectModel}->getPrimaryKeys();\n"
            . "\n"
            . "            \$i = 0;\n"
            . "\n"
            . "            foreach (\$primaryKeys as \$field) {\n"
            . "                \${$objectDao}->andEqual(\$field, \$keys[\$i], \$$objectDao::FIXED_QUERY);\n"
            . "                \$i++;\n"
            . "            }\n"
            . "        }\n"
            . "\n"
            . "        \$dataset = \$this->dataset ?? Request::asDataset();\n"
            . "        \$fields  = \$this->fields ?? Request::getFields();\n"
            . "        \$orderBy = \$this->orderBy ?? Request::getOrderBy();\n"
            . "        \$page    = \$this->page ?? Request::getPage();\n"
            . "        \$perPage = \$this->perPage ?? Request::getPerPage();\n"
            . "        \$query   = \$this->query ?? Request::getQuery();\n"
            . "\n"
            . "        \${$objectDao}->setFields(\$fields)\n"
            . "            ->setQuery(\$query);\n"
            . "\n"
            . "        if (empty(\$keys)) {\n"
            . "            \${$objectDao}->setPerPage(\$perPage)\n"
            . "                ->setPage(\$page)\n"
            . "                ->setOrderBy(\$orderBy);\n"
            . "\n"
            . "            if (\$dataset) {\n"
            . "                return \${$objectDao}->dataset();\n"
            . "            } else {\n"
            . "                return \${$objectDao}->list();\n"
            . "            }\n"
            . "        } else {\n"
            . "            \$dto = \${$objectDao}->get();\n"
            . "\n"
            . "            if (is_null(\$dto)) {\n"
            . "                throw new NotFound(\$this->notFound ?? 'Registro não encontrado!');\n"
            . "            }\n"
            . "\n"
            . "            return \$dto;\n"
            . "        }\n"
            . "    }\n";

        // Update method
        $update = "    public function update($payload, \$mix = null)\n"
            . "    {\n"
            . "        \$json  = \$mix ?? Request::getJson();\n"
            . "        \$$objectDao   = new \\{$namespace}\\Dao\\{$className};\n"
            . "        \$$objectModel = new \\{$namespace}\\Model\\{$className};\n"
            . "\n"
            . "        \$$objectDto = \$this->get($payload);\n"
            . "\n"
            . "        if (is_null(\$$objectDto)) {\n"
            . "            throw new NotFound(\$this->notFound ?? 'Registro não encontrado!');\n"
            . "        }\n"
            . "\n"
            . "        \$$objectDto = \$this->jsonToDto(\$$objectDto, \$json);\n"
            . "        \${$objectDao}->update(\$$objectDto);\n"
            . "\n"
            . "        Response::setHttpResponseCode(201);\n"
            . "    }\n";

        if ($options->controllerMethods) {
            $use = "use \\MonitoLib\\Exception\\NotFound;\n"
                . "use \\MonitoLib\\Request;\n"
                . "use \\MonitoLib\\Response;\n"
                . "\n";
        }

        $f = "<?php\n"
            . "namespace $namespace\\Controller;\n"
            . "\n"
            . $use
            . "class $className extends \\MonitoLib\\Controller\n"
            . "{\n"
            . "    const VERSION = '1.0.0';\n"
            . "    /**\n"
            . "     * 1.0.0 - " . date('Y-m-d') . "\n"
            . "     * initial release\n"
            . "     *\n"
            . '     * ' . __CLASS__ . ' v' . self::VERSION . ' ' . App::now() . "\n"
            . "     */\n";

        if ($options->controllerMethods) {
            $f .= "\n";

             if ($table['type'] === 'table') {
                $f .= $create
                    . $delete;
            }

            $f .= $get;

            if ($table['type'] === 'table') {
                $f .= $update;
            }
        }

        $f .= '}';

        return $f;
    }
}
