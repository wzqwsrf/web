<?php
require_once('sqlkeyname.php');
require_once('sqlkeytable.php');

class MemberSql extends KeyNameSql
{
	var $strIpKey;
	
    public function __construct()
    {
    	$this->strIpKey = $this->Add_id('ip');
        parent::__construct(TABLE_MEMBER.'2', 'email');
    }

    public function Create()
    {
    	$str = $this->ComposeVarcharStr('email', 128, false).','
         	  . ' `password` CHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL ,'
    		  . $this->ComposeDateStr().','
    		  . $this->ComposeTimeStr().','
         	  . ' `status` TINYINT UNSIGNED NOT NULL ,'
         	  . ' `activity` INT UNSIGNED NOT NULL ,'
         	  . $this->ComposeVarcharStr('name', 64).','
         	  . ' `signature` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,'
    		  . $this->ComposeIntStr($this->strIpKey).','
    		  . $this->ComposeForeignStr($this->strIpKey).','
         	  . ' FULLTEXT ( `signature` ) ,'
         	  . ' INDEX ( `status` ) ,'
    		  . _SqlComposeDateTimeIndex().','
         	  . ' UNIQUE ( `email` )';
    	return $this->CreateIdTable($str);
    }
}

// ****************************** Member table *******************************************************

function SqlUpdateLoginField($strEmail, $strIp)
{
	// Create UPDATE query
	$strQry = "UPDATE member SET ip = '$strIp', login = NOW() WHERE email = '$strEmail' LIMIT 1";
	return SqlDieByQuery($strQry, 'Update member login time failed');
}

function SqlUpdatePasswordField($strEmail, $strPassword)
{
	// Create UPDATE query
	$strQry = "UPDATE member SET password = '".md5($strPassword)."' WHERE email = '$strEmail' LIMIT 1";
	return SqlDieByQuery($strQry, 'Update member password failed');
}

function SqlUpdateLoginEmail($id, $strEmail)
{
	// Create UPDATE query
	$strQry = "UPDATE member SET email = '$strEmail' WHERE id = '$id' LIMIT 1";
	return SqlDieByQuery($strQry, 'Update member login email failed');
}

function SqlExecLogin($strEmail, $strPassword)
{
	if ($member = SqlGetSingleTableData(TABLE_MEMBER, _SqlBuildWhereAndArray(array('email' => $strEmail, 'password' => md5($strPassword)))))
	{	// Login Successful
		return $member['id'];
	}
	return false;
}

function SqlInsertMember($strEmail, $strPassword)
{
	$strIp = UrlGetIp();

	// Create INSERT query
	$strQry = "INSERT INTO member(id, email, password, ip, register, login, status, activity) VALUES('0', '$strEmail', '".md5($strPassword)."', '$strIp', NOW(), NOW(), '1', '0')";
	return SqlDieByQuery($strQry, 'Insert member failed');
}

function SqlGetIdByEmail($strEmail)
{
	if ($member = SqlGetSingleTableData(TABLE_MEMBER, _SqlBuildWhere('email', $strEmail)))
	{
		return $member['id'];
	}
	return false;
}
/*
function SqlGetMemberEmails()
{
    return SqlGetTableData(TABLE_MEMBER, "status = '2'");
}
*/
function SqlGetMemberById($strId)
{
    return SqlGetTableDataById(TABLE_MEMBER, $strId);
}

function SqlGetMemberByIp($strIp)
{
    return SqlGetTableData(TABLE_MEMBER, _SqlBuildWhere('ip', $strIp), '`login` DESC');
}

function SqlGetEmailById($strId)
{
	if ($member = SqlGetMemberById($strId))
	{
		return $member['email'];
	}
	return false;
}

function SqlChangeActivity($strId, $iChangeValue)
{
	if ($member = SqlGetMemberById($strId))
	{
		$iActivity = $member['activity'];
		$iActivity += $iChangeValue;

		// Create UPDATE query
		$strQry = "UPDATE member SET activity = '$iActivity' WHERE id = '$strId' LIMIT 1";
		return SqlDieByQuery($strQry, 'Update member activity failed');
	}
	return false;
}

function SqlUpdateStatus($strId, $iNewStatus)
{
	// Create UPDATE query
	$strQry = "UPDATE member SET status = '$iNewStatus' WHERE id = '$strId' LIMIT 1";
	return SqlDieByQuery($strQry, 'Update member status failed');
}


// ****************************** Profile table *******************************************************

function SqlGetProfileByMemberId($strMemberId)
{
	return SqlGetSingleTableData(TABLE_PROFILE, _SqlBuildWhere_member($strMemberId));
}

function SqlGetNameByMemberId($strMemberId)
{
	if ($profile = SqlGetProfileByMemberId($strMemberId))
	{
		return $profile['name'];
	}
	return false;
}

function SqlUpdateProfile($id, $strName, $strPhone, $strAddress, $strWeb, $strSignature)
{
	if (SqlGetProfileByMemberId($id))
	{	// Create UPDATE query
		$strQry = "UPDATE profile SET name = '$strName', phone = '$strPhone', address = '$strAddress', web = '$strWeb', signature = '$strSignature' WHERE member_id = '$id' LIMIT 1";
        return SqlDieByQuery($strQry, 'Update profile failed');
	}
	
	$strQry = "INSERT INTO profile(id, member_id, name, phone, address, web, signature) VALUES('0', '$id', '$strName', '$strPhone', '$strAddress', '$strWeb', '$strSignature')";
    return SqlDieByQuery($strQry, 'Insert profile failed');
}

function SqlDeleteProfileByMemberId($strMemberId)
{
    return SqlDeleteTableData(TABLE_PROFILE, _SqlBuildWhere_member($strMemberId), '1');
}

?>
