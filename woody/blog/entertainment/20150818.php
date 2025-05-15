<?php require_once('php/_20150818.php'); ?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title><?php echo GetBlogTitle(20150818, false, false); ?></title>
<meta name="description" content="My second PHP application, to estimate the net value of SZ162411 based on XOP, ^SPSIOP and USDCNY exchange rate.">
<?php EchoInsideHead(); ?>
<link href="../../../common/style.css" rel="stylesheet" type="text/css" />
</head>

<body bgproperties=fixed leftmargin=0 topmargin=0>
<?php _LayoutTopLeft(false); ?>

<?php DemoPrefetchData(); ?>

<div>
<h1><?php echo GetBlogTitle(20150818, false, false); ?></h1>
<p>Aug 18, 2015
<br />As my CSR <a href="20141016.php">Stock</a> is turning into cash soon, I am considering to use the USD to trade XOP while using my other CNY to trade SZ162411 together. 
I was watching stock quotations on Yahoo and Sina everyday, and always need to click on the calculator application to convert the price between XOP and SZ162411, soon I got bored. 
<br />Later I thought of my first <a href="20100905.php">PHP</a> application 5 years ago, and decided to write my second PHP application.
It was planned to put all the usual stock quotations together, and to estimate SZ162411 net value based on XOP, ^SPSIOP and USDCNY exchange rate.
Today the first version is released, and I am writing this record of programming details for future reference.
<br />Thanks <?php EchoXueqiuId('6188729918', 'abkoooo'); ?> for Sina realtime US stock data format.
<br />Using Sina realtime source for US and Chinese stocks, oil futures and forex: <?php EchoSinaDataLink('gb_xop,sz162411,hf_CL,USDCNY'); ?>
<br />At first I found that <i>fopen</i> will always fail to open those urls, maybe my Yahoo Web Hosting does not support <i>allow_url_fopen</i>. 
I searched and found curl to solve this problem, copied some curl code to my <i>url_get_contents</i> function with name similar as <i>file_get_contents</i>.
<br />To optimize web response time, I used 2 files <?php EchoSinaDebugLink('gb_xop'); ?> and <?php EchoSinaDebugLink('sz162411'); ?> to save last updated stock data.
The following are checked:
</p>
<ol>
  <li>New request in the same minute using data in original files directly.</li>
  <li>Using gb_xop.txt for US stock data after US market closed.</li>
  <li>Using sz162411.txt for Chinese stock data after Chinese market closed.</li>
</ol>
<p>Similarly, oil future data is stored in file <?php EchoSinaDebugLink('hf_cl'); ?>, and USDCNY forex data in usdcny.txt.
<?php echo ImgCompleteLenna(false); ?>
</p>

<h3><a name="sma">SMA</a></h3>
<p>Aug 20, 2015
<br />To set up my own trading rules, and to avoid following the market all night, I plan to trade XOP simply on Simple Moving Average (SMA) values.
This version added the premium of current trading price comparing with XOP estimation of SZ162411 net value,
and the days in past 100 trading days did the estimated SMA value fitted in the trading range.
<br />The historic data of XOP only need to update once a day, using Yahoo stock historic data: <?php EchoExternalLink(GetYahooStockHistoryUrl('XOP')); ?>
<br />The official fund net value only need to update once a day too.
Using file <?php EchoSinaDebugLink('f_162411'); ?> for official SZ162411 net value from <?php EchoSinaDataLink('f_162411'); ?>,
as I do not konw when the data will be updated, I update it once an hour stupidly.
<br />Using debug file <?php echo GetDebugFileLink(); ?> for temp data checking.
</p>

<h3>The Most Stupid Bug in Recent Years</h3>
<p>May 15, 2016
<br />Last week USDCNY was rising significantly again, and a new bug of SZ162411 net value estimation floated on water. On Friday, May 13, my estimation was about 1% higher than the official data.
I began to check the problem yesterday, found that the last automatic calibration was done on the evening of May 12, after the official net value of May 11 data released.
And the automatic calibration on May 13 was not done yet. In other words, the calibration was one day late for quite some time now, but when USDCNY had little change, the problem was ignored.
<br />It was easy to find the bug, I used the simplest way to fix the estimation bug caused by continue Chinese and USA market close after Chinese New Year.
Because only Hongkong QDII would have the chance of official QDII net value newer than ETF, I rewrote part of the code by checking if it was HK QDII,
and modified an actually unrelated code, what should be <font color=gray><code>$iHours = STOCK_HOUR_END + ($this->usdhkd_ref ? 0 : 24);</code></font> was written as <font color=gray><code>$iHours = STOCK_HOUR_END + ($this->usdhkd_ref) ? 0 : 24;</code></font>
<br />But this bug made me feel very bad, this time I can not say I am still a 6 years <font color=red>new</font> PHP programmer. As a proud 25 years C programmer, this is also a very stupid bug in C language!
</p>

<h3>Automatic and manual <a name="calibrationhistory">calibration history</a></h3>
<p>Oct 6, 2016
<br />SZ162411 calibration history.
</p>

</div>

<?php _LayoutBottom(false); ?>

</body>
</html>

