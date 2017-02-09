<?php
/**
 * This file is a part of the CIDRAM package, and can be downloaded for free
 * from {@link https://github.com/Maikuolan/CIDRAM/ GitHub}.
 *
 * CIDRAM COPYRIGHT 2016 and beyond by Caleb Mazalevskis (Maikuolan).
 *
 * License: GNU/GPLv2
 * @see LICENSE.txt
 *
 * This file: Optional security extras module (last modified: 2017.02.03).
 *
 * Many thanks to Michael Hopkins, the creator of ZB Block (GNU/GPLv2), and to
 * the community behind it (Spambot Security) for inspiring/developing many of
 * the signatures contained within this module.
 */

/** Prevents execution from outside of CIDRAM. */
if (!defined('CIDRAM')) {
    die('[CIDRAM] This should not be accessed directly.');
}

/** Inherit trigger closure (see functions.php). */
$Trigger = $CIDRAM['Trigger'];

/** Options for instantly banning (sets tracking time to 1 year and infraction count to 1000). */
$InstaBan = array('Options' => array('TrackTime' => 31536000, 'TrackCount' => 1000));

/** Directory traversal protection. */
$Trigger(preg_match(
    "\x01" . '(?:/|%5[cf])\.{2,}(?:/|%5[cf])' . "\x01i",
str_replace("\\", '/', $CIDRAM['BlockInfo']['rURI'])), 'Traversal attack'); // 2017.01.13

/** Detect bad/dangerous/malformed requests. */
$Trigger(preg_match(
    "\x01" . '(?:(/|%5[cf])\.(/|%5[cf])|(/|%5[cf]){3,}|[\x00-\x1f\x7f])' . "\x01i",
str_replace("\\", '/', $CIDRAM['BlockInfo']['rURI'])), 'Bad request'); // 2017.01.13

/**
 * Query-based signatures start from here.
 * Please report all false positives to https://github.com/Maikuolan/CIDRAM/issues
 */
if (!empty($_SERVER['QUERY_STRING'])) {
    $Query = str_replace("\\", '/', strtolower(urldecode($_SERVER['QUERY_STRING'])));
    $QueryNoSpace = preg_replace('/\s/', '', $Query);

    $Trigger(preg_match('/\((?:["\']{2})?\)/', $QueryNoSpace), 'Command injection'); // 2016.12.31

    $Trigger(preg_match(
        '/(?:_once|able|as(c|hes|sert)|c(hr|ode|ontents)|e(cho|regi|scape|va' .
        'l)|ex(ec|ists)?|f(ile|late|unction)|get(c|csv|ss?)?|i(f|nclude)|len' .
        '(gth)?|nt|open|p(ress|lace|lode|uts)|print(f|_r)?|re(ad|place|quire' .
        '|store)|rot13|s(tart|ystem)|w(hil|rit)e)["\':(\[{<$]/',
    $QueryNoSpace), 'Command injection'); // 2017.01.13

    $Trigger(preg_match(
        '/\$(?:globals|_(cookie|env|files|get|post|request|se(rver|ssion)))/',
    $QueryNoSpace), 'Command injection'); // 2017.01.13

    $Trigger(preg_match('/http_(?:cmd|sum)/', $QueryNoSpace), 'Command injection'); // 2017.01.02
    $Trigger(preg_match('/pa(?:rse_ini_file|ssthru)/', $QueryNoSpace), 'Command injection'); // 2017.01.02
    $Trigger(preg_match('/rewrite(?:cond|rule)/', $QueryNoSpace), 'Command injection'); // 2017.01.02
    $Trigger(preg_match('/u(?:nserializ|ploadedfil)e/', $QueryNoSpace), 'Command injection'); // 2017.01.13
    $Trigger(strpos($QueryNoSpace, 'dotnet_load') !== false, 'Command injection'); // 2016.12.31
    $Trigger(strpos($QueryNoSpace, 'execcgi') !== false, 'Command injection'); // 2016.12.31
    $Trigger(strpos($QueryNoSpace, 'move_uploaded_file') !== false, 'Command injection'); // 2016.12.31
    $Trigger(strpos($QueryNoSpace, 'symlink') !== false, 'Command injection'); // 2016.12.31
    $Trigger(strpos($QueryNoSpace, 'tmp_name') !== false, 'Command injection'); // 2016.12.31
    $Trigger(strpos($QueryNoSpace, '_contents') !== false, 'Command injection'); // 2016.12.31

    $Trigger(preg_match('/%(?:0[0-8bcef]|1)/i', $_SERVER['QUERY_STRING']), 'Non-printable characters in query'); // 2016.12.31

    $Trigger(preg_match('/(?:amp(;|%3b)){2,}/', $QueryNoSpace), 'Nesting attack'); // 2016.12.31

    $Trigger((
        strpos($CIDRAM['BlockInfo']['rURI'], '/ucp.php?mode=login') === false &&
        preg_match('/%(?:(25){2,}|(25)+27)/', $_SERVER['QUERY_STRING'])
    ), 'Nesting attack'); // 2017.01.01

    $Trigger(preg_match(
        '/(?:<(\?|body|i?frame|object|script)|(body|i?frame|object|script)>)/',
    $QueryNoSpace), 'Script injection'); // 2017.01.05

    $Trigger(preg_match(
        '/_(?:cookie|env|files|get|post|request|se(rver|ssion))\[/',
    $QueryNoSpace), 'Global variable hack'); // 2017.01.13

    $Trigger(strpos($QueryNoSpace, 'globals['), 'Global variable hack'); // 2017.01.01

    $Trigger(substr($_SERVER['QUERY_STRING'], -3) === '%00', 'Null truncation attempt'); // 2016.12.31
    $Trigger(substr($_SERVER['QUERY_STRING'], -4) === '%000', 'Null truncation attempt'); // 2016.12.31
    $Trigger(substr($_SERVER['QUERY_STRING'], -5) === '%0000', 'Null truncation attempt'); // 2016.12.31

    $Trigger(strpos($QueryNoSpace, '@$' . '_[' . ']=' . '@!' . '+_') !== false, 'Shell upload attempt'); // 2017.01.02

    $Trigger(preg_match('/%(?:20\'|25[01u]|[46]1%[46]e%[46]4)/', $_SERVER['QUERY_STRING']), 'Hack attempt'); // 2017.01.05
    $Trigger(preg_match('/p(?:ath|ull)\[?\]/', $QueryNoSpace), 'Hack attempt'); // 2017.01.06
    $Trigger(preg_match('/\'%2[05]/', $_SERVER['QUERY_STRING']), 'Hack attempt'); // 2017.01.05
    $Trigger(preg_match('/\|(?:include|require)/', $QueryNoSpace), 'Hack attempt'); // 2017.01.01
    $Trigger(strpos($Query, 'rm ' . '-rf') !== false, 'Hack attempt', '', $InstaBan); // 2017.01.02
    $Trigger(strpos($QueryNoSpace, "'='") !== false, 'Hack attempt'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, '.php/login.php') !== false, 'Hack attempt'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, '1http:') !== false, 'Hack attempt'); // 2017.01.01
    $Trigger(strpos($QueryNoSpace, ';c' . 'hmod7' . '77') !== false, 'Hack attempt', '', $InstaBan); // 2017.01.05
    $Trigger(strpos($_SERVER['QUERY_STRING'], '=-1%27') !== false, 'Hack attempt'); // 2017.01.05
    $Trigger(substr($QueryNoSpace, 0, 1) === ';', 'Hack attempt'); // 2017.01.05

    $Trigger(substr($QueryNoSpace, 0, 2) === '()', 'Bash/Shellshock', '', $InstaBan); // 2017.01.05

    $Trigger(strpos($QueryNoSpace, 'allow_url_include=on') !== false, 'Plesk hack'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, 'auto_prepend_file=php://input') !== false, 'Plesk hack'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, 'cgi.force_redirect=0') !== false, 'Plesk hack'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, 'cgi.redirect_status_env=0') !== false, 'Plesk hack'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, 'disable_functions=""') !== false, 'Plesk hack'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, 'open_basedir=none') !== false, 'Plesk hack'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, 'safe_mode=off') !== false, 'Plesk hack'); // 2017.01.05
    $Trigger(strpos($QueryNoSpace, 'suhosin.simulation=on') !== false, 'Plesk hack'); // 2017.01.05

    $Trigger(preg_match('/pag(?:e|ina)=-/', $QueryNoSpace), 'Probe attempt'); // 2017.01.08
    $Trigger(substr($QueryNoSpace, 0, 1) === '-', 'Probe attempt'); // 2017.01.05

    $Trigger(preg_match(
        '/\[(?:[alrw]\]|classes|file|itemid|l(astrss_ap_enabled|oadfile|ocal' .
        'serverfile)|pth|src)/',
    $QueryNoSpace), 'Probe attempt'); // 2017.01.17

    $Trigger(strpos($QueryNoSpace, '+result:') !== false, 'Spam attempt'); // 2017.01.08
    $Trigger(strpos($QueryNoSpace, 'result:+\\') !== false, 'Spam attempt'); // 2017.01.08

    $Trigger(strpos($_SERVER['QUERY_STRING'], '++++') !== false, 'Overflow attempt'); // 2017.01.05
    $Trigger(preg_match('/[\'"`]\+[\'"`]/', $QueryNoSpace), 'XSS attack'); // 2017.01.03
    $Trigger(preg_match('/[\'"`]|[\'"`]/', $QueryNoSpace), 'Pipe detected'); // 2017.01.08
    $Trigger(preg_match('/(?:["\'];|[;=]\|)/', $QueryNoSpace), 'Execution attempt'); // 2017.01.13

    $Trigger(count($_REQUEST) >= 500, 'Hack attempt', 'Too many request variables sent!'); // 2017.01.01

}

/**
 * UA-based signatures start from here (UA = User Agent).
 * Please report all false positives to https://github.com/Maikuolan/CIDRAM/issues
 */
if ($CIDRAM['BlockInfo']['UA'] && !$Trigger(strlen($CIDRAM['BlockInfo']['UA']) > 4096, 'Bad UA', 'User agent string is too long!')) {
    $UA = str_replace("\\", '/', strtolower(urldecode($CIDRAM['BlockInfo']['UA'])));
    $UANoSpace = preg_replace('/\s/', '', $UA);

    $Trigger(preg_match('/\((?:["\']{2})?\)/', $UANoSpace), 'Command injection'); // 2017.01.02

    $Trigger(preg_match(
        '/(?:_once|able|as(c|hes|sert)|c(hr|ode|ontents)|e(cho|regi|scape|va' .
        'l)|ex(ec|ists)?|f(ile|late|unction)|get(c|csv|ss?)?|i(f|nclude)|len' .
        '(gth)?|open|p(ress|lace|lode|uts)|print(f|_r)?|re(ad|place|quire|st' .
        'ore)|rot13|s(tart|ystem)|w(hil|rit)e)["\':(\[{<$]/',
    $UANoSpace), 'Command injection'); // 2017.01.20

    $Trigger(preg_match(
        '/\$(?:globals|_(cookie|env|files|get|post|request|se(rver|ssion)))/',
    $UANoSpace), 'Command injection'); // 2017.01.13

    $Trigger(preg_match('/http_(?:cmd|sum)/', $UANoSpace), 'Command injection'); // 2017.01.02
    $Trigger(preg_match('/pa(?:rse_ini_file|ssthru)/', $UANoSpace), 'Command injection'); // 2017.01.02
    $Trigger(preg_match('/rewrite(?:cond|rule)/', $UANoSpace), 'Command injection'); // 2017.01.02
    $Trigger(preg_match('/u(?:nserialize|ploadedfile)/', $UANoSpace), 'Command injection'); // 2017.01.02
    $Trigger(strpos($UANoSpace, 'dotnet_load') !== false, 'Command injection'); // 2017.01.02
    $Trigger(strpos($UANoSpace, 'execcgi') !== false, 'Command injection'); // 2017.01.02
    $Trigger(strpos($UANoSpace, 'move_uploaded_file') !== false, 'Command injection'); // 2017.01.02
    $Trigger(strpos($UANoSpace, 'symlink') !== false, 'Command injection'); // 2017.01.02
    $Trigger(strpos($UANoSpace, 'tmp_name') !== false, 'Command injection'); // 2017.01.02
    $Trigger(strpos($UANoSpace, '_contents') !== false, 'Command injection'); // 2017.01.02

    $Trigger(preg_match('/%(?:0[0-8bcef]|1)/i', $CIDRAM['BlockInfo']['UA']), 'Non-printable characters in UA'); // 2017.01.02

    $Trigger(preg_match(
        '/(?:<(\?|body|i?frame|object|script)|(body|i?frame|object|script)>)/',
    $UANoSpace), 'Script injection'); // 2017.01.08

    $Trigger(preg_match(
        '/(?:globals|_(cookie|env|files|get|post|request|se(rver|ssion)))\[/',
    $UANoSpace), 'Global variable hack'); // 2017.01.13

    $Trigger(strpos($UANoSpace, '$_' . '[$' . '__') !== false, 'Shell upload attempt', '', $InstaBan); // 2017.01.02
    $Trigger(strpos($UANoSpace, '@$' . '_[' . ']=' . '@!' . '+_') !== false, 'Shell upload attempt', '', $InstaBan); // 2017.01.02

    $Trigger(preg_match('/0wn[3e]d/', $UANoSpace), 'Hack UA', '', $InstaBan); // 2017.01.06
    $Trigger(preg_match('/:(\{[a-z]:|[a-z0-9][;:]\})/', $UANoSpace), 'Hack UA', '', $InstaBan); // 2017.01.20
    $Trigger(preg_match('/h[4a]c' . 'k(?:e[dr]|ing|t([3e][4a]m|[0o]{2}l))/', $UANoSpace), 'Hack UA', '', $InstaBan); // 2017.01.06
    $Trigger(preg_match('/Y[EI]$/', $CIDRAM['BlockInfo']['UA']), 'Possible/Suspected hack UA'); // 2017.01.06
    $Trigger(strpos($UA, 'rm ' . '-rf') !== false, 'Hack UA', '', $InstaBan); // 2017.01.02
    $Trigger(strpos($UA, 'wordpress ha') !== false, 'Hack UA', '', $InstaBan); // 2017.01.06
    $Trigger(strpos($UANoSpace, 'if(') !== false, 'Hack UA', '', $InstaBan); // 2017.01.06
    $Trigger(strpos($UANoSpace, 'r0' . '0t') !== false, 'Hack UA', '', $InstaBan); // 2017.01.02
    $Trigger(strpos($UANoSpace, 'sh' . 'el' . 'l_' . 'ex' . 'ec') !== false, 'Hack UA', '', $InstaBan); // 2017.01.02
    $Trigger(strpos($UANoSpace, 'whcc/') !== false, 'Hack UA', '', $InstaBan); // 2017.01.06
    $Trigger(strpos($UANoSpace, '\0\0\0') !== false, 'Hack UA', '', $InstaBan); // 2017.01.09
    $Trigger(strpos($UANoSpace, '}__') !== false, 'Hack UA', '', $InstaBan); // 2017.01.02

    $Trigger(preg_match(
        '/(?:cha0s|f(hscan|uck)|jdatabasedrivermysqli|morfeus|urldumper|xmlset_roodkcable|zollard)/',
    $UANoSpace), 'Hack UA', '', $InstaBan); // 2017.02.03

    $Trigger(strpos($UANoSpace, 'captch') !== false, 'CAPTCHA cracker UA'); // 2017.01.08

    $Trigger(preg_match('/(?:(aihit|casper)bot|mamac(asper|yber))/', $UANoSpace), 'Probe UA', '', $InstaBan); // 2017.01.06

    $Trigger(preg_match(
        '/(?:^b55|-agent-|auto_?http|bigbrother|cybeye|d((iavol|ragoste)a|ow' .
        'nloaddemon)|e(ak01ag9|catch)|i(chiro|ndylibrary|ntelium)|k(angen|mc' .
        'crew)|libwww-pavuk|m(o(get|zillaxyz)|sie6\.0.*deepnet)|net(ants|com' .
        'ber)|p(atchone|aros|entru|lanetwork|robethenet)|riddler|s(asqia|led' .
        'ink|noopy|tingbot)|toata|updown_tester|w(hitehataviator|orio)|xirio' .
        '|you?dao|zmeu)/',
    $UANoSpace), 'Probe UA'); // 2017.02.02

    $Trigger(preg_match(
        '/(?: obot|ie 5\.5 compatible browser)/',
    $UA), 'Probe UA'); // 2017.02.02

    $Trigger(strpos($UANoSpace, 'wopbot') !== false, 'Bash/Shellshock UA', '', $InstaBan); // 2017.01.06

    $Trigger(preg_match('/(?:x(rumer|pymep)|хрумер)/', $UANoSpace), 'Spam UA', '', $InstaBan); // 2017.01.02
    $Trigger(preg_match('/[<\[](?:a|link|url)[ =>\]]/', $UA), 'Spam UA'); // 2017.01.02
    $Trigger(preg_match('/^\.?=/', $UANoSpace), 'Spam UA'); // 2017.01.07
    $Trigger(strpos($UANoSpace, '/how-') !== false, 'Spam UA'); // 2017.01.04
    $Trigger(strpos($UANoSpace, '>click') !== false, 'Spam UA'); // 2017.01.04
    $Trigger(strpos($UANoSpace, 'ruru)') !== false, 'Spam UA'); // 2017.01.07

    $Trigger(preg_match(
        '/(?:a(btasty|dwords|llsubmitter|velox)|b(acklink|ad-neighborhood|ds' .
        'm|ea?stiality|iloba|ork-edition|uyessay)|c(asino|ialis|igar|heap|ou' .
        'rsework)|d(eltasone|issertation|rugs)|e(ditionyx|roti[ck]|stimatewe' .
        'bstats)|f(orex|unbot)|g(abapentin|erifort|inkg?o|uestbook)|h(entai|' .
        'rbot)|in(cest|come|vestment)|jailbreak|k(amagra|eylog)|l(axative|e(' .
        'sbian|vitra|xap)|i(ker\.profile|nk(ba|che)ck|pitor)|olita|uxury|yco' .
        'sa\.se)|m(ail\.ru|e(laleuca|nthol)|ixrank|rie8pack)|n(e(rdybot|tzch' .
        'eckbot|urontin)|olvadex)|o(rgasm|utlet)|p(axil|harma|illz|lavix|orn' .
        '|r(0n|opecia|osti))|r(eviewsx|ogaine)|s(ex[xy]|hemale|ickseo|limy|p' .
        'utnik|tart\.exe|terapred|ynthroid)|t(entacle|[0o]p(hack|less|sites)' .
        ')|u(01-2|nlock)|v((aluation|oila)bot|arifort|[1i](agra|olation|tol)' .
        ')|xanax|zdorov)/',
    $UANoSpace), 'Spam UA'); // 2017.02.02

    $Trigger(preg_match(
        '/(?: (audit|href|mra |quibids )|\(build 5339\))/',
    $UA), 'Spam UA'); // 2017.02.02

    $Trigger(preg_match('/[\'"`]\+[\'"`]/', $UANoSpace), 'XSS attack'); // 2017.01.03
    $Trigger(strpos($UANoSpace, '`') !== false, 'Execution attempt'); // 2017.01.13

    $Trigger(preg_match(
        '/(?:digger|e((mail)?collector|mail(ex|search|spider|siphon)|xtract(' .
        'ion|or))|iscsystems|microsofturl|oozbot|psycheclone)/',
    $UANoSpace), 'Email havester'); // 2017.01.07

    $Trigger(strpos($UANoSpace, 'email') !== false, 'Possible/Suspected email havester'); // 2017.01.06

    $Trigger(preg_match('/%(?:[01][0-9a-f]|2[257]|3[ce]|[57][bd]|[7f]f)/', $UANoSpace), 'Bad UA'); // 2017.01.06

    $Trigger(preg_match(
        '/(?:re-?animator|webster)/',
    $UANoSpace), 'Banned UA', '', $InstaBan); // 2017.02.02

    $Trigger(preg_match('/test\'?$/', $UANoSpace), 'Banned UA'); // 2017.02.02
    $Trigger(preg_match('/^(?:\'?test|-|default|foo)/', $UANoSpace), 'Banned UA'); // 2017.02.02
    $Trigger(preg_match('/^[\'"].*[\'"]$/', $UANoSpace), 'Banned UA'); // 2017.02.02
    $Trigger(strpos($UA, '   ') !== false, 'Banned UA'); // 2017.02.02
    $Trigger(strpos($UANoSpace, '(somename)') !== false, 'Banned UA', '', $InstaBan); // 2017.02.02

    $Trigger(preg_match(
        '/(?:3mir|a(dmantx|lphaserver|thens|ttache)|blogsnowbot|collect|d(at' .
        'acha|igout4uagent|sarobot)|exabot|f(astlwspider|indlinks|loodgate)|' .
        'irgrabber|m(agnet|(ajestic|j)12|fibot)|nutch|p(ackrat|cbrowser|surf' .
        ')|r(eaper|sync)|s(alad|hai|[iy]phon|upport\.wordpress\.com)|t(akeou' .
        't|weetmeme)|user-agent|visaduhoc|wolf)/',
    $UANoSpace), 'Banned UA'); // 2017.02.03

    $Trigger(preg_match('/(?:80legs|chinaclaw)/', $UANoSpace), 'Scraper UA', '', $InstaBan); // 2017.01.08
    $Trigger(preg_match('/^(?:abot|spider)/', $UANoSpace), 'Scraper UA'); // 2017.01.07
    $Trigger(strpos($UANoSpace, 'fetch/') !== false, 'Scraper UA'); // 2017.01.06
    $Trigger(strpos($UANoSpace, 'vlc/') !== false, 'Possible/Suspected scraper UA'); // 2017.01.07

    $Trigger(preg_match(
        '/(?:3(60spider|d-ftp)|a(6-indexer|ccelo|ffinity|ghaven|href|ipbot|n' .
        'alyticsseo|pp3lewebkit|r(chivebot|tviper))|b(azqux|ender|i(nlar|tvo' .
        '|xo)|nf.fr|ogahn|oitho|pimagewalker)|c(cbot|entiverse|msworldmap|om' .
        'moncrawl|overscout|r4nk|rawlfire|uriousgeorge|ydral)|d(aylife|ebate' .
        '|igext|(cp|isco|ouban|ownload)bot|owjones|tsagent)|e((na|uro|xperi)' .
        'bot|nvolk|vaal|zoom)|f(dm|etch(er.0|or)|ibgen)|g(alaxydownloads|et(' .
        'download\.ws|ty|url11)|slfbot|urujibot)|h(arvest|eritrix|olmes|ttp(' .
        'fetcher|unit)|ttrack)|i(mage(.fetcher|walker)|linkscrawler|n(agist|' .
        'docom|fluencebot)|track)|j(akarta|ike)|k(ey(wenbot|wordsearchtool)|' .
        'imengi|kman)|l(arbin|ink(dex|walker)|iperhey|(t|ush)bot)|m(a(hiti|h' .
        'onie|ttters)|iabot|lbot|ormor|ot-v980|rchrome|ulticrawler)|n(e(ofon' .
        'ie|tseer|wsbot)|ineconnections)|o(fflinenavigator|odlebot)|p(age(fe' .
        'tch|gett|_verifi)er|anscient|ath2|ic(grabber|s|tsnapshot|turefinder' .
        ')|i(pl|xmatch|xray)|oe-component-client-|owermarks|roximic|(s|ure)b' .
        'ot|urity)|qqdownload|r(ankivabot|ebi-shoveler|everseget|ganalytics|' .
        'ocketcrawler|ulinki)|s(afeassign|bider|bl[.-]bot|crape|emrush|eo(en' .
        'g|profiler|stat)|istrix|ite(bot|intel)|n[iy]per|olomono|pbot|pyder|' .
        'search|webot)|t(-h-u-n|agsdir|ineye|opseo|raumacadx|urnitinbot)|u(1' .
        '2bot|p(downer|ictobot))|v(bseo|isbot|oyager)|w(arebay|auuu|bsearchb' .
        'ot|eb(alta|capture|download|ripper)|ikio|indows(3|seven)|inhttp|kht' .
        'mlto|orldbot|otbox)|xtractorpro|yoofind)/',
    $UANoSpace), 'Scraper UA'); // 2017.01.13

    $Trigger(preg_match(
        '/(?:c(hilkat|copyright)|flipboard|g(ooglealerts|rub)|python)/',
    $UANoSpace), 'Possible/Suspected scraper UA'); // 2017.01.07

    $Trigger(preg_match('/(?:brandwatch|magpie)/', $UANoSpace), 'Snoop UA', '', $InstaBan); // 2017.01.13
    $Trigger(strpos($UANoSpace, 'catch') !== false, 'Risky UA'); // 2017.01.13

    $Trigger(preg_match('/(?:anonymous|vpngate)/', $UANoSpace), 'Proxy UA'); // 2017.01.13

    $Trigger(preg_match(
        '/(?:cncdialer|d(esktopsmiley|s_juicyaccess)|foxy.1|genieo|hotbar|ic' .
        'afe|m(utant|yway)|o(otkit|ssproxy)|qqpinyinsetup|si(cent|mbar)|tenc' .
        'enttraveler|wsr-agent|zeus)/',
    $UANoSpace), 'Malware UA'); // 2017.01.13

    $Trigger(preg_match('/(?:360se|theworld)\)/', $UANoSpace), 'Malware UA'); // 2017.01.13

    $Trigger(preg_match(
        '/(?:200please|a(nalyzer|wcheck)|blex|c(entric|omment|razywebcrawler' .
        ')|d(ataprovider|ot(bot|comdotnet|netdotcom))|mo(reover|z\.com)|next' .
        'gensearchbot|p(agesinventory|rofiler)|r(6_|adian6|ogerbot)|s(earchm' .
        'etricsbot|eo(hunt|kicks|mon|tool)|phider)|v(agabondo|bseo\.com)|w(e' .
        'bm(astercoffee|eup)|ise-guys))/',
    $UANoSpace), 'SEO UA'); // 2017.02.02

    $Trigger(preg_match(
        '/(?:a(bonti|ccserver|cme.spider|nyevent-http)|b(igbozz|lackbird|log' .
        'search|logbot|salsa)|c(atexplorador|liqzbot|ontextad|orporama|rowsn' .
        'est|yberpatrol)|d(le_spider|omainappender|umprendertree)|flightdeck' .
        'reportsbot|g(imme60|ooglebenjojo)|http-agent|isitwp|k(2spider|emvi)' .
        '|lexxebot|joc|livelapbot|m(acinroyprivacyauditors|asscan|etaintelli' .
        'gence)|n(ettrapport|mapscriptingengine|rsbot)|p(4bot|4load|acrawler' .
        '|ageglimpse|egasusmonitoring|hpcrawl|ingdom|rlog)|r(arelyused|obo(c' .
        'op|spider))|s(creener|itedomain|mut|nap(preview)?bot|oapclient|ocia' .
        'layer|pyglass|quider)|urlappendbot|w(asalive|atchmouse|eb(-monitori' .
        'ng|masteraid|money|thumbnail)|hatweb|ikiapiary|in(http|inet)|maid\.' .
        'com|sr-agent|wwtype)|xenu|xovi|yeti|zibber|zurichfinancialservices)/',
    $UANoSpace), 'Unauthorised'); // 2017.02.03

    $Trigger(preg_match(
        '/(?:^(bot|java|msie|windows-live-social-object-extractor)|\((java|[' .
        'a-z]\:[0-9]{2,}))/',
    $UANoSpace), 'Unauthorised'); // 2017.02.03

    $Trigger(preg_match(
        '/(?:^(go 1)|m(ovable type|msie 999\.1))/',
    $UA), 'Unauthorised'); // 2017.02.03

    $Trigger(preg_match(
        '/(?:internet explorer)/',
    $UA), 'Fake IE'); // 2017.02.03

    $Trigger(preg_match(
        '/(?:drop ?table|(_table|assert|co(de|ntents)|dotnet_load|e(cho|regi' .
        '|scape|val|x(ec(utable)?|ists)?)|f(ile|unction)|g(et(c(sv)?|ss?)|zi' .
        'nflate)|if|[ints]able|nt|open|p(lace|uts)|re(ad|store)|s(chema|tart' .
        '|ystem)|thru|un(ction|serialize)|w(hil|rit)e)\(|database\(\))/',
    $UA), 'UAEX'); // 2017.02.02

    $Trigger(preg_match("\x01" . '(?:[./]seo|seo/)' . "\x01", $UANoSpace), 'SEO UA'); // 2017.01.08

    $Trigger(empty($CIDRAM['Ignore']['Seznam.cz']) && strpos($UANoSpace, 'seznambot') !== false, 'Seznam.cz'); // 2017.02.02 (ASNs 43037, 200600)

}