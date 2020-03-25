<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;
use MonitoLib\Functions;

class Controller extends Creator
{
    const VERSION = '1.0.1';

    public function create($table)
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

        // \MonitoLib\Dev::pre($payload);

        $payload = substr($payload, 0, -2);

        $objectName = $table['object'];
        $className  = $table['class'];
        $namespace  = $table['namespace'];
        $objectDao  = $objectName . 'Dao';
        $objectDto  = $objectName . 'Dto';

        $f = "<?php\n"
            . "namespace $namespace\\Controller;\n"
            . "\n"
            . "use \MonitoLib\App;\n"
            . "use \MonitoLib\Exception\NotFound;\n"
            . "\n"
            . '// ' . __CLASS__ . ' v' . self::VERSION . ' ' . App::now() . "\n"
            . "\n"
            . "class $className extends \\MonitoLib\\Controller\n"
            . "{\n"
            . "    const VERSION = '1.0.0';\n"
            . "    /**\n"
            . "     * 1.0.0 - " . date('Y-m-d') . "\n"
            . "     * initial release\n"
            . "     */\n"
            . "\n";

        if ($table['type'] === 'table') {
            $f .= "    public function create()\n"
                . "    {\n"
                . "        \$json = \$this->request->getJson();\n"
                . "\n"
                . "        \$$objectDao = new \\{$namespace}\\Dao\\{$className};\n"
                // . "        // \$$objectDto = \$$objectDao->get();\n"
                // . "\n"
                // . "        // if (!is_null(\$$objectDto)) {\n"
                // . "        //     throw new \MonitoLib\Exception\BadRequest('Registro já existe!');\n"
                // . "        // }\n"
                // . "\n"
                // . "        \$$objectDto = new \\{$table->namespace}dto\\{$className};\n";
                . "        \$$objectDto = \$this->jsonToDto(new \\{$namespace}\\Dto\\{$className}, \$json);\n";

            $ml = 0;

            // foreach ($table->columns as $column) {
            //     // \MonitoLib\Dev::pre($column);
            //     $c = Functions::toLowerCamelCase($column->name);
            //     if (!in_array($column->name, ['upd_time', 'upd_user_id']) && !$column->isPrimary) {
            //         $value = "\$json->{$c}";

            //         if ($column->name === 'ins_time') {
            //             $value = "date('Y-m-d H:i:s')";
            //         }
            //         if ($column->name === 'ins_user_id') {
            //             $value = "User::getId()";
            //         }

            //         $f .= "        \$$objectDto->set" . ucfirst($c) . "($value);\n";
            //     }

            //     if (strlen($c) > $ml) {
            //         $ml = strlen($c);
            //     }
            // }

            $f .= "        \${$objectDao}->insert(\$$objectDto);\n"
                . "\n"
                . "        \$this->response->setHttpResponseCode(201);\n"
                . "    }\n";
        }

        if ($table['type'] === 'table') {
            $f .= "    public function delete($payload)\n"
                . "    {\n"
                . "        \$$objectDao = new \\{$namespace}\\Dao\\{$className};\n"
                . "        \$deleted = \$$objectDao{$equals}"
                . "            ->delete();\n"
                . "\n"
                // . "        if (\$deleted > 0) {\n"
                . "        \$this->response->setHttpResponseCode(204);\n"
                // . "        } else {\n"
                // . "            throw new \MonitoLib\Exception\BadRequest('Não foi possível deletar!');\n"
                // . "        }\n"
                . "    }\n";
        }

        $f .= "    public function get($payload)\n"
            . "    {\n"
            . "        \$$objectDao = new \\{$namespace}\\Dao\\{$className};\n"
            . "        \$$objectDto = \$$objectDao{$equals}"
            . "            ->setFields(\$this->request->getFields())\n"
            . "            ->setQuery(\$this->request->getQuery())\n"
            . "            ->get();\n"
            . "\n"
            . "        if (is_null(\$$objectDto)) {\n"
            . "            throw new NotFound('Registro não encontrado!');\n"
            . "        } else {\n"
            // . "            \$this->response->setData(\$this->toArray(\$$objectDto));\n"
            . "            \$this->response->setData(\$$objectDto);\n"
            . "        }\n"
            . "    }\n"
            . "    public function list()\n"
            . "    {\n"
            // . "        \${$objectName}Ds  = \$this->dao()->dataset();\n"
            . "        \$$objectDao = new \\{$namespace}\\Dao\\{$className};\n"
            . "        \${$objectName}Ds  = \${$objectDao}->setFields(\$this->request->getFields())\n"
            . "            ->setPage(\$this->request->getPage())\n"
            . "            ->setPerPage(\$this->request->getPerPage())\n"
            . "            ->setOrderBy(\$this->request->getOrderBy())\n"
            . "            ->setQuery(\$this->request->getQuery())\n"
            . "            ->dataset();\n"
            . "\n"
            // . "        \${$objectName}Ds['data'] = \$this->toArray(\${$objectName}Ds['data']);\n"
            . "        \$this->response->setDataset(\${$objectName}Ds);\n"
            . "    }\n";

        if ($table['type'] === 'table') {
            $f .= "    public function update($payload)\n"
                . "    {\n"
                . "        \$json = \$this->request->getJson();\n"
                . "\n"
                // . "        // Valida o json recebido\n"
                // . "        if (!is_null(\$errors = \$this->validateJson(\$json, App::getStoragePath('schemas/json') . '{$objectName}_patch.json'))) {\n"
                // . "            throw new \MonitoLib\Exception\BadRequest('Não foi possível validar o schema!', \$errors);\n"
                // . "        }\n"
                // . "\n"
                . "        \$$objectDao = new \\{$namespace}\\Dao\\{$className};\n"
                . "        \$$objectDto = \$$objectDao{$equals}"
                . "            ->get();\n"
                . "\n"
                . "        if (is_null(\$$objectDto)) {\n"
                . "            throw new NotFound('Registro não encontrado!');\n"
                . "        }\n"
                . "\n"
                . "        \$this->jsonToDto(\$$objectDto, \$json);\n"
                // . "\n";
                // foreach ($table->columns as $column) {
                //     $c = Functions::toLowerCamelCase($column->name);
                //     $s = 'set' . ucfirst($c);

                //     if (!in_array($column->name, ['ins_time', 'ins_user_id']) && !$column->isPrimary) {
                //         if (in_array($column->name, ['upd_time', 'upd_user_id'])) {
                //             if ($column->name == 'upd_time') {
                //                 $value = "date('Y-m-d H:i:s')";
                //             }
                //             if ($column->name == 'upd_user_id') {
                //                 $value = "User::getId()";
                //             }

                //             $f .= "        \$$objectDto->{$s}($value);\n"
                //                 . "\n";
                //         } else {
                //             $f .= "        if (isset(\$json->{$c})) {\n"
                //                 . "            \$$objectDto->{$s}(\$json->{$c});\n"
                //                 . "        }\n"
                //                 . "\n";
                //         }
                //     }
                // }

                    . "        \${$objectDao}->update(\$$objectDto);\n"
                    . "\n"
                    // . "        if (\$updated > 0) {\n"
                    . "        \$this->response->setMessage('Registro atualizado com sucesso!')\n"
                    . "             ->setHttpResponseCode(200);\n"
                    // . "        } else {\n"
                    // . "            throw new \MonitoLib\Exception\InternalError('Não foi possível atualizar!');\n"
                    // . "        }\n"
                    . "    }\n";
            }

            $f .= "}";

        // \MonitoLib\Dev::pre(get_parent_class($this));

        // call_user_func(array(get_parent_class($this), 'save'), $table, $f);
        $this->createFile($table, 'controller.php', $f);
    }
}
