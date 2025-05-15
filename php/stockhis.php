<?php
require_once('class/ini_file.php');

// max 20 months history used
define('MAX_QUOTES_DAYS', 620);
define('BOLL_DAYS', 20);

define('SMA_SECTION', 'SMA');

function _ignoreCurrentTradingData($strDate)
{        
    $ymd = GetNowYMD();
    if ($ymd->GetYMD() == $strDate)
    {
        if ($ymd->IsStockTradingHourEnd() == false)
        {   // market still trading, do not use today's data
            return true;
        }
    }
    return false;
}

// ****************************** SMA functions *******************************************************
function _estSma($arF, $iAvg)
{
    $f = 0.0;
//    $iNum = $iAvg;
    $iNum = $iAvg - 1;
    
    $iCount = 0;
//    for ($i = 0; $i < $iNum; $i ++)
    foreach ($arF as $fVal)
    {
        $f += $fVal;
        $iCount ++;
        if ($iCount == $iNum)	break;
    }
    return strval($f / $iNum);
}

// axx + bx + c = 0
function GetQuadraticEquationRoot($a, $b, $c)
{
    $delta = $b * $b - 4.0 * $a * $c;
    if ($delta >= 0.0)
    {
        $x1 = (0.0 - $b + sqrt($delta)) / (2.0 * $a); 
        $x2 = (0.0 - $b - sqrt($delta)) / (2.0 * $a);
//         $strDebug = sprintf('%.2f, %.2f', $x1, $x2);
//         DebugString($strDebug);
        return array($x1, $x2);
    }
    return false;
}

/*
a = (n - 4) * (n - 1)² - 4 * (n - 1)
b = (8 - 2 * (n - 4) * (n - 1)) * ∑Xn
c = (n - 4) * (∑Xn)² - 4 * ∑Xn²
*/
function _estBollingerBands($arF, $iAvg)
{
    $fSum = 0.0;
    $fQuadraticSum = 0.0;
    $iNum = $iAvg - 1;
    $iCount = 0;
//    for ($i = 0; $i < $iNum; $i ++)
    foreach ($arF as $fVal)
    {
//        $fVal = $arF[$i];
        $fSum += $fVal;
        $fQuadraticSum += $fVal * $fVal;
        $iCount ++;
        if ($iCount == $iNum)	break;
    }
    $f = 1.0 * ($iAvg - 4);
    $a = $f * $iNum * $iNum - 4 * $iNum;
    $b = (8 - 2 * $f * $iNum) * $fSum;
    $c = $f * $fSum * $fSum - 4 * $fQuadraticSum;
    
    if ($ar = GetQuadraticEquationRoot($a, $b, $c))
    {
        list($x1, $x2) = $ar;
        $sigma1 = ($fSum - $iNum * $x1) / 2;
        $sigma2 = ($fSum - $iNum * $x2) / 2;
        return array(strval($x1 - 2 * $sigma1), strval($x2 - 2 * $sigma2));
    }
    return false;
}

function _estNextBollingerBands($arF, $iAvg)
{
    $fSum = 0.0;
    $fQuadraticSum = 0.0;
    $iNum = $iAvg - 2;
    $iCount = 0;
//    for ($i = 0; $i < $iNum; $i ++)
    foreach ($arF as $fVal)
    {
//        $fVal = $arF[$i];
        $fSum += $fVal;
        $fQuadraticSum += $fVal * $fVal;
        $iCount ++;
        if ($iCount == $iNum)	break;
    }
    $f = 1.0 * ($iAvg - 8);
    $a = $f * $iNum * $iNum - 16 * $iNum;
    $b = (32 - 2 * $f * $iNum) * $fSum;
    $c = $f * $fSum * $fSum - 16 * $fQuadraticSum;
    
    if ($ar = GetQuadraticEquationRoot($a, $b, $c))
    {
        list($x1, $x2) = $ar;
        $sigma1 = ($fSum - $iNum * $x1) / 4;
        $sigma2 = ($fSum - $iNum * $x2) / 4;
        return array(strval($x1 - 2 * $sigma1), strval($x2 - 2 * $sigma2));
    }
    return false;
}

// ****************************** Private functions *******************************************************
function _isWeekEnd($strYMD, $strNextDayYMD)
{
//    DebugString('StringYMD in _isWeekEnd 1');
    $ymd = new StringYMD($strYMD);
    if ($strNextDayYMD)
    {
//    	DebugString('StringYMD in _isWeekEnd 2');
        $next_ymd = new StringYMD($strNextDayYMD);
        if ($ymd->GetDayOfWeek() >= $next_ymd->GetDayOfWeek())     return true;
    }
    else
    { 
        if ($ymd->IsFriday())   return true;
        
        // If this Friday is not a trading day
        $now_ymd = GetNowYMD();
        if ($now_ymd->IsWeekDay())
        {
            if ($ymd->GetDayOfWeek() > $now_ymd->GetDayOfWeek())     return true;
        }
        else
        {
            return true;
        }
    }
    return false;
}

function _isMonthEnd($strYMD, $strNextDayYMD)
{
//    DebugString('StringYMD in _isMonthEnd 1');
    $ymd = new StringYMD($strYMD);
    if ($strNextDayYMD)
    {
//    	DebugString('StringYMD in _isMonthEnd 2');
        $next_ymd = new StringYMD($strNextDayYMD);
    }
    else
    {   // If the last none weekend day of a certain month is not a trading day 
        $now_ymd = GetNowYMD();
        if (($now_ymd->GetYMD() == $strYMD) || $now_ymd->IsWeekend())
        {
        	$iTick = $now_ymd->GetNextTradingDayTick();
        	$next_ymd = new TickYMD($iTick);
        }
        else	$next_ymd = $now_ymd;
    }
    
    if ($ymd->GetMonth() == $next_ymd->GetMonth())		return false;    // same month    
    return true;
}

class MaxMin
{
    var $fMax;
    var $fMin;
    
    public function __construct() 
    {
        $this->fMax = false;
        $this->fMin = false;
    }

    function Init($fMax, $fMin)
    {
        if ($this->fMin == false && $this->fMax == false)
        {
            $this->fMin = $fMin;
            $this->fMax = $fMax;
        }
    }
    
    function Set($fVal) 
    {
        if ($fVal > $this->fMax)  $this->fMax = $fVal;
        if ($fVal < $this->fMin)  $this->fMin = $fVal;
    }
    
    function Fit($fVal)
    {
        if ($fVal > $this->fMin && $fVal < $this->fMax) return true;
        return false;
    }
}

class StockHistory
{
    var $aiNum;     // days/weeks/months 
    
    var $arSMA = array();
    var $arNext = array();
    var $arAfterHour = array();
    
    var $arColor = array();
    var $arOrder;
    
    var $strStartDate;		// 2014-11-13
    
    var $stock_ref;	// MyStockReference
    
    function GetStartDate()
    {
    	return $this->strStartDate;
    }
    
    function _buildNextName($strName)
    {
        return $strName.'Next';
    }
    
    function _cfg_set_SMA($cfg, $strName, $strSma, $strNext = false)
    {
        $this->arSMA[$strName] = $strSma;
        $this->arNext[$strName] = $strNext;
        
        $cfg->set_var(SMA_SECTION, $strName, $strSma);
        if ($strNext)
        {
        	$cfg->set_var(SMA_SECTION, $this->_buildNextName($strName), $strNext);
        }
    }
    
    function _cfg_get_SMA($cfg, $strName)
    {
        $this->arSMA[$strName] = $cfg->read_var(SMA_SECTION, $strName);
        
        if ($str = $cfg->read_var(SMA_SECTION, $this->_buildNextName($strName)))
        {
        	$this->arNext[$strName] = $str;
        }
        else
        {
        	$this->arNext[$strName] = false;
        }
    }

    function _get_EMA($iDays)
    {
    	$strName = 'EMA'.strval($iDays);
//    	DebugString($strName.' '.$this->strStartDate.' '.$this->GetStockId());

		$sql = GetStockEmaSql($iDays);
    	$this->arSMA[$strName] = $sql->GetClose($this->GetStockId(), $this->strStartDate);
       	$this->arNext[$strName] = false;
       	$this->arAfterHour[$strName] = false;
    }
    
    function _cfg_set_SMAs($cfg, $strPrefix, $afClose)
    {
        foreach ($this->aiNum as $i)
        {
            $this->_cfg_set_SMA($cfg, $strPrefix.strval($i), _estSma($afClose, $i), _estSma($afClose, $i - 1));
        }
        list($strUp, $strDown) = _estBollingerBands($afClose, BOLL_DAYS);
        list($strUpNext, $strDownNext) = _estNextBollingerBands($afClose, BOLL_DAYS);
        $this->_cfg_set_SMA($cfg, $strPrefix.'BOLLUP', $strUp, $strUpNext);
        $this->_cfg_set_SMA($cfg, $strPrefix.'BOLLDN', $strDown, $strDownNext);
    }
    
    function _cfg_get_SMAs($cfg, $strPrefix)
    {
        foreach ($this->aiNum as $i)
        {
            $this->_cfg_get_SMA($cfg, $strPrefix.strval($i));
        }
        $this->_cfg_get_SMA($cfg, $strPrefix.'BOLLUP');
        $this->_cfg_get_SMA($cfg, $strPrefix.'BOLLDN');
    }
    
    function _loadConfigSMA($cfg)
    {
	    $this->_cfg_get_SMAs($cfg, 'D');
	    $this->_cfg_get_SMAs($cfg, 'W');
	    $this->_cfg_get_SMAs($cfg, 'M');
    }

    function _getDayWeekMonthData(&$afClose, &$afWeeklyClose, &$afMonthlyClose)
    {
        $strNextDayYMD = false;
        $his_sql = GetStockHistorySql();
    	if ($result = $his_sql->GetFromDate($this->GetStockId(), $this->strStartDate, MAX_QUOTES_DAYS))
    	{
    		while ($record = mysqli_fetch_assoc($result)) 
    		{
    			$fClose = floatval($record['adjclose']);
    			$afClose[] = $fClose;
            
    			$strYMD = $record['date'];
    			if (_isWeekEnd($strYMD, $strNextDayYMD))	
    			{
    				$afWeeklyClose[] = $fClose;
//    				DebugString($strYMD.' '.$record['adjclose'], true);
    			}
    			if (_isMonthEnd($strYMD, $strNextDayYMD))	
    			{
    				$afMonthlyClose[] = $fClose;
//    				DebugString($strYMD.' '.$record['adjclose'], true);
    			}
    			$strNextDayYMD = $strYMD;
    		}
    		mysqli_free_result($result);
    	}
    }
    
    function _saveConfigSMA($cfg)
    {
        $afClose = array();
        $afWeeklyClose = array();
        $afMonthlyClose = array();
        $this->_getDayWeekMonthData($afClose, $afWeeklyClose, $afMonthlyClose);
/*
        $strNextDayYMD = false;
        $his_sql = GetStockHistorySql();
    	if ($result = $his_sql->GetFromDate($this->GetStockId(), $this->strStartDate, MAX_QUOTES_DAYS))
    	{
    		while ($record = mysqli_fetch_assoc($result)) 
    		{
    			$fClose = floatval($record['adjclose']);
    			$afClose[] = $fClose;
            
    			$strYMD = $record['date'];
    			if (_isWeekEnd($strYMD, $strNextDayYMD))	
    			{
    				$afWeeklyClose[] = $fClose;
//    				DebugString($strYMD.' '.$record['adjclose'], true);
    			}
    			if (_isMonthEnd($strYMD, $strNextDayYMD))	
    			{
    				$afMonthlyClose[] = $fClose;
//    				DebugString($strYMD.' '.$record['adjclose'], true);
    			}
    			$strNextDayYMD = $strYMD;
    		}
    		mysqli_free_result($result);
    	}
*/
	    $this->_cfg_set_SMAs($cfg, 'D', $afClose);
	    $this->_cfg_set_SMAs($cfg, 'W', $afWeeklyClose);
	    $this->_cfg_set_SMAs($cfg, 'M', $afMonthlyClose);
        
        $cfg->save_data();
    }
    
    function _configSMA()
    {
        $cfg = new INIFile($this->stock_ref->strConfigName);
        $strCurDate = $this->strStartDate;
        if ($cfg->group_exists(SMA_SECTION))
        {
            $strDate = $cfg->read_var(SMA_SECTION, 'Date');
            if ($strDate == $strCurDate)
            {
                $this->_loadConfigSMA($cfg);
            }
            else
            {
//                $cfg->add_group(SMA_SECTION);
                $cfg->set_group(SMA_SECTION);
                $cfg->set_var(SMA_SECTION, 'Date', $strCurDate);
                $this->_saveConfigSMA($cfg);
            }
        }
        else
        {
            $cfg->add_group(SMA_SECTION);
            $cfg->set_var(SMA_SECTION, 'Date', $strCurDate);
            $this->_saveConfigSMA($cfg);
        }

        $this->_get_EMA(50);
        $this->_get_EMA(200);
    }

    function _onTestData($strPrefix, $afClose)
    {
        foreach ($this->aiNum as $i)
        {
            $this->arAfterHour[$strPrefix.strval($i)] = _estSma($afClose, $i);
        }
        list($strUp, $strDown) = _estBollingerBands($afClose, BOLL_DAYS);
        $this->arAfterHour[$strPrefix.'BOLLUP'] = $strUp;
        $this->arAfterHour[$strPrefix.'BOLLDN'] = $strDown;
    }
    
    function _onTest()
    {
    	$fPrice = floatval($this->stock_ref->GetPrice());
        $afClose = array($fPrice);
        $afWeeklyClose = array($fPrice);
        $afMonthlyClose = array($fPrice);
        $this->_getDayWeekMonthData($afClose, $afWeeklyClose, $afMonthlyClose);
/*
        $strNextDayYMD = false;
        $his_sql = GetStockHistorySql();
    	if ($result = $his_sql->GetFromDate($this->GetStockId(), $this->strStartDate, MAX_QUOTES_DAYS))
    	{
    		while ($record = mysqli_fetch_assoc($result)) 
    		{
    			$fClose = floatval($record['adjclose']);
    			$afClose[] = $fClose;
            
    			$strYMD = $record['date'];
    			if (_isWeekEnd($strYMD, $strNextDayYMD))	
    			{
    				$afWeeklyClose[] = $fClose;
//    				DebugString($strYMD.' '.$record['adjclose'], true);
    			}
    			if (_isMonthEnd($strYMD, $strNextDayYMD))	
    			{
    				$afMonthlyClose[] = $fClose;
//    				DebugString($strYMD.' '.$record['adjclose'], true);
    			}
    			$strNextDayYMD = $strYMD;
    		}
    		mysqli_free_result($result);
    	}
*/
	    $this->_onTestData('D', $afClose);
	    $this->_onTestData('W', $afWeeklyClose);
	    $this->_onTestData('M', $afMonthlyClose);
    }
    
    function GetRef()
    {
        return $this->stock_ref;
    }
/*    
    function GetSymbol()
    {
        return $this->stock_ref->GetSymbol();
    }
*/
    function GetStockId()
    {
        return $this->stock_ref->GetStockId();
    }
    
    function _calcStartDate()
    {
        $his_sql = GetStockHistorySql();
    	if ($result = $his_sql->GetAll($this->GetStockId(), 0, 2))
    	{
    		while ($record = mysqli_fetch_assoc($result)) 
    		{
    			$strDate = $record['date'];
                if (_ignoreCurrentTradingData($strDate))
                {
                	continue;
                }
                else 
                {
                	mysqli_free_result($result);
                	return $strDate;
                }
            }
            mysqli_free_result($result);
        }
        return false;
    }

    function GetBullBear()
    {
    	if (isset($this->arSMA['EMA50']))
    	{
    		return (floatval($this->arSMA['EMA50']) > floatval($this->arSMA['EMA200'])) ? GetFontElement('牛市') : GetRemarkElement('熊市');
    	}
    	return false;
    }
    
    function NeedAfterHourEst()
    {
    	$ref = $this->GetRef();
    	if ($this->GetStartDate() == $ref->GetDate())	return false;
//    	if ($ref->IsSymbolUS())
//    	{
    		$ref->SetTimeZone();
    		$now_ymd = GetNowYMD();
    		return ($now_ymd->GetHourMinute() > 1520) ? true : false;
//    	}
//    	return false;
    }
    
    function _getColorAndOrderArray($arSMA)
    {
    	$mm = new MaxMin();
    	$mmW = new MaxMin();
    	$arVal = array();
    	foreach ($arSMA as $strKey => $strVal)
    	{
    		$fVal = floatval($strVal);
    		$strColor = false;
    		$strFirst = substr($strKey, 0, 1); 
    		if ($strFirst == 'D')
    		{
    			$mm->Init(0.0, 10000000.0);
    			$mm->Set($fVal);
    			$arVal[] = $fVal;
    		}
    		else if ($strFirst == 'W')
    		{
    			$mmW->Init($mm->fMax, $mm->fMin);
    			$mmW->Set($fVal);
    			if ($mm->Fit($fVal))		$strColor = 'silver';
    			else						$arVal[] = $fVal;
    		}
    		else if ($strFirst == 'M')
    		{
    			if ($mm->Fit($fVal))        $strColor = 'silver';
    			else if ($mmW->Fit($fVal))  $strColor = 'gray';
    			else						$arVal[] = $fVal;
    		}
    		else	// if ($strFirst == 'E')
    		{
    			if ($mm->Fit($fVal))        $strColor = 'silver';
    			else 						$strColor = 'yellow';
    		}
    		$this->arColor[$strKey] = $strColor;
    	}
    	
    	sort($arVal, SORT_NUMERIC);
    	$ar = array();
    	foreach ($arVal as $fVal)
    	{
    		$ar[] = strval_round($fVal);
    	}	
    	$this->arOrder = array_unique($ar);
	}
    
	function GetSMA()
	{
		return $this->arSMA;
	}
	
	function GetOrderArray()
	{
		return $this->arOrder;
	}
	
	function GetColor($strKey)
	{
		return $this->arColor[$strKey];
	}
	
    public function __construct($ref, $bAfterHour = false) 
    {
        $this->stock_ref = $ref;
        $this->aiNum = array(5, 10, 20);
                                                              
		$ref->SetTimeZone();
		$this->strStartDate = $this->_calcStartDate();
        $this->_configSMA();
        
        if ($bAfterHour && $this->NeedAfterHourEst())	
        {
        	$this->_onTest();
        	$this->_getColorAndOrderArray($this->arNext);
        }
        else
        {
        	$this->_getColorAndOrderArray($this->arSMA);
        }
    }
}

?>
