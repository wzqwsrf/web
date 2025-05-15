<?php
require_once('regexp.php');
require_once('externallink.php');
require_once('sql.php');
require_once('gb2312.php');

require_once('sql/sqlipaddress.php');
require_once('sql/sqlstock.php');

require_once('stock/stocksymbol.php');
require_once('stock/yahoostock.php');
require_once('stock/stockprefetch.php');
require_once('stock/stockref.php');

require_once('stock/mysqlref.php');
require_once('stock/mystockref.php');
require_once('stock/cnyref.php');
require_once('stock/netvalueref.php');
require_once('stock/holdingsref.php');
require_once('stock/fundref.php');
require_once('stock/qdiiref.php');

require_once('stock/fundpairref.php');

function StockGetSymbol($str)
{
	$str = trim($str);
	if ($strSymbol = BuildChinaFundSymbol($str))		return $strSymbol;
	if ($strSymbol = BuildChinaStockSymbol($str))	return $strSymbol;
	if (strpos($str, '_') === false)	$str = strtoupper($str);
    return $str;
}

function GetInputSymbolArray($strSymbols)
{
	$strSymbols = str_replace(array(',', '，', '、', "\\n", "\\r", "\\r\\n"), ' ', $strSymbols);
    $arSymbol = array();
    foreach (explode(' ', $strSymbols) as $str)
    {
    	if (!empty($str))		$arSymbol[] = StockGetSymbol($str);
    }
    return $arSymbol;
}

function GetYahooNetValueSymbol($strEtfSymbol)
{
    if (empty($strEtfSymbol))   return false;
    return YAHOO_INDEX_CHAR.$strEtfSymbol.'-IV';
}

// ****************************** Stock data functions *******************************************************

/* Sina data
nf_IC2006
http://hq.sinajs.cn/list=s_sh000001 上证指数
http://hq.sinajs.cn/list=s_sz399001 深证成指
http://hq.sinajs.cn/list=int_hangseng 恒生指数
http://hq.sinajs.cn/list=s_sz399300 沪生300
http://hq.sinajs.cn/list=int_dji 道琼斯
http://hq.sinajs.cn/list=int_nasdaq 纳斯达克
http://hq.sinajs.cn/list=int_sp500 标普500
http://hq.sinajs.cn/list=int_ftse 英金融时报指数
*/
// http://blog.sina.com.cn/s/blog_7ed3ed3d0101gphj.html
// http://hq.sinajs.cn/list=sh600151,sz000830,s_sh000001,s_sz399001,s_sz399106,s_sz399107,s_sz399108
// 期货 http://hq.sinajs.cn/rn=1318986550609&amp;list=hf_CL,hf_GC,hf_SI,hf_CAD,hf_ZSD,hf_S,hf_C,hf_W
// http://hq.sinajs.cn/rn=1318986628214&amp;list=USDCNY,USDHKD,EURCNY,GBPCNY,USDJPY,EURUSD,GBPUSD,
// http://hq.sinajs.cn/list=gb_dji

function RemoveDoubleQuotationMarks($str)
{
    $str = strstr($str, '"');
    $str = ltrim($str, '"');
    $strLeft = strstr($str, '"', true);     // works with no ending "
    if ($strLeft)   return $strLeft;
    return $str;
}

function explodeQuote($str)
{
    return explode(',', RemoveDoubleQuotationMarks($str));
}

function StockNeedFile($strFileName, $iInterval = SECONDS_IN_MIN)
{
	$now_ymd = GetNowYMD();
	return $now_ymd->NeedFile($strFileName, $iInterval);
}

function GetSinaQuotes($arSymbol)
{
	$strSinaSymbols = implode(',', $arSymbol);
	$strFileName = DebugGetPathName('debugsina.txt');
	$iCount = count($arSymbol);
	if (DebugIsAdmin() && $iCount > 1)
	{
//		DebugVal($iCount, 'total prefetch - '.$strSinaSymbols);
	}
	else
	{
		if (StockNeedFile($strFileName) == false)
		{	// pause 1 minute after curl error response
//			DebugString('Ignored: '.$strSinaSymbols, true);
			return false;
		}
	}
    
    if ($str = url_get_contents(GetSinaDataUrl($strSinaSymbols), UrlGetRefererHeader(GetSinaFinanceUrl()), $strFileName))
    {
    	if ($iCount >= count(explode('=', $str)))		DebugVal($iCount, __FUNCTION__.' failed: '.$str);		// Sina returns error in an empty file
    	else											return $str;
    }
    return false;
}

// ****************************** Stock display functions *******************************************************

function StockGetPriceDisplay($strDisp, $strPrev, $iPrecision = false)
{
    if ($strDisp)
    {
    	$fDisp = floatval($strDisp);
    	$fPrev = floatval($strPrev);
        
        if ($fDisp > $fPrev + MIN_FLOAT_VAL)         $strColor = 'red';
        else if ($fDisp < $fPrev - MIN_FLOAT_VAL)   $strColor = 'green';
        else                                         $strColor = 'black';

        $strDisp = strval_round($fDisp, $iPrecision);
        return GetFontElement($strDisp, $strColor);
    }
    return '';
}

function GetNumberDisplay($fVal)
{
    return StockGetPriceDisplay(strval($fVal), '0');
}

function GetRatioDisplay($fVal)
{
    return StockGetPriceDisplay(strval($fVal), '1');
}

function StockGetPercentage($strDivisor, $strDividend)
{
	$f = floatval($strDivisor);
	if ($f == 0.0)
	{
		return false;
	}
    return (floatval($strDividend)/$f - 1.0) * 100.0;
}

function StockCompareEstResult($strStockId, $strNetValue, $strDate, $strSymbol)
{
	$nav_sql = GetNavHistorySql();
    if ($nav_sql->InsertDaily($strStockId, $strDate, $strNetValue))
    {
    	$fund_est_sql = GetFundEstSql();
       	if ($strEstValue = $fund_est_sql->GetClose($strStockId, $strDate))
       	{
       		$fPercentage = StockGetPercentage($strNetValue, $strEstValue);
       		if (($fPercentage !== false) && (abs($fPercentage) > 1.0))
       		{
       			$strLink = GetNetValueHistoryLink($strSymbol);
       			$str = sprintf('%s%s 实际值%s 估值%s 误差:%.2f%%', $strSymbol, $strLink, $strNetValue, $strEstValue, $fPercentage); 
       			trigger_error('Net value estimation error '.$str);
       		}
       	}
    	return true;
    }
    return false;
}

function StockUpdateEstResult($strStockId, $strNetValue, $strDate)
{
	$nav_sql = GetNavHistorySql();
	if ($nav_sql->GetRecord($strStockId, $strDate) == false)
    {   // Only update when net value is NOT ready
    	$fund_est_sql = GetFundEstSql();
		$fund_est_sql->WriteDaily($strStockId, $strDate, $strNetValue);
	}
}

// ****************************** StockReference public functions *******************************************************
function RefGetTableColumnNav($ref)
{
	$strStockDisplay = GetTableColumnStock($ref);
	if ($ref->CountNav() > 0)		return new TableColumnNav($strStockDisplay);	
	return 								new TableColumnPrice($strStockDisplay);
}

function RefGetPosition($ref)
{
	$sql = new FundPositionSql();
   	if ($fRatio = $sql->ReadVal($ref->GetStockId()))	return $fRatio;
	return $ref->GetDefaultPosition();  
}

function FundGetHedgeVal($strStockId)
{
	$sql = new FundHedgeValSql();
   	return $sql->ReadInt($strStockId);
}

// ****************************** Stock final integration functions *******************************************************
function StockPrefetchArrayData($arSymbol)
{
    PrefetchSinaStockData(array_unique($arSymbol));
}

function _addFundPairSymbol(&$ar, $strSymbol)
{
	$ar[] = $strSymbol;
	if ($strPairSymbol = SqlGetFundPair($strSymbol))	$ar[] = $strPairSymbol;
}

function _addHoldingsSymbol(&$ar, $strSymbol)
{
	if (SqlCountHoldings($strSymbol) > 0)
	{
		$sql = GetStockSql();
		$holdings_sql = GetHoldingsSql();
    	foreach ($holdings_sql->GetHoldingsArray($sql->GetId($strSymbol)) as $strId => $strRatio)
    	{
    		_addFundPairSymbol($ar, $sql->GetStockSymbol($strId));
    	}
    }
}

function _getAllSymbolArray($strSymbol)
{
   	$ar = array($strSymbol);
   	$sym = new StockSymbol($strSymbol);
    if ($sym->IsFundA())
    {
        if (in_arrayQdiiMix($strSymbol))
        {
        	_addHoldingsSymbol($ar, $strSymbol);
        	if ($strSymbol == 'SZ164906')				$ar[] = 'KWEB';
			else if ($strSymbol == 'SH501225')		$ar[] = 'SMH';
        }
        else if (in_arrayQdii($strSymbol))
        {
        	if ($strEstSymbol = QdiiGetEstSymbol($strSymbol))		_addFundPairSymbol($ar, $strEstSymbol);
        }
        else if (in_arrayQdiiHk($strSymbol))
        {
        	if ($strEstSymbol = QdiiHkGetEstSymbol($strSymbol))		_addFundPairSymbol($ar, $strEstSymbol);
        }
        else if (in_arrayQdiiJp($strSymbol))
        {
        	if ($strEstSymbol = QdiiJpGetEstSymbol($strSymbol))		_addFundPairSymbol($ar, $strEstSymbol); 
        }
        else if (in_arrayQdiiEu($strSymbol))
        {
        	if ($strEstSymbol = QdiiEuGetEstSymbol($strSymbol))		_addFundPairSymbol($ar, $strEstSymbol); 
        }
        else
        {
        	if ($strPairSymbol = SqlGetFundPair($strSymbol))		$ar[] = $strPairSymbol;
        }
    }
	else if ($sym->IsSymbolA())
    {
        if ($strSymbolB = SqlGetAbPair($strSymbol))		$ar[] = $strSymbolB;
        else if ($strSymbolA = SqlGetBaPair($strSymbol))
        {
        	$ar[] = $strSymbolA;
        	$strSymbol = $strSymbolA;
        }
    		
        if ($strSymbolH = SqlGetAhPair($strSymbol))	
        {
          	$ar[] = $strSymbolH;
            if ($strSymbolAdr = SqlGetHadrPair($strSymbolH))	$ar[] = $strSymbolAdr;
        }
    }
    else if ($sym->IsSymbolH())
    {
        if ($strSymbolA = SqlGetHaPair($strSymbol))
        {
        	$ar[] = $strSymbolA;
        	if ($strSymbolB = SqlGetAbPair($strSymbolA))		$ar[] = $strSymbolB;
        }
        if ($strSymbolAdr = SqlGetHadrPair($strSymbol))		$ar[] = $strSymbolAdr;
    }
    else
    {
       	_addHoldingsSymbol($ar, $strSymbol);
    	if ($strSymbolH = SqlGetAdrhPair($strSymbol))
        {
           	$ar[] = $strSymbolH;
            if ($strSymbolA = SqlGetHaPair($strSymbolH))
            {
            	$ar[] = $strSymbolA;
            	if ($strSymbolB = SqlGetAbPair($strSymbolA))		$ar[] = $strSymbolB;
            }
        }
        
       	if ($strPairSymbol = SqlGetFundPair($strSymbol))
       	{
       		$ar[] = $strPairSymbol;
         	if ($strSymbol == 'ASHR' || $strSymbol == 'hf_CHA50CFD')	$ar[] = 'fx_susdcnh';
      	}
    }
//   	DebugPrint($ar, __FUNCTION__, true);
    return $ar;
}

function StockPrefetchArrayExtendedData($ar)
{
    $arAll = array();
    
	$sql = GetStockSql();
    foreach ($ar as $strSymbol)
    {
   		if ($sql->GetId($strSymbol))		$arAll = array_merge($arAll, _getAllSymbolArray($strSymbol));
   		else								$arAll[] = $strSymbol;	// new stock symbol	
    }
    StockPrefetchArrayData($arAll);
}

function StockPrefetchExtendedData()
{
    StockPrefetchArrayExtendedData(func_get_args());
}

function StockGetReference($strSymbol)
{
	$sym = new StockSymbol($strSymbol);

/*    if ($sym->IsSinaFund())				return new FundReference($strSymbol);
	else*/ if ($sym->IsEastMoneyForex())	return new CnyReference($strSymbol);
    										return new MyStockReference($strSymbol);
}

function StockGetQdiiReference($strSymbol)
{
    if (in_arrayQdii($strSymbol))					return new QdiiReference($strSymbol);
    else if (in_arrayQdiiHk($strSymbol))			return new QdiiHkReference($strSymbol);
    else if (in_arrayQdiiJp($strSymbol))			return new QdiiJpReference($strSymbol);
    else if (in_arrayQdiiEu($strSymbol))			return new QdiiEuReference($strSymbol);
    return false;
}

function StockGetFundReference($strSymbol)
{
	if ($ref = StockGetQdiiReference($strSymbol))	return $ref;
	else if (in_arrayQdiiMix($strSymbol))			return new HoldingsReference($strSymbol);
	else if (in_arrayChinaIndex($strSymbol))		return new FundPairReference($strSymbol);
	return new FundReference($strSymbol);
}

function _getAbPairReference($strSymbol)
{
	$pair_sql = new AbPairSql();
	if ($pair_sql->GetPairSymbol($strSymbol))						return new AbPairReference($strSymbol);
	else if ($strSymbolA = $pair_sql->GetSymbol($strSymbol))		return new AbPairReference($strSymbolA);
	return false;
}

function _getAdrPairReference($strSymbol)
{
	$pair_sql = new AdrPairSql();
	if ($pair_sql->GetPairSymbol($strSymbol))						return new AdrPairReference($strSymbol);
	else if ($strAdr = $pair_sql->GetSymbol($strSymbol))			return new AdrPairReference($strAdr);
	return false;
}

function _getAhPairReference($strSymbol)
{
	$pair_sql = new AhPairSql();
	if ($pair_sql->GetPairSymbol($strSymbol))						return new AhPairReference($strSymbol);
	else if ($strSymbolA = $pair_sql->GetSymbol($strSymbol))		return new AhPairReference($strSymbolA);
	return false;
}

function StockGetPairReferences($strSymbol)
{
    $ab_ref = false;
    $ah_ref = false;
    $adr_ref = false;
    
	if ($ab_ref = _getAbPairReference($strSymbol))
    {
    	if ($ah_ref = _getAhPairReference($ab_ref->GetSymbol()))
    	{
    		$h_ref = $ah_ref->GetPairRef();
    		$adr_ref = _getAdrPairReference($h_ref->GetSymbol());
    	}
    }
	else if ($ah_ref = _getAhPairReference($strSymbol))
    {
    	$h_ref = $ah_ref->GetPairRef();
    	$adr_ref = _getAdrPairReference($h_ref->GetSymbol());
    	$ab_ref = _getAbPairReference($ah_ref->GetSymbol());
    }
    else if ($adr_ref = _getAdrPairReference($strSymbol))
    {
    	$h_ref = $adr_ref->GetPairRef();
    	if ($ah_ref = _getAhPairReference($h_ref->GetSymbol()))		$ab_ref = _getAbPairReference($ah_ref->GetSymbol());
    }
    
    return array($ab_ref, $ah_ref, $adr_ref);
}

function UseSameDayNav($sym)
{
	$strSymbol = $sym->GetSymbol();
	if (in_arrayQdii($strSymbol))				return false;
	else if (in_arrayQdiiMix($strSymbol))	return	in_arrayHkMix($strSymbol);
	return true;
}

	
?>
