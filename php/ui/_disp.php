<?php EchoDocType(); ?>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title><?php EchoTitle(false); ?></title>
<meta name="description" content="<?php EchoMetaDescription(false); ?>">
<?php EchoInsideHead(); ?>
<link href="/common/style.css" rel="stylesheet" type="text/css" />
</head>

<body bgproperties=fixed leftmargin=0 topmargin=0>
<?php _LayoutTopLeft(false); ?>

<div>
<h1><?php EchoTitle(false); ?></h1>
<?php EchoAll(false); ?>
</div>

<?php _LayoutBottom(false); ?>
</body>
</html>
