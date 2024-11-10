<?php
require_once('sqlval.php');

class IntSql extends ValSql
{
    public function __construct($strTableName, $strIntName = 'num')
    {
        parent::__construct($strTableName, $strIntName);
    }

    function CreateIntTable($strExtra = '')
    {
    	$str = $this->ComposePrimaryIdStr().','
         	  . $this->ComposeIdStr($this->GetValName())
         	  . $strExtra;
    	return $this->CreateTable($str);
    }
    
    public function Create()
    {
    	return $this->CreateIntTable();
    }
    
    function WriteString($strId, $str)
    {
    	return $this->WriteVal($strId, $str, true);
    }
    
    function ReadString($strId)
    {
    	return $this->ReadVal($strId, true);
    }
    
    function WriteInt($strId, $iInt)
    {
    	return $this->WriteString($strId, strval($iInt));
    }
    
    function ReadInt($strId)
    {
    	if ($str = $this->ReadString($strId))	return intval($str);
    	return false;
    }
}

class FundHedgeValSql extends IntSql
{
    public function __construct()
    {
        parent::__construct('fundhedgeval');
    }
}

?>
