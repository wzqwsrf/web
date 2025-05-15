<?php
require_once('_account.php');
require_once('../php/ui/commentparagraph.php');

function EchoAll($bChinese = true)
{
    global $acct;
    
    if ($str = $acct->GetQuery())
    {
        $strQuery = 'ip='.$str;
        $strLink = GetIpLink($str, $bChinese);
    	$strWhere = $acct->BuildWhereByIp($str);
    }
    else if ($str = $acct->GetPageIdQuery())
    {
        $strQuery = 'page_id='.$str;
        $strLink = $acct->GetCommentPageLink($str);
    	$strWhere = $acct->BuildWhereByPage($str);
    }
    else if ($str = $acct->GetMemberId())
    {
//        $strQuery = 'member_id='.$str;
        $strQuery = 'email='.SqlGetEmailById($str);
        $strLink = GetMemberLink($str, $bChinese);
    	$strWhere = $acct->BuildWhereByMember($str);
    }
    else
    {
        $strQuery = false;
        $strLink = '';
        $strWhere = false;
    }

    $iTotal = $acct->CountComments($strWhere);
    $iStart = $acct->GetStart();
    $iNum = $acct->GetNum();
    $strMenuLink = GetMenuLink($strQuery, $iTotal, $iStart, $iNum, $bChinese);
    
    EchoHtmlElement($strLink.' '.$strMenuLink);
    $acct->EchoComments($strWhere, $iStart, $iNum, $bChinese);
    EchoHtmlElement($strMenuLink);
}

function GetTitle($bChinese = true)
{
    global $acct;
    
    if ($str = $acct->GetQuery())					$strWho = $str;
    else if ($str = $acct->GetPageIdQuery())			$strWho = basename($acct->GetPageUri($str));
    else if ($str = $acct->GetMemberId())			$strWho =	SqlGetEmailById($str);
    else												$strWho = GetAllDisplay($bChinese);
    
	return $strWho.($bChinese ? '用户评论' : ' User Comments');
}

function GetMetaDescription($bChinese = true)
{
	$str = GetTitle($bChinese); 
    if ($bChinese)	$str .= '集中管理页面。为所有PHP写的网页提供分评论人、评论链接和IP地址等筛选功能分页显示全部评论。包括满足条件的编辑和删除链接。';
    else				$str .= ' manage page, display all comments by user, page link and IP address, with related edit and delete links.';
	return CheckMetaDescription($str);
}

	$acct = new CommentAccount('ip');
?>
