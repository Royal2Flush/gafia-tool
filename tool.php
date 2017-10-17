<html>

<head>
	<title>Mafia Tool</title>
</head>

<body>

<?php

//==================================//
// PREDEFINED VARIABLES AND CLASSES //
//==================================//

$BASEURL = "http://www.neogaf.com/forum/showthread.php";
$PSTURL = "http://www.neogaf.com/forum/showpost.php";
$STARTPOST = "<!-- post #";
$ENDPOST = "<!-- / post #";
$STARTHIGHLIGHT = "<span class=\"highlight\">";
$ENDHIGHLIGHT = "</span>";
$VOTECOMMAND = "vote";
$DOUBLECOMMAND = "double";
$UNVOTECOMMAND = "unvote";
$PREPHASECHANGE = "day";
$DAYSTART = "begins";
$DAYEND = "has ended";
$PRESTARTPOSTER = "member.php";
$STARTPOSTER = ">";
$ENDPOSTER = "</a>";
$STARTQUOTE = "<blockquote";
$STARTMETA = "<div class=\"post-meta";
$ENDMETA = "</a>";
$STARTPOSTNR = "><strong>";
$ENDPOSTNR = "</strong>";
$STARTLINK = "id=\"postcount";
$ENDLINK = "\" name";
$ENDQUOTE = "</blockquote>";
$STARTPAGES = "<li class=\"pageof\"";
$ENDPAGES = "</li>";
$PAGES1 = "page";
$PAGES2 = "of";

$TOPICNR = htmlspecialchars($_GET["t"]);

function generateLink($p) // generates a link to a specific post
{
	return ("http://www.neogaf.com/forum/showthread.php?p=".$p."#post".$p);
}

class Vote
{
	private $target = "NA";
	private $sender = "NA";
	private $weight = 1;
	private $active = FALSE;
	private $post1 = 0;
	private $post2 = 0;
	private $link1 = 0;
	private $link2 = 0;
	private $written = FALSE;

	public function cast($t,$s,$w,$p,$l)
	{
		$this->target = $t;
		$this->sender = $s;
		$this->weight = $w;
		$this->post1 = $p;
		$this->link1 = $l;
		$this->active = TRUE;
	}

	public function revoke($p,$l)
	{
		$this->post2 = $p;
		$this->link2 = $l;
		$this->active = FALSE;
	}

	public function isSender($s)
	{
		return strcmp($this->sender,$s);
	}

	public function isWritten()
	{
		return $this->written;
	}

	public function getWeight()
	{
		return $this->weight;
	}

	public function isActive()
	{
		return $this->active;
	}

	public function getTarget()
	{
		return $this->target;
	}

	public function getPost()
	{
		if ($this->post2 == 0)
		{
			return $this->post1;
		}
		else
		{
			return $this->post2;
		}
	}

	public function write($bb)
	{
		for ($i = 0; $i < $this->weight; $i++)
		{
			if ($this->active == FALSE)
			{
				echo "<strike>";
				$bb = $bb."[strike]";
			}
			echo $this->sender." <a href=".generateLink($this->link1).">".$this->post1."</a>";
			$bb = $bb.$this->sender." [url=".generateLink($this->link1)."]".$this->post1."[/url]";
			if ($this->active == FALSE)
			{
				echo "</strike> (<a href=".generateLink($this->link2).">".$this->post2."</a>)";
				$bb = $bb."[/strike] ([url=".generateLink($this->link2)."]".$this->post2."[/url])";
			}
			echo "</br>";
			$bb = $bb."</br>";
		}
		$this->written = TRUE;

		return $bb;
	}
}

//======================================//
// CRAWL AND ANALYZE CONTENT FROM FORUM //
//======================================//

$safeArray = unserialize(file_get_contents($TOPICNR.".data"));

$votelist;
$currentPage;

if (array_key_exists("voteList",$safeArray))
{
	$votelist = $safeArray["voteList"];
	if(array_key_exists("pageanchor",$safeArray))
	{
		$currentPage = $safeArray["pageanchor"];
	}
	else
	{
		$currentPage = 0;
	}
	$dss = count($votelist)-1;
}
else
{
	$votelist = array();
	$currentPage = 1;
	$dss = -1;
}

$lastPage = 400;
$inDayPhase = FALSE;
$startDay = 0;
$dailyList = array();

ob_start();
echo "<html><head><title>Mafia votecount</title></head><body>";

while($currentPage <= $lastPage) // main loop over all pages of the thread
{
	$URL = $BASEURL."?t=".$TOPICNR."&page=".$currentPage;
	$s = strtolower(file_get_contents($URL)); // s: string with the entire page html

	$s = ltrim(strchr($s,$STARTPAGES,FALSE),$STARTPAGES);
	$n = strchr($s,$ENDPAGES,TRUE); // n: string with "page x of y"

	$currentPage = ltrim(rtrim(ltrim(strchr(strchr($n,$PAGES2,TRUE),$PAGES1,FALSE),$PAGES1)));
	$lastPage = ltrim(rtrim(ltrim(strchr($n,$PAGES2,FALSE),$PAGES2)));

	while($s !== "")
	{
		$s = ltrim(strchr($s,$STARTPOST,FALSE),$STARTPOST);
		$p = strchr($s,$ENDPOST,TRUE); // p: post

		$u = rtrim(strchr(ltrim(strchr(strchr($p,$PRESTARTPOSTER,FALSE),$STARTPOSTER,FALSE),$STARTPOSTER),$ENDPOSTER,TRUE)); // u: user
		$m = ltrim(strchr(strchr($p,$STARTMETA,FALSE),$ENDMETA,TRUE),$STARTMETA); // m: meta
		$l = ltrim(strchr(strchr($m,$STARTLINK,FALSE),$ENDLINK,TRUE),$STARTLINK); // l: link
		$r = ltrim(strchr(strchr($m,$STARTPOSTNR,FALSE),$ENDPOSTNR,TRUE),$STARTPOSTNR); // r: post number

		$q = strchr($p,$STARTQUOTE,FALSE); // quote (if any)
		while (strlen($q) !== 0) // remove all quotes from current post
		{
			$q = strchr($q,$ENDQUOTE,TRUE);
			$p = str_replace($q,"",$p);
			$q = strchr($p,$STARTQUOTE,FALSE);
		}

		while($p !== "") // go through all opening highlight tags in post
        	{
			$p = ltrim(strchr($p,$STARTHIGHLIGHT,FALSE),$STARTHIGHLIGHT);
			$c = strchr($p,$ENDHIGHLIGHT,TRUE); // c: command

			// are we in the day phase?
			if ($inDayPhase == TRUE)
			{
				// is it a vote command?
				$vc = rtrim(ltrim(ltrim(strchr(ltrim(ltrim(strchr($c,$VOTECOMMAND,FALSE),$VOTECOMMAND)),":",FALSE),":"))); // vc: votecommand
				$dc = rtrim(ltrim(ltrim(strchr(ltrim(ltrim(strchr($c,$DOUBLECOMMAND,FALSE),$DOUBLECOMMAND)),":",FALSE),":"))); // dc: doublecommand
				$uc = strchr($c,$UNVOTECOMMAND,FALSE); // uc: unvotecommand
				if(strlen($vc)>0 && strlen($uc)==0)
				{
					for ($i = count($dailyList) - 1; $i >= 0; $i--)
					{
						if ($dailyList[$i]->isSender($u) == 0)
						{
							if ($dailyList[$i]->isActive() == TRUE)
							{
								$dailyList[$i]->revoke($r,$l);
							}
							break;
						}
					}
					$v = new Vote();
					$v->cast($vc,$u,1,$r,$l);
					$dailyList[] = $v;
				}

				// is it a doublevote command?
				if(strlen($dc)>0)
                                {
                                        for ($i = count($dailyList) - 1; $i >= 0; $i--)
                                        {
                                                if ($dailyList[$i]->isSender($u) == 0)
                                                {
                                                        if ($dailyList[$i]->isActive() == TRUE)
                                                        {
                                                                $dailyList[$i]->revoke($r,$l);
                                                        }
                                                        break;
                                                }
                                        }
                                        $v = new Vote();
                                        $v->cast($dc,$u,2,$r,$l);
                                        $dailyList[] = $v;
                               }

				// is it an unvote command?
                	        if(strlen($uc)>0)
                        	{
                                	for ($i = count($dailyList) - 1; $i >= 0; $i--)
                                	{
                                        	if ($dailyList[$i]->isSender($u) == 0)
                                        	{
							if ($dailyList[$i]->isActive() == TRUE)
                                                        {
                                                                $dailyList[$i]->revoke($r,$l);
                                                        }
        	                                        break;
                	                        }
                        	        }
				}

				// is it the day end command?
				$ec = strchr($c,$DAYEND,FALSE); // ec: endcommand
				if (strlen($ec)>0)
				{
					$inDayPhase = FALSE;
					$votelist[] = $dailyList;
					$safeArray["pageanchor"] = $currentPage;
					unset($dailyList);
				}
			}
			else // it's night phase
			{
				$bc = ltrim(strchr($c,$PREPHASECHANGE,FALSE),$PREPHASECHANGE); // bc: begincommand
				if (strlen($c)>0)
				{
					$newDay = ltrim(rtrim(strchr($bc,$DAYSTART,TRUE)));
					if (strlen($newDay)>0)
					{
						$dss++;
						$inDayPhase = TRUE;
						$dailyList = array();
					}
				}
			}
		}
	}
	$currentPage = $currentPage + 1;
}

$safeArray["voteList"] = $votelist;
file_put_contents($TOPICNR.".data", serialize($safeArray));

if ($inDayPhase == TRUE)
{
	$votelist[] = $dailyList;
}


//=======================================//
// GENERATE OUTPUT FROM ANALYZED CONTENT //
//=======================================//

$handledPost = 0;
$bb = "";

if (count($votelist[$dss]==0))
{
	$lastupdate = "End of Day ".($dss);
}
else
{
	$lastupdate = "post ".$votelist[$dss][count($votelist[$dss])-1]->getPost();
}
echo "<b>Last Update: ".$lastupdate."</b> <a href=tool.php?t=".$TOPICNR.">Recount</a></br><br><a href=index.html>Back to start page</a>";

echo "<h2>Quickjump</h2>";

for ($d = 0; $d <= $dss; $d++)
{
	echo "Day ".($d + 1).": <a href=\"#HTML".($d + 1)."\">View</a> | <a href=\"#BB".($d + 1)."\">BB-Code</a></br>";
}


for ($d = 0; $d <= $dss; $d++)
{
	echo "<h2 id=\"HTML".($d + 1)."\">Day ".($d + 1)."</h2>";
	$bb = $bb."</code><h3 id=\"BB".($d + 1)."\">Day ".($d + 1)." votes</h3><code>";
	for ($i = 0; $i < count($votelist[$d]); $i++)
	{
		if ($votelist[$d][$i]->isWritten() == FALSE)
		{
			echo "</br>";
			$bb = $bb."</br>";
			$t = $votelist[$d][$i]->getTarget();
			$cnt = 0;
			for ($j = 0; $j < count($votelist[$d]); $j++)
			{
				if ($votelist[$d][$j]->isActive() == TRUE)
				{
					if ($votelist[$d][$j]->getTarget() == $t)
					{
						$cnt = $cnt + $votelist[$d][$j]->getWeight();
					}
				}
			}
			echo "<u><b>".htmlspecialchars($t)." (".$cnt.")</b></u></br>";
			$bb = $bb."[u][b]".htmlspecialchars($t)." (".$cnt.")[/b][/u]</br>";
			for ($j = 0; $j < count($votelist[$d]); $j++)
                	{
                        	if ($votelist[$d][$j]->getTarget() == $t)
                        	{
                                	$bb = $votelist[$d][$j]->write($bb);

                        	}
                	}
		}
	}
}

echo "<h2>BB Code</h2><code>".$bb."</code></body></html>";

$content = ob_get_contents();
ob_end_clean();
file_put_contents($TOPICNR.".html", $content);

echo "Done. <a href=".$TOPICNR.".html>Show votecount</a>"

?>

</body>
</html>
