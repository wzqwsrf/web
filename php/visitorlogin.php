<?php

function _getLoginLink($strCn, $strUs, $bChinese)
{
    return GetPhpLink(PATH_ACCOUNT.'login', false, ($bChinese ? $strCn : $strUs), $bChinese);
}

function VisitorLogin($bChinese)
{
   	global $acct;

	SwitchSetSess();
	if ($strMemberId = $acct->GetLoginId()) 
	{
	    $strLink = GetMemberLink($strMemberId, $bChinese);
	    $strLoginLink = _getLoginLink('切换', 'Change', $bChinese);
	    $strAccount = $bChinese ? '登录账号' : ' login account ';  
	    $str = $strLoginLink.$strAccount.$strLink;
	}
	else
	{
	    $strLoginLink = _getLoginLink('登录', 'login', $bChinese);
	    $strRegisterLink = GetPhpLink(PATH_ACCOUNT.'register', false, ($bChinese ? '注册' : 'register'), $bChinese);
		$str = $bChinese ? '更多选项？请先'.$strLoginLink.'或者'.$strRegisterLink.'。' : 'More options? Please '.$strLoginLink.' or '.$strRegisterLink.' account.';
	}

	LayoutBegin();
	EchoHtmlElement(GetRemarkElement($str));
	LayoutEnd();
}

?>
