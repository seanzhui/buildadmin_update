<?php

namespace modules\dataexport\library;

use Throwable;
use think\facade\Db;
use think\db\Query;
use app\admin\model\routine\Dataexport;

class ExportLib
{
    /**
     * @var object
     * @phpstan-var  Dataexport
     */
    protected object $row;

    protected array $fields = [];
    protected array $join   = [];
    protected array $where  = [];
    protected array $order  = [];

    /**
     * @throws Throwable
     */
    public function __construct(int $id)
    {
        $this->row = Dataexport::where('id', $id)->find();

        foreach ($this->row->field_config as $item) {
            $asName                = $this->row->main_table . '.' . $item['name'] . ' as ' . $this->row->main_table . '_' . $item['name'];
            $item['field']         = $this->row->main_table . '_' . $item['name'];
            $this->fields[$asName] = $item;
        }

        if ($this->row->join_table) {
            foreach ($this->row->join_table as $joinTable) {
                foreach ($joinTable['fields'] as $item) {
                    $joinAsName            = !empty($joinTable['asname']) ? $joinTable['asname'] : $joinTable['table'];
                    $asName                = $joinAsName . '.' . $item['name'] . ' as ' . $joinAsName . '_' . $item['name'];
                    $item['field']         = $joinAsName . '_' . $item['name'];
                    $this->fields[$asName] = $item;
                }

                $condition = vsprintf('%s.%s = %s.%s', [
                    $this->row->main_table,
                    $joinTable['fk'],
                    $joinAsName,
                    $joinTable['pk']
                ]);

                $this->join[] = [$joinTable['table'] . ' ' . $joinAsName, $condition, $joinTable['type'] ?? 'LEFT'];
            }
        }

        // 筛选
        if ($this->row->where_field) {
            foreach ($this->row->where_field as $item) {
                $this->where[] = [$item['field'], $item['operator'], $item['value']];
            }
        }

        // 排序
        if ($this->row->order_field) {
            foreach ($this->row->order_field as $item) {
                $this->order[$item['field']] = $item['value'];
            }
        }
    }

    public function getSql(string $type = '', array $data = []): string|Db|Query
    {
        $res = Db::name($this->row->main_table)
            ->alias($this->row->main_table)
            ->field(implode(',', array_keys($this->fields)))
            ->where($this->where)
            ->order($this->order);
        foreach ($this->join as $item) {
            $res->join($item[0], $item[1], $item[2]);
        }

        if ($type == 'limit') {
            return $res->fetchSql()->limit($data[0], $data[1])->select();
        } elseif ($type == 'test') {
            return $res->fetchSql()->limit(10)->select();
        }
        return $res;
    }

    public function getXlsTitle(): array
    {
        $title = [];
        foreach ($this->fields as $key => $value) {
            $title[] = $value['title'] ?: $key;
        }
        return $title;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function assignment($value, $comment)
    {
        if ($comment) {
            $comment = explode(',', $comment);
            foreach ($comment as $v) {
                list($itemKey, $itemValue) = explode('=', $v);
                if ($itemKey == $value) {
                    return $itemValue;
                }
            }
        }
        return $value;
    }

    /**
     * 获取数据量
     * @throws Throwable
     */
    public function getCount(): int
    {
        $sql = $this->getSql();
        return $sql->count();
    }

}