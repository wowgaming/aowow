<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


// class CharacterList extends BaseType                     // new profiler-related parent: ProfilerType?; maybe a trait is enough => use ProfileHelper;
// class GuildList extends BaseType
// class ArenaTeamList extends BaseType

trait customProfileHelper
{
    protected     $queryBase = ''; // SELECT p.*, p.id AS ARRAY_KEY FROM ?_profiles p';
    protected     $queryOpts = array(
                        'p'   => [['pa', 'pg']],
                        'pam' => [['?_profiles_arenateam_member pam ON pam.memberId = p.id', true], 's' => ', pam.status'],
                        'pa'  => ['?_profiles_arenateam pa ON pa.id = pam.teamId', 's' => ', pa.mode, pa.name'],
                        'pgm' => [['?_profiles_guid_member pgm ON pgm.memberId = p.Id', true], 's' => ', pgm.rankId'],
                        'pg'  => ['?_profiles_guild pg ON pg.if = pgm.guildId', 's' => ', pg.name']
                    );
}

class ProfileList extends BaseType
{
    use profilerHelper;

    protected   $queryBase = 'SELECT `c`.*, `c`.`guid` AS ARRAY_KEY FROM characters c';
    protected   $queryOpts = array(
                    'c'   => [['gm', 'g', 'ca', 'ct', 'atm'], 'g' => 'ARRAY_KEY', 'o' => 'level DESC, name ASC'],
                    'gm'  => ['j' => ['guild_member gm ON gm.guid = c.guid', true], 's' => ', gm.rank AS guildRank'],
                    'g'   => ['j' => ['guild g ON g.guildid = gm.guildid', true], 's' => ', g.name AS guild'],
                    'ca'  => ['j' => ['character_achievement ca ON ca.guid = c.guid', true], 's' => ', GROUP_CONCAT(DISTINCT ca.achievement SEPARATOR " ") AS _acvs'],
                    'ct'  => ['j' => ['character_talent ct ON ct.guid = c.guid AND ct.spec = c.activespec', true], 's' => ', GROUP_CONCAT(DISTINCT ct.spell SEPARATOR " ") AS _talents'],
                    'atm' => ['j' => ['arena_team_member atm ON atm.guid = c.guid', true], 's' => ', GROUP_CONCAT(DISTINCT CONCAT(atm.arenaTeamId, ":", atm.personalRating) SEPARATOR " ") AS _teamData'],
                    'at'  => ['j' => 'arena_team at ON atm.arenaTeamId = at.arenaTeamId'],
                    'sk'  => ['j' => 'character_skills sk ON sk.guid = c.guid', 's' => ', sk.value AS skillValue']
                );

    public function __construct($conditions = [], $miscData = null)
    {
        // select DB by realm
        if (!$this->selectRealms($miscData))
        {
            trigger_error('no access to auth-db or table realmlist is empty', E_USER_WARNING);
            return;
        }

        parent::__construct($conditions, $miscData);

        // $character = array(
            // 'id'                => 2,
            // 'name'              => 'CharName',
            // 'region'            => ['eu', 'Europe'],
            // 'battlegroup'       => ['pure-pwnage', 'Pure Pwnage'],
            // 'realm'             => ['dafuque', 'da\'Fuqúe'],
            // 'level'             => 80,
            // 'classs'            => 11,
            // 'race'              => 6,
            // 'faction'           => 1,                           // 0:alliance; 1:horde
            // 'gender'            => 1,                           // 0:male, 1:female
            // 'skincolor'         => 0,                           // playerbytes  % 256
            // 'hairstyle'         => 0,                           // (playerbytes >> 16) % 256
            // 'haircolor'         => 0,                           // (playerbytes >> 24) % 256
            // 'facetype'          => 0,                           // (playerbytes >> 8) % 256                 [maybe features]
            // 'features'          => 0,                           // playerBytes2 % 256                       [maybe facetype]
            // 'source'            => 2,                           // source: used if you create a profile from a genuine character. It inherites region, realm and bGroup
            // 'sourcename'        => 'SourceCharName',            //  >   if these three are false we get a 'genuine' profile [0 for genuine characters..?]
            // 'user'              => 1,                           //  >   'genuine' is the parameter for _isArmoryProfile(allowCustoms)   ['' for genuine characters..?]
            // 'username'          => 'TestUser',                  //  >   also, if 'source' <> 0, the char-icon is requestet via profile.php?avatar
            // 'published'         => 1,                           // public / private
            // 'pinned'            => 1,                           // usable for some utility funcs on site
            // 'nomodel'           => 0x0,                         // unchecks DisplayOnCharacter by (1 << slotId - 1)
            // 'title'             => 0,                           // titleId currently in use or null
            // 'guild'             => 'GuildName',                 // only on chars; id or null
            // 'description'       => 'this is a profile',         // only on custom profiles
            // 'arenateams'        => [],                          // [size(2|3|5) => DisplayName]; DisplayName gets urlized to use as link
            // 'playedtime'        => 0,                           // exact to the day
            // 'lastupdated'       => 0,                           // timestamp in ms
            // 'achievementpoints' => 0,                           // max you have
            // 'talents'           => array(
                // 'builds' => array(
                    // ['talents' => '', 'glyphs' => ''],          // talents:string of 0-5 points; glyphs: itemIds.join(':')
                // ),
                // 'active'  => 1                                  // 1|2
            // ),
            // 'customs'           => [],                          // custom profiles created from this char; profileId => [name, ownerId, iconString(optional)]
            // 'skills'            => [],                          // skillId => [curVal, maxVal]; can contain anything, should be limited to prim/sec professions
            // 'inventory'         => [],                          // slotId => [itemId, subItemId, permEnchantId, tempEnchantId, gemItemId1, gemItemId2, gemItemId3, gemItemId4]
            // 'auras'             => [],                          // custom list of buffs, debuffs [spellId]

            // // completion lists: [subjectId => amount/timestamp/1]
            // 'reputation'        => [],                          // factionId => amount
            // 'titles'            => [],                          // titleId => 1
            // 'spells'            => [],                          // spellId => 1; recipes, pets, mounts
            // 'achievements'      => [],                          // achievementId => timestamp
            // 'quests'            => [],                          // questId => 1

            // // UNKNOWN
            // 'bookmarks'         => [2],                         // UNK pinned or claimed userId => profileIds..?
            // 'statistics'        => [],                          // UNK all statistics?      [achievementId => killCount]
            // 'activity'          => [],                          // UNK recent achievements? [achievementId => killCount]
            // 'glyphs'            => [],                          // not really used .. i guess..?
            // 'pets'              => array(                       // UNK
                // [],                                             // one array per pet, structure UNK
            // ),
        // );

        if ($this->error)
            return;

        reset($this->dbNames);                              // only use when querying single realm
        $realmId     = key($this->dbNames);
        $realms      = Util::getRealms();
        $acvCache    = [];
        $talentCache = [];
        $atCache     = [];
        $distrib     = [];

        // post processing
        foreach ($this->iterate() as $guid => &$curTpl)
        {
            // guild / +rank
            if (!$curTpl['guild'])
                $curTpl['guild'] = 0;

            if (!$curTpl['guildRank'])
                $curTpl['guildRank'] = -1;

            // battlegroup
            $curTpl['battlegroup'] = CFG_BATTLEGROUP;

            // realm
            if (strpos($guid, ':'))
            {
                $r = explode(':', $guid)[0];
                if (!empty($realms[$r]))
                {
                    $curTpl['realm']  = $realms[$r]['name'];
                    $curTpl['region'] = $realms[$r]['region'];
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
            }

            // achievement points pre
            if ($acvs = explode(' ', $curTpl['_acvs']))
                foreach ($acvs as $a)
                    if ($a && !isset($acvCache[$a]))
                        $acvCache[$a] = $a;

            // talent points pre
            if ($talents = explode(' ', $curTpl['_talents']))
                foreach ($talents as $t)
                    if ($t && !isset($talentCache[$t]))
                        $talentCache[$t] = $t;

            // arenateam membership
            if ($arenaTeams = explode(' ', $curTpl['_teamData']))
                foreach ($arenaTeams as $at)
                    if ($_ = explode(':', $at))
                        if (!isset($atCache[$_[0]]))
                            $atCache[$_[0]] = $_[0];

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

        if ($acvCache)
            $acvCache = DB::Aowow()->selectCol('SELECT id AS ARRAY_KEY, points FROM ?_achievement WHERE id IN (?a)', $acvCache);

        if ($atCache)
            $atCache = new ArenaTeamList(array(['at.arenaTeamId', array_values($atCache)]), $miscData);

        foreach ($this->iterate() as $guid => &$curTpl)
        {
            if ($limit <= 0 || $distrib[$curTpl['realm']] <= 0)
            {
                unset($this->templates[$guid]);
                continue;
            }

            $distrib[$curTpl['realm']]--;
            $limit--;

            $a  = explode(' ', $curTpl['_acvs']);
            $t  = explode(' ', $curTpl['_talents']);
            $td = explode(' ', $curTpl['_teamData']);
            unset($curTpl['_acvs']);
            unset($curTpl['_talents']);
            unset($curTpl['_teamData']);

            // achievement points post
            $curTpl['achievementpoints'] = array_sum(array_intersect_key($acvCache, array_combine($a, $a)));

            // talent points post
            $curTpl['talents'] = [0, 0, 0];
            if ($t)
                Util::arraySumByKey($curTpl['talents'], DB::Aowow()->selectCol('SELECT tab AS ARRAY_KEY, SUM(rank) FROM ?_talents WHERE spell IN (?a) AND `class` = ?d GROUP BY tab', $t, $curTpl['class']));

            // arenateams
            $curTpl['arenateams'] = [];
            foreach ($td as $data)
            {
                $d = explode(':', $data);
                if ($atCache->getEntry($d[0]))
                {
                    $curTpl['arenateams'][$atCache->getField('type')] = array(
                        'name'   => $atCache->getField('name'),
                        'rating' => $d[1]
                    );
                }
            }
        }
    }

    public function getListviewData($addInfo = 0)
    {
        $data = [];
        foreach ($this->iterate() as $__)
        {
            $data[$this->id] = array(
                'id'                => $this->curTpl['guid'],
                'name'              => $this->curTpl['name'],
                'race'              => $this->curTpl['race'],
                'classs'            => $this->curTpl['class'],
                'gender'            => $this->curTpl['gender'],
                'level'             => $this->curTpl['level'],
                'faction'           => (1 << ($this->curTpl['race'] - 1)) & RACE_MASK_ALLIANCE ? 0 : 1,
                'talenttree1'       => $this->curTpl['talents'][0],
                'talenttree2'       => $this->curTpl['talents'][1],
                'talenttree3'       => $this->curTpl['talents'][2],
                'talentspec'        => $this->curTpl['activespec']++,               // 0 => 1; 1 => 2
                'achievementpoints' => $this->curTpl['achievementpoints'],
                'guild'             => $this->curTpl['guild'],                      // 0 if none
                'guildrank'         => $this->curTpl['guildRank'],
                'realm'             => Util::urlize($this->curTpl['realm']),
                'realmname'         => $this->curTpl['realm'],
                // 'battlegroup'       => Util::urlize($this->curTpl['battlegroup']),  // was renamed to subregion somewhere around cata release
                // 'battlegroupname'   => $this->curTpl['battlegroup'],
                'region'            => Util::urlize($this->curTpl['region'])
            );

            if ($addInfo == PROFILEINFO_ARENA_2S)
                $data[$this->id]['rating'] = $this->curTpl['arenateams'][2]['rating'];
            else if ($addInfo == PROFILEINFO_ARENA_3S)
                $data[$this->id]['rating'] = $this->curTpl['arenateams'][3]['rating'];
            else if ($addInfo == PROFILEINFO_ARENA_5S)
                $data[$this->id]['rating'] = $this->curTpl['arenateams'][5]['rating'];
            else
                $data[$this->id]['arenateams'] = $this->curTpl['arenateams'];

            // if (!empty($this->curTpl['description']))
                // $data[$this->id]['description'] = $this->curTpl['description'];

            // if (!empty($this->curTpl['icon']))
                // $data[$this->id]['icon'] = $this->curTpl['icon'];

            // if ($this->curTpl['cuFlags'] & PROFILE_CU_PUBLISHED)
                // $data[$this->id]['published'] = 1;

            // if ($this->curTpl['cuFlags'] & PROFILE_CU_PINNED)
                // $data[$this->id]['pinned'] = 1;

            // if ($this->curTpl['cuFlags'] & PROFILE_CU_DELETED)
                // $data[$this->id]['deleted'] = 1;
        }

        return array_values($data);
    }

    public function renderTooltip($interactive = false)
    {
        if (!$this->curTpl)
            return [];

        $title = '';
        $name  = $this->getField('name');
        if ($_ = $this->getField('chosenTitle'))
            $title = (new TitleList(array(['bitIdx', $_])))->getField($this->getField('gender') ? 'female' : 'male', true);

        if ($this->isCustom)
            $name .= ' (Custom Profile)';
        else if ($title)
            $name = sprintf($title, $name);

        $x  = '<table>';
        $x .= '<tr><td><b class="q">'.$name.'</b></td></tr>';
        if ($g = $this->getField('guild'))
            $x .= '<tr><td>&lt;'.$g.'&gt;</td></tr>';
        else if ($d = $this->getField('description'))
            $x .= '<tr><td>'.$d.'</td></tr>';
        $x .= '<tr><td>'.Lang::game('level').' '.$this->getField('level').' '.Lang::game('ra', $this->curTpl['race']).' '.Lang::game('cl', $this->curTpl['classs']).'</td></tr>';
        $x .= '</table>';

        return $x;
    }

    public function getJSGlobals($addMask = 0) {}
}


class ProfileListFilter extends Filter
{
    public    $extraOpts     = [];
    protected $enums         = array(
        -1 => array(                                        // arena team sizes
            12 => 2,
            15 => 3,
            18 => 5
        ),
        -2 => array(                                        // professions (by setting key #24, he next elements are increments of it)
            24 => null, 171, 164, 333, 202, 182, 773, 755, 165, 186, 393, 197
        ),
    );

    protected $genericFilter = array(                       // misc (bool): _NUMERIC => useFloat; _STRING => localized; _FLAG => match Value; _BOOLEAN => stringSet
        // { id: 2,   name: 'gearscore',               type: 'num' },
        // { id: 3,   name: 'achievementpoints',       type: 'num' },
        // { id: 21,  name: 'wearingitem',             type: 'str-small' },
        // { id: 23,  name: 'completedachievement',    type: 'str-small' },
        // { id: 5,   name: 'talenttree1',         type: 'num' },
        // { id: 6,   name: 'talenttree2',         type: 'num' },
        // { id: 7,   name: 'talenttree3',         type: 'num' },
         9 => [FILTER_CR_STRING,    'g.name',            ], // guildname
        10 => [FILTER_CR_NUMERIC,   'gm.rank'            ], // guildrank
    );

    protected function createSQLForCriterium(&$cr)
    {
        if (in_array($cr[0], array_keys($this->genericFilter)))
        {
            if ($genCR = $this->genericCriterion($cr))
                return $genCR;

            unset($cr);
            $this->error = true;
            return [1];
        }

        $skillId = 0;
        switch ($cr[0])
        {
            case 36:                                        // hasguild [yn]
                if ($this->int2Bool($cr[1]))
                    return ['gm.guildId', null, $cr[1] ? '!' : null];
                break;
            case 12:                                        // teamname2v2
            case 15:                                        // teamname3v3
            case 18:                                        // teamname5v5
                if ($_ = $this->modularizeString(['at.name'], $cr[2]))
                    return ['AND', ['at.type', $this->enums[-1][$cr[0]]], $_];

                break;
            case 13:                                        // teamrtng2v2
            case 16:                                        // teamrtng3v3
            case 19:                                        // teamrtng5v5
            case 14:                                        // teamcontrib2v2
            case 17:                                        // teamcontrib3v3
            case 20:                                        // teamcontrib5v5
                break;
            case 25:                                        // alchemy [num]
            case 26:                                        // blacksmithing [num]
            case 27:                                        // enchanting [num]
            case 28:                                        // engineering [num]
            case 29:                                        // herbalism [num]
            case 30:                                        // inscription [num]
            case 31:                                        // jewelcrafting [num]
            case 32:                                        // leatherworking [num]
            case 33:                                        // mining [num]
            case 34:                                        // skinning [num]
            case 35:                                        // tailoring [num]
                if (!$this->isSaneNumeric($cr[2]) || !$this->int2Op($cr[1]))
                    break;

                $this->extraOpts['sk']['s'][] = ', sk.value AS skill'.$this->enums[-2][$cr[0]];
                $this->formData['extraCols'][] = $this->enums[-2][$cr[0]];
                return ['AND', ['sk.skill', $this->enums[-2][$cr[0]]], ['sk.value', $cr[2], $cr[1]]];
        }

        unset($cr);
        $this->error = 1;
        return [1];
    }

    protected function createSQLForValues()
    {
        $parts = [];
        $_v    = $this->fiData['v'];

        // region (rg), battlegroup (bg) and server (sv) are passed to ArenaTeamList as miscData and handled there

        // name [str] - the table is case sensitive. Since i down't want to destroy indizes, lets alter the search terms
        if (!empty($_v['na']))
        {
            $lower  = $this->modularizeString(['c.name'], Util::lower($_v['na']),   !empty($_v['ex']) && $_v['ex'] == 'on');
            $proper = $this->modularizeString(['c.name'], Util::ucWords($_v['na']), !empty($_v['ex']) && $_v['ex'] == 'on');

            $parts[] = ['OR', $lower, $proper];
        }

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

        // race [list]
        if (!empty($_v['ra']))
        {
            $_ = (array)$_v['ra'];
            if ($_ = array_intersect([1, 2, 3, 4, 5, 6, 7, 8, 10, 11], $_))
                $parts[] = ['c.race', $_];
            else
                unset($_v['ra']);
        }

        // class [list]
        if (!empty($_v['cl']))
        {
            $_ = (array)$_v['cl'];
            if ($_ = array_intersect([1, 2, 3, 4, 5, 6, 7, 8, 9, 11], $_))
                $parts[] = ['c.class', $_];
            else
                unset($_v['cl']);
        }

        // min level [int]
        if (isset($_v['minle']))
        {
            if (is_int($_v['minle']) && $_v['minle'] > 0)
                $parts[] = ['c.level', $_v['minle'], '>='];
            else
                unset($_v['minle']);
        }

        // max level [int]
        if (isset($_v['maxle']))
        {
            if (is_int($_v['maxle']) && $_v['maxle'] > 0)
                $parts[] = ['c.level', $_v['maxle'], '<='];
            else
                unset($_v['maxle']);
        }


        return $parts;
    }
}

?>
