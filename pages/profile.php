<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


// menuId 5: Profiler g_initPath()
//  tabId 1: Tools    g_initHeader()
class ProfilePage extends GenericPage
{
    use TrProfiler;

    protected $gDataKey  = true;
    protected $tabId     = 1;
    protected $path      = [1, 5, 1];
    protected $tpl       = 'profile';
    protected $js        = ['filters.js', 'TalentCalc.js', 'swfobject.js', 'profile_all.js', 'profile.js', 'Profiler.js'];
    protected $css       = array(
        ['path' => 'talentcalc.css'],
        ['path' => 'Profiler.css']
    );

    private   $isCustom  = false;
    private   $profile   = null;

    public function __construct($pageCall, $pageParam)
    {
        $params = explode('.', $pageParam);

        parent::__construct($pageCall, $pageParam);

        // temp locale
        if ($this->mode == CACHE_TYPE_TOOLTIP && isset($_GET['domain']))
            Util::powerUseLocale($_GET['domain']);

        if (count($params) == 1 && intVal($params[0]))
        {
            // todo: some query to validate existence of char
            if ($foo = DB::Aowow()->selectCell('SELECT 2161862'))
                $this->subjectGUID = $foo;
            else
                $this->notFound();

            $this->isCustom  = true;
            $this->profile = intVal($params[0]);
        }
        else if (count($params) == 3)
        {

            $this->getSubjectFromUrl($pageParam);
            if (!$this->subjectName)
                $this->notFound();

            // names MUST be ucFirst. Since we don't expect partial matches, search this way

            // 3 possibilities
            // 1) already synced to aowow
            if ($this->subjectGUID = DB::Aowow()->selectCell('SELECT id FROM ?_profiler_profiles WHERE realm = ?d AND name = ?', $this->realmId, Util::ucFirst($this->subjectName)))
            {
                $this->subject = new ProfileList(array(['name', Util::ucFirst($this->subjectName)]), ['sv' => $params[1]]);
                if ($this->subject->error)
                    $this->notFound();

                // $this->subjectGUID = $this->subject->getField('guid');
                $this->profile = $params;

            }
            // 2) not yet synced but exists on realm
            else if ($guid = DB::Characters($this->realmId)->selectCell('SELECT guid FROM characters WHERE name = ?', Util::ucFirst($this->subjectName)))
            {
                $newId = Util::scheduleResync(TYPE_PROFILE, $this->realmId, $guid);
                $this->doResync = ['profile', $newId];
                $this->initialSync();
            }
            // 3) does not exist at all
            else
                $this->notFound();
        }
        else if ($params || !isset($_GET['new']))
            $this->notFound();
    }

    protected function generateContent()
    {
        // + .titles ?
        $this->addJS('?data=enchants.gems.glyphs.itemsets.pets.pet-talents.quick-excludes.realms.statistics.weight-presets.achievements&locale='.User::$localeId.'&t='.$_SESSION['dataKey']);
    }

    protected function generatePath()
    {

    }

    protected function generateTitle()
    {
        array_unshift($this->title, Util::ucFirst(Lang::game('profile')));
    }

    protected function generateTooltip($asError = false)
    {
        $x = '$WowheadPower.registerProfile('.($this->isCustom ? $this->profile : "'".implode('.', $this->profile)."'").', '.User::$localeId.', {';
        if ($asError)
            return $x."});";

        $name       = $this->subject->getField('name');
        $guild      = $this->subject->getField('guild');
        $gRankName  = $this->subject->getField('guildrank');
        $lvl        = $this->subject->getField('level');
        $ra         = $this->subject->getField('race');
        $cl         = $this->subject->getField('class');
        $gender     = $this->subject->getField('gender');
        // $desc       = $this->subject->getField('description');
        $title      = '';
        if ($_ = $this->subject->getField('chosenTitle'))
            $title = (new TitleList(array(['bitIdx', $_])))->getField($gender ? 'female' : 'male', true);

        if ($this->isCustom)
            $name .= ' (Custom Profile)';
        else if ($title)
            $name = sprintf($title, $name);

        $x .= "\n";
        $x .= "\tname_".User::$localeString.": '".Util::jsEscape($name)."',\n";
        $x .= "\ttooltip_".User::$localeString.": '".$this->subject->renderTooltip()."',\n";
        $x .= "\ticon: \$WH.g_getProfileIcon(".$ra.", ".$cl.", ".$gender.", ".$lvl."),\n";           // (race, class, gender, level, iconOrId, 'medium')
        $x .= "});";

        return $x;
    }

    public function display($override = '')
    {
        if ($this->mode != CACHE_TYPE_TOOLTIP)
            return parent::display($override);

        // do not cache profile tooltips
        header('Content-type: application/x-javascript; charset=utf-8');
        die($this->generateTooltip());
    }

    public function notFound()
    {
        if ($this->mode != CACHE_TYPE_TOOLTIP)
            return parent::notFound(Util::ucFirst(Lang::game('profile')), '[NNF]profile or char doesn\'t exist');

        header('Content-type: application/x-javascript; charset=utf-8');
        echo $this->generateTooltip(true);
        exit();
    }
}

?>
