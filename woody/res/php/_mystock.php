<?php
require_once('_stock.php');
require_once('_editmergeform.php');
require_once('_editstockoptionform.php');
require_once('/php/ui/ahparagraph.php');
require_once('/php/ui/smaparagraph.php');
require_once('/php/ui/fundestparagraph.php');
require_once('/php/ui/tradingparagraph.php');
require_once('/php/ui/stockgroupparagraph.php');

function _checkStockTransaction($strGroupId, $ref)
{
	if ($stockgroupitem = StockGroupHasSymbol($strGroupId, $ref->strSqlId))
	{
		if (intval($stockgroupitem['record']) > 0)
		{
		    return $stockgroupitem['id'];
		}
	}
	return false;
}

function _echoMyStockTransactions($strMemberId, $ref, $bChinese)
{
    $arGroup = array();
	if ($result = SqlGetStockGroupByMemberId($strMemberId)) 
	{
		while ($stockgroup = mysql_fetch_assoc($result)) 
		{
		    $strGroupId = $stockgroup['id'];
		    if ($strGroupItemId = _checkStockTransaction($strGroupId, $ref))
		    {
		        $arGroup[$strGroupId] = $strGroupItemId;
		    }
		}
		@mysql_free_result($result);
	}
	
	$iCount = count($arGroup);
	if ($iCount == 0)    return;
	foreach ($arGroup as $strGroupId => $strGroupItemId)
	{
	    $result = SqlGetStockTransactionByGroupItemId($strGroupItemId, 0, MAX_TRANSACTION_DISPLAY); 
	    EchoStockTransactionParagraph($strGroupId, $ref, $result, $bChinese);
	}
	
	if ($iCount == 1)
	{
	    StockEditTransactionForm($strGroupId, $strGroupItemId, $bChinese);
	}
	else
	{
	    StockMergeTransactionForm($arGroup, $bChinese);
	}
}

function _setMyStockLink($ref, $strPageSymbol, $bChinese)
{
	$strSymbol = $ref->GetStockSymbol();
	if ($strPageSymbol != $strSymbol)	$ref->strExternalLink = GetMyStockLink($strSymbol, $bChinese);
}

function _echoMyStock($strSymbol, $bChinese)
{
    MyStockPrefetchDataAndForex(array($strSymbol));
    
    $uscny_ref = new CNYReference('USCNY');
    $hkcny_ref = new CNYReference('HKCNY');
    $hshare_ref = false;
    $hadr_ref = false;
    	
    $sym = new StockSymbol($strSymbol);
    if ($sym->IsFundA())
    {
        $fund = MyStockGetFundReference($strSymbol);
        $ref = $fund->stock_ref; 
    }
    else
    {
    	if ($ref_ar = MyStockGetHAdrReference($sym))		list($ref, $hshare_ref, $hadr_ref) = $ref_ar;
        else												$ref = new MyStockReference($strSymbol);
    }
    EchoReferenceParagraph(array($ref), $bChinese);
    
    if ($sym->IsFundA())
    {
        if ($fund->fPrice)      EchoFundEstParagraph($fund, $bChinese);
        EchoFundTradingParagraph($fund, false, $bChinese);
    }
    else
    {
        if ($hshare_ref)
        {
			_setMyStockLink($hshare_ref, $strSymbol, $bChinese);
        	EchoAhParagraph(array($hshare_ref), $hkcny_ref, $bChinese);
        }
        if ($hadr_ref)
        {
			_setMyStockLink($hadr_ref, $strSymbol, $bChinese);
        	EchoAdrhParagraph(array($hadr_ref), $uscny_ref, $hkcny_ref, $bChinese);
        }
   		if ($sym->IsSymbolA())
   		{
   			if ($hshare_ref)	EchoAhTradingParagraph($hshare_ref, $hadr_ref, $bChinese);
   			else 				EchoTradingParagraph($ref, $bChinese);
       	}
    }
    
    EchoMyStockSmaParagraph($ref, $hshare_ref, $hadr_ref, $bChinese);
    if ($strMemberId = AcctIsLogin())
    {
    	EchoStockGroupParagraph($bChinese);	
        _echoMyStockTransactions($strMemberId, $ref, $bChinese);
    }
    return $sym;
}

function _echoMyStockLinks($sym, $bChinese)
{
	$strQuery = UrlGetQueryString();
    $str = UrlBuildPhpLink(STOCK_PATH.'editstock', $strQuery, STOCK_OPTION_EDIT_CN, STOCK_OPTION_EDIT, $bChinese);
    $str .= ' '.UrlBuildPhpLink(STOCK_PATH.'editstockreversesplit', $strQuery, STOCK_OPTION_REVERSESPLIT_CN, STOCK_OPTION_REVERSESPLIT, $bChinese);
    if ($sym->IsSymbolH())
    {
    	if ($bChinese)	$str .= ' '.UrlGetPhpLink(STOCK_PATH.'editstockadr', $strQuery, STOCK_OPTION_ADR_CN, true);
    }
    EchoParagraph($str);
}

function EchoMyStock($bChinese)
{
    if ($str = UrlGetQueryValue('symbol'))
    {
        $sym = _echoMyStock(StockGetSymbol($str), $bChinese);
        if (AcctIsAdmin())
        {
        	_echoMyStockLinks($sym, $bChinese);
        }
    }
    EchoPromotionHead('', $bChinese);
}

function EchoMyStockTitle($bChinese)
{
    if ($bChinese)  echo '我的股票';
    else              echo 'My Stock ';
    EchoUrlSymbol();
}

    AcctNoAuth();

?>

