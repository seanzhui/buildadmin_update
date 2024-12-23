<?php

namespace app\common\service;

use Exception;
use think\Service;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class ZincSearchService extends Service
{

    protected $client;
    protected $projectName;

    public function __construct()
    {
        $baseUri = env('zinc.scheme', 'http') . '://' .
            env('zinc.host', '127.0.0.1') . ':' .
            env('zinc.port', '4080');
        $this->client = new Client([
            'auth' => [
                env('zinc.account', 'admin'),
                env('zinc.password', '123456'),
            ],
            'base_uri' => $baseUri,
            'timeout' => env('zinc.time', '10'),
        ]);
        $this->projectName = 'tuwen_';
    }

    public static function build()
    {
        return new self();
    }

    /**
     * 处理json数据
     * @param ResponseInterface $resp
     * @return mixed
     * @throws Exception
     */
    public function handleJson(ResponseInterface $resp)
    {
        if ($resp->getStatusCode() !== 200) {
            throw new Exception('请求失败,失败原因:' . $resp->getBody(), $resp->getStatusCode());
        }
        $body = $resp->getBody();
        return json_decode($body, true);
    }

    /**
     * 获取zincSearch版本
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function version()
    {
        $resp = $this->client->get('/version');
        return $this->handleJson($resp);
    }

    /**
     * 批量新增或者批量更新
     * @param $index string 索引
     * @param $primaryKey string 主键
     * @param $data array 数据
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function bulk($index, $primaryKey, array $data)
    {
        $params = [];
        $key = 0;
        foreach ($data as $v) {
            $params[$key]['index'] = [
                '_index' => $this->projectName . $index,
                '_id' => strval($v[$primaryKey]),
            ];
            $key++;
            $params[$key] = $v;
            $key++;
        }
        // 把数组转化成ndjson
        $ndjson = "";
        foreach ($params as $key => $item) {
            $json = json_encode($item);
            if (isset($params[$key + 1])) {
                $ndjson .= $json . PHP_EOL;
            } else {
                $ndjson .= $json;
            }
        }
        $resp = $this->client->request('POST', "/api/_bulk", ['body' => $ndjson]);
        return $this->handleJson($resp);
    }

    /**
     * 批量新增(不能指定zincSearch的主键)
     * @param $index string 索引
     * @param $data array 数据
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function bulkV2($index, $data)
    {
        $params = [
            'index' => $this->projectName . $index,
        ];
        foreach ($data as $k => $v) {
            $params['records'][$k] = $v;
        }
        $resp = $this->client->request('POST', "/api/_bulkv2", ['json' => $params]);
        return $this->handleJson($resp);
    }

    /**
     * 新增或者编辑
     * @param $index string 索引
     * @param $id string 主键的值
     * @param $data array 数据
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function createOrUpdate($index, $id, $data)
    {
        $resp = $this->client->request('PUT', '/api/' . $this->projectName . $index . '/_doc/' . $id, ['json' => $data]);
        return $this->handleJson($resp);
    }

    /**
     * 删除
     * @param $index string 索引
     * @param $id string 主键的值
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function delete($index, $id)
    {
        $resp = $this->client->request('DELETE', '/api/' . $this->projectName . $index . '/_doc/' . $id);
        return $this->handleJson($resp);
    }

    /**
     * 搜索
     * @param $index string 索引
     * @param $keyword string 关键词
     * @param $filed array 需要显示的字段
     * @param $from int 开始查询的下标,默认为0
     * @param $maxResult int 查询的条数,默认为20
     * @param $searchType string 搜索模式,默认为match
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function search($index, $keyword, array $filed = [], $from = 0, $maxResult = 20, $searchType = 'match')
    {
        $json = [
            'search_type' => $searchType,
            'query' => [
                'term' => $keyword
            ],
            'from' => $from,
            'max_results' => $maxResult,
            '_source' => $filed
        ];

        $resp = $this->client->request('POST', '/api/' . $this->projectName . $index . '/_search', ['json' => $json]);
        $result = $this->handleJson($resp);
        $result = $result['hits']['hits'];
        return array_column($result, '_source');
    }

    /**
     * 搜索数量
     * @param $index string 索引
     * @param $keyword string 关键词
     * @param $filed array 需要显示的字段
     * @param $from int 开始查询的下标,默认为0
     * @param $maxResult int 查询的条数,默认为20
     * @param $searchType string 搜索模式,默认为match
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function searchTotal($index, $keyword)
    {
        $from = 0;
        $maxResult = 999999;
        $json = [
            'search_type' => 'match',
            'query' => [
                'term' => $keyword
            ],
            'from' => $from,
            'max_results' => $maxResult,
            '_source' => []
        ];

        $resp = $this->client->request('POST', '/api/' . $this->projectName . $index . '/_search', ['json' => $json]);
        $result = $this->handleJson($resp);
        $result = $result['hits']['total']['value'];
        return  $result;
    }

    /**
     * 商户商品搜索
     * @param $index string 索引
     * @param $keywordField string 搜索的字段名
     * @param $keyword string 关键词
     * @param $field array 需要查询的字段
     * @param $form int 开始查询的下标,默认为0
     * @param $size int 查询的条数,默认为20
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function searchShopGoods($index, $shopId, $keyword, $field = [], $form = 0, $size = 20)
    {
        $json = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'describe' => [
                                    'query' => $keyword,
                                ]
                            ]
                        ], 
                        [
                            'match' => [
                                'mall_shop_id' => [
                                    'query' => $shopId,
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            '_source' => $field,
            'from' => $form,
            'size' => $size,
        ];
        $resp = $this->client->request('POST', '/es/' . $this->projectName . $index . '/_search', ['json' => $json]);
        $result = $this->handleJson($resp);
        $result = $result['hits']['hits'];
        return array_column($result, '_source');
    }
    // 数量
    public function searchShopGoodsNumber($index, $shopId, $keyword)
    {
        $json = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'describe' => [
                                    'query' => $keyword,
                                ]
                            ]
                        ], 
                        [
                            'match' => [
                                'mall_shop_id' => [
                                    'query' => $shopId,
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            '_source' => [],
            'from' => 0,
            'size' => 999999,
        ];
        $resp = $this->client->request('POST', '/es/' . $this->projectName . $index . '/_search', ['json' => $json]);
        $result = $this->handleJson($resp);
        $result = $result['hits']['total']['value'];
        return  $result;
    }

    /**
     * 相似推荐
     * @param $index string 索引
     * @param $keywordField string 搜索的字段名
     * @param $keyword string 关键词
     * @param $idField string 主键
     * @param $id string 主键的值(相似推荐排除自己)
     * @param $field array 需要查询的字段
     * @param $form int 开始查询的下标,默认为0
     * @param $size int 查询的条数,默认为20
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function relevant($index, $keywordField, $keyword, $idField, $id, $field = [], $form = 0, $size = 20)
    {
        $json = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                $keywordField => [
                                    'query' => $keyword,
                                ]
                            ]
                        ], [
                            'match' => [
                                'state' => [
                                    'query' => 1,
                                ]
                            ]
                        ], [
                            'match' => [
                                'is_public' => [
                                    'query' => 1,
                                ]
                            ]
                        ]
                    ],
                    'must_not' => [
                        'match' => [
                            $idField => [
                                'query' => $id
                            ]
                        ]
                    ]
                ]
            ],
            '_source' => $field,
            'from' => $form,
            'size' => $size,
        ];
        $resp = $this->client->request('POST', '/es/' . $this->projectName . $index . '/_search', ['json' => $json]);
        $result = $this->handleJson($resp);
        $result = $result['hits']['hits'];
        return array_column($result, '_source');
    }

    public function createIndex($params)
    {
        $resp = $this->client->request('POST', "/api/index", ['json' => $params]);
        return $this->handleJson($resp);
    }

    /**
     * 搜索
     * @param $index string 索引
     * @param $type int 类型
     * @param $name string 文档名称
     * @param $categoryId int 分类
     * @param $orderBy int 排序规则,1:最新,2:最热,不传递或者传递空则是综合
     * @param $from int 查询开始位置
     * @param $size int 查询数量
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function esSearch($index, $type, $name, $categoryId, $orderBy, $from = 0, $size = 15)
    {
        if (!empty($orderBy)) {
            if ($orderBy == 1) {
                $sort = 'create_time:desc';
            } else {
                $sort = 'click_nums:desc';
            }
        } else {
            $sort = [
                ['click_nums' => 'desc'],
                ['create_time' => 'desc'],
            ];
        }
        $json = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'state' => [
                                    'query' => 1
                                ]
                            ]
                        ],
                        [
                            'match' => [
                                'is_public' => [
                                    'query' => 1
                                ]
                            ]
                        ]
                    ],
                ],
            ],
            'sort' => $sort,
            'from' => $from,
            'size' => $size,
        ];
        if (!empty($type)) {
            $json['query']['bool']['must'] = array_merge($json['query']['bool']['must'], [
                [
                    'match' => [
                        'type' => [
                            'query' => $type
                        ]
                    ]
                ]
            ]);
        }
        if (!empty($name)) {
            $json['query']['bool']['must'] = array_merge($json['query']['bool']['must'], [
                [
                    'match_phrase' => [
                        'name' => [
                            'query' => $name
                        ]
                    ]
                ]
            ]);
        }
        if (!empty($categoryId)) {
            $json['query']['bool']['must'] = array_merge($json['query']['bool']['must'], [
                [
                    'match_phrase' => [
                        'category_id' => [
                            'query' => strval($categoryId)
                        ]
                    ]
                ]
            ]);
        }
        $resp = $this->client->request('POST', '/es/' . $this->projectName . $index . '/_search', ['json' => $json]);
        return $this->handleJson($resp);
    }


    /**
     * 编辑
     * @param $index string 索引
     * @param $id string 主键的值
     * @param $data array 数据
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function update($index, $id, $data)
    {
        $indexName = $this->projectName . $index;
        $resp = $this->client->request('POST', "/api/$indexName/_update/$id", ['json' => $data]);
        return $this->handleJson($resp);
    }
    /**
     * 根据id获取数据,不存在则查询新增
     * @param $index string 索引
     * @param $id string 主键的值
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function getIdData($index, $id)
    {
        $indexName = $this->projectName . $index;
        $resp = $this->client->request('GET', "/api/$indexName/_doc/$id");
        $result = $this->handleJson($resp);
        $result = $result['_source'];
        return $result;
    }
}
