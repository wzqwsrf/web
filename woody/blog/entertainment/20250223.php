<?php
require_once('php/_entertainment.php');

function GetMetaDescription($bChinese)
{
	return 'Translation pending.';
}

function EchoAll($bChinese)
{
	$strImage = ImgFreeFood($bChinese);
	
	EchoBlogDate($bChinese);
    echo <<<END
<br />Translation pending.
$strImage
</p>
END;
}

require('../../../php/ui/_disp.php');
?>
