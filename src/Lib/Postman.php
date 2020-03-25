<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;
use MonitoLib\Functions;

class Postman extends Creator
{
    const VERSION = '1.0.0';

    public function create ($table)
    {
        // \MonitoLib\Dev::pre($table);
        $header = [
            [
                'key' => 'Content-Type',
                'name' => 'Content-Type',
                'value' => 'application/json',
                'type' => 'text'
            ]
        ];
        $createBody = [
            'mode' => 'raw',
            'raw' => ''
        ];
        $putBody = [
            'mode' => 'raw',
            'raw' => ''
        ];

        $cb = "{\n";
        $pb = "{\n";
        $keys = '';

        foreach ($table->columns as $column) {
            // \MonitoLib\Dev::pre($column);

            if (!$column->isPrimary) {
                $cb .= "\t\"" . $column->object . "\": \"\",\n";
                $pb = $cb;
            }

            if ($column->isPrimary) {
                $keys .= '/{{' . $column->object . '}}';
            }
        }

        $tests = [
            [
                'listen' => 'test',
                'script' => [
                    'exec' => [
                        'var json = pm.response.json();',
                    ],
                    'type' => 'text/javascript'
                ]
            ]
        ];

        $table->isSecure = true;

        if ($table->isSecure) {
            array_push($tests[0]['script']['exec'], 'pm.test("Tem token de autenticação", function () {', '    pm.response.to.have.header("Authorization");', '});');
        }

        array_push($tests[0]['script']['exec'], 'pm.test("Retornou sucesso", function () {', '    pm.response.to.be.success;', '});');

        $bodyTests = $tests;
        $noBodyTests = $tests;
        
        array_push($bodyTests[0]['script']['exec'], 'pm.test("Retornou um objeto", function () {', '    pm.response.to.have.jsonBody();', '});');
        array_push($noBodyTests[0]['script']['exec'], 'pm.test("Não retornou dados", function () {', '    pm.response.to.not.have.body();', '});');


        $cb = substr($cb, 0, -2);
        $pb = substr($pb, 0, -2);

        $createBody['raw'] = $cb . "\n}";
        $putBody['raw'] = $pb . "\n}";
        $path = explode('/', $keys);
        array_shift($path);

        $create = [
            'name' => $table->class,
            'event' => $noBodyTests,
            'request' => [
                'method' => 'POST',
                'header' => $header,
                'body' => $createBody,
                'url' => [
                    'raw' => '{{domain}}' . $table->route,
                    'host' => [
                        '{{domain}}' . $table->route,
                    ]
                ]
            ],
        ];

        $get = [
            'name' => $table->class,
            'event' => $bodyTests,
            'request' => [
                'method' => 'GET',
                'header' => $header,
                'url' => [
                    'raw' => '{{domain}}' . $table->route . $keys,
                    'host' => [
                        '{{domain}}' . $table->route
                    ]
                ]
            ],
        ];

        $put = [
            'name' => $table->class,
            'event' => $bodyTests,
            'request' => [
                'method' => 'PUT',
                'header' => $header,
                'body' => $putBody,
                'url' => [
                    'raw' => '{{domain}}' . $table->route . $keys,
                    'host' => [
                        '{{domain}}' . $table->route
                    ]
                ]
            ],
        ];

        $delete = [
            'name' => $table->class,
            'event' => $noBodyTests,
            'request' => [
                'method' => 'DELETE',
                'header' => $header,
                'url' => [
                    'raw' => '{{domain}}' . $table->route . $keys,
                    'host' => [
                        '{{domain}}' . $table->route
                    ]
                ]
            ]
        ];

        $getAll = [
            'name' => $table->class,
            'event' => $bodyTests,
            'request' => [
                'method' => 'GET',
                'header' => $header,
                'url' => [
                    'raw' => '{{domain}}' . $table->route,
                    'host' => [
                        '{{domain}}' . $table->route,
                    ]
                ]
            ],
        ];

        $postman = [
            'info' => [
                'name' => $table->projectName,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [
                [
                    'name' => $table->class,
                    'item' => []
                ]
            ]
        ];

        if (!empty($path)) {
            $put['request']['url']['path'] = $path;
            $get['request']['url']['path'] = $path;
            $delete['request']['url']['path'] = $path;
        }

        array_push($postman['item'][0]['item'], $create, $put, $get, $delete, $getAll);

        // echo json_encode($postman, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";exit;

        App::createPath($table->output . 'tests');

        file_put_contents($table->output . 'tests' . DIRECTORY_SEPARATOR . $table->class . '.json', json_encode($postman, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
