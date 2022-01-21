<?php
require_once('/php/layout.php');
require_once('/woody/php/_woodymenu.php');

function _menuLoopMyPhoto($bChinese)
{
    $arPhoto = array('photo2006', 'photo2007', 'photo2008', 'photo2009', 'photo2010', 'photo2011', 'photo2012', 'photo2014', 'photo2015'); 
    $iLevel = 1;
    
	MenuBegin();
	WoodyMenuItem($iLevel, 'image', $bChinese);
	MenuContinueNewLine();
    MenuDirFirstLast($arPhoto);
	MenuContinueNewLine();
    MenuSwitchLanguage($bChinese);
    MenuEnd();
}

function _LayoutTopLeft($bChinese = true, $bAdsense = true)
{
    LayoutTopLeft('_menuLoopMyPhoto', true, $bChinese, $bAdsense);
}

function _LayoutBottom($bChinese = true)
{
    LayoutTail($bChinese, true);
}

function EchoTitle($bChinese = true)
{
	global $acct;
	
	$strYear = substr($acct->GetTitle(), -4, 4);
	if ($bChinese)	$strYear .= '年相片';
	else				$strYear .= ' Photos';

    echo $strYear;
}

   	$acct = new TitleAccount();
?>
