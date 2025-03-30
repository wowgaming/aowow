<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


class AreaTriggerList extends BaseType
{
    use spawnHelper;

    public static   $type       = Type::AREATRIGGER;
    public static   $brickFile  = 'areatrigger';
    public static   $dataTable  = '?_areatrigger';
    public static   $contribute = CONTRIBUTE_CO;

    protected       $queryBase = 'SELECT a.*, a.id AS ARRAY_KEY FROM ?_areatrigger a';
    protected       $queryOpts = array(
                        'a'      => [['s']],
                        's'      => ['j' => ['?_spawns s ON s.type = 503 AND s.typeId = a.id', true], 's' => ', s.areaId']
                    );

    public function __construct(array $conditions = [], array $miscData = [])
    {
        parent::__construct($conditions, $miscData);

        foreach ($this->iterate() as $id => &$_curTpl)
            if (!$_curTpl['name'])
                $_curTpl['name'] = 'Unnamed Areatrigger #' . $id;
    }

    public function getListviewData() : array
    {
        $data = [];

        foreach ($this->iterate() as $__)
        {
            $data[$this->id] = array(
                'id'      => $this->curTpl['id'],
                'type'    => $this->curTpl['type'],
                'name'    => $this->curTpl['name'],
            );

            if ($_ = $this->curTpl['areaId'])
                $data[$this->id]['location'] = [$_];
        }

        return $data;
    }

    public function getJSGlobals($addMask = GLOBALINFO_ANY)
    {
        return [];
    }

    public function renderTooltip() { }
}

class AreaTriggerListFilter extends Filter
{
    protected $genericFilter = array(
        2 => [parent::CR_NUMERIC, 'id', NUM_CAST_INT]       // id
    );

    // fieldId => [checkType, checkValue[, fieldIsArray]]
    protected $inputFields = array(
        'cr'  => [parent::V_LIST,  [2],                  true ], // criteria ids
        'crs' => [parent::V_RANGE, [1, 6],               true ], // criteria operators
        'crv' => [parent::V_REGEX, parent::PATTERN_INT,  true ], // criteria values - all criteria are numeric here
        'na'  => [parent::V_REGEX, parent::PATTERN_NAME, false], // name - only printable chars, no delimiter
        'ma'  => [parent::V_EQUAL, 1,                    false], // match any / all filter
        'ty'  => [parent::V_RANGE, [0, 5],               true ]  // types
    );

    protected function createSQLForValues()
    {
        $parts = [];
        $_v    = &$this->fiData['v'];

        // name [str]
        if (isset($_v['na']))
            if ($_ = $this->modularizeString(['name']))
                $parts[] = $_;

        // type [list]
        if (isset($_v['ty']))
            $parts[] = ['type', $_v['ty']];

        return $parts;
    }
}

?>
