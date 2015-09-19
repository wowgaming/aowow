<?php

if (!defined('AOWOW_REVISION'))
    die('invalid access');

// !do not cache!
/* older version
new Listview({
    template: 'profile',
    id: 'characters',
    name: LANG.tab_characters,
    parent: 'lkljbjkb574',
    visibleCols: ['race','classs','level','talents','gearscore','achievementpoints','rating'],
    sort: [-15],
    hiddenCols: ['arenateam','guild','location'],
    onBeforeCreate: pr_initRosterListview,
    data: [
        {id:30577430,name:'Çircus',achievementpoints:0,guild:'swaggin',guildrank:5,arenateam:{2:{name:'the bird knows the word',rating:1845}},realm:'maiev',realmname:'Maiev',battlegroup:'whirlwind',battlegroupname:'Whirlwind',region:'us',roster:2,row:1},
        {id:10602015,name:'Gremiss',achievementpoints:3130,guild:'Team Discovery Channel',guildrank:3,arenateam:{2:{name:'the bird knows the word',rating:1376}},realm:'maiev',realmname:'Maiev',battlegroup:'whirlwind',battlegroupname:'Whirlwind',region:'us',level:80,race:5,gender:1,classs:9,faction:1,gearscore:2838,talenttree1:54,talenttree2:17,talenttree3:0,talentspec:1,roster:2,row:2}
    ]
});
*/

// menuId 5: Profiler g_initPath()
//  tabId 1: Tools    g_initHeader()
class ProfilesPage extends GenericPage
{
    use TrProfiler;

    protected $tpl      = 'profiles';
    protected $js       = ['filters.js', 'profile_all.js', 'profile.js'];
    protected $css      = [['path' => 'Profiler.css']];
    protected $tabId    = 1;
    protected $path     = [1, 5, 0];
    protected $region   = '';                               // seconded..
    protected $realm    = '';                               // not sure about the use

    protected $sumChars = 0;

    public function __construct($pageCall, $pageParam)
    {
        $cat = explode('.', $pageParam);
        if ($cat[0] && count($cat) < 3 && $cat[0] === 'eu' || $cat[0] === 'us')
        {
            $this->region = $cat[0];

            // if ($cat[1] == Util::urlize(CFG_BATTLEGROUP))
                // $this->realm = CFG_BATTLEGROUP;

            if (isset($cat[1]))
            {
                foreach (Util::getRealms() as $r)
                {
                    if (Util::urlize($r['name']) == $cat[1])
                    {
                        $this->realm = $r['name'];
                        break;
                    }
                }
            }
        }

        $this->filterObj = new ProfileListFilter();

        // clean search if possible
        $form = $this->filterObj->getForm('form');
        if (!empty($form['rg']))
        {
            $url = '?profiles='.$form['rg'];
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

            $this->sumChars += DB::Characters($idx)->selectCell('SELECT count(*) FROM characters WHERE deleteInfos_Name IS NULL');
        }

        parent::__construct($pageCall, $pageParam);

        $this->name   = Util::ucFirst(Lang::game('profiles'));
        $this->subCat = $pageParam ? '='.$pageParam : '';
    }

    protected function generateTitle()
    {
        $this->title[] = Util::ucFirst(Lang::game('profiles'));

        // -> battlegroup
        // -> server
        // -> region
        // Alonsus - Cruelty / Crueldad - Europe - Profiles - World of Warcraft
        // Norgannon - German - Europe - Profile - World of Warcraft

        // direkt unter </form>
        // &roster=1 => Guild
        // &roster=[2-4] => Arena Team
        // <div class="text"><h2 style="padding-top: 0;">sprintf($guildRoster/$arenaRoster, $name)</h2></div>
    }

    protected function generatePath()
    {
        if ($this->region)
        {
            $this->path[] = $this->region;

            if ($this->realm)
            {
                $this->path[] = Util::urlize(CFG_BATTLEGROUP);
                if ($this->realm != CFG_BATTLEGROUP)
                    $this->path[] = Util::urlize($this->realm);
            }
        }
    }

    protected function generateContent()
    {
        $this->addJS('?data=weight-presets.realms&locale='.User::$localeId.'&t='.$_SESSION['dataKey']);

        $conditions = array(
            ['deleteInfos_Name', null]
        );

        // if (!User::isInGroup(U_GROUP_EMPLOYEE))
            // $conditions[] = [['cuFlags', CUSTOM_EXCLUDE_FOR_LISTVIEW, '&'], 0];

        // if ($this->category)
            // $conditions[] = ['typeCat', (int)$this->category[0]];

        // recreate form selection
        $this->filter = $this->filterObj->getForm('form');
        $this->filter['query'] = isset($_GET['filter']) ? $_GET['filter'] : null;
        $this->filter['fi']    =  $this->filterObj->getForm();

        if ($_ = $this->filterObj->getConditions())
            $conditions[] = $_;

        $data   = [];
        $params = array(
            'id'          => 'characters',
            'name'        => '$LANG.tab_characters',
            'hideCount'   => 1,
            // 'roster'      => 3,
            'visibleCols' => "$['race', 'classs', 'level', 'talents', 'achievementpoints']",
            'onBeforeCreate' => '$pr_initRosterListview'        // $_GET['roster'] = 1|2|3|4 .. 2,3,4 arenateam-size (4 => 5-man), 1 guild .. it puts a resync button on the lv...
        );

        if ($_ = $this->filterObj->getForm('extraCols', true))
        {
            $xc = [];
            foreach ($_ as $skId)
                $xc[] = "Listview.funcBox.createSimpleCol('Skill + ".$skId."', g_spell_skills[".$skId."], '7%', 'skill + ".$skId."')";

            $params['extraCols'] = '$['.implode(', ', $xc).']';
        }

        $miscParams = [];
        if ($this->realm)
            $miscParams['sv'] = $this->realm;
        if ($this->region)
            $miscParams['rg'] = $this->region;

        $profiles = new ProfileList($conditions, $miscParams);
        if (!$profiles->error)
        {
            $data = $profiles->getListviewData();

            // create note if search limit was exceeded
            if (0 /* filter were applied */)
            {
                $params['note'] = sprintf(Util::$tryFilteringString, 'LANG.lvnote_charactersfound2', $this->sumChars, $profiles->getMatches());
                $params['_truncated'] = 1;
            }
            else
                $params['note'] = sprintf(Util::$tryFilteringString, 'LANG.lvnote_charactersfound', $this->sumChars, 0);

            if ($this->filterObj->error)
                $params['_errors'] = '$1';
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
