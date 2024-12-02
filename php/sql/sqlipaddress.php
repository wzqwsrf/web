<?php
require_once('sqlint.php');
/*
define('IP_STATUS_NORMAL', '0');
define('IP_STATUS_CRAWLER', '1');
define('IP_STATUS_MALICIOUS', '2');
*/
function GetIp($strId)
{
	return long2ip($strId);
}
    
function GetIpId($strIp)
{
	return sprintf("%u", ip2long($strIp));
}
/*
class IpSql extends TableSql
{
    public function __construct()
    {
        parent::__construct('ip');
    }

    public function Create()
    {
    	$str = $this->ComposePrimaryIdStr().','
         	  . ' `visit` INT UNSIGNED NOT NULL ,'
         	  . ' `login` SMALLINT UNSIGNED NOT NULL ,'
         	  . ' `status` TINYINT UNSIGNED NOT NULL ,'
         	  . ' INDEX ( `status` )';
    	return $this->CreateTable($str);
    }

    public function GetRecord($strIp)
    {
   		return $this->GetRecordById(GetIpId($strIp));
    }

    function _makeUpdateArray($strVisit = '0', $strLogin = '0', $strStatus = '0')
    {
    	return array('visit' => $strVisit,
    				  'login' => $strLogin,
    				  'status' => $strStatus);
    }

    function InsertIp($strIp)
    {
       	if ($this->GetRecord($strIp) == false)
       	{
       		if ($strId = GetIpId($strIp))
       		{
       			return $this->InsertArrays(array('id' => $strId), $this->_makeUpdateArray());
       		}
       	}
		return false;
    }
    
    function UpdateIp($strIp, $strVisit, $strLogin, $strStatus)
    {
    	$ar = $this->_makeUpdateArray($strVisit, $strLogin, $strStatus);
    	if ($record = $this->GetRecord($strIp))
    	{	
    		if ($record['visit'] == $strVisit)		unset($ar['visit']);
    		if ($record['login'] == $strLogin)		unset($ar['login']);
    		if ($record['status'] == $strStatus)	unset($ar['status']);
    		if (count($ar) > 0)
    		{
    			return $this->UpdateById($ar, $record['id']);
    		}
    	}
    	return false;
    }

    function IncLogin($strIp)
    {
    	if ($record = $this->GetRecord($strIp))
    	{
    		$iVal = intval($record['login']);
    		$iVal ++;
    		return $this->UpdateIp($strIp, $record['visit'], strval($iVal), $record['status']);
    	}
    	return false;
    }

    function AddVisit($strIp, $iCount)
    {
    	if ($record = $this->GetRecord($strIp))
    	{
    		$iVal = intval($record['visit']);
    		$iVal += $iCount;
    		return $this->UpdateIp($strIp, strval($iVal), $record['login'], $record['status']);
    	}
    	return false;
    }

    function SetStatus($strIp, $strStatus)
    {
    	if ($record = $this->GetRecord($strIp))
    	{
    		if ($record['status'] != $strStatus)
    		{
    			return $this->UpdateIp($strIp, $record['visit'], $record['login'], $strStatus);
    		}
    	}
    	return false;
    }
    
    function GetStatus($strIp)
    {
    	if ($record = $this->GetRecord($strIp))
    	{
    		return $record['status'];
    	}
    	return false;
    }
}
*/
class IpAddressSql extends TableSql
{
    public function GetRecord($strIp)
    {
   		return $this->GetRecordById(GetIpId($strIp));
    }

    function InsertIp($strIp)
    {
       	if ($this->GetRecord($strIp) == false)
       	{
       		if ($strId = GetIpId($strIp))
       		{
       			return $this->InsertId($strId);
       		}
       	}
		return false;
    }
    
    function DeleteByIp($strIp)
    {
    	return $this->DeleteById(GetIpId($strIp));
    }
}

class IpCrawlerSql extends IpAddressSql
{
    public function __construct()
    {
        parent::__construct('ipcrawler');
    }
}

class IpMaliciousSql extends IpAddressSql
{
    public function __construct()
    {
        parent::__construct('ipmalicious');
    }
}

class IpIntSql extends IntSql
{
    public function WriteInt($strIp, $iTick)
    {
    	return parent::WriteInt(GetIpId($strIp), $iTick);
    }
    
    public function ReadInt($strIp)
    {
    	return parent::ReadInt(GetIpId($strIp));
    }
}

class IpVisitSql extends IpIntSql
{
    public function __construct()
    {
        parent::__construct('ipvisit');
    }
}


class IpLoginSql extends IpIntSql
{
    public function __construct()
    {
        parent::__construct('iplogin');
    }
}

?>
