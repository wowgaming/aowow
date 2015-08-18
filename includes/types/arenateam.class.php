<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');



class ArenaTeamList extends BaseType
{
    use listviewHelper, profilerHelper;

    protected   $queryBase = 'SELECT `at`.*, `at`.`arenaTeamId` AS ARRAY_KEY FROM arena_team at';
    protected   $queryOpts = array(
                    'at'  => [['atm', 'c'], 'g' => 'ARRAY_KEY', 'o' => 'rating DESC'],
                    'atm' => ['j' => 'arena_team_member atm ON atm.arenaTeamId = at.arenaTeamId'],
                    'c'   => ['j' => 'characters c ON c.guid = atm.guid', 's' => ', GROUP_CONCAT(c.name SEPARATOR " ") AS mNames, GROUP_CONCAT(IF(c.guid = at.captainGuid, -c.class, c.class) SEPARATOR " ") AS mClasses, BIT_OR(1 << (race - 1)) AS raceMask']
                );

    private     $rankOrder = [];

    public function __construct($conditions = [], $miscData = null)
    {
        // select DB by realm
        if (!$this->selectRealms($miscData))
        {
            trigger_error('no access to auth-db or table realmlist is empty', E_USER_WARNING);
            return;
        }

        parent::__construct($conditions, $miscData);

        if ($this->error)
            return;

        // ranks in DB are inaccurate. recalculate from rating (fetched as DESC from DB)
        foreach ($this->dbNames as $rId => $__)
            foreach ([2, 3, 5] as $type)
                $this->rankOrder[$rId][$type] = DB::Characters($rId)->selectCol('SELECT arenaTeamId FROM arena_team WHERE `type` = ?d ORDER BY rating DESC', $type);

        reset($this->dbNames);                              // only use when querying single realm
        $realmId     = key($this->dbNames);
        $realms      = Util::getRealms();
        $distrib     = [];

        // post processing
        foreach ($this->iterate() as $guid => &$curTpl)
        {
            // battlegroup
            $curTpl['battlegroup'] = CFG_BATTLEGROUP;

            // realm, rank
            if (strpos($guid, ':'))
            {
                $r = explode(':', $guid)[0];
                if (!empty($realms[$r]))
                {
                    $curTpl['realm']  = $realms[$r]['name'];
                    $curTpl['region'] = $realms[$r]['region'];
                    $curTpl['rank']   = array_search($curTpl['arenaTeamId'], $this->rankOrder[$r][$curTpl['type']]) + 1;
                }
                else
                {
                    trigger_error('character "'.$curTpl['name'].'" belongs to nonexistant realm #'.$r, E_USER_WARNING);
                    unset($this->templates[$guid]);
                    continue;
                }
            }
            else if (count($this->dbNames) == 1)
            {
                $curTpl['realm']  = $realms[$realmId]['name'];
                $curTpl['region'] = $realms[$realmId]['region'];
                $curTpl['rank']   = array_search($curTpl['arenaTeamId'], $this->rankOrder[$realmId][$curTpl['type']]) + 1;
            }

            // faction
            $curTpl['faction'] = Util::sideByRaceMask($curTpl['raceMask']) - 1;
            unset($curTpl['raceMask']);

            // team members
            $_n = explode(' ', $curTpl['mNames']);
            $_c = explode(' ', $curTpl['mClasses']);
            $curTpl['members'] = [];
            if (!count($_n) || !count($_c))
                trigger_error('arena team #'.$guid.' has no members', E_USER_WARNING);
            else
                for ($i = 0; $i < count($_n); $i++)
                    $curTpl['members'][] = [$_n[$i], abs($_c[$i]), $_c[$i] < 0];

            // equalize distribution
            if (empty($distrib[$curTpl['realm']]))
                $distrib[$curTpl['realm']] = 1;
            else
                $distrib[$curTpl['realm']]++;
        }

        $limit = CFG_SQL_LIMIT_DEFAULT;
        foreach ($conditions as $c)
            if (is_int($c))
                $limit = $c;

        $total = array_sum($distrib);
        foreach ($distrib as &$d)
            $d = ceil($limit * $d / $total);

        foreach ($this->iterate() as $guid => &$curTpl)
        {
            if ($limit <= 0 || $distrib[$curTpl['realm']] <= 0)
            {
                unset($this->templates[$guid]);
                continue;
            }

            $distrib[$curTpl['realm']]--;
            $limit--;
        }
    }

    public function getListviewData()
    {
        $data = [];
        foreach ($this->iterate() as $__)
        {
            $data[$this->id] = array(
                'id'                => $this->curTpl['arenaTeamId'],
                'name'              => $this->curTpl['name'],
                'realm'             => Util::urlize($this->curTpl['realm']),
                'realmname'         => $this->curTpl['realm'],
                // 'battlegroup'       => Util::urlize($this->curTpl['battlegroup']),  // was renamed to subregion somewhere around cata release
                // 'battlegroupname'   => $this->curTpl['battlegroup'],
                'region'            => Util::urlize($this->curTpl['region']),
                'faction'           => $this->curTpl['faction'],
                'size'              => $this->curTpl['type'],
                'rank'              => $this->curTpl['rank'],
                'wins'              => $this->curTpl['seasonWins'],
                'games'             => $this->curTpl['seasonGames'],
                'rating'            => $this->curTpl['rating'],
                'members'           => $this->curTpl['members']
            );
        }

        return array_values($data);
    }

    public function renderTooltip() {}
    public function getJSGlobals($addMask = 0) {}
}


class ArenaTeamListFilter extends Filter
{
    public    $extraOpts     = [];
    protected $genericFilter = [];

    protected function createSQLForCriterium(&$cr)
    {
        // there are none, if we et one, thats an error!
        unset($cr);
        return [0];
    }

    protected function createSQLForValues()
    {
        $parts = [];
        $_v    = $this->fiData['v'];

        // region (rg), battlegroup (bg) and server (sv) are passed to ArenaTeamList as miscData and handled there

        // name [str]
        if (!empty($_v['na']))
            if ($_ = $this->modularizeString(['at.name'], $_v['na'], !empty($_v['ex']) && $_v['ex'] == 'on'))
                $parts[] = $_;

        // side [list]
        if (!empty($_v['si']))
        {
            if ($_v['si'] == 1)
                $parts[] = ['c.race', [1, 3, 4, 7, 11]];
            else if ($_v['si'] == 2)
                $parts[] = ['c.race', [2, 5, 6, 8, 10]];
            else
                unset($_v['ra']);
        }

        // size [int]
        if (!empty($_v['sz']))
        {
            if (in_array($_v['sz'], [2, 3, 5]))
                $parts[] = ['at.type', $_v['sz']];
            else
                unset($_v['sz']);
        }

        return $parts;
    }
}

?>
