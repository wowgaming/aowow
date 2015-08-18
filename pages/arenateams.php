<?php

if (!defined('AOWOW_REVISION'))
    die('invalid access');


// menuId 5: Profiler g_initPath()
//  tabId 1: Tools    g_initHeader()
class ArenaTeamsPage extends GenericPage
{
    use ProfilerPage;

    protected $tpl      = 'arena-teams';
    protected $js       = ['filters.js', 'profile_all.js', 'profile.js'];
    protected $css      = [['path' => 'Profiler.css']];
    protected $tabId    = 1;
    protected $path     = [1, 5, 3];

    protected $sumTeams = 0;

    public function __construct($pageCall, $pageParam)
    {
        $this->getSubjectFromUrl($pageParam);

        $this->filterObj = new ArenaTeamListFilter();

        // clean search if possible
        $form = $this->filterObj->getForm('form');
        if (!empty($form['rg']))
        {
            $url = '?arena-teams='.$form['rg'];
            if (!empty($form['sv']))
                $url .= '.'.Util::urlize($form['sv']);

            if ($_ = $this->filterObj->urlize(['sv' => '', 'rg' => '']))
                $url .= '&filter='.$_;

            header('Location: '.$url , true, 302);
        }

        foreach (Util::getRealms() as $idx => $r)
        {
            if ($this->region && $r['region'] != $this->region)
                continue;

            if ($this->realm && $r['name'] != $this->realm)
                continue;

            $this->sumTeams += DB::Characters($idx)->selectCell('SELECT count(*) FROM arena_team');
        }

        parent::__construct($pageCall, $pageParam);

        $this->name   = Lang::profiler('arenaTeams');
        $this->subCat = $pageParam ? '='.$pageParam : '';
    }

    protected function generateTitle()
    {
        if ($this->realm)
            array_unshift($this->title, $this->realm,/* CFG_BATTLEGROUP,*/ Lang::profiler('regions', $this->region), Lang::profiler('arenaTeams'));
        else if ($this->region)
            array_unshift($this->title, Lang::profiler('regions', $this->region), Lang::profiler('arenaTeams'));
        else
            array_unshift($this->title, Lang::profiler('arenaTeams'));
    }

    protected function generateContent()
    {
        $this->addJS('?data=realms&locale='.User::$localeId.'&t='.$_SESSION['dataKey']);

        // recreate form selection
        $this->filter = $this->filterObj->getForm('form');
        $this->filter['query'] = isset($_GET['filter']) ? $_GET['filter'] : null;
        $this->filter['fi']    =  $this->filterObj->getForm();

        $conditions = [];
        if (!User::isInGroup(U_GROUP_EMPLOYEE))
            $conditions[] = ['at.rating', 1000, '>'];

        if ($_ = $this->filterObj->getConditions())
            $conditions[] = $_;

        $data   = [];
        $hCols  = ['arenateam', 'guild'];
        $vCols  = ['rank', 'wins', 'losses', 'rating'];
        $params = array(
            'id'          => 'arena-teams',
            'hideCount'   => 1,
            'roster'      => 3,
            'sort'        => "$[6]",
            'extraCols'   => "$[Listview.extraCols.members]",
            // 'onBeforeCreate' => '$pr_initRosterListview'        // $_GET['roster'] = 1|2|3|4 .. 2,3,4 arenateam-size (4 => 5-man), 1 guild .. it puts a resync button on the lv...
        );

        $miscParams = [];
        if ($this->realm)
            $miscParams['sv'] = $this->realm;
        if ($this->region)
            $miscParams['rg'] = $this->region;

        $teams = new ArenaTeamList($conditions, $miscParams);
        if (!$teams->error)
        {
            $dFields = $teams->hasDiffFields(['faction', 'type']);
            if (!($dFields & 0x1))
                $hCols[] = 'faction';

            if (($dFields & 0x2))
                $vCols[] = 'size';

            $data = $teams->getListviewData();

            // create note if search limit was exceeded
            if (0 /* filter were applied */)
            {
                $params['note'] = sprintf(Util::$tryFilteringString, 'LANG.lvnote_arenateamsfound2', $this->sumTeams, $teams->getMatches());
                $params['_truncated'] = 1;
            }
            else
                $params['note'] = sprintf(Util::$tryFilteringString, 'LANG.lvnote_arenateamsfound', $this->sumTeams, 0);

            if ($this->filterObj->error)
                $params['_errors'] = '$1';

            $params['hiddenCols']  = '$'.Util::toJSON($hCols);
            $params['visibleCols'] = '$'.Util::toJSON($vCols);
        }

        $this->lvTabs[] = array(
            'file'   => 'profile',
            'data'   => $data,
            'params' => $params
        );

        Lang::sort('game', 'cl');
        Lang::sort('game', 'ra');
    }

    private function getTalentDistribution($tString)
    {
        $classMask = 1 << ($this->character['classs'] - 1);
        $distrib   = DB::Aowow()->selectCol('SELECT COUNT(t.id) FROM dbc_talent t JOIN dbc_talenttab tt ON t.tabId = tt.id WHERE tt.classMask & ?d GROUP BY tt.id ORDER BY tt.tabNumber ASC', $classMask);
        $result    = [0, 0, 0];

        $start = 0;
        foreach ($distrib as $idx => $len)
        {
            $result[$idx] = array_sum(str_split(substr($tString, $start, $len)));
            $start += $len;
        }

        return $result;
    }
}

?>
