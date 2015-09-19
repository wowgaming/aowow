<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


// menuId 5: Profiler g_initPath()
//  tabId 1: Tools    g_initHeader()
class ArenaTeamPage extends GenericPage
{
    use ProfilerPage;


    protected $tabId    = 1;
    protected $path     = [1, 5, 3];
    protected $tpl      = 'arena-team';
    protected $js       = ['profile_all.js', 'profile.js'];
    protected $css      = [['path' => 'Profiler.css']];

    public function __construct($pageCall, $pageParam)
    {
        $this->getSubjectFromUrl($pageParam);
        if (!$this->subjectName)
            $this->notFound();

        parent::__construct($pageCall, $pageParam);

        $this->subject = new ArenaTeamList(array(['at.name', $this->subjectName]), ['sv' => $this->realm]);
        if ($this->subject->error)
        {
            $this->doResync = ['arena-team', 123456];
            $this->initialSync();
        }

        $this->name = sprintf(Lang::profiler('arenaRoster'), $this->subject->getField('name'));
    }

    protected function generateTitle()
    {
        // poperly format $realm
        $team  = ($this->subject->getField('name') ?: $this->subjectName);
        $team .= ' ('.$this->realm.' - '.($this->region == 'us' ? 'US &amp; Oceanic' : 'Europe').')';

        array_unshift($this->title, $team, Lang::game('profiles'));
    }

    protected function generateContent()
    {
        $this->addJS('?data=realms.weight-presets&locale='.User::$localeId.'&t='.$_SESSION['dataKey']);

        $this->redButtons[BUTTON_RESYNC] = [1056093, 'arena-team'];

        /****************/
        /* Main Content */
        /****************/


        // statistic calculations here


        /**************/
        /* Extra Tabs */
        /**************/

        // tab: members
        $member = new ProfileList(array(['atm.arenaTeamId', $this->subject->getField('arenaTeamId')]), ['sv' => $this->realm]);
        if (!$member->error)
        {
            $info = 0;
            switch ($this->subject->getField('type'))
            {
                case 2: $info = PROFILEINFO_ARENA_2S; break;
                case 3: $info = PROFILEINFO_ARENA_3S; break;
                case 5: $info = PROFILEINFO_ARENA_5S; break;
            }

            $this->lvTabs[] = array(
                'file'   => 'profile',
                'data'   => $member->getListviewData($info),
                'params' => array(
                    'sort'        => "$[-15]",
                    'visibleCols' => "$['race','classs','level','talents','gearscore','achievementpoints','rating']",
                    'hiddenCols'  => "$['arenateam','guild','location']"
                )
            );
        }
    }
}

?>
