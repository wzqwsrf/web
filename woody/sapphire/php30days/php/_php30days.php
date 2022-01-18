<?php
require_once('/php/layout.php');
require_once('/woody/php/_navwoody.php');

function NavLoop30Days($bChinese)
{
    $ar30Days = array('blue', 'hat', 'crown', 'yellow', 'leopard'); 
    $iLevel = 1;
    
	MenuBegin();
	WoodyMenuItem($iLevel + 1, 'image', $bChinese);
	MenuContinueNewLine();
    if ($bChinese)
    {
       	MenuWriteItemLink($iLevel, 'photo30days', URL_CNPHP, '满月艺术照');
    }
    else
    {
       	MenuWriteItemLink($iLevel, 'photo30days', URL_PHP, '30 Days');
    }
	MenuContinueNewLine();
    MenuDirLoop($ar30Days);
	MenuContinueNewLine();
    MenuSwitchLanguage($bChinese);
    MenuEnd();
}

function _LayoutTopLeft($bChinese = true, $bAdsense = true)
{
    LayoutTopLeft('NavLoop30Days', true, $bChinese, $bAdsense);
}

   	$acct = new Account();
?>
