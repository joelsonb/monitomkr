<?php
namespace MonitoMkr\Lib;

use MonitoLib\App;
use MonitoLib\Functions;

class Postman
{
    const VERSION = '1.0.0';

    public function create($table)
    {
        $class = $table['class'];
        $url   = $table['url'];

        $header = [
            [
                'key'   => 'Content-Type',
                'name'  => 'Content-Type',
                'value' => 'application/json',
                'type'  => 'text'
            ]
        ];
        $createBody = [
            'mode' => 'raw',
            'raw'  => ''
        ];
        $putBody = [
            'mode' => 'raw',
            'raw'  => ''
        ];

        $cb = "{\n";
        $pb = "{\n";
        $keys = '';

        foreach ($table['columns'] as $column) {
            $isPrimary = $column['primary'];
            $object    = $column['object'];

            if (!$isPrimary) {
                $cb .= "\t\"" . $object . "\": \"\",\n";
                $pb = $cb;
            } else {
                $keys .= '/{{' . $object . '}}';
            }
        }

        $tests = [
            [
                'listen' => 'test',
                'script' => [
                    'exec' => [
                    ],
                    'type' => 'text/javascript'
                ]
            ]
        ];

        $var   = [
            'var json = pm.response.json();'
        ];
        $c200  = [
            'pm.test("Retornou sucesso", function () {', 
            '    pm.response.to.be.success;', 
            '});'
        ];
        $c201  = [
            'pm.test("Retornou código 201", function () {', 
            '    pm.response.to.have.status(201);', 
            '});'
        ];
        $c204  = [
            'pm.test("Retornou código 204", function () {', 
            '    pm.response.to.have.status(204);', 
            '});'
        ];
        $body  = [
            'pm.test("Retornou um objeto", function () {', 
            '    pm.response.to.have.jsonBody();', 
            '});'
        ];
        $empty = [
            'pm.test("Não retornou dados", function () {', 
            '    pm.response.to.not.have.body();', 
            '});'
        ];

        $createTests = array_merge($c201, $empty);
        $deleteTests = array_merge($c204, $empty);
        $getTests    = array_merge($var, $c200, $body);
        $updateTests = array_merge($c204, $empty);

        $cb = substr($cb, 0, -2);
        $pb = substr($pb, 0, -2);

        $createBody['raw'] = $cb . "\n}";
        $putBody['raw'] = $pb . "\n}";
        $path = explode('/', $keys);
        array_shift($path);

        $create = [
            'name' => $class . ' :: create',
            'event' => [
                [
                    'listen' => 'test',
                    'script' => [
                        'exec' => $createTests,
                        'type' => 'text/javascript'
                    ]
                ]
            ],
            'request' => [
                'method' => 'POST',
                'header' => $header,
                'body' => $createBody,
                'url' => [
                    'raw' => '{{host}}' . $url,
                    'host' => [
                        '{{host}}' . $url,
                    ]
                ]
            ],
        ];

        $get = [
            'name' => $class . ' :: get by id(s)',
            'event' => [
                [
                    'listen' => 'test',
                    'script' => [
                        'exec' => $getTests,
                        'type' => 'text/javascript'
                    ]
                ]
            ],
            'request' => [
                'method' => 'GET',
                'header' => $header,
                'url' => [
                    'raw' => '{{host}}' . $url . $keys,
                    'host' => [
                        '{{host}}' . $url
                    ]
                ]
            ],
        ];

        $put = [
            'name' => $class . ' :: update',
            'event' => [
                [
                    'listen' => 'test',
                    'script' => [
                        'exec' => $updateTests,
                        'type' => 'text/javascript'
                    ]
                ]
            ],
            'request' => [
                'method' => 'PUT',
                'header' => $header,
                'body' => $putBody,
                'url' => [
                    'raw' => '{{host}}' . $url . $keys,
                    'host' => [
                        '{{host}}' . $url
                    ]
                ]
            ],
        ];

        $delete = [
            'name' => $class . ' :: delete',
            'event' => [
                [
                    'listen' => 'test',
                    'script' => [
                        'exec' => $deleteTests,
                        'type' => 'text/javascript'
                    ]
                ]
            ],
            'request' => [
                'method' => 'DELETE',
                'header' => $header,
                'url' => [
                    'raw' => '{{host}}' . $url . $keys,
                    'host' => [
                        '{{host}}' . $url
                    ]
                ]
            ]
        ];

        $getAll = [
            'name' => $class . ' :: get by query',
            'event' => [
                [
                    'listen' => 'test',
                    'script' => [
                        'exec' => $getTests,
                        'type' => 'text/javascript'
                    ]
                ]
            ],
            'request' => [
                'method' => 'GET',
                'header' => $header,
                'url' => [
                    'raw' => '{{host}}' . $url,
                    'host' => [
                        '{{host}}' . $url,
                    ]
                ]
            ],
        ];

        $postman = [
            'info' => [
                'name' => $table['namespace'] . '\\' . $class,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [
                [
                    'name' => $class,
                    'item' => []
                ]
            ]
        ];

        if (!empty($path)) {
            $put['request']['url']['path'] = $path;
            $get['request']['url']['path'] = $path;
            $delete['request']['url']['path'] = $path;
        }

        array_push($postman['item'][0]['item'], $create, $getAll, $put, $get, $delete);

        return json_encode($postman, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
