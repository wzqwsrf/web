<?php
require_once('../php/debug.php');
require_once('../php/ui/echoelement.php');

define('DISP_TITLE', '429 Too Many Requests');

function GetRetryAfter()
{
	return 'Retry-After: '.strval(SECONDS_IN_DAY);
}

function EchoHead()
{
	EchoCharset();
	EchoTitle(DISP_TITLE);
//	EchoViewPort();
//	EchoCSS();
}

function EchoBody()
{
	EchoHeading(DISP_TITLE);
	EchoHtmlElement(GetRetryAfter());
}

	$acct = false;
	http_response_code(429);
	header(GetRetryAfter());

	EchoDocType();
?>

<html lang="en">
<head>
<?php EchoHead(); ?>
</head>
<body>
<?php EchoBody(); ?>
</body>
</html>
