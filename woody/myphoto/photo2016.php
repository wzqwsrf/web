<?php
require_once('php/_photo2016.php');

function GetMetaDescription($bChinese)
{
	return 'Woody 2016 personal photos and related links. Including Mia in Hong Kong Disneyland.';
}

function EchoAll($bChinese)
{
	$strDisney = Photo20161113($bChinese);
	
    echo <<<END
$strDisney
END;
}

require('../../php/ui/_disp.php');
?>
