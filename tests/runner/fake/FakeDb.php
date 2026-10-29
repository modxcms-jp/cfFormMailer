<?php
/**
 * テスト用の DB（db()）。
 *
 * - リソースのテンプレート：入力の documents[id] を返す
 * - cfFormDB：入力の db_tables（SHOW TABLES で見つかるテーブルの数）を返し、
 *   insert された行を $GLOBALS['__cfm']['db_inserts'] に記録する
 */
class FakeDb
{
    public $config = ['dbase' => 'modx'];

    private $documents;
    private $tableCount;
    private $lastId = 0;
    private $results = [];

    public function __construct(array $input)
    {
        $this->documents = $input['documents'];
        $this->tableCount = $input['db_tables'];
    }

    public function select($fields = '*', $from = '', $where = '', $orderby = '', $limit = '')
    {
        $row = false;
        if (preg_match('/site_content/', $from) && preg_match('/id=(\d+)/', $where, $m)) {
            $row = isset($this->documents[$m[1]]) ? ['id' => (int) $m[1], 'content' => $this->documents[$m[1]]] : false;
        }
        return $this->store([$row]);
    }

    public function getRow($rs, $mode = 'assoc')
    {
        return isset($this->results[$rs]) ? array_shift($this->results[$rs]) : false;
    }

    public function query($sql)
    {
        $rows = [];
        if (stripos($sql, 'SHOW TABLES') === 0) {
            $rows = array_fill(0, $this->tableCount, ['table' => 'cfformdb']);
        }
        return $this->store($rows);
    }

    public function getRecordCount($rs)
    {
        return isset($this->results[$rs]) ? count($this->results[$rs]) : 0;
    }

    public function insert($fields, $table)
    {
        $GLOBALS['__cfm']['db_inserts'][] = ['table' => $table, 'fields' => $fields];
        return ++$this->lastId;
    }

    public function escape($s)
    {
        return addslashes($s);
    }

    private function store(array $rows)
    {
        $id = count($this->results) + 1;
        $this->results[$id] = array_values(array_filter($rows, function ($r) {
            return $r !== false;
        }));
        return $id;
    }
}
