<?php
/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 5.2.0
*/namespace
Adminer;const
VERSION="5.2.0";error_reporting(24575);set_error_handler(function($_c,$Bc){return!!preg_match('~^Undefined (array key|offset|index)~',$Bc);},E_WARNING|E_NOTICE);$Wc=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($Wc||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Wi=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Wi)$$X=$Wi;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection($g=null){return($g?:Db::$ee);}function
adminer(){return
Adminer::$ee;}function
driver(){return
Driver::$ee;}function
connect(){$Cb=adminer()->credentials();$J=Driver::connect($Cb[0],$Cb[1],$Cb[2]);return(is_object($J)?$J:null);}function
idf_unescape($u){if(!preg_match('~^[`\'"[]~',$u))return$u;$xe=substr($u,-1);return
str_replace($xe.$xe,$xe,substr($u,1,-1));}function
q($Q){return
connection()->quote($Q);}function
escape_string($X){return
substr(q($X),1,-1);}function
idx($va,$x,$k=null){return($va&&array_key_exists($x,$va)?$va[$x]:$k);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
number_type(){return'((?<!o)int(?!er)|numeric|real|float|double|decimal|money)';}function
remove_slashes(array$Eg,$Wc=false){if(function_exists("get_magic_quotes_gpc")&&get_magic_quotes_gpc()){while(list($x,$X)=each($Eg)){foreach($X
as$pe=>$W){unset($Eg[$x][$pe]);if(is_array($W)){$Eg[$x][stripslashes($pe)]=$W;$Eg[]=&$Eg[$x][stripslashes($pe)];}else$Eg[$x][stripslashes($pe)]=($Wc?$W:stripslashes($W));}}}}function
bracket_escape($u,$Ca=false){static$Fi=array(':'=>':1',']'=>':2','['=>':3','"'=>':4');return
strtr($u,($Ca?array_flip($Fi):$Fi));}function
min_version($nj,$Ie="",$g=null){$g=connection($g);$zh=$g->server_info;if($Ie&&preg_match('~([\d.]+)-MariaDB~',$zh,$B)){$zh=$B[1];$nj=$Ie;}return$nj&&version_compare($zh,$nj)>=0;}function
charset(Db$f){return(min_version("5.5.3",0,$f)?"utf8mb4":"utf8");}function
ini_bool($Zd){$X=ini_get($Zd);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
sid(){static$J;if($J===null)$J=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$J;}function
set_password($mj,$N,$V,$F){$_SESSION["pwds"][$mj][$N][$V]=($_COOKIE["adminer_key"]&&is_string($F)?array(encrypt_string($F,$_COOKIE["adminer_key"])):$F);}function
get_password(){$J=get_session("pwds");if(is_array($J))$J=($_COOKIE["adminer_key"]?decrypt_string($J[0],$_COOKIE["adminer_key"]):false);return$J;}function
get_val($H,$m=0,$qb=null){$qb=connection($qb);$I=$qb->query($H);if(!is_object($I))return
false;$K=$I->fetch_row();return($K?$K[$m]:false);}function
get_vals($H,$d=0){$J=array();$I=connection()->query($H);if(is_object($I)){while($K=$I->fetch_row())$J[]=$K[$d];}return$J;}function
get_key_vals($H,$g=null,$Bh=true){$g=connection($g);$J=array();$I=$g->query($H);if(is_object($I)){while($K=$I->fetch_row()){if($Bh)$J[$K[0]]=$K[1];else$J[]=$K[0];}}return$J;}function
get_rows($H,$g=null,$l="<p class='error'>"){$qb=connection($g);$J=array();$I=$qb->query($H);if(is_object($I)){while($K=$I->fetch_assoc())$J[]=$K;}elseif(!$I&&!$g&&$l&&(defined('Adminer\PAGE_HEADER')||$l=="-- "))echo$l.error()."\n";return$J;}function
unique_array($K,array$w){foreach($w
as$v){if(preg_match("~PRIMARY|UNIQUE~",$v["type"])){$J=array();foreach($v["columns"]as$x){if(!isset($K[$x]))continue
2;$J[$x]=$K[$x];}return$J;}}}function
escape_key($x){if(preg_match('(^([\w(]+)('.str_replace("_",".*",preg_quote(idf_escape("_"))).')([ \w)]+)$)',$x,$B))return$B[1].idf_escape(idf_unescape($B[2])).$B[3];return
idf_escape($x);}function
where(array$Z,array$n=array()){$J=array();foreach((array)$Z["where"]as$x=>$X){$x=bracket_escape($x,true);$d=escape_key($x);$m=idx($n,$x,array());$Uc=$m["type"];$J[]=$d.(JUSH=="sql"&&$Uc=="json"?" = CAST(".q($X)." AS JSON)":(JUSH=="sql"&&is_numeric($X)&&preg_match('~\.~',$X)?" LIKE ".q($X):(JUSH=="mssql"&&strpos($Uc,"datetime")===false?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$X)):" = ".unconvert_field($m,q($X)))));if(JUSH=="sql"&&preg_match('~char|text~',$Uc)&&preg_match("~[^ -@]~",$X))$J[]="$d = ".q($X)." COLLATE ".charset(connection())."_bin";}foreach((array)$Z["null"]as$x)$J[]=escape_key($x)." IS NULL";return
implode(" AND ",$J);}function
where_check($X,array$n=array()){parse_str($X,$Va);remove_slashes(array(&$Va));return
where($Va,$n);}function
where_link($s,$d,$Y,$Ff="="){return"&where%5B$s%5D%5Bcol%5D=".urlencode($d)."&where%5B$s%5D%5Bop%5D=".urlencode(($Y!==null?$Ff:"IS NULL"))."&where%5B$s%5D%5Bval%5D=".urlencode($Y);}function
convert_fields(array$e,array$n,array$M=array()){$J="";foreach($e
as$x=>$X){if($M&&!in_array(idf_escape($x),$M))continue;$wa=convert_field($n[$x]);if($wa)$J
.=", $wa AS ".idf_escape($x);}return$J;}function
cookie($C,$Y,$De=2592000){header("Set-Cookie: $C=".urlencode($Y).($De?"; expires=".gmdate("D, d M Y H:i:s",time()+$De)." GMT":"")."; path=".preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]).(HTTPS?"; secure":"")."; HttpOnly; SameSite=lax",false);}function
get_settings($zb){parse_str($_COOKIE[$zb],$Ch);return$Ch;}function
get_setting($x,$zb="adminer_settings"){$Ch=get_settings($zb);return$Ch[$x];}function
save_settings(array$Ch,$zb="adminer_settings"){$Y=http_build_query($Ch+get_settings($zb));cookie($zb,$Y);$_COOKIE[$zb]=$Y;}function
restart_session(){if(!ini_bool("session.use_cookies")&&(!function_exists('session_status')||session_status()==1))session_start();}function
stop_session($ed=false){$ej=ini_bool("session.use_cookies");if(!$ej||$ed){session_write_close();if($ej&&@ini_set("session.use_cookies",'0')===false)session_start();}}function&get_session($x){return$_SESSION[$x][DRIVER][SERVER][$_GET["username"]];}function
set_session($x,$X){$_SESSION[$x][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($mj,$N,$V,$j=null){$aj=remove_from_uri(implode("|",array_keys(SqlDriver::$dc))."|username|ext|".($j!==null?"db|":"").($mj=='mssql'||$mj=='pgsql'?"":"ns|").session_name());preg_match('~([^?]*)\??(.*)~',$aj,$B);return"$B[1]?".(sid()?SID."&":"").($mj!="server"||$N!=""?urlencode($mj)."=".urlencode($N)."&":"").($_GET["ext"]?"ext=".urlencode($_GET["ext"])."&":"")."username=".urlencode($V).($j!=""?"&db=".urlencode($j):"").($B[2]?"&$B[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($A,$Ve=null){if($Ve!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($A!==null?$A:$_SERVER["REQUEST_URI"]))][]=$Ve;}if($A!==null){if($A=="")$A=".";header("Location: $A");exit;}}function
query_redirect($H,$A,$Ve,$Ng=true,$Gc=true,$Pc=false,$ti=""){if($Gc){$Rh=microtime(true);$Pc=!connection()->query($H);$ti=format_time($Rh);}$Lh=($H?adminer()->messageQuery($H,$ti,$Pc):"");if($Pc){adminer()->error
.=error().$Lh.script("messagesPrint();")."<br>";return
false;}if($Ng)redirect($A,$Ve.$Lh);return
true;}class
Queries{static$Ig=array();static$Rh=0;}function
queries($H){if(!Queries::$Rh)Queries::$Rh=microtime(true);Queries::$Ig[]=(preg_match('~;$~',$H)?"DELIMITER ;;\n$H;\nDELIMITER ":$H).";";return
connection()->query($H);}function
apply_queries($H,array$T,$Cc='Adminer\table'){foreach($T
as$R){if(!queries("$H ".$Cc($R)))return
false;}return
true;}function
queries_redirect($A,$Ve,$Ng){$Ig=implode("\n",Queries::$Ig);$ti=format_time(Queries::$Rh);return
query_redirect($Ig,$A,$Ve,$Ng,false,!$Ng,$ti);}function
format_time($Rh){return
sprintf('%.3f s',max(0,microtime(true)-$Rh));}function
relative_uri(){return
str_replace(":","%3a",preg_replace('~^[^?]*/([^?]*)~','\1',$_SERVER["REQUEST_URI"]));}function
remove_from_uri($cg=""){return
substr(preg_replace("~(?<=[?&])($cg".(SID?"":"|".session_name()).")=[^&]*&~",'',relative_uri()."&"),0,-1);}function
get_file($x,$Ob=false,$Tb=""){$Vc=$_FILES[$x];if(!$Vc)return
null;foreach($Vc
as$x=>$X)$Vc[$x]=(array)$X;$J='';foreach($Vc["error"]as$x=>$l){if($l)return$l;$C=$Vc["name"][$x];$Ai=$Vc["tmp_name"][$x];$vb=file_get_contents($Ob&&preg_match('~\.gz$~',$C)?"compress.zlib://$Ai":$Ai);if($Ob){$Rh=substr($vb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Rh))$vb=iconv("utf-16","utf-8",$vb);elseif($Rh=="\xEF\xBB\xBF")$vb=substr($vb,3);}$J
.=$vb;if($Tb)$J
.=(preg_match("($Tb\\s*\$)",$vb)?"":$Tb)."\n\n";}return$J;}function
upload_error($l){$Qe=($l==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($l?'Unable to upload a file.'.($Qe?" ".sprintf('Maximum allowed file size is %sB.',$Qe):""):'File does not exist.');}function
repeat_pattern($mg,$y){return
str_repeat("$mg{0,65535}",$y/65535)."$mg{0,".($y%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$X));}function
format_number($X){return
strtr(number_format($X,0,".",','),preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
friendly_url($X){return
preg_replace('~\W~i','-',$X);}function
table_status1($R,$Qc=false){$J=table_status($R,$Qc);return($J?reset($J):array("Name"=>$R));}function
column_foreign_keys($R){$J=array();foreach(adminer()->foreignKeys($R)as$p){foreach($p["source"]as$X)$J[$X][]=$p;}return$J;}function
fields_from_edit(){$J=array();foreach((array)$_POST["field_keys"]as$x=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$x];$_POST["fields"][$X]=$_POST["field_vals"][$x];}}foreach((array)$_POST["fields"]as$x=>$X){$C=bracket_escape($x,true);$J[$C]=array("field"=>$C,"privileges"=>array("insert"=>1,"update"=>1,"where"=>1,"order"=>1),"null"=>1,"auto_increment"=>($x==driver()->primary),);}return$J;}function
dump_headers($Md,$ff=false){$J=adminer()->dumpHeaders($Md,$ff);$Yf=$_POST["output"];if($Yf!="text")header("Content-Disposition: attachment; filename=".adminer()->dumpFilename($Md).".$J".($Yf!="file"&&preg_match('~^[0-9a-z]+$~',$Yf)?".$Yf":""));session_write_close();if(!ob_get_level())ob_start(null,4096);ob_flush();flush();return$J;}function
dump_csv(array$K){foreach($K
as$x=>$X){if(preg_match('~["\n,;\t]|^0|\.\d*0$~',$X)||$X==="")$K[$x]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$K)."\r\n";}function
apply_sql_function($r,$d){return($r?($r=="unixepoch"?"DATETIME($d, '$r')":($r=="count distinct"?"COUNT(DISTINCT ":strtoupper("$r("))."$d)"):$d);}function
get_temp_dir(){$J=ini_get("upload_tmp_dir");if(!$J){if(function_exists('sys_get_temp_dir'))$J=sys_get_temp_dir();else{$o=@tempnam("","");if(!$o)return'';$J=dirname($o);unlink($o);}}return$J;}function
file_open_lock($o){if(is_link($o))return;$q=@fopen($o,"c+");if(!$q)return;chmod($o,0660);if(!flock($q,LOCK_EX)){fclose($q);return;}return$q;}function
file_write_unlock($q,$Ib){rewind($q);fwrite($q,$Ib);ftruncate($q,strlen($Ib));file_unlock($q);}function
file_unlock($q){flock($q,LOCK_UN);fclose($q);}function
first(array$va){return
reset($va);}function
password_file($h){$o=get_temp_dir()."/adminer.key";if(!$h&&!file_exists($o))return'';$q=file_open_lock($o);if(!$q)return'';$J=stream_get_contents($q);if(!$J){$J=rand_string();file_write_unlock($q,$J);}else
file_unlock($q);return$J;}function
rand_string(){return
md5(uniqid(strval(mt_rand()),true));}function
select_value($X,$_,array$m,$si){if(is_array($X)){$J="";foreach($X
as$pe=>$W)$J
.="<tr>".($X!=array_values($X)?"<th>".h($pe):"")."<td>".select_value($W,$_,$m,$si);return"<table>$J</table>";}if(!$_)$_=adminer()->selectLink($X,$m);if($_===null){if(is_mail($X))$_="mailto:$X";if(is_url($X))$_=$X;}$J=adminer()->editVal($X,$m);if($J!==null){if(!is_utf8($J))$J="\0";elseif($si!=""&&is_shortable($m))$J=shorten_utf8($J,max(0,+$si));else$J=h($J);}return
adminer()->selectVal($J,$_,$m,$X);}function
is_mail($qc){$xa='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$cc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$mg="$xa+(\\.$xa+)*@($cc?\\.)+$cc";return
is_string($qc)&&preg_match("(^$mg(,\\s*$mg)*\$)i",$qc);}function
is_url($Q){$cc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return
preg_match("~^(https?)://($cc?\\.)+$cc(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$Q);}function
is_shortable(array$m){return
preg_match('~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~',$m["type"]);}function
count_rows($R,array$Z,$je,array$sd){$H=" FROM ".table($R).($Z?" WHERE ".implode(" AND ",$Z):"");return($je&&(JUSH=="sql"||count($sd)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$sd).")$H":"SELECT COUNT(*)".($je?" FROM (SELECT 1$H GROUP BY ".implode(", ",$sd).") x":$H));}function
slow_query($H){$j=adminer()->database();$ui=adminer()->queryTimeout();$Gh=driver()->slowQuery($H,$ui);$g=null;if(!$Gh&&support("kill")){$g=connect();if($g&&($j==""||$g->select_db($j))){$se=get_val(connection_id(),0,$g);echo
script("const timeout = setTimeout(() => { ajax('".js_escape(ME)."script=kill', function () {}, 'kill=$se&token=".get_token()."'); }, 1000 * $ui);");}}ob_flush();flush();$J=@get_key_vals(($Gh?:$H),$g,false);if($g){echo
script("clearTimeout(timeout);");ob_flush();flush();}return$J;}function
get_token(){$Lg=rand(1,1e6);return($Lg^$_SESSION["token"]).":$Lg";}function
verify_token(){list($Bi,$Lg)=explode(":",$_POST["token"]);return($Lg^$_SESSION["token"])==$Bi;}function
lzw_decompress($Ia){$Yb=256;$Ja=8;$eb=array();$Yg=0;$Zg=0;for($s=0;$s<strlen($Ia);$s++){$Yg=($Yg<<8)+ord($Ia[$s]);$Zg+=8;if($Zg>=$Ja){$Zg-=$Ja;$eb[]=$Yg>>$Zg;$Yg&=(1<<$Zg)-1;$Yb++;if($Yb>>$Ja)$Ja++;}}$Xb=range("\0","\xFF");$J="";$wj="";foreach($eb
as$s=>$db){$pc=$Xb[$db];if(!isset($pc))$pc=$wj.$wj[0];$J
.=$pc;if($s)$Xb[]=$wj.$pc[0];$wj=$pc;}return$J;}function
script($Ih,$Ei="\n"){return"<script".nonce().">$Ih</script>$Ei";}function
script_src($bj,$Qb=false){return"<script src='".h($bj)."'".nonce().($Qb?" defer":"")."></script>\n";}function
nonce(){return' nonce="'.get_nonce().'"';}function
input_hidden($C,$Y=""){return"<input type='hidden' name='".h($C)."' value='".h($Y)."'>\n";}function
input_token(){return
input_hidden("token",get_token());}function
target_blank(){return' target="_blank" rel="noreferrer noopener"';}function
h($Q){return
str_replace("\0","&#0;",htmlspecialchars($Q,ENT_QUOTES,'utf-8'));}function
nl_br($Q){return
str_replace("\n","<br>",$Q);}function
checkbox($C,$Y,$Ya,$ue="",$Ef="",$cb="",$we=""){$J="<input type='checkbox' name='$C' value='".h($Y)."'".($Ya?" checked":"").($we?" aria-labelledby='$we'":"").">".($Ef?script("qsl('input').onclick = function () { $Ef };",""):"");return($ue!=""||$cb?"<label".($cb?" class='$cb'":"").">$J".h($ue)."</label>":$J);}function
optionlist($Jf,$rh=null,$fj=false){$J="";foreach($Jf
as$pe=>$W){$Kf=array($pe=>$W);if(is_array($W)){$J
.='<optgroup label="'.h($pe).'">';$Kf=$W;}foreach($Kf
as$x=>$X)$J
.='<option'.($fj||is_string($x)?' value="'.h($x).'"':'').($rh!==null&&($fj||is_string($x)?(string)$x:$X)===$rh?' selected':'').'>'.h($X);if(is_array($W))$J
.='</optgroup>';}return$J;}function
html_select($C,array$Jf,$Y="",$Df="",$we=""){static$ue=0;$ve="";if(!$we&&substr($Jf[""],0,1)=="("){$ue++;$we="label-$ue";$ve="<option value='' id='$we'>".h($Jf[""]);unset($Jf[""]);}return"<select name='".h($C)."'".($we?" aria-labelledby='$we'":"").">".$ve.optionlist($Jf,$Y)."</select>".($Df?script("qsl('select').onchange = function () { $Df };",""):"");}function
html_radios($C,array$Jf,$Y="",$vh=""){$J="";foreach($Jf
as$x=>$X)$J
.="<label><input type='radio' name='".h($C)."' value='".h($x)."'".($x==$Y?" checked":"").">".h($X)."</label>$vh";return$J;}function
confirm($Ve="",$sh="qsl('input')"){return
script("$sh.onclick = () => confirm('".($Ve?js_escape($Ve):'Are you sure?')."');","");}function
print_fieldset($t,$Be,$qj=false){echo"<fieldset><legend>","<a href='#fieldset-$t'>$Be</a>",script("qsl('a').onclick = partial(toggle, 'fieldset-$t');",""),"</legend>","<div id='fieldset-$t'".($qj?"":" class='hidden'").">\n";}function
bold($La,$cb=""){return($La?" class='active $cb'":($cb?" class='$cb'":""));}function
js_escape($Q){return
addcslashes($Q,"\r\n'\\/");}function
pagination($E,$Fb){return" ".($E==$Fb?$E+1:'<a href="'.h(remove_from_uri("page").($E?"&page=$E".($_GET["next"]?"&next=".urlencode($_GET["next"]):""):"")).'">'.($E+1)."</a>");}function
hidden_fields(array$Eg,array$Pd=array(),$yg=''){$J=false;foreach($Eg
as$x=>$X){if(!in_array($x,$Pd)){if(is_array($X))hidden_fields($X,array(),$x);else{$J=true;echo
input_hidden(($yg?$yg."[$x]":$x),$X);}}}return$J;}function
hidden_fields_get(){echo(sid()?input_hidden(session_name(),session_id()):''),(SERVER!==null?input_hidden(DRIVER,SERVER):""),input_hidden("username",$_GET["username"]);}function
enum_input($U,$ya,array$m,$Y,$tc=null){preg_match_all("~'((?:[^']|'')*)'~",$m["length"],$Le);$J=($tc!==null?"<label><input type='$U'$ya value='$tc'".((is_array($Y)?in_array($tc,$Y):$Y===$tc)?" checked":"")."><i>".'empty'."</i></label>":"");foreach($Le[1]as$s=>$X){$X=stripcslashes(str_replace("''","'",$X));$Ya=(is_array($Y)?in_array($X,$Y):$Y===$X);$J
.=" <label><input type='$U'$ya value='".h($X)."'".($Ya?' checked':'').'>'.h(adminer()->editVal($X,$m)).'</label>';}return$J;}function
input(array$m,$Y,$r,$Ba=false){$C=h(bracket_escape($m["field"]));echo"<td class='function'>";if(is_array($Y)&&!$r){$Y=json_encode($Y,128|64|256);$r="json";}$Xg=(JUSH=="mssql"&&$m["auto_increment"]);if($Xg&&!$_POST["save"])$r=null;$nd=(isset($_GET["select"])||$Xg?array("orig"=>'original'):array())+adminer()->editFunctions($m);$Zb=stripos($m["default"],"GENERATED ALWAYS AS ")===0?" disabled=''":"";$ya=" name='fields[$C]'$Zb".($Ba?" autofocus":"");$zc=driver()->enumLength($m);if($zc){$m["type"]="enum";$m["length"]=$zc;}echo
driver()->unconvertFunction($m)." ";$R=$_GET["edit"]?:$_GET["select"];if($m["type"]=="enum")echo
h($nd[""])."<td>".adminer()->editInput($R,$m,$ya,$Y);else{$_d=(in_array($r,$nd)||isset($nd[$r]));echo(count($nd)>1?"<select name='function[$C]'$Zb>".optionlist($nd,$r===null||$_d?$r:"")."</select>".on_help("event.target.value.replace(/^SQL\$/, '')",1).script("qsl('select').onchange = functionChange;",""):h(reset($nd))).'<td>';$be=adminer()->editInput($R,$m,$ya,$Y);if($be!="")echo$be;elseif(preg_match('~bool~',$m["type"]))echo"<input type='hidden'$ya value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$Y)?" checked='checked'":"")."$ya value='1'>";elseif($m["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$m["length"],$Le);foreach($Le[1]as$s=>$X){$X=stripcslashes(str_replace("''","'",$X));$Ya=in_array($X,explode(",",$Y),true);echo" <label><input type='checkbox' name='fields[$C][$s]' value='".h($X)."'".($Ya?' checked':'').">".h(adminer()->editVal($X,$m)).'</label>';}}elseif(preg_match('~blob|bytea|raw|file~',$m["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$C'>";elseif($r=="json"||preg_match('~^jsonb?$~',$m["type"]))echo"<textarea$ya cols='50' rows='12' class='jush-js'>".h($Y).'</textarea>';elseif(($qi=preg_match('~text|lob|memo~i',$m["type"]))||preg_match("~\n~",$Y)){if($qi&&JUSH!="sqlite")$ya
.=" cols='50' rows='12'";else{$L=min(12,substr_count($Y,"\n")+1);$ya
.=" cols='30' rows='$L'";}echo"<textarea$ya>".h($Y).'</textarea>';}else{$Qi=driver()->types();$Se=(!preg_match('~int~',$m["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$m["length"],$B)?((preg_match("~binary~",$m["type"])?2:1)*$B[1]+($B[3]?1:0)+($B[2]&&!$m["unsigned"]?1:0)):($Qi[$m["type"]]?$Qi[$m["type"]]+($m["unsigned"]?0:1):0));if(JUSH=='sql'&&min_version(5.6)&&preg_match('~time~',$m["type"]))$Se+=7;echo"<input".((!$_d||$r==="")&&preg_match('~(?<!o)int(?!er)~',$m["type"])&&!preg_match('~\[\]~',$m["full_type"])?" type='number'":"")." value='".h($Y)."'".($Se?" data-maxlength='$Se'":"").(preg_match('~char|binary~',$m["type"])&&$Se>20?" size='".($Se>99?60:40)."'":"")."$ya>";}echo
adminer()->editHint($R,$m,$Y);$Xc=0;foreach($nd
as$x=>$X){if($x===""||!$X)break;$Xc++;}if($Xc&&count($nd)>1)echo
script("qsl('td').oninput = partial(skipOriginal, $Xc);");}}function
process_input(array$m){if(stripos($m["default"],"GENERATED ALWAYS AS ")===0)return;$u=bracket_escape($m["field"]);$r=idx($_POST["function"],$u);$Y=$_POST["fields"][$u];if($m["type"]=="enum"||driver()->enumLength($m)){if($Y==-1)return
false;if($Y=="")return"NULL";}if($m["auto_increment"]&&$Y=="")return
null;if($r=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?idf_escape($m["field"]):false);if($r=="NULL")return"NULL";if($m["type"]=="set")$Y=implode(",",(array)$Y);if($r=="json"){$r="";$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}if(preg_match('~blob|bytea|raw|file~',$m["type"])&&ini_bool("file_uploads")){$Vc=get_file("fields-$u");if(!is_string($Vc))return
false;return
driver()->quoteBinary($Vc);}return
adminer()->processInput($m,$Y,$r);}function
search_tables(){$_GET["where"][0]["val"]=$_POST["query"];$uh="<ul>\n";foreach(table_status('',true)as$R=>$S){$C=adminer()->tableName($S);if(isset($S["Engine"])&&$C!=""&&(!$_POST["tables"]||in_array($R,$_POST["tables"]))){$I=connection()->query("SELECT".limit("1 FROM ".table($R)," WHERE ".implode(" AND ",adminer()->selectSearchProcess(fields($R),array())),1));if(!$I||$I->fetch_row()){$Ag="<a href='".h(ME."select=".urlencode($R)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$C</a>";echo"$uh<li>".($I?$Ag:"<p class='error'>$Ag: ".error())."\n";$uh="";}}}echo($uh?"<p class='message'>".'No tables.':"</ul>")."\n";}function
on_help($kb,$Eh=0){return
script("mixin(qsl('select, input'), {onmouseover: function (event) { helpMouseover.call(this, event, $kb, $Eh) }, onmouseout: helpMouseout});","");}function
edit_form($R,array$n,$K,$Zi,$l=''){$di=adminer()->tableName(table_status1($R,true));page_header(($Zi?'Edit':'Insert'),$l,array("select"=>array($R,$di)),$di);adminer()->editRowPrint($R,$n,$K,$Zi);if($K===false){echo"<p class='error'>".'No rows.'."\n";return;}echo"<form action='' method='post' enctype='multipart/form-data' id='form'>\n";if(!$n)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table class='layout'>".script("qsl('table').onkeydown = editingKeydown;");$Ba=!$_POST;foreach($n
as$C=>$m){echo"<tr><th>".adminer()->fieldName($m);$k=idx($_GET["set"],bracket_escape($C));if($k===null){$k=$m["default"];if($m["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$k,$Ug))$k=$Ug[1];if(JUSH=="sql"&&preg_match('~binary~',$m["type"]))$k=bin2hex($k);}$Y=($K!==null?($K[$C]!=""&&JUSH=="sql"&&preg_match("~enum|set~",$m["type"])&&is_array($K[$C])?implode(",",$K[$C]):(is_bool($K[$C])?+$K[$C]:$K[$C])):(!$Zi&&$m["auto_increment"]?"":(isset($_GET["select"])?false:$k)));if(!$_POST["save"]&&is_string($Y))$Y=adminer()->editVal($Y,$m);$r=($_POST["save"]?idx($_POST["function"],$C,""):($Zi&&preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(!$_POST&&!$Zi&&$Y==$m["default"]&&preg_match('~^[\w.]+\(~',$Y))$r="SQL";if(preg_match("~time~",$m["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$r="now";}if($m["type"]=="uuid"&&$Y=="uuid()"){$Y="";$r="uuid";}if($Ba!==false)$Ba=($m["auto_increment"]||$r=="now"||$r=="uuid"?null:true);input($m,$Y,$r,$Ba);if($Ba)$Ba=false;echo"\n";}if(!support("table")&&!fields($R))echo"<tr>"."<th><input name='field_keys[]'>".script("qsl('input').oninput = fieldChange;")."<td class='function'>".html_select("field_funs[]",adminer()->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>"."\n";echo"</table>\n";}echo"<p>\n";if($n){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"]))echo"<input type='submit' name='insert' value='".($Zi?'Save and continue edit':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n",($Zi?script("qsl('input').onclick = function () { return !ajaxForm(this.form, '".'Saving'."…', this); };"):"");}echo($Zi?"<input type='submit' name='delete' value='".'Delete'."'>".confirm()."\n":"");if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo
input_hidden("referer",(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"])),input_hidden("save",1),input_token(),"</form>\n";}function
shorten_utf8($Q,$y=80,$Xh=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$y).")($)?)u",$Q,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$y).")($)?)",$Q,$B);return
h($B[1]).$Xh.(isset($B[2])?"":"<i>…</i>");}function
icon($Ld,$C,$Kd,$wi){return"<button type='submit' name='$C' title='".h($wi)."' class='icon icon-$Ld'><span>$Kd</span></button>";}if(isset($_GET["file"])){if(substr(VERSION,-4)!='-dev'){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");}@ini_set("zlib.output_compression",1);if($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("h:M��h��g�б���\"P�i��m��cC���d<��f�a��:;NB�q�R;1Lf�9��u7%�d\\;3��A��`%�E�!���e9&���r4�M��A��v2�\r&:�Φs���0��*3�Má���-;�L�C@��i:dt3-8a�I\$ã��e���	��#9lT!Ѻ��>�e��\0�d��d�C��:6\\�c�A��rh��M4�k����Z|�O+��f�9��X�7h\"�Si����|�+9����ƣ���-4W�~�T:�zkH�b{���&�єt��:ܸ.K�v8#\",7!pp2��\0\\�� �\$�r7���ތ#��i\"�a�T�(L�2�#:\0Τ�x���XFǉ�d�&�jv����ڗ��@d�E�����!,9.+�`J�ahbDP<��|\"���Cp�>�ˑ+b2	L���{�F���Q�|����r��Kl�ɏ�_�t=���b�K|�������\r=�R�>����0��(���k�b�JU,PUumI.t�A-K��X�4�z�)MP��k���3e`�N>D#�9\\��(YT��@�h�L��1]ȴ��ͺNKն2\\73i/V��l��Y�җBA�/[J��ĘВ\r;'�2^텪�b�ۣ3�T=0H�8\r�+6��kf�C�]q��W)���²C��2`A���82�!��hm�вGD����2-C���Yc`�<�s��6�2���9�u���y��ҝMgy�=,CZO~^3���0�2�<��k0���wM�{d#`Zۀ����޺�����6�C%���=Rq���_+�-�K>��\n'G���A�\$����^�j><�gf�h�m�b*/\$\$l��ش�g)Aj�� w�#ᣃ�������TN��]�T���%Z���jJ����Cf4���zF�'�*� x�����ho&k\r��,��r�:>s(�LAs����5Ct���n�6��� ll\\9D��\\!��mv�\0�A{9`�.��סS�lg6���!.2��0�P�Ԡ�i\r\$7�w���;G��\$�0�CI�^�J\n�L�Pc��'�*Eh���b��;�p�B��(�x�:D�L�.j�9AC@�I3jf�5sI`X}��Ҕ#���7��T`d���jhH49S\nq��HJQ �H`,F��P�0\\{��m\r�~@20u!	\$�PoQ�4ǚ�\nZL�M��B��k)@�d��SL�pv���y�ЃB��^o���*�R�\"����#��rͥS;\r4�&G�I��T	�r���9=6���Q�T\0�\0��f#��=\$�������H6�P�Y�:�G\$�����0�9:a�3Hz;G�\r!hJ�n�7��	�oY������WLv�ې�i|���%�-��d\$�p�D�R2T\r�paU��n�5r��j�\$�r%D���)\$GڃBu��:��`�(l���SD)I	����9�*ꁗ\rt�2� �zI���g�[X�c,u�\rvJ5?����\"�:�^�.u�J�P�o\$t\$�18��\nnK��T%EZ,6��DH�V�󆪹i�&z��xpdr�x*�}ʐR�25+��ѓ�f�2�w��q�0X1�2dX�ߢ�̏W��ËV8f\"냐q(u��E��G�qM�#а�#K�3WA�v�Y���Þe�K]t��]E��j�=SX���@��Ӈ\r�Ә\$��9��ܬ0�P7\"D.匎<;��Nj�=�����^�mmڒG�68 �C�%v'�y��k/�^�5���3�@�.ڛ��k�a�*�D������:�7��C}��`�`�`)�7���|	�3� i�騽��4�\0.:�Q�L���؜ͨ��f�'�%�ݩM	���Y3�\0�##tP6�(�B�d����o�y�6�|�5��IH7�����z?�(��Ŗ\$�RWT�谦�:��(��`r϶i���s�=D\\��,kr�1��ٓ2�`��A�9��&n��~��Ҭ�6;�vp �M#�]����ɴ���A���iJ��.���t����Ys�OPwĸ�m��Z�����AU�ʷJ�N?����z�3\$P�qs�U9,�����#��5Pnb��uNѐ{N`�饙��۝�i��w\rb�&E�\\tg��b�a�1�+m�pw#�vl��U�����\0�e.��(�wb@����\\�w�(�)��E���;�Z�]/&���|>Q�\"c	<F�\r�7����ϵ\\�'�S�fe�\rR���Vlo/.��\n���F�o� �eh��e��jנ�T�sa4�2�2� �`o�\\�A?��]�IoB[�{7'��Q%�6�s7\$Ó�~%��u)�5i���0�#����\r�f���MX�N\\ɇ\0���,T���ETo\0�{o��R��r�^����C @Z.C,��c�'�J-�BL�\r�P�CD\"�b�^.\"�����h��\0�����\r\0���\n`�	 � ��n�o	����\r��\r��0�`���0��	��\rp� �	0�\n �F@�`� V\0�\n��\r\0�\n�j��\n@�\0�\r��\n��	 �\n@�@�\r\0�& �\n@� �@� ��z��Ƃ*��w�q0g�5�aPxG�����	�	��\n�\n�����\rp���\rp�\r��	��0�\r���`�\r@�@�� ���^\r �\0�\r�d@���3 ��1Q9�AB��<�t�1N?�S���v-�a��p��	p�P�\n����0������\0�@������Ѱ\r�^��\"i@�\n�� �6 �\0�	 p\n��\n��`� �qޒQD�B�M�d9�TUq�1���2�	�\n2�rR#��2+\r�/��#��@��\" �Q\r����\r����@�\n�h\n��〪���\0�`�	��@�!�;�Co�Uҋ2�����e Qk �p ���!P�3с!��r%���p�	 �,��`���\n�`\n@��ff ���`� �\n�@�	��F#�`p��# ���o���\\%Bl��?��M-jP��r��3/�3*Qlp�	p�\r`�=��\n\0_>�1�'�#\0�>\0�\r�������� �\n@��� f�0�'�@Ā�����\0�\rd�Fh�I\$�`���,����C�ȕPݔT�>�7\0]E̣ʉDG���AC�\\BMDԈ�fmd��(\r�OG�F��iDN��ɜn2�4tΔwFt��F����H�CԈu+���\$K�6蔓E��.AKԏL*1JK>�����M��H��\"GN�Pj�E�>� �H&5H��LM#E�P�c��c8��l����,���C�N�Pt�@V t�\n���ݴ�I	kG�H	�)D(�JPl�1jn�lԍ��J�~�*&�n\\���HUfLk�K��r�F���<|HNx\\� Nl�N���I��\0rzMt�U|Z��ĸ����Ԙ�\r�HC�΀B\"�@�b�cn�A����J9Ort�A4�\r��@h��A^`�^�V0�^!L�jU����.�^\r\"���ka���lp��d� ���}\0��N�����IGP���U�YtyMPr��Y��E�ԥx����6`�`jg���1SB�܂�����X�8�V?Id[I��Q.�����`��i��̲vԞ��U)Ԝ���\n2PV�~�� �����\"\"&��r]-� �p*�\0f\"�Kj`�q\nJ�\"q��F�.��\"@r��(�`��3q>\"��f��\r\$ح�� �R1��h&H�`Z�V	u+Moʬ�\n3J�\r Đ�2I� �D'�!S0W0J?y�pjZ.�\n\r ��pw�\"�-+�zr!`�|v2\nl�f(�m�<��=�F\r�Q}��~7��\r��#�o�3��}���x<�~��W���iE�ã��[�8\n�bjj�\r��: ���)v��'{��V�q\no{���)C����߂�i���\r�%�遀���C�(��k�k������4ؐd�� ������jXLN�(A�}xe���|��w���G��xh��X�x�\r��%K���޼oq�x�������8s�4e���xρ��n�*4F�c�8~�h�Ip]��{���%�( �Ӓ��<�V����C�B��{w����O��ƣ��}�Q�8�[���[�{�cT%�&��o�����:�*b�E�`�m�I�YW�k�8��Yo������u�)����Y5o�9��ަ���ى��<�8(�?�\0[s�@*8���}��ߟ9g�\rӟ������\0���\n'w±x)݌٩�����92�Z1��@[I�+��_��5�7=��D�q�z!}�K��Nd��3�\0��q�+��C����Y_�g�8��y����ډ�K�4�{��S�8�2Z��z��X\0Ϩz���S���ڱ��e� �\r�>�:������Ǭ�_�ZÏ��e�������:�����u���{�U�M���a�����B��zɈ���b2YS�WJ(wOwӁwm��تZN�l��˧C��9����8BD��6���Zy�x{���;!�[m�����{}��)��#�4�[���(�b���ɘ���Ն�u��������,O�\"F�7�y?�9���nd�}�����{ݍs�{���e�ʦ>\"�cc����d���cs�{��vdCN�[���GM�C瓭�DE@");}elseif($_GET["file"]=="dark.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("h:M��h��g���h0�LЁ�d91�S!��	�F�!��v}0��f�a��	G2�Na�'3I��d�K%�	��m(\r&�X��o;NB���y>2S�*�^#��Q���1=���J��W^��L�����o����c!��f��6�m�a���l4&1Lf�9��u7VDc3�n82IΆ��,:5���r�P��1��m�>�5��W/��Fc���Dh2�L�\rN����Wo&�hk��e2ٌ��b12Ƽ~0� ��D}N�\0�f4�M�C�����n=��p��Zش�N�~�;���-C ��%�z�99�P���\"����;�\0f��8�9�p�:�m�8��@\nX�:��r�3#����C�[�Cx�#��I2\\�\"��p����]#��5R�r6�#�L7���!H�\$�\$IRd�'ˉ8]	��x��+�>�C�@-��;���b�<�2Ô�N4,�����-Mr�6Ic�X4�a���5KE�Th@1@���R�K�9\r�(�9�#8�G�CpwID5�2�l\"_'��UB��U�9c@�G=C\n��S��0���j��7PU�����9J]�<׋\nƲσz�?B��2�����4\r/�P\r�M[X��F�_��j���H�bnC&�¡f%@cC^.2�8�׎C�}^��sw�L��/�5O�M��ڳ	*X�?�b�.I�g��&�aq�݊>���FN�-�`�y��4�s���j\\&:�Sa�P;����H�����X��ޯ���d�kt?.���,ZO��@@8�Z3�c�\"��ß\n�=A�H1\\�Z�^/k���΃L�uC\\�c�)0O��M��lpr��7�\r��q�����WRa�������c@���wm�k/�8�*?�̐�4�5�\\m���k��>�d1n��UQ#�����w�憟�Lo&hĪPrnR,5����z�\"\$3��dYH(p\r�ALAC�)pT�Pl�!\"L��8��R�&�\0�����Z౒0P8������J	�`��e�0�	����1��	�D��Js�H���)�k� ��[���C�y�pjx,\rA���m!���<h1�");}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("':�̢���i1��1��	4������Q6a&��:OAI��e:NF�D|�!���Cy��m2��\"���r<�̱���/C�#����:DbqSe�J�˦Cܺ\n\n��ǱS\rZ��H\$RAܞS+XKvtd�g:��6��EvXŞ�j��mҩej�2�M�����B��&ʮ�L�C�3���Q0�L��-x�\n��D���yNa�Pn:�����s��͐�(�cL��/���(�5{���Qy4��g-�����i4ڃf��(��bU���k��o7�&�ä�*ACb����`.����\r����������\n��Ch�<\r)`�إ`�7�Cʒ���Z���X�<�Q�1X���@�0dp9EQ�f����F�\r��!���(h��)��\np'#Č��H�(i*�r��&<#��7K��~�# ��A:N6�����l�,�\r��JP�3�!@�2>Cr���h�N��]�(a0M3�2��6��U��E2'!<��#3R�<�����X���CH�7�#n�+��a\$!��2��P�0�.�wd�r:Y����E��!]�<��j��@�\\�pl�_\r�Z���ғ�TͩZ�s�3\"�~9���j��P�)Q�YbݕD�Yc��`��z�c��Ѩ��'�#t�BOh�*2��<ŒO�fg-Z����#��8a�^��+r2b��\\��~0�������W����n��p!#�`��Z��6�1�2��@�ky��9\r��B3�pޅ�6��<�!p�G�9�n�o�6s��#F�3���bA��6�9���Z�#��6��%?�s��\"��|؂�)�b�Jc\r����N�s��ih8����ݟ�:�;��H�ތ�u�I5�@�1��A�PaH^\$H�v��@ÛL~���b9�'�����S?P�-���0�C�\nR�m�4���ȓ:���Ը�2��4��h(k\njI��6\"�EY�#��W�r�\r��G8�@t���Xԓ��BS\nc0�k�C I\rʰ<u`A!�)��2��C�\0=��� ���P�1�ӢK!�!��p�Is�,6�d���i1+����k���<��^�	�\n��20�Fԉ_\$�)f\0��C8E^��/3W!א)�u�*���&\$�2�Y\n�]��Ek�DV�\$�J���xTse!�RY� R��`=L���ޫ\nl_.!�V!�\r\nH�k��\$א`{1	|�����i<jRrPTG|��w�4b�\r���4d�,�E��6���<�h[N�q@Oi�>'ѩ\r����;�]#��}�0�ASI�Jd�A/Q����⸵�@t\r�UG��_G�<��<y-I�z򄤝�\"�P��B\0������q`��vA��a̡J�R�ʮ)��JB.�T��L��y����Cpp�\0(7�cYY�a��M��1�em4�c��r��S)o����p�C!I���Sb�0m��(d�EH����߳�X���/���P���y�X��85��\$+�֖���gd�����y��ϝ�J��� �lE��ur�,dCX�}e������m�]��2�̽�(-z����Z��;I��\\�) ,�\n�>�)����\rVS\njx*w`ⴷSFi��d��,���Z�JFM}Њ ��\\Z�P��`�z�Z�E]�d��ɟO�cmԁ]� ������%�\"w4��\n\$��zV�SQD�:�6���G�wM��S0B�-s��)�Z�cǁ2��δA;��n�Wz/A�Zh�G~�c�c%�[�D�&l�FR�77|�I���3��g0�L���a��c�0RJ�2��%���F� S� �L�^� tr���t����ʩ;��.喚Ł�>����[�a�N���^�(!g�@1����N�z�<b�ݖ�����O,��Cu��D�tj޹I;)�݀�\nn�c��Ȃ�W<s�	�\0�hN�P�9��{ue��ut뵕������3��=��g�����J����WQ�0���w9p-���	�������'5��\nO��e)M�)_k�z\0V�����;j�l��\n����x�Pf�-�`C�.@&]#\0ڶp�y͖ƛ�t�d�� ��b}�	G1�m�ru���*�_�xD�3�q��B�sQ��u��s%�\n�5s�ut���{�s�y���N��4�,J{4@��\0��P���^��=��l���`�e~F١h3o�\"��q�R<iUT�[Q��U��M�6�T. ��0'�pe\\�����5����pCe	ٕ�\"*�M	����D���?�h��2���zU�@7�C�4�a��iE!f�\$�B��<�9o*\$��lH�\$ �@����P\rN�Y�n<\$�	�Q�=�F&��*@]\0��� W'd� z\$��j�P[��\$���0#&��_�`+�B)�w�v%	����LcJ��RS��i`�Ů	�F�W	��\nBP\n�\r\0}	瑩0�Z���/`j\$�: �8ie���φx�����a ���Gn�sgO��U%VU��@�N��ϐ�d+�(oJ�@X���zM'F٣�WhV�I^٢�1>�@�\"���� ��Q�R!�\\�`[������.�0fb�F;���Fp�p/t`����(��V���b�Ȳ�(��H�l����ԯ1v�����H��1T�3�q���1�Ѫf�\nT\$���Nq+��`ލv�ǜ�\r�Vm���r���'ϸ��g%�\"L�m����(�(CLz��\"h�X�m=�\\H\n0U�� f&M\$�g\$�U`a\rP�>`�#g��h��`�R4H��'�����GK;\"M�ۨT�h�BE�n\"b>���\r���#�\0�N:�#_	QQ1{	f:B���R�&���)J��Br�+�K.\$�Pq�-r�S%TIT&Q���{#2o(*P��5�`�1H���'	<T�d����s��,N�� ����^\r%�3��\r&��4�B�/\0�kLH\$�4d�>���/�ඵ�H���*���3J�А�<�Hh��p�'��O/&�2I.�x3V.�s5�e3�ێZ�(�9E�g�;R�;�J��Q�@��vgz@������'dZ&�,U���F��b*�D��H! �\r�;%�x'G#��͠w��#�֠�2;#�Bv�X��a�\nb�{4K�G��%���GuE`\\\rB\r\0�-mW\rM\"��#E�cFbF�nz���@4J��[\$��%2V��%�&T�V��d�4hemN�-;Eľ%E�E�r�<\"@�F�P�L �߭�4E����z`�u�7�N�4��\0�F:h�K�h/:�\"�M�Z��\r+P4\r?��S��O;B��0\$FCEp��M\"�%H4D�|��LN�FtE��g���5�=J\r\"��޼5��4�K�P\rbZ�\r\"pEQ'DwK�W0��g'�l\"h�QF�C,�Cc���IH�P�hF]5�& f�T��iSTUS�����[4�[u�Ne�\$o�K��O ��b\" 5�\0�D�)E�%\"�]��/���ЌJ�6U�d��`��a)V-0��DӔbM�)���������`��%�ELt��+��6C7j�d��:�V4ơ3� -�R\rG�IT��#�<4-CgCP{V�\$'����g��R@�'��S=%���F�k:��k��9����e]aO��G9�;��-6��8W��*�x\"U��YlB���������	��\n��p���l����Z�m\0�5����Oq̨��b�W1s@��K�-p���E�Spw\nGWoQ�qG}vp�w}q��q�\\�7�RZ�@��t��t�;pG}w׀/%\"L�E\0t�h�)�\r��J�\\W@�	�|D#S��ƃV��R�z�2���v�����	�}�����(�\0y<�X\r��x���q�<��Isk1S�-Q4Yq8�#��v���d.ֹS;q�!,'(���<.�J7H�\"��.����u�����#�Q�\re�r�Xv[�h\$�{-�Y���JBg��iM8��'�\nƘtDZ~/�b���8��\$��DbR�O�O��`O5S>����[�D�ꔸ����_3X�)��'��Jd\r�X����UD�U�X8�x�-旅�P�N`�	�\n�Z���@Ra48��:���\0�x���N�\\�0%��f��\\��>\"@^\0Zx�Z�\0ZaBr#�X��\r��{��˕�flFb\0[�ވ\0[�6���	��� �=��\n��WB��\$'�kG�(\$y�e9�(8�& h��Rܔ��o�ȼ Ǉ���Y��4��7_��d��9�'���������z\r���  ����v�G��O8���MOh'��X�S0�\0\0�	��9�s?���I�MY�8� 9����HO��,4	��xs��P�*G����c8��Qɠ��wB|�z	@�	���9c�K��QG�bFj�X��oS�\$��dFHĂP�@ѧ<嶴�,�}�m��r��\"�'k�`��c�x��e�C��C��:���:X� �T���^�d�Æqh��s���Lv�Ү0\r,4�\r_v�L�j�jM��b[  ��ls���Z�@�����;f��`2Yc�e�'�Mer��F\$�!��\n��	*0\r�AN�LP��jٓ����;ƣV�Q|(��3����[p��8���|�^\r�Bf/�D���Ҟ B��_�N5M�� \$�\naZЦ���~�Ule�rŧr��Z�aZ�����գs8R�G�Z��w���N�_Ʊ�Yϣ�m����]��;ƚL�����c������Ű��I�Q3��O��|�y*`� �5��4�;&v8�#�R�8+`X�bV�6�ƫi�3F��E���oc82�M�\"����G�Wb\rO�C�Vd�ӭ�w\\�ͯ*cSi�Qү��R`�d7}	���)�ϴ�,�+bd�۹�FN�3��L\\��eRn\$&\\r��+d��]O5kq,&\"D�CU6j�p���\\'�@o�~�5N=�|�&�!��B�w�H�yyz7��(Ǎ���b5(3փ_\0`z�b�Уr��8	�Z�v�8L˓�)��S�M<�*7\$��\rR�b���B%��ƴDs�z�R>[�Q����&Q������'\r�pp�z�/<��}L�#��Ε���Z��\"t��\n��.4�g�P��p�D�n�ʹN��F�d\0`^����\rnȂ׳#_�� w(�2�<7-��X޹\0��s��,^�hC,�!:�\rK��.��Ӣ�Ţ���\\��+v�Z��\0�Q9eʛ˞E�w?>�\$}��D#���c�0MV3�%Y���\r��tj5��7��{ŝ�Lz=�<��8I�M�����G����L�\$��2��{(�pe?u�,R�d*X�4�����\0\"@���}<.@��	��N��\$�XU�js�/��<>\"* �#\$����&CPI	��t������?� ��	�O��\\��_��Q5Y�H@���b��c�h����뱖��O0T�'�8�w�����j+H�v_#�����06�w֎�X��d+�ܓ\\��\n\0	\\�>s��A	PF�d8m'@�\nH�\0�c�OwS�����Y�`�����R��Dna\"��~�?�m���|@6��+�GxV��\0��W�Ӱ�nw���.�؃b��9Í��E�|E���\rЈr�\"��x���-���\rN6�n�\$Ҭ�-B�H�^�)��y&��ךW�ǧ�bv�R�	���N\0��n�	T��`8X��A\r:{O�@\" �!��\$K�qo��jY֪J�����h}d<1I�xd����TT4NeeC0䥿�:D�F�5L�*::H�jZ��F�R�MրnS\n>PO�[�\$V8;#�K\\'�B��R�د��R�_�8�j��*Ej�\\~v���v��p@T�X�\0002dE	�H�V���D�\"Q'EDJB~A��A�Il*'\n�Y��.�+�9��pg���/�\"�1�8�0�IA�FCȨ�V*a��P�d�У5H\"�A��6�s�Y��;訞�/��0��v}y�\r����ץ1�u\"ˋ�m��_�0焄`���\\B1^\nk\r]lh�}]HBW`��0�꨹rFf�)�W,�ҧ]sm9'O�xԽ�,�9J8��?�4�����\"҅�۽�<�-S����M�;�v��6y|�Z����%�a�#8��TC�!�p��\n��CZ(�w��a������?9|��0<BL\r�\n�]�PB0�&�+t�H���օ�Dx^��,�L�}[��B�x}��ru��\0��\0005��S@\"Uؔ@��\0�\$��ސ\"Ҡ��]l/	��I�B4��.�6���d7��\r@=���߬���*G j����f`��:Hn��bĀ71��)C<@A�Y#�����e�o��Y!��I�DM�\nlt����/)�\\43)��2��ɸ�)���f[ ppp1���#��Ð�p\0��œl��^{��A��TH�6�����\n\0P�H�.\r���|�T�FD0��S�y����'1���K���d�����B���C�&�)�W�s Hee+@4� r���ۚ*Lp1<�f�N�Y'�-	XKVa��L���\"���\"�l��q��.YJH�m HV�/�lC�&��H)o�&\\2���%���z\n^Q(6�D� ����Jq���\00a#�6\0vr,�M��&A�������9%Yd��B�h��!W\0�b\r{���@�1��I�22�A��)�H�a@r�0G��7Dd.�LM�<��2���,k/��Me����}Ғ3�=\0�&��B��\nPd.\"��F3X��Sd(*�J6 ���F:��)1�1�?lQ&����h<J͋�f�d�Eպ*�x\n\0��.\"B -�#��Ηt�IΫ���	I8 ��8dh	��x���~��	L!K(�BX��-��h��c/�r��P�I���N�2�|��׶��|\"�M�'��K,\\H��e5*o]4��FP	2��<)�T���o��\n���I�ڢ�!�(���_8Xr�;u�����NJ�����[r��DC:�@�ͳ�l�\0�e\\*x@Aȡ&�(�5��,����#1x� �!T�D���(Q���DJ|D D:\0�A�й� �baE�?rn��Wkx��X=i��,\$3�[�r�9B�Ʊ�d��\0��H��4���<(z���?�sIbJ�g U�\n(}���J\"��A��B�19�~�I�#�\$��%d  e\"�`���t���'O=���@\$��O�\nmT�o+�Z����-�����PF?�_�I�J�X ģ2���-V�;�?2���0�*P3����_T<E�J�\\(�2����)�IQ���鬩���R��L&��!ȯK�iц�t����K�HRl�ȬEs�������D��xǴ�i���!faB���F��e>�V����-Qj�I��7���\"%Rh� g��M������-�b�58R����*��9��ꊰ���9�2Q0���IR[�Z��N\0���20�����\\[@�Q\0��Jx�����EC{���\$lp1=\0�Rо�>E~�������:0���%��R+)\0�	ƑQ�@(\"�_j�T�X\0����\r1�\0P�9#\0����H;B�|���L�Z�����6�/B��\nB�{���|H�,�	*;��(�`�2@6�>�	�?P\0/���\0|\\�eB�`��jq�U/\rc�����҆�6(N\0�/\$�\n8�j*U�\$��y*�=�;���\$�f��8X�BCE��r\"/�����kځ%\\9k���B���0�F��(��'�U���Ʈm�@k�T\0��E��sEhy�e\n�)�)��b7��(W%,�J�r��2D�rhE��\n0Q�3� U�9TPO������8j|�}�R<0���Zl ��T�������*�\$��U\r�\"�.� Ts~�~(�3�a���@��+���l�`:�`�:O�i��BX�?ʄ��7��Lj|�:n�K:ز}�\0��UMc`P%nn\n,�4�Q'%+H.�\"#G��3`�����\n1fg\0�М'�k��qxD<\"��,a|{~���C<S�i�B�\nkN���G�}���k:��������g�)�JD���hÛf�\"�kV~��mM`HO�kD��^�0/tj�l�\r�!�f<��G��T���v�#@�ek@2�w���0�ܭt���į1�u�yvː%8�?1���l��xt��mp��fK3Z�J�=\0@�^p��ۑ����]Ҳ'�t١@C�b��\r[��V��-���o�-��ݠe�}��Y��	-�-m�I\0+��V�D�[B+��(�-�4�>�q��i>=��/0-�cL�pJ b\nd��)�#��G�s����\"�Q�N����`.�ȍ�yȐEtP�q�I]��J8���rWT��I���f�aG�.떄7y��l��A��7'�1�	�S�-�xI��m���L:e�ΉA�W��ζEI��Wz��3W���)*/)C���x*c]�%�}����_��IvͲ�'�\$U��S4k�5WʏJC���7*�b%<WC@�	����c{޴���3)X�&&��eL�I���,N� 2k#p5���f4���Ǻ�z�#��\\����N�b�U��oy���S�4�`q�~1�=�8厉�*�OOJ�C�����'Dd,@kL�������\\�j2ͩ����<�@_q�2�\0�ձ�)`�������s���F\0�����\n���F��<*�x*����`����-��\r���|@����7�H@w����H]��\0�����_w��h0!�s�1Ϗ��Ǭ�hW��.��=W��R*�A_���EDԷ�?1,Ub�9=t�4è��W��^���;����@��(1<D�ÊHx�T()0z�`�_�;��AL��)\n�K[f�H���Wo�@bBK�iM���d+�>�vI�(z:��.݀��9uiѤDY����O`���]I\0��R�Ć,K,���6L��\"\"�1g�(���|T.,�9vb+\rk]u�&�|��b�S��d[�,g��aJ�(C��k��\rF�+	��9��L��))UA�B�U�h�g��c3x�-n9�����x��2��q�ib�rY7�k�y�f�,������)�٪�J:�N�8�Rcly\n��2�W�;�.>�v6Q#A0��{έi��7~@VX���^��11-�+�v|��]Vf���.�{	���\r��;�1lp�/��uF��d�\$PЮ0=@kS�0h��Ɉ@��/*(O�V.��G>�(r��!�6�����Y=XZ@�:�'&0�6kE|���'|H;���N�g�%�W�+��4�;̓��'x|�f�9���(O��d���w%9]��f}��G���s���¾�����XM0����gQ���8̄�+O}�͝0}�9�������Nh�/mgD���s������\n�74勳P~}O)�Ug�9���j�8P��ݸ�(�%����j�7oAB��i)��K��u�� �}s�1�=od�V[Ĵ\n��zl�Mзr:F#{��*#�x��ܰ�<Ds��k/mw :^����1��ύD��2�z*��n��%�����i�Ù *�!8-��tH�'����\r�к�4����8`��\"�����i]�ZZ�>Z\0ަ9����+䟂~��\$ޭ��L�P\\쇁�XA�������i���z�h�\$�SM�T'���1���D��	��5E�\0Ğ\$�ttԮ��:\rMƷS��Ӗ�ls��Af�K�k,N�l�D^zz�dS��/rt�N�>��o%i��\0J�B�po��R����/֘٫x\ny�+��,e4��q5Q'JD�]�B@m����R�Ski~����t0�[ 1�z	���&��^�\nO����V����GV@T*�H9�ωG0\0'�`�Ѱ\r���bQKsLd�*;\n����.ĔUNp�,L�@TR�e��b��F���y�n> IK��rG�	@��?cI�ݓu%G�O�1���C�h�5T�y��I��:\\0��X��>�ʊ�0�޾�QB���EI/-LBT�!b��6���k`jp\0K���>k�d���/���ISk.+*���R�|gR���W\\w���t�.)�^Zc8�Z�~F��Sǵ�S�m̕;b>\0jz=�T'�>��q�y}:�u��&��W�DQ��c-����6<[��e�x�ؠ���[���L�\0wm�l�t�z��<S�&��db�x��oi�gK�\r`�µ�?D5u@b���N��O�𤷤���Y�[�����{�Nr鉞�t���\0��tMs�cBW?�*D�.p���'2��Ge\rp*#�e�����C���\"�QI\n��hi�Q�@���\rl	����_.���t*�^��s�9���Whq���~,��Yθ��dQs¦\r�Bj��D�ǡ��<<T)C�\n�����&�D{\r�l���-R��\r@rk��Ϣ��+Z���P������u8Ȩ����s�و���o�#��g��u\$F�&\n-v\"P����j�nnt�1��V������Awbx߄�D�5��-�0�a�\0\r�/!�I����|/����h��n�Gf-Mdna�^(e�a��¨�Y��Z,�S�E�N��\\�����=�4~Mʹ�\r����Ft�Ŧ�u\"|`��E��R�z��D�`�{��@�k/K�Y����3sJ�䃿5XGͪ�%�9)Q�� �Q���1t�h��!TR���H���Q�\r�C��E�0�#w�G2��/���/��=^ �/Ժ�ΐ����E��\0{+���t�+��q�б��I�t�|����v��q��Ԉƌ&�\r\\�Vߠ=���Eb��nO�rn��X({�ɹuzK��`=:�\n����\0����[�%�:p���q+��R�ldY��\"��[V�u{H-��H�_��8j��V��5����\"\0\"N?E;+�O~�wN�];L�'���SOF����䁻��D�-�!#sN�<��� ¯��mu����G�8���Tn]�����:�zIMn� O�8���z5���o\\5�7�<��Ų#8���?sN�L��	}�x��&4�?�[�z���󳷶����<*W������e}{HZ���,(<o�o�xW�t�2���#�A*�����o\\�R�}xH>NP�|Qɚ|x�'�-� ��2\0��?ƾ2*\r|]t��p�\"�ڲJuuXyb�D\n�Z|�H7�_�W���GuXyH>T\r�G����Ql�������n!�u'�*�C5��>U�2!b	��9Pw��4�����}y�W�|���a\$�g������T�U��&~9(\\*�!b_����w�7\\����]=�\\*���@�#N7ͪ��5QN`@<\0�6!�9��l��\$�wI\$4���2��\$�&���.RZ����Y��uyᤳ�p�&SI��@�EJiL�c���V�1F�1��Z\r\r���h��k���HH��˿�����K���?x��-0\n��d�N3K��C�59)ľ:B#���dN5A1�Ɖ����Od[3ڠ��h�[s~)�9�DN�y����>���X��'Ƚ�ϐH���,��)ڂ�\"�e�0;\0�qeo>��=�|�2�G+B�@z�������@]}��rQ��� k/�|�G�:ѯ�W\0�a4>��^|���g�o�XE�9p���Lrg�A��6��p�e����1�*����7��[�>]�#�?jB�~�/�}�3�:��U\$�?�<��G��a���\n>0#!i�>.{A}'hQ�Lw�~�W_��Th#d��û��d��FQ�����*{��\"�\"�P{���}�4�N���i��\r_����e?l4�2�?\n�F��	��q�U��Ľ�_��`_�����j��{_k_�o�~��c*#�(�/�!Dn�F�`��?@s�B�!�?;�E��������\0k�	�*N��D;���+d\nZZdB��� ��`B5�P\n8�������c#ou��k�ˊM�ݯw�.��F�J���!|�Ĉ2Fc�Y).����XHy�[��~����#/�&����[�����Y@���(|\r\0,O��0Yb��βŬ�\$0���aˑ����� �A\$��0,�@�Ӱ>>9��\\t�i�<�\0�q\0�}@`�\0fVj����dߠ'(����	!_�n��0+c���iig8a]'=-�B!(��8�_���x�j�����)\rH5H�Yn	,f�r��}-d\$��H��2n鴆ܛ�=�-�d���FE-d��a��N_z4@��[�n��\$x!!i0T����u�8�ɸ����\0PZ8Z����c����+Њ�AAF(����`mg*�vS, ǆ��KcA�۬ &��9������c�0w�+�n��=��)\$���Q�~A��a�\0004\0u�{�(��\$���y	!��B�� A<�a��Az ���ZA4\$ZY9.aX\r��d�A�L�v|oOz|�Z�(�e�Z�Ć�");}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("v0��F����==��FS	��_6MƳ���r:�E�CI��o:�C��Xc��\r�؄J(:=�E���a28�x�?�'�i�SANN���xs�NB��Vl0���S	��Ul�(D|҄��P��>�E�㩶yHch��-3Eb�� �b��pE�p�9.����~\n�?Kb�iw|�`��d.�x8EN��!��2��3���\r���Y���y6GFmY�8o7\n\r�0�<d4�E'�\n#�\r���.�C!�^t�(��bqH��.���s���2�N�q٤�9��#{�c�����3nӸ2��r�:<�+�9�CȨ���\n<�\r`��/b�\\���!�H�2SڙF#8Ј�I�78�K��*ں�!���鎑��+��:+���&�2|�:��9���:��N���pA/#�� �0D�\\�'�1����2�a@��+J�.�c,�����1��@^.B��ь�`OK=�`B��P�6����>(�eK%! ^!Ϭ�B��HS�s8^9�3�O1��.Xj+���M	#+�F�:�7�S�\$0�V(�FQ�\r!I��*�X�/̊���67=�۪X3݆؇���^��gf#W��g��8ߋ�h�7��E�k\r�ŹG�)��t�We4�V؝����&7�\0R��N!0�1W���y�CP��!��i|�gn��.\r�0�9�Aݸ���۶�^�8v�l\"�b�|�yHY�2�9�0�߅�.��:y���6�:�ؿ�n�\0Q�7��bk�<\0��湸�-�B�{��;�����W����&�/n�w��2A׵�����A�0yu)���kLƹtk�\0�;�d�=%m.��ŏc5�f���*�@4�� ���c�Ƹ܆|�\"맳�h�\\�f�P�N��q����s�f�~P��pHp\n~���>T_��QOQ�\$�V��S�pn1�ʚ��}=���L��Jeuc�����aA|;��ȓN��-��Z�@R��ͳ� �	��.��2�����`RE���^iP1&��ވ(���\$�C�Y�5�؃��axh@��=Ʋ�+>`��ע���\r!�b���r��2p�(=����!�es�X4G�Hhc �M�S.��|YjH��zB�SV��0�j�\nf\r�����D�o��%��\\1���MI`(�:�!�-�3=0������S���gW�e5��z�(h��d�r�ӫ�Ki�@Y.�����\$@�s�ѱEI&��Df�SR}��rڽ?�x\"�@ng����PI\\U��<�5X\"E0��t8��Y�=�`=��>�Q�4B�k���+p`�(8/N�qSK�r����i�O*[J��RJY�&u���7������#�>���Xû�?AP���CD�D���\$�����Y��<���X[�d�d��:��a\$�����Π��W�/ɂ�!+eYIw=9���i�;q\r\n���1��x�0]Q�<�zI9~W��9RD�KI6��L���C�z�\"0NW�WzH4��x�g�ת�x&�F�aӃ��\\�x��=�^ԓ���KH��x��ٓ0�EÝ҂ɚ�X�k,��R���~	��̛�Ny��Sz���6\0D	���؏�hs|.��=I�x}/�uN���'R���n'�|so8r��t����a�\0�5�P�֠dẘ��̕q����5(X�Hp|K�2`�]FU�~!��=� �|�,up�\\���C�o�T�e╙C�}*��f�#�shp��5����mZ�x��fn~v)DH4�e��v��V��by�T��̥,���<�y,̫֞�2���z^����K��2�xo	� ���2� I��a�h�~��c�ej�6��)�]����5�͍dG׊E�t�'N�=V��ɜ@����b^����p:k��1�StTԙ�F�F��`��`��{{���4��7�pcP�ط��V��9�ىLt�	M�����{�C�l��n47s�PL��!�9{l a�������!pG%��)�<��2*�<�9rV����)�|�A����Ip=�\n7d�>j�^6�\09�#�՗7�T�[���i:���X�D�'&8�/�����;�#�f�%��Kj3��;��Z�^�]��NQw�tȬ\$����ҹ���ǎ�-��;�L�X�+��P�̄�:�N���� \0ǲ�P��y�jt>��.[�<w�\"|��so-�;';�ǟ��t\r��t�	�I������T��\nL\n�)���(A�a�\" ���	�&�P��@O\n師0�(M&��b\0��@�@�\n`�=�������*̔��8�/��kH�F���\"�F�����B&�,�<����4b��eN�)�FEO��NSN��O��\r�.x��\"��k�D\r�� �0�p[�2RI0Z�������'���f�ix�P0d��|�h�O���mkH�Β��7���\nn�����eP\"�0x�P����02�n6�Wχ�N[�!����6ﰣ\r.u\rp��P��.(�mGt\rox���1!\n�\r�:��z+�lV�'���|?P�P��:�0�� �bT��au�x`��co}��O�1W���q8�l��\\��u��@���\$NePKq��g�A(�mc�L'`Bh\r-�!�b`���k �������`N�0�	���nN�`��D\0�@~����`K���]���|���ʾ�A#��i�Y�xf�\r�4 ,v�\0ދQ�ɠN��Ro���m��� 1�&Ǫ�p�r ��np�6%�%ly\rb�ʕ(�S)')@�ޯD�M�I�s {&�KH�@d�l�wf0��x��6��~3OP�h0\"��D�+�A�\$I�`b�\$��\$�R�L�� Q\"R�%���R�FV�Ny+F\n��	 �%fz���*�T���Mɾ�R�%@ڝ6\"�bN�5.r�\0�W���d��4�'l|9.#`���憀�أj6�Τ�v����vڥ�\rh\r�s7i�\"@�\\DŰi8#q8��	�\0ֶbL. �\rdTb@E �c2`P( B'�����0��/��|�3����R.So*���cA)4K�}�:S����\0O8�B@�@�CC@�A'B\0N=�;S�7S�;��D	��MW7s�ED�\rŨ�p��<�DȺ�9 �}4����_o.��rԉI\r�HQz�EsB��\0e�J�� ��KwHt�J4,^25h2�i%;�=���LL6}��7#w<�lrT�;tPl76�P�rJ�\n@���5\0P!`\\\r@�\"C�-\0RSH~F倵ņO�@ǭ����g���)F�*h�\0�p�COu6�ҎYO�Rg w9B�Ӛ��L\"䘵�_63gU5\r7,6\"��1����y��V�%VğWX��]O��J�	#XQGIXɰ��Sq�+�(��q�R�GH.l6[R�0\0�%H��C}Sr7��7�cYK���)�.�C��r�;�Ц)�M+�3�� ��4��|�Ϊ1�ZJ`׉5W��L��-Smx��H��dR*����JЦ\r���|52����-C-1R�R��T`N�e@'Ʀ*�*`�>���\0|��C!nE,�ag�.��b�f�Ý8ӓ_���a`G���p�`�m�6��Rz�\0���[-#mO�1H\rd�M�MNMqnM��nq����R6�m�On-t�v��æ\r�]`���-�`j���X�Mo�]`OU�AF����37�p�>'J'm�('M=j9jV�ZbBn�<�@�<���fe�:\0�K(��N���uN����-!��1vҍH(�Qg��µ���xC�<@� �c�[�c\\2o,5�˃q0m}�i~+��e�Ѷ��*�}��Ƞ�}��M��~���|�̘\r�� �@�\"hB�\$B�2�c\$g�\$�5b?�6!w��+~�l1����`��	s������	��.�v7m�Ec`Q�ecb6���`�\"&f�x�\"�2�E~Fz��\$�[/�0,w~`u��>w�%���X�\$دv�V�\"-�R����%W���D�@ʀVo�����E@�y���h���1��\"tЙ�O���/�������!�[����`:x}�@]��b� �@�����5�U(K�y���S������>8D͸����yw�=�|T,�'L��Y�����\\�L�͌��d���.����@�ђ���9<��`9E��Z�C�ײ\\h�=�qR�`GGW�X{��5�-L��RJ\$JP+�7X����ulӘh̵���Y�P�g����z����u�iwyL�y�����cY�7yF<�v\r��57�d�O�g�k�Yq�8�p�	���\n��*'�9\"�`���w[�G��HD�y�_]�c��iR�˖�o��w����9	�{��]�Oݍ8��C�67�:I�v�S����:_�U7��1�z��ڵyy���͹���M�0ͬ��c0�z��?��z�7}W�'����5��_eƸ��zm�l\nC�X_�(��Ý��{@�}�X`SgBլD��u��ñ�!�k~���Y�O�vK�\0�c�r�r�(�^`�n��;y�7�z+�{���W�:\$�M����\"I����%�om����Š	��,PK����9������ʅ���g\n�޸a~����x�%�~���W�ؽ����R��ٌ�݋X��x����%���z�SzթX?�y#}��L);�!��yߕ�s��������و����:��x�z+UԺ���|��1��u��HO�'��c����ϩ{���c�<g��/���V:퉠�Ɋ�<���\\3����e\0�Z��Tx�Zq\nl����_������I_�Z�����y���,ۙ]����9�ۚ͠,회�����k�:;�}-��������E\0S~2����\\	��UͺTV3�o�E�|�E��� C�m�Ϡ���I�=�\0�Н�z��kGٹ\0��ّ�9��1	�y�������=5ۛ���<Й��]G�Й��S��cՇ�!\r��DR�]�P'�������pLtǚ�H+`Ӿ�=�e9ڇ�Q{�9_b\$5��l�Uzy�n�z`xb�k�M	�3�� Z\r���q]�)ֽ{#�c���WI�\r��8�\r��3�䩽a���SI�'�^a�~e�D稟��>o�2 N���ސP>cΞ���������^G�����Y���͞��~x����^��Rg\\��\$+�ՍP�kY*4��~��,��Mݶ�W-�hhG�_Iԉv-��?iv��e>T\"\$��[Ը+,�)�K����u�q?KW�\rk�L%�}�tԻ�~�0��|Pk��՟T�=�?hE�n=Es�~�����xJH��K�Vuk�?X?��7�B)��ci��D����\rא>D'�,ʟ�>v�@�X�+\rr���@\r��U�X������ׯ�����Ӏ��1P>U,�3�G��>>tѥ}����\"=�D}<T������%�9���i�ʫ�o�1�e]���h�i�&]�|��*���l����1�D\r)XZRY�l��\"�E��/���8�ײ�*�ByK��4��5���Nrz\\�p�ӽ9�Yz�JH�S��>/�4C������&����sC��I�;Z,ۆb3���\rϖ��{�|v�D\nٟNpÁ^����Ay�0az�<��Ԝ��MPS0ڠ�jew=�Ooz�4��>h1��L%R�S�	���}�u82��𦴮9o��n�cM<�uƶ0\r�p�~�A�\nj�Q��3z��(�;�3E�a�]�eU��l40�,u����f��f��H0݆\$1����C�A�fi������嬇�>�Xc���ʂT\"��6pHg�D�H�?�\"p�l&�K/��?����`2l�m�*��TB�K\"��ϋ�ɠ���\$P\"o�eV�k�<��o��I�r�:�=�(�x2�����*Ȁ@=hCE��F6u+�,Z�Y��i����r�^lP�x,g��*�Ȗ��QE)1i�hJ��\"�IF���l�Y�|��T�f�V�}U��eo�	5Q?)���\0c��M�F�ʼ�ڑj�l��lP��m�4��\r�*�`⨸���LMiqb���V�)Q�W��R^a.>���g	�o�x��\n\0P`@��`\$�4鏍r��Z�#H��&�ncv)�lF�	 N�coX�F�9 g�����\"�9�'�4t�^�!�<	��#����0���#�h�G|\n�Bx\0����P�T0������6\0TD~#���<�Ԑ���@)H�b1�I�[h�Gj7��_��!0	�CX@���\0�\n1���@Z@\"ǎ8�N��\0��jH�\0�E����#�ۊi:�����i#�hD���s#�!��T�L��\$\$�)��B�׎�v�)����-?h���c�P	 Oto\$p�օB7����#�~��D#�-0dbDH�	�Rl�J?�\"�`\n�A?\0T,(�RM����p\$�\"�:I�SRz�t�\$��'I>�쓜���'QM262�P���R���	��\\�cxF�&�69�^��	�pN)OHj:Q��~�y\0�G�yz�Fc�Ĺ)s*<mQ�\n(2��2��8�zT�#~[� ��G?-��1�.P;�\\�&���5֍\0mT�Qn)p\$���\\�����v��Zr�K�(c\n�Yw����s/�gK�]nb��e�.�K��P�p:K�C�{6arؘl���1�)��/���04�KU���S@x���˜��Q����vT�̄�c.��ʚ�:�� 0�ķ�L1چT0b�h�ZL�]�A�q�VN�L�\nr�0�V#\0��Ta������2�`�`2,\r��-imMhSI�T�xC�,�L��.IOt��	7�_����T�rg�I�Kxq8gI��qM�cDŦA3	� �r`\\�U��L���Ӝ�'A9[�8PQ08���2�1� rBK`�P%�Tq	�C�F����_�h�Qu<�C\n��@�<y��,&C��v0%PO\"x�*��綔�2�&���X)=p.\0�{�(N'�y��.2�\$��>a���&��)��@\0�Ov����#X���\nNz�,�xbi��ꁦ����=i��ZyR����g�3M�V�s�\n\nq���\0�?�Ah�A;`�;m���(KAz\r��!���O\0EBJP�BޡXO\0�@�Q4�V0�kuX�tYK٫h�=n��{���@�f�=�6d)}�*���������� 9�5��F�B\0N�'\0ѵ�Ц���`)P˔�o#ڀ���F�>�#��B�jQ1�r�*)�/Rl	��qXzA�|Q�9�)H�	҃@=*��J�D���+�p��`�+d\n�E����؀���g�(\0�P,����̤��IX��t��m���k�6�4۔\r.��K�+���ߤ�m��Ip&���t�=:O�KQM\0�	@O�]<��M�zJLS@U�r�wJ����1ڧ�\n�\$�)ɨ	��@a�H�]����Bj.�\0�U ���\0��'Ǵ\0B�\0�+T�����WR�Y�4	 T9��O\nsS�9`N>�>��&�MF�Ԋ��'�4���� �\n���j@*�T���1p�f*����9��L��i@���5@��/�QR��Tv��U�eHjF��U��c��Y@�Vz����g槍[iN����UT�%\"��R\n�R��h��Z�XJ����56��EkUj�����e�1Y���F7�?��e#oJ�Vn��W\rV@QY���������>3�ۀ*�!�L�\0Lj\0I���)\nLd1P�!���Z)���]Z!8u�̥o���b ��(���TB�&��CV|�/%p��)`ȅ�\\�Ȯ�Hb\n�L�S��PGf�󇶹��J�s����Ԋ��AZ�L�R`<׌5�5]��Lw*:Ҹ@l`����'��f��q��EJ7Hގ/\n�m��\n܏o���\0Q`�9.���qѬF����V-!�\n��-3X��5U]��2\0����\r��!Ŋd)���Ʊ\rXlb�,�n�4q���dK1�d�!�w�Lof�rW-�Cvm�\0�p�	��צ����*ɖx==��yd[� V��0�(�e+;�:��7�͑	\"b�,u\0{��\n)��F<���\$�T�'R��V�`��2\\VJ��8��P����+`ɋ��;W�d4��FJJd�R��Φ�4�KL��԰d矵富l|���5.��L���\"U~�`N�mUm��Q��TzN�7�U:���\0��v��[J��\0(����S'*[k>��������T�8�=��AN���W����p�Ԋ����toL����Y�sX�숲��,�0���\0�\nq������V�L�����u�l�s���n\0]� �/i�ا1���� �c�<�K�u��֝z���fTŇm\$�+�[�1hXB��`���7c.kv��0��6�7j�؉5v�2]�F�D����dKu���e�exvQ����5B�[��2�\"��e�E#^Yo�t��l�i��7�j�t�]��[qM	dc��}ȭ�h��Ɓ%̷P�Q�_p�ۨ��qkj��޻�\\��ei��	��D8*д1��@�+��\n��7����B2@x�4�&�\"��W����<\n���me�����7�mKD;����G;C�f�봿	��d9+�Z@�w�]�.���Xw� }��n_u���Q���T�cԋxA5��(T��-X *f����\0��\"�n��*��<�W3��po,�p��+s��2\"��\"މ�P�M���r��Q +����A���82r���pFp�����@}�	�u��f�7�L�|�×-��m?j>S���p�E�z@>��`?\0\ncx.��!a��꾯a��Z+ʫ�%���M��Xw�0�z��DĊ����h�X����U��J��lM#|�S�`�Jck�~�EM^(e���ю��.yP�;�+�ᕕ���@ۈ)�Gbjōqh� �+%�zp=0\nmIƀ+\n�S�|Βc@��ƠpR�޸��H�6	\0w�����_\"�8�r>�aw\n��:��	\"\\q��J��L�w��O<|c�V\0x݆�@|*�W�k6p��OpL��m�[��FTA��\n�C�� ��R9]vDF�9�w��%���F}�<h��#���\0�N~����6'ȦJUV8v\0�F����\n��R����;�.N��3�\r�s/ �-����zx�p�[<�F]�.�:'����ѠFG*q�6�G1�Gܚ��3]�l@�dÜ��J�;Ș��\0007,Vc\r#�we�P�\n�qA�Z^V�zR�'t|k��qq������k�>L�ߑ���R9�g&fE\0R<�d�/�w��S�e����E7�c��Q�*ec2e��Hk2���c00¶o����;2!I�jp����?Y��93[�����(`e�(�Hw���3��\"J���=��*��W���<큝a��32@s�2�7 ��<Y[�&y�������˶^37g�6��� ��L|Ǭ�q��Ssݓ����>��#r���Q4Rh4�I���סb�g�69���4@w������.E3�����;/���q]1�WX���7q�Μ�3%O�Y�s��}�~���f�&Y��1��Αq�]�A�\"|+~-2(�,mcp�6s<�(�6���e�4��@�[mӽ���U,��=9�y ^��<�2�H��������x e#c��XI�W|�%�+i,�*��3�Q\$���K�\0�=��x�2נ�԰�P��7#ȳ��0VX��S�B@��l.%�\rܥ�Hҧu޼Z��7p޲@���������2=�]jn��ϴ�YQ��ZCƎ��Q�Ag�8d�^1<�\\ �g�Q[�~�PR�yH_Z�V�ۆ����H��ƾ�2U�\rAQ���wK:�\r��\r�t�qgB.�%�����G����*�c�+F������b�&-�G�qb\0\n���)�1\n�( r�0��Vx\0�(�M�Z�6X-��'f2�P^��ō��E�,&�b���u�°��C�8@��ͧl�8��ڐ\r�D�����-����;�k�ENӶ��\r��{-��W�a]��l�z&�vՊ{�H�Z�Q�����m�M{c&�@[�4���t��c!y�0:|����䶡�E�OtTr6���e�\"�x��|��}e�e�>cdn\"�2�CvR`:k>�X�֠��V&��\"��Jعs7q���OY���\"����&����Ş�D:V���L�[޺�Y��؝��B��m� �=��y[�\r�c�v�]�SZ���&X3v�Q��g{=`k���-��\nE�pJ٭�D�o~;9\\S'�p4��kG|Z�[v/��Ë`�����U������jh(W�\\@9�;\\)�xXC�\0����_\r��C�O~��ʹD��p��K<2h�v8�&�����@��ൔqL�<U�uFn*�\\���#���B����\\`*�=\r�V=B���e�`8�\"���:��X8�3�����|h�=��Nr�|i�W9\n3����b�0J�L3�B	)�\\s*�a��?r3��\n��rH,���u�zV@���4=#���|����aȔ���}��7�\0�i�&%��Q���l!�S>L׆[�X�!���>���=m�_�v\0D@�x�E\0�۾A �3�jDF��n������kw���渣�\0�W�Z��~��L���&L+D��#\0>@��>v�;�p�6\$+�?C��Q��<RC!�ɀ��M���0y�\$!���r�/d覫��1Wz{��:B�5�ctq��۶��蟾j��u�7���N\rݢ��i�N�����2��Ȯ������w]J�8��A@��tu4�4�@�}.�+�&��/�G[��#\\FV'z�Q���5{c��]I%�{�u�y�o^)lIf�o��P���GI��_9V���}����[���)h@O�qx��\0.���ż���y�ݐw�{|�asTE�.wO��޾��6-��p�8�^�v��#��V���\r�������7N�+��������>�K̾k�@W�X�����\\�d{Y\\��Oevh?�m�7tm�8���0�l�����ON}c>|��Z�V�Nꅷ�έcìv�@W��RIkt��}��:@dV :W��]h�[�ח͹�cr\n�)��!�t�iI��+���&�̑\"|�C������ox�(�p��fu���N����VR\n�\0x槎9��873ǣ��ʀV!��ȇ��B��2�l\r�G�6�Y+(u��5A��E��?���wv���������9��\\llw����X���x�����D��M>㼂���F���H?3������)Έ�	}�+[��or:����w?�����Ȯ)\0'D��������N��H�鸘����/�Sþ�@<3���x#�� yN���D.�?�2�m,p�\0O���&?��h���>\r��\\�	�w,=h�G'E���#�H�����_�߷ʤܡjz��\\��%pKέ&i|�Z���k}]����5��!�5�T�{�]�(�7�Or/��>�.&0��c�>�������>Z.9\0�>+@)���T� ���_O��5�!�t4��pk�\"忞�x�]�Z���t�\0<�@����c?/}�N�4A����p�C<�N�W�Z\05ь��)�^�H����N}o�P���\\p6�O�.#��o��!T!�\$K*n����Pqr����'��\0������V��NE�h�F���#��xM}~G��sP��E��*#�A�̄���(F���/�!���gM��o�g�ɩ��(yb�~��|3��a@���������'��������ޤ�����Ɵ�����k3y��'z�<���,��,��\${j�v�ļ)\n���q\0������z���A=�	[٠�=)s�Mm\0X����?������\"\"��\0�=�V��p���J��q���kދ뽶���o�x.c���6.�cmö<d�!7��B��\\ޓ���h��2�瓨�\r<���BH����E���&+.��P	�,��)0==�2-#'��=��3x��1��X����f󺌤�3��°@\$�菩w��J��3@�_<\092�!���,9r��3 ���]���R�̫A%����#�\\�2'?�v��bB��\nL�/!�@�s����%DA&\$�W�E	zH`�+��C�PP�%_�,��3��\0��s@����� ۛ�!�>`��s��64�)3B��ք�X@���Bha���{8 �	�gA�\r=���}�P�\"l�A@���T��A��A����z?\0@�͆/v�[hy��s�@��	@ۃ�2� @�A�(���?\0�5�bxx�n�&��ƍ7�Jk����y\$�	4������L#�B@��yEc�\\��Z�7�X�9�p<���#�9�t!�Ot��J0�M���+Y�k����LX:���y��.�[�@ئ�Z;���#�����>o8�H�`MP#3�\n8=�݅`�z{���\nRwLe�2(!	�'�	�\0��L�)���䜘�'m�okB� c��`ja��Ҟ�}P��,-��|�P�f-(5�I�&|0C�%�~)��F��3����,̡��������ԉJ8�����⁐�\r�\rX�⳦*�Lݛ�@.D�\$��\$6�/�f��مv\n�*`������s�\\�|�>�H����1�D-gg�jy�����Rp��\n/�Bt1�&�4;�}�1�ݐ�'��.���.��C���GjC�d0��C�2<>\"���1�4C 	��ɏ�2���C0�(\r*�0���(RJ\n\0d�(�0��;`;����>�u��b���\r)TǨI�ap�(�3\r�B1C�DC('aI�J�@Rq���|E��DaB�2Do2���()Xjp��yha%�Q�A1C�TJ�'��<�A��P�x�-�2��@\$����|J��D�N�M)视���JħTFc�5������c��\r�D�<.C#�G�*��3��ć\r.�#CN<[q8�3�\rI�����p�D�\$Q��D}�@��3�V0�B�n'��EP\0�Q�\r�UQBnU��%�R�Q�0��H0�D;\$4�O>�g�QC)�RqS�`0�S5<B��|O�Hŏ�\r� E_�Y�ME��DӨ%0`\0���F#[�\rp>hAxx5�9�p=��`7���+Z,xB�����\")0x�(bn?�+�],?&��S�����a@3�^���,	�A�%ACz'�a&�\\!�`�*�c\$���a8�C1�Z��ӌ`�@\"�dц�n��	F\0��:~��!a \0�x�(�q˰�r�(�i��F��L�R\0�������lf	��\rtl�F�5\0Cq�.{���,\0Oln����m����?ѽ��|i��F��m1�����+��X8|q����n����=�!�jF!�1�5�	��4��w��&�[�QրODa���e����Go�����tu04���g��B+�F�8\nQ��Wl7����)�SQ�G\$q��Ʊ�[\0/ƻ��l�}�qѺG!�k 7Ʒ��A�G�����G��jQ�G�L}q��\"�~Q�G�\$n��G������{�H�k�����	����������́q�H tk��Q �~��G�܃R���I�\rw��B�\$N4c���0-�\r�7���b{���xȡ	\0Z	RF�5��0\r2!FN\n�iaMGd�qCf�4�{��#y!TF#��_:Ӑ�2�5�>C\\c��WD�\"�F�D(�����x��7q�Ê\rk����x���7��#��_Df�jp�&Ɩ�1����]�����`)\0۲D�H\n�!���\0��`	�*�p\$����8RI�6	 ��(\0�?x\n*D�b�4��D�,!Q�ᅜ�:�d����`+2K ��a�\0�M��d��%��><5&\"@�\n��\0�H�0�%�Ɋ3��AR��\$| <������f����>� +PL5Ɲ&��@�\0����0ƻ'Km�o���k�>H\"l�j��8�K}�#�_ ��2?�|�cB~�X\ni6I�\n܏�=� �\"ɽ(i�r�&��3O��I �R�>��,\0�(�v��J@L�O҇�z\rl����)A2p�+���a �)Lm��ZL�������,��(��,�B�\$�A�\0���!�2D��0��Ir����+�z�l���8p�AR��&8̅�ȼX����?�]�^�r�e��%�Z�+Mc\$Ɋ|�J��������+(һ��T[Q £����_��l��E��^ \0��1\0�,+��\n\0�&�00�\nLƣ��1�hɒ����;���k\"��n-��\0;�T:�:��Z��;cKa[�R�\r CI1u%���ȕ�%�{)�\r<K�� <����%hr��D���K %�\0�(�h�譀��4Ҫƨ[��Q��(?BW�8,� �K�,�2@&\0���R�>����rm>�H�w#^2I\"m��V1Cr(�`�\\�̌:��F�e��T�H��hT=@64�/�K,q��\"���&X�+��1��ҝ����e����[٠>q0(C�\$�1\\m��:%���j\0�.�\0002\0�0���>.�1@++��\"�����2j@܅I23ٓ ��1�NL�\r`\n��ȼj\$��0H��7j���lҲ�/�U\"���@;�[��;� K2�`�&�3`�U҂��׌T�!��{(c���.`>�>ˢ�[%9+���m�!)H��%\0�J����4Pɒ���2dʯ����R�33t��H���ɇ�MJ����&�>ۈ���\r�*P`��.H\0�@ �I�+4hI��5Xe��6ԓ`�Y5T�@6MZA���ɤl�i: f���ϗ�x`�(�'��Nv�^g �6��3;�����.���e��0d'`��TԿ3/\nK7�x#�(),�!��7�!����7����M�\"4��݈�1��\$y6��%x��7���>N%8�߀)�,l�X�6���I8�z���@!,&\\���HQ�BL\"�\$��+��8\$��+A�8��s�NlӜ���vQ�ܓ���6�\nA\n7f<���c&̰�n@7�P�h�zNh2Hn��5:��o��;:ĿS~�0H6���l%x9s>�A%,t�>��\0005��BQ%oN�D�3�'�n3��\$��Pzm.��m8���� ��9����D�A�A�;8\r�ƀ�<\09g�.kd��^��ƂB	Pd��F�`@ڬ?+T�kF��0Ճ��ke02C��k��)S/aU68��Fkj��m`'��h[�-=��E�˘Ȥ�ֻ�)2���Xd���.��+�փn\n����¼�����\r��������65ۀ�(��ZR�[/|�2/c*̠S��a(d�8(����9m`*��0a(�&�>ܹ@�Mt���o����F<_��F��c4��K�D�R���4���\r�)���0�9�,��R��!�B�K�&�:0#�\$����+#��a���h\\��H,9\0[���30#\0��O2j�2����'Ψʐ D������+�B�t-l	��+9+�ӑ���D��3\0����A3{[҆]:X82Ă��%R�-�F��Sy�X8����	���&��-�	`�K��Z�k5���by͟&r��qD�저Ʉ�6�OM�x 3�C;;��يX'��U�/>��,<A>��,�g\$R���r�o�\0�Y� �c:T����\0��TcI5\$i�M��=�gAW%�G\09(�\n���M�	���Mp��W<�B���f�z%|٨���?4��Q�?M�6Q�C��!��v:�5�^��7��9��\"\0\r�}G�_�4hTܭ�M�	P2��-m�	^f� )�\nLn���\0�+Ь�:�*|�AR\0���'���\rd��j\0�������@t)d�\nJ����|@�@p��d�Pc��4����(!\$�`�f���5�ASMc���%|�ԞO-I�ٳ�M�I�١���M*�:�t��fҨ�\"�9R0+Ȯ`�ɜ�#ɯL	5P5t�K� O� Y;��SN���\0��@\\�kL�~D�3��oJD�t���3���Lu���\r\\�!�FTde�DF]�I`4�@0�H��tp�2��'H��E�����k�,����D!��ֆ�MB�nM�\$D���\n��4�M�A=bW�.la,��K����8�8�M��Qj݄/H���\0���a��7T�IM����~e84OS�?�8���NBÔ�S�N0?T�Ӝ)->�:S�N�=�!��O��0	�H�<�����8��TO�\"!�T\0�>�T!O�`u��+-@4��t�<��T\rP̑��P�=���D�u\0�P��K8��S�QD��7ɹ'��c��X\rF��K�9Gl�\n�Q�T��R9R\$��Ԉ�4 �`\r��.�R`\\�'0	RR��O/D���	P\nB��6�+310T��T�\nA0%��Hɕ7���g�24 ����D�[r5���R��%:� 2��j�h�GS\$gm����Q��\0���A�R ��T���FYArB�@w�H�@�����^\$�jy/�R�\0'�`l[��	VI�:�7)���,�Z���''BŠ��`��#B���X�\\KLF��BH�.\r��q��?0`QIm�\rx�^�t���M����kD�va��v����T�EѰpʔ�O�LHaP����4� ������Q�̙ѫ ~�I�_O�f�4�6�\n\0��\0� XE���B�CfTP��DC�\0@�bX	b�0d��D6k,40�(oUb�qY�6di��\nU<P(��<]T\$��?h\n`Ï��z8H��Y�\n�������:DD\0�(��b*\n�`\\��\$ ��\n` ���X	՟\0�8����=��\0�`x �<>��\r7�ʪ�E��A�1I+��`��:mc�����hR�P�gYDC��YdCQg'�\r��&hE:K�D*\0v����ȑ�\0�M�?H�(¬�bO��(}U���c��=YdU�VJ=\rqJ�GY�~�6y#�U���[q���B�չ�[��i���1ou��cY%pQ!E��x��_]�rQx��]AH5���,b�pD]uc ^��U���en\n���cu�W������X#�4�lu@��_��q�5}\\_5����~����_�R�%_��\0006X����rkdamX,��w�\r#�`�x(�\"��i�<�	!�Ѷ�:0�\r��<9\"`�'aU�T�\$eDX_a�,\0<Xia���g�Ya`��3��R\r�vXB+\r����Tm��X����h�i�Ya�n�XOb(���f�V*��axn������Xh0�\0��c@�\0;شhP7��b�x�=X�a�Pڃ�c���~�	c5�6:X��:3��he���������d�O��X^�<VLNUd��IT�0\0O�DX\0\0mv7� iQb�XiD�\rvD!>ZC��Z����Xh�y�(�RGTUY����zb�R4\$Ն�j��ܻZ>�dՔ6,K � �)5�Y�G�/�e,�Z�]ٮP�%C\"6�oG~-�\r�<��+s�·j��Y��e�@1Y�dM�!E@��r��\0Z\\��\0<Y�g͟v{!&e��1Y�ӈ:lY�cA�V5f՛�r�Ը���pY���6��GAh�vEWY�h�Z���5i0[Vm�GiIU�7Hl�\0[qٳi=��9ZUf�;ӥ�\0�Ŏ֐�qfŤ�u٨kc� Z,,��V�ZSf�hv���j�����K�C� �~+-�M�Z[gLvV�\$]j��֮��g=�6�7.n�7�<���r��+t��*X}bm���Z��Pv���c}���Z�k��5���+q8D��[j�!��Z8�a��i���Z��m�`�Gs@m�	�ٵk�a�Y&3�+�;�aa�#���G0\nE��[Oa|�A���!��a[`��V�T���A[r\nՎK��{m�9�N\$ݸv߀��6\0��n冖�۔iv�٧g\r�֔Z&}��Y��%��dڃl���Y�rM��;�.��v�Z=g���[@��!\rK�Qݽ6��̍�V�\$�o�(����j�>����p�aT�\roX�V���]�v�[��m�mp��6��RFZ��٭p\0\r�\\�)\0�P�\nȬր\\AqY6���h`��\\1q\r�@�C!�\r����0�o\0�80�P��;@B��\\:m�V��pxb+�yq�%W\\xm�͏�r\nL@:\\�i��r�pk�MG\$�l���2�m��`�Zk>�iW!\\�?%ʀ�ܭjT}RܿqlvW(�e���B��q���&�m��)[h���7��r�?K�[W�xw\\Wq�]��\0�XoI�1p-��8��hM�M�Y�EP��\\%o`�	��!m{� �_��v��_��YX%U��\r������>\r͛V�����w[X䣄 v2�UqxDH*��u�u���@,m�\$�\\I`�����,6E��d�t���0�搏��\\[v�طl.�vM���]�#�Џ�1mE�WdX/v���\n�!�ޖ���`��S�\0[db��s݅v%��w]�vsf��trG\0003��#�ڃ�]�g]��^����&��x�����x��W�;�h�d�Cv 6��3v����4�x�9���M��\"��Ә���7c䷘;�yՕw�\0�y��7�فy5�w�	%im���ޔ��閩ݤ����=H�B��^�z�:a=�ez�,׮��z���9�0�����	x%�ר^Ez����X�xCW�٩x-�W�Zuj�!j�E��X��{e����{�����t��ضV�����_{�	�\\|u���^7oU��i��'�{�*HXy��9!��\0;Xu����� >U�r\$��{�����1dh>���v#�`/��~��U}����{��J-\rQ_!�5�-9cH��hX�@l21��X܏���>�������O��\0�\0,P��f\n`\\�\"��*G��>�\nW�\0V�bVj�	]������\ne��\$�d�����%�\r���_��3r��:Pj���m�\0��0��	#�*�#���8��퀚�\0�ڎ����\r��`m�)�_�\n����Zx����H�,71�M��P\0��	�g��:�\\����?��C����@\"�쥘�+�&>��I\0�9i*�*DJe���(x�@�������xV��d@��zG��`ʦf\r_̀��@��6��ك�Q����.\r`��ۃ�*΅\$G��2X1��\r�`7ڵ��`�F\0\$�L?v`*��QW���Z*MC����ZF\0,a�5��\0��Q�,���x:`уVxC`�2`#�A�0�G���\\�A��?��!������`��@�\n`�`h)`Հ���7h?���M���4��.�T�E�\nx��@0x�r\n�)\\`}�X\"��6	\0�?=��I_��-��\r�O��@�����na`�Hj�\r��w�\0��M*����x	�(�.\n���vW�໅6!�0� �\nN��\n)*w��\n؏�C��Dx�˅�0�@��Zb:���EZ�k�^V�Z�MU����<C�V�������?��W�`����jF�����	I#���\0��ؕX�ⷁ�XT%��*b\0��x\"Ub���\r�࿉.Xe\0��Vj����\r�V���/x��J���J�b���ػ�o��,�b��8�b}�^(?�J�	2x���\rb�v@\0�Ώ�?��Rj`���>�j*���cA�XjD_�>�6x���kC��\"=��#j>�kC�ˋT��/K�?����?Ң8Х%Y�; %�?p��ꀧ��:��a.-\nH������S����c]�\0*���Цf3\0)co�x�x��y�V>��\0V� ���*�?���щ�?0\n��I�&4����& �7��]&AN�'�*ܙ�?6Kd*M��x-c׍�=��dA��?�`ŏ�@�\n�\r�����.?���A�ġ�,)8�X�#\0�� 	\n������p#j>��\0&е���a>*6�*�Ʒ\"�������@ ��\0X=�	�y\0��d���\r\$dǎ> �3��L��\$�c�\n�:�cY8��H���>A��d]gx�`œ�N��dk]#���L菻�:6�u�G}t\n��e��P��d���tzܨ�j *d�Z5i\n�ߋ>�����XɅˈ�99�F?�Ux,�7��G�T�H^9���偪�YU��J�ye_[&W�6\$!�9�A�m��W����4�=	��Wgb�FXarᅂ*O�+峇�4y<e��FA�e�^,XI�>���Te�Z=ko���6Q9]�j�X\n��e�?=��eߒ�Jyzd6J�(��A�6�쭑�>y|e���/��c�?��9�f#~a����c9|�7�����\0��`	ʃe�?~\0X%ޔZ�9Gf%�^Fc��?v)%�~9��Nd��0Տ�kIW>��e����?�9+�Ǝ��&�%��G��MX/�\n���[aÅ�6X��Y��E�����&@����j�J�̈́�7�%⟘��Fҏ	���n~y�*���0�j�R���e���\ny��%�h*�>�S�FW�b��\n� �⍃J�*e��\n�.-�>�kH��V��h���������g�J8XdC�h\n� ��X	��\0��Ar�7�e��V��\r�Yd���^�2��B�PV{�N1��d�\n�\"��'�?�U�m�ny�)g�\0h��>r����ی�*�¥���Y��s� kY�\$	�>n��➩J���BJ�����?v|��%g��0���V?WA�\n�g��z�-��X``ȑ�k@\"f	�R9xX��L��@*\0��0��d'��78�h<����%��X/\$�?n�7��C�F���6���Z�i\$n��bhO�(0�F��j��\0��Ҡ�ld��6z��`���?����*\n�VR#��2>�I��j�c�d���H�mes�x���H>�DU�倶�*ج��f�Ya#Ù�8Y�h樒�z7�~�v?8�\0��ރ�T%?&Au��ō.-�\0����\$�i	�Z�C����ΎG����L�\0\0��^�J�`�y�x��\$c����h�����L-�L���W�/���9�\$ ?�FYg[/p�#��\\��Pi&�\n��e�E� \n���>�s�##m��G�'���G���G�A诏Ɗ�\"�S�같�>�8�+���\0���I5঩n��)i.L���N����zL�ݧƞ�p�R��-�����^����J�JV�h��`�Ҥ}F��K)��*i��f�4b�z��f����\n�+\$����F*��&��i-Ü�Z���a2xh*[����/��`�ۣ�����ᔤL��i|���\"#k����b9����Jh�N\n�~����8	?g˝F'ؖ�7��\n�Y*��6K\n֭�(6_�rb*��	���IZ�Y��Z�ꚪf�v�y1j�#�H�!�f���b�NC�F*L�n�Cc��2AU�d���AX�b_]���b�]h����`�	\0��6R���ԍ�2Kraz'�<�rkK�vqz9�����Z�\r�^��M�B�`�ZA�y�,��Q����rRks��Q8ej�(69�e��y����.���8�C�K���/\0��?�kk�?�U8�l?��詉���������ʈ��8��/䫓���\n���\rsM�-EZ�J�e���?��fh��XL�7�楘e\$�\"�*�g]����th���X�h�Z���\0�����r-��VH8�Ǡh�,�ç�v)d��h���`��A>P��>�����\$�1	\n\$o7,� �Si9p:���1 �eb<ܡ�8L� �j\\�gT�-��fk�Pa@��xT����+eKұLIk��R����;0Ϙ\rS��Lȷ�K�\rɀ�ɬ]x*6�ճ������M!%Q>��[>l~n�I7�ϳ�!Cl�E\0T���td�!����UG�{R��\0ڷp*VV�}��҇\$Ʌ����<�2&���څaA\0��\nZ����s��Tl�\0<� ?d�����\n`+8����ɮ�D��㇀�Zg*j�f\"`�����]V�hkU���frj�a��>�:��{og����;m�1�^�\\#Ŋ�+ ��V�`�m�Z���mӷ�){{m۩.6�|�����{v_��mY�0���S���:���9�8U)��y&��� v���,��s,�@�\0�4-ɛ\"��^�M���n�+���-{��59ʓ��K�;�&@ҳJt\rD��>�oV���+\0�N�n���[*HD�\\�CP\"�Q(%�۪	)&	 /նf�O�\$ځW,�Q, ����O���P!ە�b1̘�φH���AF��*��t�\nMH��sb���Aj��)�����kM�R1F�D����K��:�\r^�ʔ��l�1��2�fItl��YIc+���)�U%�:�S�]%���1�g\$�5���%|��_>�#�S�#\$��2L�k&4�A���&A	 �!&L��I���`*�ɣ+��`��,�V�M�!}C�\\x�Ge�˾�\$;0�k�(��c��H��lۀ���ٿt�ԺJ;36�����Q����܉;v� 3l�1IE\r��\$%��	_�@�.��P�B��\\�8�=u<��P#ﵻ(?U\"�6=D��\"�Ny����]�0\rQɎ=�HD�`����c0,\0ݟ2�T��[0�����k��}�W���3�oӳ��\r?v̐_T��	�%�����{=�A��p/_���_�pJ|*%�\"�?3�o���\r\0002��/\0`>p����9�*}ǔ	�	����Z�Hk[���ˆ�\\+C��p�6\ri0�|�s�Je-zN)��-Y��=���9h�6������8n�Q��Z)^<T\0�F8��q���\\GY\r���J�@\r#��b��ᅊLK���7�)6����sqn�6�(�H�t �gA��G�(cg�A�`\n��T���u���_��\\�˼�u���+���#e��`��=�.��~⻭L8�;s����v^R\rɅ!�Xf��N��䪻�=��h�����6�\0�ȈO�]��-bB��?S �Tܲ!�߅%�an�Ir�`��%�]���ӹĘ��I��@:�gQ����ʊ�'1����86�R]���ؖ6O�x\$j�.Q�x|��6�/X��=r�t�kRz�����;�M����plW*ی�E |�AqO\n�5<��	#����8pF3��0Pg�m���+�]�%,UUq�8&��,g���,;o*h��7g��Q@47H�1Q��GvM�Z������.�;���qq�h3��R̶@9��(bB�0�帨C@��%�㞡Tp�)��IJa!��*�mU�7t�4����G����fLJ\r�HM�I��9)h�H�O9�^�}H�`ɳn��9��Sg�kW*�W�F�W*�� \\��2sǳ�(��sϱ�7|�Ʌ�/=�����>����i\$n�=��s�	_<79s�t)�y�W�G@�Jt]!����'A���=�_A�����/>@��1��B�r��з@��t9Ѕ!��W̨d�P�}���a�E��m=�E�R�O&51q�T= `_�Һ	WT��U\"�׽~�(��jN�\"E��#u���VBM��n>�;�Ҷ��B��]��o|��/��c�Z�q��[��t�һZ����������҇2=3r�Ƅ4,m!=�_��g�3�*��5��AO ����D�Q���,r#8����*��lf�E��R�7S��AI�!�A�J<f�2.��N�J@�B�gU{����A�\$��ԯT ��R)0�s��_v@\r|\0��xJ`9\0�2[ٔ�u�'PA&�M�{� ��	<R�]��+����w�Ֆ��E?q�}=�`1ك��\"�� 7^#�]�1F�5����sHmאF-9��׿P�t��g\\{�m�__}c�1�:\\��׷X����G^�U�!DaXv��}=� N�����ؒr���׿a�o���OW�0t�C�y'�^k�%-]=��1����\\�!��~!��J:O�O��yWd�5��Ӹ\"�U�\n7R=Lu'�WW&�;��t��~�S\0�b0凁J�Y�\0006m22G X@�U(�Zom .��l�-��x�nFU_\n�m��v�\"�l�cp�����+x�=5��f���eR뢰v��me���)^-9��BU����bI6lq��/Mj���1�K�����=��Y�[p�p�@�o��7�0i�S���V�T�v���a`8lh\n�l<�,�+�M}�U�7s��WI_\$V\\;��n��ܙ�y����M�q���E��q�&q�^=ՄK��P=eܙ�\n�`��	|�|�{�	k�Hv�#2�Z�ٞ7~�8�`�=���?l��u��haV	5��z&U^��n��YX\$q�R���q���M��jw��\rv���!�U�#'��Uva?q]ǀ�\"��1T���e�T�Gz\\�f#1�;u��fN�A6F7�W���t�-�����ce��1���O����gs���|�Q]�+�N}��sޣZ]U^�)7q�o84���c���}Ux�ޢ��6���j�O�W�o��UT|�mB��ǎ\r�|������E�x6'V��xU�\$t<��} \r�/I�,�l~,��z�����><�K�w�^Ls����U(��>�a.d��\r����m��heP���yn�>���d�\0003��^<�9�)�wm���wIX7\\��{&\0�\r�2C�\0T������<D�'q����2I���M��N��o������%)\"y�&4�66-1�U����8��,���5�B)m�����Qh�y�S�[K9�'��`�xGrZ�!M��Wm�8y��T�\\e�jq�y,9=]NO�3_q��M��\nr����k{t�w&�y*�f������3�qT57�Ɵ3���I�Um���/E���g)�݂I��64a��@62K(߯{���N�M�Q��G1=1]�^;���8h޲u�ۿ��TZ���\"�z4�þ(y���(y���|&\n5�:˫�u�O�bw?�9�7�R�|���,kk����~��[��\r�/�K.7e�cM�F���iu��`�U�9�?���Mqo�t���x��]K�y�����w��?�}u�'�\0��,	�C��Bp����=�ϘM�@��d|��Zq��ǹ��<�}P+�|	\$��w�4Ġ���43L��\$��bw_*�K�I�z?~�Iz�O�+�qk�x���{�����\n\rG�w�{�L���KU��~Iٱ�O`E��%�wR ��!�_�ʂu/KS��,�k}�@u}�1��	x}���M8�\r�uS���|o�4��e(�b%�n�\r���Z|9�4t���(�[R\n��\0�|d�HM�U|=n�Y2�=��|_�W���?q���6W�˗�\"���p�G�_\"���Aӷx%4_���q�3�����GE4��絪�o����^�gH��~L�����_6v2ݗ\0К���6}>u�� ơF�S٥�䉀��ϴv�}1�K�����iv8L���ɤ�����k�K!���X|�E�|��{ΟlѲ� ����5�U��^�WE;���&x�lú��D\nvR�\ry���]��9�ϲ=Ǝ��}���!�K����_ڍ�w�b<���v����~��|�D��u���}~����o���пt� J�>�ٿcv1�4T�Пq�#>��x���\"�z�\r��=]��Y|�W��mx�5�l}��+������ͥ}�\rwiq�i�7 0��|������{vw��ߓ~Aּ�a���&���XƂJ}����%���������x_0�t��w��L�4JS� ��f���Cu��g�\"�}�KO �wx������rt?����J\0�P+�t!�.;�F�'���}%f?X���]b���>ʱ6�Q�[��P�3������H�pg/߼��a����� 53Ƀ����ޠ{��oh;t��'ɟK{��?�=�pG��L���ҽ�12�����e��|`;����bLI�В?�w��Gl4M�=�����}#���4�I���7D��w�ݿv8\n�!,���X%����6_\\P��������Ք���c��������u��/�߿��Y�t��g(H� ��Z/g�{m�(%�+ӷ��h�&ͦ��#P%������w�	q�ôweF�S2��i����`���������ڈ���-�`;L���»�W�Et��?�\nJ,�/1�'��uS�<Jv��z;�g��� 	�k�`�a��7Yk5^;��_\0005bX������/^j�<�����Ψ�L;�V�h�gTI.���\n�6(h���P]��ruH��ɼh�U	r�� ��\"|@��>�(�@��\"!�0!�`�% ��R�a*Ϭ\0iEC�Er�EnH�Pɔ:V�Dh�I2x�\n���'܊n@���կ�c&�u\r�em(�i+�DX\$TH\n����0P��_#(#s�/��#!]b�O��ID'�Pt�y����\n4�m*�D^D�U�@��`\r\$���}D�Q}@��&\r%}b�x�T@:7�W�,�1R@w�]�@ ��P1�3����D\$0G�L�\rSk��l#\0_l'�e(��ì�X���z��>&T<�c|�\"p?�/�Y�N `�+E�łO�-jb��y�D6�[)Qxj������0]�qs\"�S�@Ġ�A]>�F��Ų0J@ݬ�%��hg�\$� ��o�.�x�Uͯo\0�A0\\��X����	 �H,�Z	��(+4W�]ol��qd%�i�3A>[4�\nK��\"K���H[\$��c�4��@遭�\\\r��Ӡn@�\0�\0�e�Qj\0k��7Y��8\$t�h\0Pԑbg\0;č�?�,>H��pY\"E�a��F�rx�Po)�\r�-2u���b0apSI�X�.��\0OP&S}ঠy9*J���ҋ���g/�z���*���*CV��_��~�6vm,����-�Ȁ?�`��\rl��17d��e�X�0�,�b�W������k+�ٸ��f���Z�8 m^[m4����ـ1j&Q�Z:�e��u�c8<lmX̶�iHH���)Mq�+W\$h��c�e��`����GѪ��/�\0Є۹��*�����C��R`�!�&5`U�>2�M��\"XBL���BcBR���:�P��~3Gi�[D�C�C�Bch ��Q8EL��?�dV���#fdn!08WE���H��Lڴ�edxј\\R�M0�52?eѽ�C!8J�Z7�Ph��X[:�=`@#�l���v\\%�M�z��1�e�I����2܌��.2�c�	aD!M�\n�\n�Ʉ�Ǻ�!�Np� �6��Z+[V���!�g��z{�����CB0)��3'�Nl�g'�&�B�(�>��21�&f�'6Pp��0j��Ր��,�X��-fc	f\$%���!04���&T+hM��!W2	_�A��\"N���1�dAu�'�DP�Xb���L�ّs?�<P�\0�\0R�\n�KZfD�a��dT�b��S0���1dU\n���HL��n�\rhJ%��~2�P�Zg*!d��ɒkG�/���r4�jVH�[U6R�ڝ�Ã����(Ƴ즙��l�����7&S�E�41�f�TM��ތ��5�a��\\�`��{B�b��h�������΅�Z3AZ0ɘc2��R�F���p[ 7݆x���E��>CCz���?v� �@-l`������Oo3�-��\$�<�zl�T��h̠��Jn���67h�\\�4����ۈ*�n.���������΀(g��U��@�{�&!6+n]%`�\\If[�(Az�\n���a��\0j�5ov�&\n������x�� *�L����ު8:f�I?j�2\$��(=&oD���+}F��-�]Oē����\$M����\n\0����;��9�I�w�Ay�&�`G[���ܙ�Cv4�'ђ�����s�uhv�D\$�bo(�y�\0g K�`�j��I���ƙ�|�\r��\\*����ĩ*�ۿ��oMc�R�!���U�c�<���`\r���=�sc�t��7���. @T�N'Ҵbl���@f\"�)=a�8Pp��q�a��O�3��[b���HW8�[��c�:\$	H�Ǐx�9*p/1Oh'#�ܱ)h��~������B��fp���C�)i`��(A}\0A	u��5I���FM\0'�J\n���vT�[(�!�\0J����\\���@*q8O��:s5<��ܤل\$�sl��}R��F����n�����\$GT�E\09<<q��\"�g����9:��l-K8��i��FB�\$k�U\$\\��#�\nS݄�B��H��mEe.�<a�. �&\\r6�}��\$��z�8)(�X���K��.�\\��OIrl�%˔4�O��F\r�i��\0�b`pr6�T��11\\����Ih��1����_��Uqx'�2\"l�}I����ѴNO\"p\r�p��!TDK��.QIm9�N����S���a��qo�\0Xe��%!��\\l-�uv���^��S:9I��e��h�N<[�/e~Υ�ҿ�JnkA>������\r'X	ʇmE������CK�yޗ*|�	����(��@��N\$�il�R��o�������)lSH��6^��,R�~\\SئqP��ኅ�(��.D��I��s�y���x!�S���Y�H^UWX�v���;���Pt�U�c\0�⮆c���*U���ZEe������\0��p���-6� #T�\"mT_;\rqܰ�+�WG�B7�;�\$BB�z(�b�8�X��,W�K�7��=D�%��Yo�8�;rI�V���Σ�	(iX�X�-\$Z��?\0�A8T��T��J���RQh�p�6P\\⤓f\r@��,=���mNo����y\". ��αÆ�ku���|=�g1t{�]K�J�]H�P�;�Ň�iճ�`s1Rb�:�MH��H{O�b�:�\\ؼQ�\$\\\$\n�v�ÿx�]~*4_�D0����^k�����n�E����LT�	1T�H�H��0ac���hC�G(߲0��!���	FY�I���x®��?	{�0�bH����!�>0��\\*��L��t�����,	 �G+��Ș*G^1��oFA{�aˌXȐ_��/���s��Ƞ]_V/P/.24dʏ�_\r9Ō�2%h-��Ϡ�>����2���1EU>�ZBh�2�d`+1���Flu|I\rg*������e��|�N���#5��|��3ѧ(ς��0F���6)���4�ʲ�E�>��TTPc��,�K\$���n�Y/��<q�R5�����'Qq����k��_��=����,gWh�;�[��\0'I'x*�&I5�T��{r��̛q��MS�?�M��6p��#:��wd���C�\0Zʮ\"��\"I���Dr�ĸѸ\n��I����K�/���{�DM��X�\0���( ��N�a(�i����\0���li�pО�?~#���}����^`��Wp��>�t�cM�\r�/�2Lj�.\0��7��*X���7����>7Z^v��dh D��÷�J��x[ïa�\$��v~�l:4gX(¨�C�GsH��0W/n���1��=3���`rԔtPN����n�:(p��[�PƱ��B=Lq`�����`�ã��Yq��=��W3�c@�\n���u6�0�/�@ہ4��  gИ�����ut��!����o��F�Ɏ��%�6��0��>^|�*�##i��K�c�Ǐ���.�`�N'�k\0���Ӡ�ρc�G�R̚.=+�Pi��~%�w�s�;�x���Q�5/�u|<�'�y���b��q &�1 &�x��'=ߏx��:H3�K�@_n8	\"�l|��n�����T�L���J�πe����pf%.�q\\Z;��D8��0\\�=�	\0�Q.0HB��U��G��M�u�����p��_ ���Ө�d��:�\$�.��*�v���G�ow W�h��A�fu���:N���u�\rV{�'�7��+�Ρ%��G��F\0ָ�Jy�9]8�/��p��u� �d�g�oM	�1� M�;���QR�H7~�	��<�Hnn�Џ��sv��uU�����>َ���2J��^/��E�`�`\rzk�^0��HPwܲRB+�x����]F�+�����f�+�8Pn� 1�u�h\0q���Y�]��>yKD��8'}�/�ۆ� y���\n���(S���1C�1�P�\$\n�>�����u�Lȋw��(!H��G��<Jc\0005n<���\0hSg\"������(�V>?\0�\"�E���}ĎUH����E�d7�\n^�_8et��:�To�#CC�I�#,j,|��bw�)�ۏo#J;�WK���j�/��#e����M�\0����Z\0��`+�C@��upa�2�o9��A�)z�����\$�u�kQmH�6��Qpx&��\"7��.��?\nT�l�?�j�x]���ɲҐL���%��Q%!�3����d��H���r1�g�	bd5����	��j���ƣ'��n�\$2E5`��Ed�?�����m	�'�\0�����S��n�|\nx�>T}&��d���b�^4\\h��ڤ��F����آMi)\0�����C\\���nP�W��|�ZK�@\r�Y�ͪ�7g#��c�`HP]L�hp>�@�#��%:x�����S}����Iw�`�]��]��\0��2�t���(���2��Gv~��s��γ�H���*�t�k�-#ݒL�y�I,.�ܑI�YP2�+��	��ݷBUorBsi��f]nI��t���z{i7�H\$ڽ�M�QܳҨ���dt&GRQ'!���Hj� 8+����#�v�-U�\"p&��[���oP/�\"�W	�/P\0		�+t&;n���B��1�Von	=�0'�I���P�NԞ�Rw@0��W�Q:�)p{�yUū�E����\n�u#��X������O���}2�T����(R�tdC�ђ��Fp��}@�(�Q�J#%(�}�,��K+�\"�t\"R�'���FR\0�d�4w�\$Mb���F�D�0,��)0�X�ҏ��X��Гt�X��9��J20,�E�r���d��)E��K�KW�A^�RQ	]d\$�R�B�J/G��\n��9L��P?Jq~E)���B���5# �\\Q\nQ)F1d��%:Jx�~P����O� �d\n�~�c��u��B6e0[}�[V��D��_ᄬ����*�S��ac�i���\r�T#�[�!y�<d��͑�F���Uж�kdV4�K9�0q��%�<VHKLJD��c�\ri�1I�J�w���+a��љ+	� l*eg1	hP�݈Q&!�,ؓ��c�+X?D��lB؆�ƅ:rV�����WJ�`���� �R�Kr4G8U-��V�������*���Ef�^�.��:	!�{#����24vi�\nY�A���;[X�hl���Cf���B¬i��M#.�\\�v4�d�з�Y&���X�29k�,��J�l�e�Wi�u��M���]�!)�Ӝ��4Ɵ�I�܇�h\$[A�CJ�9mC��-*����8}�KMEY��]j*Z��+%V�mH��BT���K=zd��`K�-��1[�6Ÿڸ��j����nI>���}ab�R֎�e��5�b�@?��O��Yc���ө�SK�:j�Y���a�+�V�R�:l&Y�J�h�ZͰqL&-�Tc�5LT��Ur���.ǥ7nB	d\\�r�+����JԤ|\$dHD�nW˯S`�+�K%	/��7R�66�>��wn@�=��7�F�[��,#.y���3ϱϣCopj�Ų�d��2@���o��6\ry\"�R\$���} w�ݘ��č���*E2��l�)�Ħo]�&�8��J�*F�j��\\�u�ʩ%I���'���f��O��>����c�iy�h��'iK��GJs���@F�Tv�8�S�\n��7,M&�q�gh�3\n\$�@NF������σ�D��N�c0� �=�Qq�▂��������r�T?}P�Jcu���D��zUܴ���#��xNX��š��_�?�neqP�aS�3&��\n4M�!�J:r��9]T������#qH��nۥY^���c�k��n�V�����F��I��,]��WM��D��'W�r#d�/v�&�2�vװiR��I}V!)�}D��&\0��*��N�����`:F<-��`��,C�\n4@�'(2.r�2��^�Ҕ8f�&�@�w�@�W���\\˔O�2J)��(�S�\$���|�>��.�q�W\0�@�9�%8<`M�0�M�\0b\0��p\$�}\09��\0p\0��9��7�\0003\0nC}l��S� \r@\0006��\0��π�:����3�gH��:�\0003��4�I�S=�~�	�3�h�ٞS;f|L�8r\0���� �xL�\0q3���ٜ�\r��6\0a3�h��� ���=4�g�ҙ�If���\0��ϙ��I��\0o4*g�g�sMf��3�4�i ���=�t�9�]4�g� B&�MD��3���9�:@L�4�p�9��=&�L��35|i��A���`��5Jk<�i��?f�M7�5zk��y�3\\�\0001\0m5�h����U�vM��5������G�\0005��5�i`	�7���'&�5 ��I��;\0\0005�	4~h4�Y�Nf�L�s3�k��I�D&�M6�q3�k��I� \r	\0c��\0�i,ׄ�m�M{�)5Fm�� I��L��4~h�i��l��M�\0b\0�k��	��F&��r��6fg��ɜ3?&��8�c3�j�ɧ�g����|\0�n\\��S=�\0006��5�k��9�s`���\0�\0�	\$��s`��M\0�7f���a�=��4�=4jjTЂ�Suf�\0004��4 ��	�\r����9�76�g<���{�\0004\0m6nl�I�Afw��\r7�i�ؙ��t&��P��6�n��٥SF&�M#�#76i��ɤ�E�wN28s8Fq����n'	��}5�n�I��Pf�M�)5o4։���t���5lL≤�\rfqM�\0a3�h�I�3f@M��=4�j�)��WgNl�e4��t��s����\"��3�s�ĳf&�N\0��4Rp y�3vf��\0c8Fp�މʓ@&t��O95*rT�٦SRgL���8�tT�	�SK@NN�G4�p��Y��C�)MӜ�3�jܩ��f�Ή�\r6�t��	��='L�q7l���iγgf�M���3�s4�)�3~&�NP��8:k,����}'ON)��4��t��syf�������r�)�S�&|M?�K96r��	�s�g4�0�u6�rD�	����M'�}6p��V���ٝ�9l�Y� �N��S9kD�	�󖦓\0000��3�s�ى��f�·�y4�x���f�M��!7bsT�i��Zf����8~ml�9�3���Mn��<k����ӫ&��[��;VkL��˓E�!���:L:y�y�S]NMԚv�\nw|�ٴS���j��7�o��ʓ�'��!7p��9�3A������<fp��٩ӯgbN��np�k��)�ӿ�����6&w�g��d&�Ε��9jgt��s��FND�35.{�ѹ���MF��6\"pt��ӭg\0�-�/:�|<�9���f�N���>h��	�N&��[��=�w<�魳�gwO�C=�k�y�SR&�M�y=&z��9�S�@���=^lD���Ӏ�?Nh�?>�q�y�3b&�͢�9h,�y�s��ϒ�!9~viÙ�sC��d��9�u�9�s�f����c=�v�������O=�q6�s��y�S�&����;^u��&��䚣9�lL����'�M\$��6fp4깱ӝ'|γ��6�iӉ�3L�p��W3�vl���s?g�΂��9�g����M}��:M���i��ͦ�ϰ�_4n~����e�_���4�o��9�Ӷ'�Ί��7�l|���s��γ��4Zjt홢�&�΃��9�k�	�\0003��	MC�K<6i������f��Ϡ�?�n�����B�nL���?�p,�Y��ˏ(PI�U7Zid���sͧmNܛ�=�mtω����5L�s3���㙳���O�7=�n�������=N��6�j�ٹ��������:T����DçJ��]7i<�	ǳ�&�N~<�?\n������g	No�?�i��S^g�Nw��=g��Yϳ�f�O<��>h�䩰��~P��@�w��i���&|M��3�y�߉�T&�M\$��@BvT��Փw&�Nӛ�B\"x4��Sg��P7��5�o�\r3:'��ڠA��\\�I�3C�����B���	��5'PA\0�>j}m�3M���ڙ�4����\0������4�{��y���h\$MH��5�90�f��ٚ�;�h,�ZS�h}M+��6fi\r��T=&���WC�<�{9�T'g�\$��L:\$�Iۓ�f�N�Bz�9!0S��M��#;~x�։�s�hQ\n��C.sU��K(3L훪7jn����@���M�8�p����T�m�K�]4�	\$��41h7�g�16Ru�y�3�fv�^�_<*�5�\$��4N���9jg���S���͒�eC�n\\��\"�c����=9�s��S�P�a3�i\$��ӳF�>п�D��ڹ�t�P!�E@&i�	�T`f�х�5���**���HN˚?=2s׹�3��O���>Jn��i���M�5>rj4��z��x��3��M���i'�Mb��:h=\0I�44���/��;d	,��+����N��F�����%t-��о��5�|=��Te(�No��3�\n�=6�2�!�h��؏�����ܿ}�	m��!��3ʃ�ϸ+6�8,��W5Ug,���+�v�Xз\n�G��Sa2�~��1��\0�45�C��B��ͷ#Cf�ԆAr�G`�+\"��A&�L���h��!�eJ��p\0:�_+�ݣ\$:Fp���Z�T\ndl7{��@!�f�H����0�CdTCH��\$\0�`X9�\$I	)��F�Ԗ�z�\r)�Ie���\n`a(˅��m�{�L��i33�j:�Q�[9�2����r���`\nE#x94��;0#j�Iؑ���q�����n�/ղ�(p#�D�\n����~�1�UN\\�emI�0��F0ci?\n��Й\$���v�r�#G��3k����4W8�D?]*�S��A�1�j[��!�C�e���jH�Z�of|@!x����9��I*X�;ayR��	e��7v^`֡z���*�\"bH`0��j��ܐ�P��p�h��0 g��Ε�o�C2ϩr�O��JJ�+\$6xll[vZ��\rh�́=�%�g�H�s������K\$�Ƒh��<�\0~�\"�-/��،<bK-���J�i�38\0W-�,0F���u�U\0��L�\\:CT�Y�1�e����&~��\06�`� �c��Et��3�f1��K�l͛��l������Ny+^b(����VZK���/����I��*R�L��J�S*b(�8��/VrDs�]�cn�dL�0��XSkhtRҚ�5�Tۊ��p`j�ʐ�7M������Hh?vxt�X �c��H�36*Wt�ٺA�\0Vʆ�;����ٷ�?`�*�������g\"�HA�bPs��6<�x�]��D�U\0�J�{1�?p5�RM\0��^�<�p0��)\r=�3�D\n�+��]Z)��u�z��xҮ�r3�f���>���!����9OXC4�},���d�ݜT)�2-��w�S,���\\yY��X�]�M	̍�B�M�ai0&��\n*���`	��i�S��MdL�7*}m���0B�xӸ��?�K�i����H���@6Z�	�IaMO��-XT��x�P��\r��%Mj	B\r��PbeBf?M)8��WD�5\"\n���W��bL��B�?K�Rڨ\$�J��C�V��)�S�gy+\n���G�f��B��Pe��<�[B\0��B�(ӌ�K=�9�(�Yf��E����'��a��\n�Z�u���g�Q���C�����6bo���o6��TN��Q꟤\"�Ж����O��(:�����,�\$�)��n֕\"�b)m���y*�P�jM�o�7\n\"��\"�Ou����`U��J��m*;�\\��5G�K#�C��]O%��':��)�S{�Bd��=���T��f����#ꖰ�ZTl��Q���/�D��*6)�a���;	�K��BZ�J���2�T�!j�ӆe\n�Z0��9���Fڥ��B�)\"Ӆchӵ��N�[U)�Ҟ���ޠ�j��o�\n�a�T��I�8U�\0��D&�%P(U�\n�}B�iR��hZ��j22 �UP�>�A�]U<�h�<iKTU�An\n�5A�W�J��G���M�Z5K�&Bj�QQt�UE6f�&��X�r��%S����ӳ��TJ�T,:�P��5�,��j�}T�oU<��2 Vx�e�}L�� )uUJ��Ȃ��9:�UV�����\n��MVzAU#	T����مEI:�P�jJ��� �%�R���R�R��V�W�[,���2(�YR���X*��F*WU�`�Z�]!���³)��V��K���;�6�T��Ȟ�&�L�J*bʩ��J��U��U3j��+��V���R��t�*��օ˒��Xڈ�������U�~�/j�4��%�\$cCS��B\n����2���Pʮc���������ѩ�&�����e�\0��5]���*���CP�WE]zD��j����V1��[��j���>Y�\r^:�t�!�@f�W���\n�0{k\0Bt�}K���SʽU��^��+R�]`j��O�{�`m�XF��!�@�Sa�P�5b��\\��,hj��v`\rQⰍVܕ�k�*��Ƣ��!���Y��i�U��uY:��j��]�oU֤�\n�A�\nY��QY��I�?0�*�B(�=XF�`Z�l�jT�P�WW¥}_:�5��{T���Xꐥ �j��!Ի��T6��`Z�������Y�| z�,���Ӵ�Vڬ��56�?��n \r8�D&��g�qC\0i.Β�!J���ؿ/�#f}�����@\$֒��W���Y����S�b�����\\�.��WVd%Xh�IV3��#���1��%`	lv�x�L��	��s&���kV��7����OO�RU%[�Ma���ZRUc��\\�%�N��Zb��f�H��!H��x�/��*�H��S��4���dZ��o\0\nm��]Q�h�U�m�ӌc��s[�Z���ޔ�	E3�\0�G��8*Nս�����[ҏ�\\��/� \0��F��I��\r��̶h5[���2�뉴�Y,R�>�cMe��0g�%Z+S��>����?\\��=m�Q5���b����cDz��S�q��s��u�*πB�@�F�5n6@�!қ�B���ej*��vj�,��]:����u�*\r�,b�+���Y���+�Е�]]�5v2>p��T#�P���%�du�I1d�NE��Vr����[�O��]���u��ݪW]��M��xeh�^�4g��Ĕm� U��v����ł��yL�����v� �ʱ��V��ڛ1Mi�^���2�\"5���R��hǮ�=-�iU�+�ײ#kZB�\r{hK��kؖ�f��ƽ�=��Llk�R�f�_��4&������.���<�y��k�TY��T�\$m`���OV�i_r�3]�iMz���M`�Na��]VX�ir���έ`��D���˘�������b6ũ����S�S2����LlJ�W�m��m��DJ^�Zv�\0�[��e|m���V��������b���Uh?,Q��9ڻ�����\"h> ��0V5���1ȰZ��SD���4�fK��2�4�ڛ1�R���9�\nu�)���g�N��}�2��_JAx�Vl���i��MH���=��X(��I�p��)��\$D�5�p}�2u0隀AbL֪�I��*�����\0�	��;bZ��\n\n��@����Z�8Q�AIY���O�ã�j�.�h6�ٷK�lE�9^�ň��̞�	X�\$�_r��r����X�JUكVYo4����\0�N}�*�zk�\0�\n�8\0�]&ăJa�kn�n�4�i�}�m��MU6�b�U������Ȥ�m�ʼ��D%D)�]�\r](�p����ٱ�^%� 5�DĊ+���ƛ�Iґ�X�Q���:��(����0�j�FU�\0*�TۙB�Q��H��&�p���3��b���v@��SWh4�b��7�X�*QW��](?�/����i�U��� ��k2���YkAdm��2�)���As.l��͒C�����Q�?c��)�)�w��ThId]%-9�DԧX�����a��U��OI\0V�hת��R��S�R+�|�劽�C�ؕ��eL�-9�*�,H+G*vݫYV',\\KS46b�e��cCk,4�)2V��c[Γ�����Ycc�_��ejzvT�XX�Qi�ײ��5z�,	S�h�\\����V�6Z��B;� �荪���52��ٔ��S*��	����p�˅�O�\0�*����Jc9N���\0I����:�e=5@l�d\0�d��8��0� ,݁E�v��=�kM��c���o2��9;+�\n��&��N��(�-��\"�(\n�ACr��c@ʪ�\0\\MH\$��3�g����|y]<��aA�+��Fm�1͞���� �\0c\0�\0����@\r�\n���B\0�	  ��C�P\0a(�h�ɐ��(��|��|-ՠɡ6���Hh\r�hH��@>�����\0mhjBU���V�-Z\0qh��U��@mZ�.�	%��t���@�yhV�M�{@ǃ�kZ8�]h��m��Cv�m Z��h��5F@��\$͒��h.�0{I��mZ'��h��5��J��-\$�1��h���d�֐�0P�WiN����d�����Ki��}�٬S��4;��5�Ө�N��-�d��i��]��I��Zy��hf�e��N���D���iJ�m�N���AZ%��ijԭ����-<���h��l�O���\$N�j��,�P��m��jf��Q��-P�#�X�p]�KU�'ژ�wj�k��V��=(µ;=\n���[S���&O�Gi��m�V��m#ZӴ<�}��M��mY�ڵ�jBֵ�ථ�hZ�Kj��-�+X6�f��˴�4��=�;^��t���j��\r�i�6�&�Zy��i����Y��-&Z���l�]��_�����h�m�;Y��mi[�#?��]�P�s�ml�\0�?�������-�Z��k��E�{\\��f��^��ln�m��Y����'l�t=�)Ֆέ��)�M5��%�)��έt[>�`yBٕ�i6ĭ\$M�mm6~E��c�ѭ��_�{l�����i6�-�;�l>���;i��g��O����͵I�v��_�m^�D�;h6ڀ��\\��l����e��m�[1��l��}��MV�m�[i��m��e�{_���pۄ��n2�-�y������=��i�,�p6�m��\\�3m���[Z��-	M���nfۅ�[s6��-�KlIi\r�U����۳Z/i*��I��m�[��umr�չv6���-L��i���u��m\r[÷\ro�e�;j����[˷�o.���i�-��J��oh��{Vͭ��T��ovލ��zv����ݷ�ov��0\r3J�AMY�o�ߍ�9�S_�,�c�o5��=�i�w\0M�Q�l�w]�r������p4��+�q.	�F�+ofݼ�;�s��\r�ʸ3o��t��v�n��3o��4Ы�w-�z�'3��}�v�:.�\$�[p��m��c��,�4������7��Ma��o>�tܫ�7.�:�3��\r��8�	�-��q�,Ӌ��-�͙��q2���s?n*�R�7����7.L��qr�4�{�7.3�b�4~�M�k���	�N��q��\\ԫ��.���q����k�3`�>�z�������6�-�JIp�j`K���(�\\��r>�PK}�&g�\\���o��ͬk��'-x��[<�\n���kg7*�:(¸mr����+�37�S\\��WnR���v��a[�h�i]�����c��rFg���3T�h\\��s6~-�K����H���rn��;��(n\\�&rF��͋��:�@\\ș�s�����''\\�Cp��U���6&�\\��as��U�[��?.y��s9pK�0>���~P蹅t -Чi`���q�l������<.�ܣ�-7���������q����AndM]�Cr���;��.l\\V�kt�g\r��O�H�^�kr�i%�ۣ�''A\\���u���+�S7�L�s4\n��˛���8�uN�]�k�w.nF]S�h����ǔ.��P�TyB�m	{�3n.��C(�yB���{u�E�~\\ں�r��\n���YnYܮ��u��]�婔?.EP�������{w`��M��v�5��fG.��̻%u��}�٣�'�]a��vjhŠ�=2��=Yʕ+bY�\$��lm*�Vh\$I�����\0����YXF��!�9j���0*aR�=|�	A�@\$�Md�YƱL�3����2�`�Ʋ�,3J�І�0��Se�]bڬ�<���c�X���dX��n����b�ڜ<�\0��	�_��w���`J�\\XVj��*Z��P��5V(��Y��g	M+I%�aRڬ�KZyL�k*�g�[��px7Ubjc2���GΣcGz���Yc3k��Q��m�Z����tT%hB�.��?��A��6��LN�\"Z�Ui\rBy�axمM��N`���%gI��G�U��0������+�wqo#0�e�wB�e�*�Wuk���p.��F�����r���O������v΅�ZlSOj��7X8�^\nFT��	��K�����Y�Ҩ�a���{�7�/\n�c��V��%o�R�~XeTɻ�S��)�tL���T�?+\r��)�T-[]�{\$GPZ�u��MW���ݩ�1zv�]>j�W�j�T4m�x���{�5ko�]�]z���Z�Wm���M�Kx���\"ǅ0j�^?�#T��U�Kɗq�HՂ��w�m�����,A��\\�z�E�B��V��W��[^��-�{�� Sl��n��X*��i��ټ��\n�,-�Q��j�U���V��Ԫr��L��B�;�]ZH]�*��ѫY��=z�t�e��ԆR\"�u�G�o�^�g����pz`�i�_�L�ûM*����^�k/Nu8[�ÉS��y���9�sT��1��ej�]\\=t��2S��O�J�ˉ+�.\r�t����H��o�����(K�?&�D�.%u����_���d��G��P&�d�ׁ��p��C�z��%�j`x̠�Z�%�ΰ/��=�����1D�7ה�onΡɸ|�4����_xs�\r\"���Hn�S������l���x�W����J��9�\"���QC�!����~'����W��z҅�s	C����@�7d����:S��	\\\n�_D�[\$y������bRH�MJX�``�1�]F���g��� j=I}��\\1�P0��C=߾�0Q�On���+�3&T���w�U�ե�:L��6�w[��uv�N�#��d\r�ֆ=����5��w�1s��<1P�}\n-�cx�����J�O�����+D��x����R���3�\0!���8�k�#�[@�UHߠS<��@(|7z/��7cr��ֲw�����o��V�c������	�\\���ۀ���&2;p\"���\$���:�&��\\���^=C���b�h������+�A��'l���`T�р\0Pn�\0�`cm1%���nـ��*|��_��lx�6R���n85��x�_���b=���#�5�ׄ�����civ|�0=<w6�8%��ӏ�և>���G&��K�[V����646�k�@֧�r3�{��Q�Wܝ`�ӂ~@���3�:\n�E��6q|����:�����\0aE\r�wT�4�T�20b`*0`2�~�\\���G���P<��e��H�`ډ�L�|l�=����0���&���5�oE���P��6����o	���\0L\0���o��\"�v��10`��L6(F��n�W��=9��\$���X7��T�q� (���0�.;\\��I<Q<�d~r��u��(n�d���o8�(d�%�ƿ��\\6+	�� !�J�G\n4��м\0��XN��;	IL9i�(���H���L�s�\0G�0�9:�^���\r�X��bv�����xky�/�bF���	�8Ya�XX�%e�Sc��d�baq���G�`��ZT�N��\n�}�X\\p��N�`f�|D'�0���l�갰���qɛ��u��IR��@���fa�a�u��\0�?�3a�1;��	�\r!>�3jNn�����k�W�C��Dp�~c\rN�����o���\"�ƹ2]��Iv;%�*V�N�̿Z����\r`�/5�\$��à�o��\\9�9A\n`�����{�:ˀv�@��\\:X�`ְ��F��ϲK,=�J	����\0�L��~T��uR�S�R�xT�EO/��V�(��0��f!���\n0�)6oDWŉ{)�P��8�ˇd��8V4t� ��9a|�XYсP�P:��L�����Eg��\"��@��L?�]h���g=lW�/��L��0\$h@܈�W�찼������\0s;zF�� C���M\0SLm�I%�%B��@2�I��8�H�'oQ���L\nh��i�#8��@�Z�UG�~�%�DR|����!���)P*�C��A�@\rj]��w��+�n*��@�ÐkIa��(ap܉���/�U��8");}elseif($_GET["file"]=="logo.png"){header("Content-Type: image/png");echo"�PNG\r\n\n\0\0\0\rIHDR\0\0\09\0\0\09\0\0\0~6��\0\0\0000PLTE\0\0\0���+NvYt�s���������������su�IJ����/.�������C��\0\0\0tRNS\0@��f\0\0\0	pHYs\0\0\0\0\0��\0\0�IDAT8�Ք�N�@��E��l϶��p6�G.\$=���>��	w5r}�z7�>��P�#\$��K�j�7��ݶ����?4m�����t&�~�3!0�0��^��Af0�\"��,��*��4���o�E���X(*Y��	6	�PcOW���܊m��r�0�~/��L�\rXj#�m���j�C�]G�m�\0�}���ߑu�A9�X�\n��8�V�Y�+�D#�iq�nKQ8J�1Q6��Y0�`��P�bQ�\\h�~>�:pSɀ������GE�Q=�I�{�*�3�2�7�\ne�L�B�~�/R(\$�)�� ��HQn�i�6J�	<��-.�w�ɪj�Vm���m�?S�H��v����Ʃ��\0��^�q��)���]��U�92�,;�Ǎ�'p���!X˃����L�D.�tæ��/w����R��	w�d��r2�Ƥ�4[=�E5�S+�c\0\0\0\0IEND�B`�";}exit;}if($_GET["script"]=="version"){$o=get_temp_dir()."/adminer.version";@unlink($o);$q=file_open_lock($o);if($q)file_write_unlock($q,serialize(array("signature"=>$_POST["signature"],"version"=>$_POST["version"])));exit;}if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";if($_SERVER["HTTP_X_FORWARDED_PREFIX"])$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];define('Adminer\HTTPS',($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure"));@ini_set("session.use_trans_sid",'0');if(!defined("SID")){session_cache_limiter("");session_name("adminer_sid");session_set_cookie_params(0,preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),"",HTTPS,true);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$Wc);if(function_exists("get_magic_quotes_runtime")&&get_magic_quotes_runtime())set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("precision",'15');function
lang($u,$sf=null){$ua=func_get_args();$ua[0]=$u;return
call_user_func_array('Adminer\lang_format',$ua);}function
lang_format($Gi,$sf=null){if(is_array($Gi)){$tg=($sf==1?0:1);$Gi=$Gi[$tg];}$Gi=str_replace("'",'’',$Gi);$ua=func_get_args();array_shift($ua);$id=str_replace("%d","%s",$Gi);if($id!=$Gi)$ua[0]=format_number($sf);return
vsprintf($id,$ua);}define('Adminer\LANG','en');abstract
class
SqlDb{static$ee;var$extension;var$flavor='';var$server_info;var$affected_rows=0;var$info='';var$errno=0;var$error='';protected$multi;abstract
function
attach($N,$V,$F);abstract
function
quote($Q);abstract
function
select_db($Kb);abstract
function
query($H,$Ri=false);function
multi_query($H){return$this->multi=$this->query($H);}function
store_result(){return$this->multi;}function
next_result(){return
false;}}if(extension_loaded('pdo')){abstract
class
PdoDb
extends
SqlDb{protected$pdo;function
dsn($jc,$V,$F,array$Jf=array()){$Jf[\PDO::ATTR_ERRMODE]=\PDO::ERRMODE_SILENT;$Jf[\PDO::ATTR_STATEMENT_CLASS]=array('Adminer\PdoResult');try{$this->pdo=new
\PDO($jc,$V,$F,$Jf);}catch(\Exception$Ec){return$Ec->getMessage();}$this->server_info=@$this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);return'';}function
quote($Q){return$this->pdo->quote($Q);}function
query($H,$Ri=false){$I=$this->pdo->query($H);$this->error="";if(!$I){list(,$this->errno,$this->error)=$this->pdo->errorInfo();if(!$this->error)$this->error='Unknown error.';return
false;}$this->store_result($I);return$I;}function
store_result($I=null){if(!$I){$I=$this->multi;if(!$I)return
false;}if($I->columnCount()){$I->num_rows=$I->rowCount();return$I;}$this->affected_rows=$I->rowCount();return
true;}function
next_result(){$I=$this->multi;if(!is_object($I))return
false;$I->_offset=0;return@$I->nextRowset();}}class
PdoResult
extends
\PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(\PDO::FETCH_ASSOC);}function
fetch_row(){return$this->fetch(\PDO::FETCH_NUM);}function
fetch_field(){$K=(object)$this->getColumnMeta($this->_offset++);$U=$K->pdo_type;$K->type=($U==\PDO::PARAM_INT?0:15);$K->charsetnr=($U==\PDO::PARAM_LOB||(isset($K->flags)&&in_array("blob",(array)$K->flags))?63:0);return$K;}function
seek($D){for($s=0;$s<$D;$s++)$this->fetch();}}}function
add_driver($t,$C){SqlDriver::$dc[$t]=$C;}function
get_driver($t){return
SqlDriver::$dc[$t];}abstract
class
SqlDriver{static$ee;static$dc=array();static$Mc=array();static$oe;protected$conn;protected$types=array();var$insertFunctions=array();var$editFunctions=array();var$unsigned=array();var$operators=array();var$functions=array();var$grouping=array();var$onActions="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";var$inout="IN|OUT|INOUT";var$enumLength="'(?:''|[^'\\\\]|\\\\.)*'";var$generated=array();static
function
connect($N,$V,$F){$f=new
Db;return($f->attach($N,$V,$F)?:$f);}function
__construct(Db$f){$this->conn=$f;}function
types(){return
call_user_func_array('array_merge',array_values($this->types));}function
structuredTypes(){return
array_map('array_keys',$this->types);}function
enumLength(array$m){}function
unconvertFunction(array$m){}function
select($R,array$M,array$Z,array$sd,array$Lf=array(),$z=1,$E=0,$Ag=false){$je=(count($sd)<count($M));$H=adminer()->selectQueryBuild($M,$Z,$sd,$Lf,$z,$E);if(!$H)$H="SELECT".limit(($_GET["page"]!="last"&&$z&&$sd&&$je&&JUSH=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$M)."\nFROM ".table($R),($Z?"\nWHERE ".implode(" AND ",$Z):"").($sd&&$je?"\nGROUP BY ".implode(", ",$sd):"").($Lf?"\nORDER BY ".implode(", ",$Lf):""),$z,($E?$z*$E:0),"\n");$Rh=microtime(true);$J=$this->conn->query($H);if($Ag)echo
adminer()->selectQuery($H,$Rh,!$J);return$J;}function
delete($R,$Jg,$z=0){$H="FROM ".table($R);return
queries("DELETE".($z?limit1($R,$H,$Jg):" $H$Jg"));}function
update($R,array$O,$Jg,$z=0,$vh="\n"){$kj=array();foreach($O
as$x=>$X)$kj[]="$x = $X";$H=table($R)." SET$vh".implode(",$vh",$kj);return
queries("UPDATE".($z?limit1($R,$H,$Jg,$vh):" $H$Jg"));}function
insert($R,array$O){return
queries("INSERT INTO ".table($R).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":" DEFAULT VALUES").$this->insertReturning($R));}function
insertReturning($R){return"";}function
insertUpdate($R,array$L,array$G){return
false;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}function
slowQuery($H,$ui){}function
convertSearch($u,array$X,array$m){return$u;}function
convertOperator($Ff){return$Ff;}function
value($X,array$m){return(method_exists($this->conn,'value')?$this->conn->value($X,$m):(is_resource($X)?stream_get_contents($X):$X));}function
quoteBinary($ih){return
q($ih);}function
warnings(){}function
tableHelp($C,$me=false){}function
hasCStyleEscapes(){return
false;}function
engines(){return
array();}function
supportsIndex(array$S){return!is_view($S);}function
checkConstraints($R){return
get_key_vals("SELECT c.CONSTRAINT_NAME, CHECK_CLAUSE
FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS c
JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t ON c.CONSTRAINT_SCHEMA = t.CONSTRAINT_SCHEMA AND c.CONSTRAINT_NAME = t.CONSTRAINT_NAME
WHERE c.CONSTRAINT_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
AND t.TABLE_NAME = ".q($R)."
AND CHECK_CLAUSE NOT LIKE '% IS NOT NULL'",$this->conn);}function
allFields(){$J=array();foreach(get_rows("SELECT TABLE_NAME AS tab, COLUMN_NAME AS field, IS_NULLABLE AS nullable, DATA_TYPE AS type, CHARACTER_MAXIMUM_LENGTH AS length".(JUSH=='sql'?", COLUMN_KEY = 'PRI' AS `primary`":"")."
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
ORDER BY TABLE_NAME, ORDINAL_POSITION",$this->conn)as$K){$K["null"]=($K["nullable"]=="YES");$J[$K["tab"]][]=$K;}return$J;}}add_driver("sqlite","SQLite");if(isset($_GET["sqlite"])){define('Adminer\DRIVER',"sqlite");if(class_exists("SQLite3")&&$_GET["ext"]!="pdo"){abstract
class
SqliteDb
extends
SqlDb{var$extension="SQLite3";private$link;function
attach($o,$V,$F){$this->link=new
\SQLite3($o);$nj=$this->link->version();$this->server_info=$nj["versionString"];return'';}function
query($H,$Ri=false){$I=@$this->link->query($H);$this->error="";if(!$I){$this->errno=$this->link->lastErrorCode();$this->error=$this->link->lastErrorMsg();return
false;}elseif($I->numColumns())return
new
Result($I);$this->affected_rows=$this->link->changes();return
true;}function
quote($Q){return(is_utf8($Q)?"'".$this->link->escapeString($Q)."'":"x'".first(unpack('H*',$Q))."'");}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($I){$this->result=$I;}function
fetch_assoc(){return$this->result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$d=$this->offset++;$U=$this->result->columnType($d);return(object)array("name"=>$this->result->columnName($d),"type"=>($U==SQLITE3_TEXT?15:0),"charsetnr"=>($U==SQLITE3_BLOB?63:0),);}function
__destruct(){$this->result->finalize();}}}elseif(extension_loaded("pdo_sqlite")){abstract
class
SqliteDb
extends
PdoDb{var$extension="PDO_SQLite";function
attach($o,$V,$F){$this->dsn(DRIVER.":$o","","");$this->query("PRAGMA foreign_keys = 1");$this->query("PRAGMA busy_timeout = 500");return'';}}}if(class_exists('Adminer\SqliteDb')){class
Db
extends
SqliteDb{function
attach($o,$V,$F){parent::attach($o,$V,$F);$this->query("PRAGMA foreign_keys = 1");$this->query("PRAGMA busy_timeout = 500");return'';}function
select_db($o){if(is_readable($o)&&$this->query("ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$o)?$o:dirname($_SERVER["SCRIPT_FILENAME"])."/$o")." AS a"))return!self::attach($o,'','');return
false;}}}class
Driver
extends
SqlDriver{static$Mc=array("SQLite3","PDO_SQLite");static$oe="sqlite";protected$types=array(array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0));var$insertFunctions=array();var$editFunctions=array("integer|real|numeric"=>"+/-","text"=>"||",);var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");var$functions=array("hex","length","lower","round","unixepoch","upper");var$grouping=array("avg","count","count distinct","group_concat","max","min","sum");static
function
connect($N,$V,$F){if($F!="")return'Database does not support password.';return
parent::connect(":memory:","","");}function
__construct(Db$f){parent::__construct($f);if(min_version(3.31,0,$f))$this->generated=array("STORED","VIRTUAL");}function
structuredTypes(){return
array_keys($this->types[0]);}function
insertUpdate($R,array$L,array$G){$kj=array();foreach($L
as$O)$kj[]="(".implode(", ",$O).")";return
queries("REPLACE INTO ".table($R)." (".implode(", ",array_keys(reset($L))).") VALUES\n".implode(",\n",$kj));}function
tableHelp($C,$me=false){if($C=="sqlite_sequence")return"fileformat2.html#seqtab";if($C=="sqlite_master")return"fileformat2.html#$C";}function
checkConstraints($R){preg_match_all('~ CHECK *(\( *(((?>[^()]*[^() ])|(?1))*) *\))~',get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$this->conn),$Le);return
array_combine($Le[2],$Le[2]);}function
allFields(){$J=array();foreach(tables_list()as$R=>$U){foreach(fields($R)as$m)$J[$R][]=$m;}return$J;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
get_databases($dd){return
array();}function
limit($H,$Z,$z,$D=0,$vh=" "){return" $H$Z".($z?$vh."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($R,$H,$Z,$vh="\n"){return(preg_match('~^INTO~',$H)||get_val("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($H,$Z,1,0,$vh):" $H WHERE rowid = (SELECT rowid FROM ".table($R).$Z.$vh."LIMIT 1)");}function
db_collation($j,$hb){return
get_val("PRAGMA encoding");}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name");}function
count_tables($i){return
array();}function
table_status($C=""){$J=array();foreach(get_rows("SELECT name AS Name, type AS Engine, 'rowid' AS Oid, '' AS Auto_increment FROM sqlite_master WHERE type IN ('table', 'view') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$K){$K["Rows"]=get_val("SELECT COUNT(*) FROM ".idf_escape($K["Name"]));$J[$K["Name"]]=$K;}foreach(get_rows("SELECT * FROM sqlite_sequence".($C!=""?" WHERE name = ".q($C):""),null,"")as$K)$J[$K["name"]]["Auto_increment"]=$K["seq"];return$J;}function
is_view($S){return$S["Engine"]=="view";}function
fk_support($S){return!get_val("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($R){$J=array();$G="";foreach(get_rows("PRAGMA table_".(min_version(3.31)?"x":"")."info(".table($R).")")as$K){$C=$K["name"];$U=strtolower($K["type"]);$k=$K["dflt_value"];$J[$C]=array("field"=>$C,"type"=>(preg_match('~int~i',$U)?"integer":(preg_match('~char|clob|text~i',$U)?"text":(preg_match('~blob~i',$U)?"blob":(preg_match('~real|floa|doub~i',$U)?"real":"numeric")))),"full_type"=>$U,"default"=>(preg_match("~^'(.*)'$~",$k,$B)?str_replace("''","'",$B[1]):($k=="NULL"?null:$k)),"null"=>!$K["notnull"],"privileges"=>array("select"=>1,"insert"=>1,"update"=>1,"where"=>1,"order"=>1),"primary"=>$K["pk"],);if($K["pk"]){if($G!="")$J[$G]["auto_increment"]=false;elseif(preg_match('~^integer$~i',$U))$J[$C]["auto_increment"]=true;$G=$C;}}$Lh=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R));$u='(("[^"]*+")+|[a-z0-9_]+)';preg_match_all('~'.$u.'\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i',$Lh,$Le,PREG_SET_ORDER);foreach($Le
as$B){$C=str_replace('""','"',preg_replace('~^"|"$~','',$B[1]));if($J[$C])$J[$C]["collation"]=trim($B[3],"'");}preg_match_all('~'.$u.'\s.*GENERATED ALWAYS AS \((.+)\) (STORED|VIRTUAL)~i',$Lh,$Le,PREG_SET_ORDER);foreach($Le
as$B){$C=str_replace('""','"',preg_replace('~^"|"$~','',$B[1]));$J[$C]["default"]=$B[3];$J[$C]["generated"]=strtoupper($B[4]);}return$J;}function
indexes($R,$g=null){$g=connection($g);$J=array();$Lh=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$g);if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$Lh,$B)){$J[""]=array("type"=>"PRIMARY","columns"=>array(),"lengths"=>array(),"descs"=>array());preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$B[1],$Le,PREG_SET_ORDER);foreach($Le
as$B){$J[""]["columns"][]=idf_unescape($B[2]).$B[4];$J[""]["descs"][]=(preg_match('~DESC~i',$B[5])?'1':null);}}if(!$J){foreach(fields($R)as$C=>$m){if($m["primary"])$J[""]=array("type"=>"PRIMARY","columns"=>array($C),"lengths"=>array(),"descs"=>array(null));}}$Ph=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($R),$g);foreach(get_rows("PRAGMA index_list(".table($R).")",$g)as$K){$C=$K["name"];$v=array("type"=>($K["unique"]?"UNIQUE":"INDEX"));$v["lengths"]=array();$v["descs"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($C).")",$g)as$hh){$v["columns"][]=$hh["name"];$v["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($C).' ON '.idf_escape($R),'~').' \((.*)\)$~i',$Ph[$C],$Ug)){preg_match_all('/("[^"]*+")+( DESC)?/',$Ug[2],$Le);foreach($Le[2]as$x=>$X){if($X)$v["descs"][$x]='1';}}if(!$J[""]||$v["type"]!="UNIQUE"||$v["columns"]!=$J[""]["columns"]||$v["descs"]!=$J[""]["descs"]||!preg_match("~^sqlite_~",$C))$J[$C]=$v;}return$J;}function
foreign_keys($R){$J=array();foreach(get_rows("PRAGMA foreign_key_list(".table($R).")")as$K){$p=&$J[$K["id"]];if(!$p)$p=$K;$p["source"][]=$K["from"];$p["target"][]=$K["to"];}return$J;}function
view($C){return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU','',get_val("SELECT sql FROM sqlite_master WHERE type = 'view' AND name = ".q($C))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($j){return
false;}function
error(){return
h(connection()->error);}function
check_sqlite_name($C){$Mc="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Mc)\$~",$C)){connection()->error=sprintf('Please use one of the extensions %s.',str_replace("|",", ",$Mc));return
false;}return
true;}function
create_database($j,$c){if(file_exists($j)){connection()->error='File exists.';return
false;}if(!check_sqlite_name($j))return
false;try{$_=new
Db();$_->attach($j,'','');}catch(\Exception$Ec){connection()->error=$Ec->getMessage();return
false;}$_->query('PRAGMA encoding = "UTF-8"');$_->query('CREATE TABLE adminer (i)');$_->query('DROP TABLE adminer');return
true;}function
drop_databases($i){connection()->attach(":memory:",'','');foreach($i
as$j){if(!@unlink($j)){connection()->error='File exists.';return
false;}}return
true;}function
rename_database($C,$c){if(!check_sqlite_name($C))return
false;connection()->attach(":memory:",'','');connection()->error='File exists.';return@rename(DB,$C);}function
auto_increment(){return" PRIMARY KEY AUTOINCREMENT";}function
alter_table($R,$C,$n,$fd,$mb,$uc,$c,$_a,$ig){$dj=($R==""||$fd);foreach($n
as$m){if($m[0]!=""||!$m[1]||$m[2]){$dj=true;break;}}$b=array();$Wf=array();foreach($n
as$m){if($m[1]){$b[]=($dj?$m[1]:"ADD ".implode($m[1]));if($m[0]!="")$Wf[$m[0]]=$m[1][0];}}if(!$dj){foreach($b
as$X){if(!queries("ALTER TABLE ".table($R)." $X"))return
false;}if($R!=$C&&!queries("ALTER TABLE ".table($R)." RENAME TO ".table($C)))return
false;}elseif(!recreate_table($R,$C,$b,$Wf,$fd,$_a))return
false;if($_a){queries("BEGIN");queries("UPDATE sqlite_sequence SET seq = $_a WHERE name = ".q($C));if(!connection()->affected_rows)queries("INSERT INTO sqlite_sequence (name, seq) VALUES (".q($C).", $_a)");queries("COMMIT");}return
true;}function
recreate_table($R,$C,array$n,array$Wf,array$fd,$_a="",$w=array(),$fc="",$ja=""){if($R!=""){if(!$n){foreach(fields($R)as$x=>$m){if($w)$m["auto_increment"]=0;$n[]=process_field($m,$m);$Wf[$x]=idf_escape($x);}}$_g=false;foreach($n
as$m){if($m[6])$_g=true;}$hc=array();foreach($w
as$x=>$X){if($X[2]=="DROP"){$hc[$X[1]]=true;unset($w[$x]);}}foreach(indexes($R)as$qe=>$v){$e=array();foreach($v["columns"]as$x=>$d){if(!$Wf[$d])continue
2;$e[]=$Wf[$d].($v["descs"][$x]?" DESC":"");}if(!$hc[$qe]){if($v["type"]!="PRIMARY"||!$_g)$w[]=array($v["type"],$qe,$e);}}foreach($w
as$x=>$X){if($X[0]=="PRIMARY"){unset($w[$x]);$fd[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}}foreach(foreign_keys($R)as$qe=>$p){foreach($p["source"]as$x=>$d){if(!$Wf[$d])continue
2;$p["source"][$x]=idf_unescape($Wf[$d]);}if(!isset($fd[" $qe"]))$fd[]=" ".format_foreign_key($p);}queries("BEGIN");}$Ta=array();foreach($n
as$m){if(preg_match('~GENERATED~',$m[3]))unset($Wf[array_search($m[0],$Wf)]);$Ta[]="  ".implode($m);}$Ta=array_merge($Ta,array_filter($fd));foreach(driver()->checkConstraints($R)as$Va){if($Va!=$fc)$Ta[]="  CHECK ($Va)";}if($ja)$Ta[]="  CHECK ($ja)";$oi=($R==$C?"adminer_$C":$C);if(!queries("CREATE TABLE ".table($oi)." (\n".implode(",\n",$Ta)."\n)"))return
false;if($R!=""){if($Wf&&!queries("INSERT INTO ".table($oi)." (".implode(", ",$Wf).") SELECT ".implode(", ",array_map('Adminer\idf_escape',array_keys($Wf)))." FROM ".table($R)))return
false;$Ni=array();foreach(triggers($R)as$Li=>$vi){$Ki=trigger($Li,$R);$Ni[]="CREATE TRIGGER ".idf_escape($Li)." ".implode(" ",$vi)." ON ".table($C)."\n$Ki[Statement]";}$_a=$_a?"":get_val("SELECT seq FROM sqlite_sequence WHERE name = ".q($R));if(!queries("DROP TABLE ".table($R))||($R==$C&&!queries("ALTER TABLE ".table($oi)." RENAME TO ".table($C)))||!alter_indexes($C,$w))return
false;if($_a)queries("UPDATE sqlite_sequence SET seq = $_a WHERE name = ".q($C));foreach($Ni
as$Ki){if(!queries($Ki))return
false;}queries("COMMIT");}return
true;}function
index_sql($R,$U,$C,$e){return"CREATE $U ".($U!="INDEX"?"INDEX ":"").idf_escape($C!=""?$C:uniqid($R."_"))." ON ".table($R)." $e";}function
alter_indexes($R,$b){foreach($b
as$G){if($G[0]=="PRIMARY")return
recreate_table($R,$R,array(),array(),array(),"",$b);}foreach(array_reverse($b)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($R,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables($T){return
apply_queries("DELETE FROM",$T);}function
drop_views($pj){return
apply_queries("DROP VIEW",$pj);}function
drop_tables($T){return
apply_queries("DROP TABLE",$T);}function
move_tables($T,$pj,$mi){return
false;}function
trigger($C,$R){if($C=="")return
array("Statement"=>"BEGIN\n\t;\nEND");$u='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$Mi=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$u\\s*(".implode("|",$Mi["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($u))?\\s+ON\\s*$u\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",get_val("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($C)),$B);$uf=$B[3];return
array("Timing"=>strtoupper($B[1]),"Event"=>strtoupper($B[2]).($uf?" OF":""),"Of"=>idf_unescape($uf),"Trigger"=>$C,"Statement"=>$B[4],);}function
triggers($R){$J=array();$Mi=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($R))as$K){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$Mi["Timing"]).')\s*(.*?)\s+ON\b~i',$K["sql"],$B);$J[$K["name"]]=array($B[1],$B[2]);}return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
begin(){return
queries("BEGIN");}function
last_id($I){return
get_val("SELECT LAST_INSERT_ROWID()");}function
explain($f,$H){return$f->query("EXPLAIN QUERY PLAN $H");}function
found_rows($S,$Z){}function
types(){return
array();}function
create_sql($R,$_a,$Vh){$J=get_val("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($R));foreach(indexes($R)as$C=>$v){if($C=='')continue;$J
.=";\n\n".index_sql($R,$v['type'],$C,"(".implode(", ",array_map('Adminer\idf_escape',$v['columns'])).")");}return$J;}function
truncate_sql($R){return"DELETE FROM ".table($R);}function
use_sql($Kb){}function
trigger_sql($R){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($R)));}function
show_variables(){$J=array();foreach(get_rows("PRAGMA pragma_list")as$K){$C=$K["name"];if($C!="pragma_list"&&$C!="compile_options"){$J[$C]=array($C,'');foreach(get_rows("PRAGMA $C")as$K)$J[$C][1].=implode(", ",$K)."\n";}}return$J;}function
show_status(){$J=array();foreach(get_vals("PRAGMA compile_options")as$If)$J[]=explode("=",$If,2)+array('','');return$J;}function
convert_field($m){}function
unconvert_field($m,$J){return$J;}function
support($Rc){return
preg_match('~^(check|columns|database|drop_col|dump|indexes|descidx|move_col|sql|status|table|trigger|variables|view|view_trigger)$~',$Rc);}}add_driver("pgsql","PostgreSQL");if(isset($_GET["pgsql"])){define('Adminer\DRIVER',"pgsql");if(extension_loaded("pgsql")&&$_GET["ext"]!="pdo"){class
PgsqlDb
extends
SqlDb{var$extension="PgSQL";var$timeout=0;private$link,$string,$database=true;function
_error($_c,$l){if(ini_bool("html_errors"))$l=html_entity_decode(strip_tags($l));$l=preg_replace('~^[^:]*: ~','',$l);$this->error=$l;}function
attach($N,$V,$F){$j=adminer()->database();set_error_handler(array($this,'_error'));$this->string="host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' user='".addcslashes($V,"'\\")."' password='".addcslashes($F,"'\\")."'";$Qh=adminer()->connectSsl();if(isset($Qh["mode"]))$this->string
.=" sslmode='".$Qh["mode"]."'";$this->link=@pg_connect("$this->string dbname='".($j!=""?addcslashes($j,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->link&&$j!=""){$this->database=false;$this->link=@pg_connect("$this->string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->link)pg_set_client_encoding($this->link,"UTF8");return($this->link?'':$this->error);}function
quote($Q){return(function_exists('pg_escape_literal')?pg_escape_literal($this->link,$Q):"'".pg_escape_string($this->link,$Q)."'");}function
value($X,array$m){return($m["type"]=="bytea"&&$X!==null?pg_unescape_bytea($X):$X);}function
select_db($Kb){if($Kb==adminer()->database())return$this->database;$J=@pg_connect("$this->string dbname='".addcslashes($Kb,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($J)$this->link=$J;return$J;}function
close(){$this->link=@pg_connect("$this->string dbname='postgres'");}function
query($H,$Ri=false){$I=@pg_query($this->link,$H);$this->error="";if(!$I){$this->error=pg_last_error($this->link);$J=false;}elseif(!pg_num_fields($I)){$this->affected_rows=pg_affected_rows($I);$J=true;}else$J=new
Result($I);if($this->timeout){$this->timeout=0;$this->query("RESET statement_timeout");}return$J;}function
warnings(){return
h(pg_last_notice($this->link));}function
copyFrom($R,array$L){$this->error='';set_error_handler(function($_c,$l){$this->error=(ini_bool('html_errors')?html_entity_decode($l):$l);});$J=pg_copy_from($this->link,$R,$L);restore_error_handler();return$J;}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($I){$this->result=$I;$this->num_rows=pg_num_rows($I);}function
fetch_assoc(){return
pg_fetch_assoc($this->result);}function
fetch_row(){return
pg_fetch_row($this->result);}function
fetch_field(){$d=$this->offset++;$J=new
\stdClass;$J->orgtable=pg_field_table($this->result,$d);$J->name=pg_field_name($this->result,$d);$J->type=pg_field_type($this->result,$d);$J->charsetnr=($J->type=="bytea"?63:0);return$J;}function
__destruct(){pg_free_result($this->result);}}}elseif(extension_loaded("pdo_pgsql")){class
PgsqlDb
extends
PdoDb{var$extension="PDO_PgSQL";var$timeout=0;function
attach($N,$V,$F){$j=adminer()->database();$jc="pgsql:host='".str_replace(":","' port='",addcslashes($N,"'\\"))."' client_encoding=utf8 dbname='".($j!=""?addcslashes($j,"'\\"):"postgres")."'";$Qh=adminer()->connectSsl();if(isset($Qh["mode"]))$jc
.=" sslmode='".$Qh["mode"]."'";return$this->dsn($jc,$V,$F);}function
select_db($Kb){return(adminer()->database()==$Kb);}function
query($H,$Ri=false){$J=parent::query($H,$Ri);if($this->timeout){$this->timeout=0;parent::query("RESET statement_timeout");}return$J;}function
warnings(){}function
copyFrom($R,array$L){$J=$this->pdo->pgsqlCopyFromArray($R,$L);$this->error=idx($this->pdo->errorInfo(),2)?:'';return$J;}function
close(){}}}if(class_exists('Adminer\PgsqlDb')){class
Db
extends
PgsqlDb{function
multi_query($H){if(preg_match('~\bCOPY\s+(.+?)\s+FROM\s+stdin;\n?(.*)\n\\\\\.$~is',str_replace("\r\n","\n",$H),$B)){$L=explode("\n",$B[2]);$this->affected_rows=count($L);return$this->copyFrom($B[1],$L);}return
parent::multi_query($H);}}}class
Driver
extends
SqlDriver{static$Mc=array("PgSQL","PDO_PgSQL");static$oe="pgsql";var$operators=array("=","<",">","<=",">=","!=","~","!~","LIKE","LIKE %%","ILIKE","ILIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");var$functions=array("char_length","lower","round","to_hex","to_timestamp","upper");var$grouping=array("avg","count","count distinct","max","min","sum");static
function
connect($N,$V,$F){$f=parent::connect($N,$V,$F);if(is_string($f))return$f;$nj=get_val("SELECT version()",0,$f);$f->flavor=(preg_match('~CockroachDB~',$nj)?'cockroach':'');$f->server_info=preg_replace('~^\D*([\d.]+[-\w]*).*~','\1',$nj);if(min_version(9,0,$f))$f->query("SET application_name = 'Adminer'");if($f->flavor=='cockroach')add_driver(DRIVER,"CockroachDB");return$f;}function
__construct(Db$f){parent::__construct($f);$this->types=array('Numbers'=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),'Date and time'=>array("date"=>13,"time"=>17,"timestamp"=>20,"timestamptz"=>21,"interval"=>0),'Strings'=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),'Binary'=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),'Network'=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"macaddr8"=>23,"txid_snapshot"=>0),'Geometry'=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),);if(min_version(9.2,0,$f)){$this->types['Strings']["json"]=4294967295;if(min_version(9.4,0,$f))$this->types['Strings']["jsonb"]=4294967295;}$this->insertFunctions=array("char"=>"md5","date|time"=>"now",);$this->editFunctions=array(number_type()=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",);if(min_version(12,0,$f))$this->generated=array("STORED");}function
enumLength(array$m){$wc=$this->types['User types'][$m["type"]];return($wc?type_values($wc):"");}function
setUserTypes($Qi){$this->types['User types']=array_flip($Qi);}function
insertReturning($R){$_a=array_filter(fields($R),function($m){return$m['auto_increment'];});return(count($_a)==1?" RETURNING ".idf_escape(key($_a)):"");}function
insertUpdate($R,array$L,array$G){foreach($L
as$O){$Zi=array();$Z=array();foreach($O
as$x=>$X){$Zi[]="$x = $X";if(isset($G[idf_unescape($x)]))$Z[]="$x = $X";}if(!(($Z&&queries("UPDATE ".table($R)." SET ".implode(", ",$Zi)." WHERE ".implode(" AND ",$Z))&&connection()->affected_rows)||queries("INSERT INTO ".table($R)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")")))return
false;}return
true;}function
slowQuery($H,$ui){$this->conn->query("SET statement_timeout = ".(1000*$ui));$this->conn->timeout=1000*$ui;return$H;}function
convertSearch($u,array$X,array$m){$ri="char|text";if(strpos($X["op"],"LIKE")===false)$ri
.="|date|time(stamp)?|boolean|uuid|inet|cidr|macaddr|".number_type();return(preg_match("~$ri~",$m["type"])?$u:"CAST($u AS text)");}function
quoteBinary($ih){return"'\\x".bin2hex($ih)."'";}function
warnings(){return$this->conn->warnings();}function
tableHelp($C,$me=false){$Ee=array("information_schema"=>"infoschema","pg_catalog"=>($me?"view":"catalog"),);$_=$Ee[$_GET["ns"]];if($_)return"$_-".str_replace("_","-",$C).".html";}function
supportsIndex(array$S){return$S["Engine"]!="view";}function
hasCStyleEscapes(){static$Pa;if($Pa===null)$Pa=(get_val("SHOW standard_conforming_strings",0,$this->conn)=="off");return$Pa;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
get_databases($dd){return
get_vals("SELECT datname FROM pg_database
WHERE datallowconn = TRUE AND has_database_privilege(datname, 'CONNECT')
ORDER BY datname");}function
limit($H,$Z,$z,$D=0,$vh=" "){return" $H$Z".($z?$vh."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($R,$H,$Z,$vh="\n"){return(preg_match('~^INTO~',$H)?limit($H,$Z,1,0,$vh):" $H".(is_view(table_status1($R))?$Z:$vh."WHERE ctid = (SELECT ctid FROM ".table($R).$Z.$vh."LIMIT 1)"));}function
db_collation($j,$hb){return
get_val("SELECT datcollate FROM pg_database WHERE datname = ".q($j));}function
logged_user(){return
get_val("SELECT user");}function
tables_list(){$H="SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema()";if(support("materializedview"))$H
.="
UNION ALL
SELECT matviewname, 'MATERIALIZED VIEW'
FROM pg_matviews
WHERE schemaname = current_schema()";$H
.="
ORDER BY 1";return
get_key_vals($H);}function
count_tables($i){$J=array();foreach($i
as$j){if(connection()->select_db($j))$J[$j]=count(tables_list());}return$J;}function
table_status($C=""){static$Bd;if($Bd===null)$Bd=get_val("SELECT 'pg_table_size'::regproc");$J=array();foreach(get_rows("SELECT
	c.relname AS \"Name\",
	CASE c.relkind WHEN 'r' THEN 'table' WHEN 'm' THEN 'materialized view' ELSE 'view' END AS \"Engine\"".($Bd?",
	pg_table_size(c.oid) AS \"Data_length\",
	pg_indexes_size(c.oid) AS \"Index_length\"":"").",
	obj_description(c.oid, 'pg_class') AS \"Comment\",
	".(min_version(12)?"''":"CASE WHEN c.relhasoids THEN 'oid' ELSE '' END")." AS \"Oid\",
	c.reltuples as \"Rows\",
	n.nspname
FROM pg_class c
JOIN pg_namespace n ON(n.nspname = current_schema() AND n.oid = c.relnamespace)
WHERE relkind IN ('r', 'm', 'v', 'f', 'p')
".($C!=""?"AND relname = ".q($C):"ORDER BY relname"))as$K)$J[$K["Name"]]=$K;return$J;}function
is_view($S){return
in_array($S["Engine"],array("view","materialized view"));}function
fk_support($S){return
true;}function
fields($R){$J=array();$ra=array('timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz',);foreach(get_rows("SELECT
	a.attname AS field,
	format_type(a.atttypid, a.atttypmod) AS full_type,
	pg_get_expr(d.adbin, d.adrelid) AS default,
	a.attnotnull::int,
	col_description(c.oid, a.attnum) AS comment".(min_version(10)?",
	a.attidentity".(min_version(12)?",
	a.attgenerated":""):"")."
FROM pg_class c
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_attribute a ON c.oid = a.attrelid
LEFT JOIN pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE c.relname = ".q($R)."
AND n.nspname = current_schema()
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$K){preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~',$K["full_type"],$B);list(,$U,$y,$K["length"],$ka,$va)=$B;$K["length"].=$va;$Xa=$U.$ka;if(isset($ra[$Xa])){$K["type"]=$ra[$Xa];$K["full_type"]=$K["type"].$y.$va;}else{$K["type"]=$U;$K["full_type"]=$K["type"].$y.$ka.$va;}if(in_array($K['attidentity'],array('a','d')))$K['default']='GENERATED '.($K['attidentity']=='d'?'BY DEFAULT':'ALWAYS').' AS IDENTITY';$K["generated"]=($K["attgenerated"]=="s"?"STORED":"");$K["null"]=!$K["attnotnull"];$K["auto_increment"]=$K['attidentity']||preg_match('~^nextval\(~i',$K["default"])||preg_match('~^unique_rowid\(~',$K["default"]);$K["privileges"]=array("insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1);if(preg_match('~(.+)::[^,)]+(.*)~',$K["default"],$B))$K["default"]=($B[1]=="NULL"?null:idf_unescape($B[1]).$B[2]);$J[$K["field"]]=$K;}return$J;}function
indexes($R,$g=null){$g=connection($g);$J=array();$ei=get_val("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = ".q($R),0,$g);$e=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $ei AND attnum > 0",$g);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption, (indpred IS NOT NULL)::int as indispartial
FROM pg_index i, pg_class ci
WHERE i.indrelid = $ei AND ci.oid = i.indexrelid
ORDER BY indisprimary DESC, indisunique DESC",$g)as$K){$Vg=$K["relname"];$J[$Vg]["type"]=($K["indispartial"]?"INDEX":($K["indisprimary"]?"PRIMARY":($K["indisunique"]?"UNIQUE":"INDEX")));$J[$Vg]["columns"]=array();$J[$Vg]["descs"]=array();if($K["indkey"]){foreach(explode(" ",$K["indkey"])as$Wd)$J[$Vg]["columns"][]=$e[$Wd];foreach(explode(" ",$K["indoption"])as$Xd)$J[$Vg]["descs"][]=(intval($Xd)&1?'1':null);}$J[$Vg]["lengths"]=array();}return$J;}function
foreign_keys($R){$J=array();foreach(get_rows("SELECT conname, condeferrable::int AS deferrable, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = (SELECT pc.oid FROM pg_class AS pc INNER JOIN pg_namespace AS pn ON (pn.oid = pc.relnamespace) WHERE pc.relname = ".q($R)." AND pn.nspname = current_schema())
AND contype = 'f'::char
ORDER BY conkey, conname")as$K){if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$K['definition'],$B)){$K['source']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$B[1])));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$B[2],$Je)){$K['ns']=idf_unescape($Je[2]);$K['table']=idf_unescape($Je[4]);}$K['target']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$B[3])));$K['on_delete']=(preg_match("~ON DELETE (".driver()->onActions.")~",$B[4],$Je)?$Je[1]:'NO ACTION');$K['on_update']=(preg_match("~ON UPDATE (".driver()->onActions.")~",$B[4],$Je)?$Je[1]:'NO ACTION');$J[$K['conname']]=$K;}}return$J;}function
view($C){return
array("select"=>trim(get_val("SELECT pg_get_viewdef(".get_val("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = ".q($C)).")")));}function
collations(){return
array();}function
information_schema($j){return
get_schema()=="information_schema";}function
error(){$J=h(connection()->error);if(preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s',$J,$B))$J=$B[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($B[3]).'})(.*)~','\1<b>\2</b>',$B[2]).$B[4];return
nl_br($J);}function
create_database($j,$c){return
queries("CREATE DATABASE ".idf_escape($j).($c?" ENCODING ".idf_escape($c):""));}function
drop_databases($i){connection()->close();return
apply_queries("DROP DATABASE",$i,'Adminer\idf_escape');}function
rename_database($C,$c){connection()->close();return
queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($C));}function
auto_increment(){return"";}function
alter_table($R,$C,$n,$fd,$mb,$uc,$c,$_a,$ig){$b=array();$Ig=array();if($R!=""&&$R!=$C)$Ig[]="ALTER TABLE ".table($R)." RENAME TO ".table($C);$wh="";foreach($n
as$m){$d=idf_escape($m[0]);$X=$m[1];if(!$X)$b[]="DROP $d";else{$jj=$X[5];unset($X[5]);if($m[0]==""){if(isset($X[6]))$X[1]=($X[1]==" bigint"?" big":($X[1]==" smallint"?" small":" "))."serial";$b[]=($R!=""?"ADD ":"  ").implode($X);if(isset($X[6]))$b[]=($R!=""?"ADD":" ")." PRIMARY KEY ($X[0])";}else{if($d!=$X[0])$Ig[]="ALTER TABLE ".table($C)." RENAME $d TO $X[0]";$b[]="ALTER $d TYPE$X[1]";$xh=$R."_".idf_unescape($X[0])."_seq";$b[]="ALTER $d ".($X[3]?"SET".preg_replace('~GENERATED ALWAYS(.*) STORED~','EXPRESSION\1',$X[3]):(isset($X[6])?"SET DEFAULT nextval(".q($xh).")":"DROP DEFAULT"));if(isset($X[6]))$wh="CREATE SEQUENCE IF NOT EXISTS ".idf_escape($xh)." OWNED BY ".idf_escape($R).".$X[0]";$b[]="ALTER $d ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}if($m[0]!=""||$jj!="")$Ig[]="COMMENT ON COLUMN ".table($C).".$X[0] IS ".($jj!=""?substr($jj,9):"''");}}$b=array_merge($b,$fd);if($R=="")array_unshift($Ig,"CREATE TABLE ".table($C)." (\n".implode(",\n",$b)."\n)");elseif($b)array_unshift($Ig,"ALTER TABLE ".table($R)."\n".implode(",\n",$b));if($wh)array_unshift($Ig,$wh);if($mb!==null)$Ig[]="COMMENT ON TABLE ".table($C)." IS ".q($mb);foreach($Ig
as$H){if(!queries($H))return
false;}return
true;}function
alter_indexes($R,$b){$h=array();$ec=array();$Ig=array();foreach($b
as$X){if($X[0]!="INDEX")$h[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");elseif($X[2]=="DROP")$ec[]=idf_escape($X[1]);else$Ig[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R)." (".implode(", ",$X[2]).")";}if($h)array_unshift($Ig,"ALTER TABLE ".table($R).implode(",",$h));if($ec)array_unshift($Ig,"DROP INDEX ".implode(", ",$ec));foreach($Ig
as$H){if(!queries($H))return
false;}return
true;}function
truncate_tables($T){return
queries("TRUNCATE ".implode(", ",array_map('Adminer\table',$T)));}function
drop_views($pj){return
drop_tables($pj);}function
drop_tables($T){foreach($T
as$R){$P=table_status1($R);if(!queries("DROP ".strtoupper($P["Engine"])." ".table($R)))return
false;}return
true;}function
move_tables($T,$pj,$mi){foreach(array_merge($T,$pj)as$R){$P=table_status1($R);if(!queries("ALTER ".strtoupper($P["Engine"])." ".table($R)." SET SCHEMA ".idf_escape($mi)))return
false;}return
true;}function
trigger($C,$R){if($C=="")return
array("Statement"=>"EXECUTE PROCEDURE ()");$e=array();$Z="WHERE trigger_schema = current_schema() AND event_object_table = ".q($R)." AND trigger_name = ".q($C);foreach(get_rows("SELECT * FROM information_schema.triggered_update_columns $Z")as$K)$e[]=$K["event_object_column"];$J=array();foreach(get_rows('SELECT trigger_name AS "Trigger", action_timing AS "Timing", event_manipulation AS "Event", \'FOR EACH \' || action_orientation AS "Type", action_statement AS "Statement"
FROM information_schema.triggers'."
$Z
ORDER BY event_manipulation DESC")as$K){if($e&&$K["Event"]=="UPDATE")$K["Event"].=" OF";$K["Of"]=implode(", ",$e);if($J)$K["Event"].=" OR $J[Event]";$J=$K;}return$J;}function
triggers($R){$J=array();foreach(get_rows("SELECT * FROM information_schema.triggers WHERE trigger_schema = current_schema() AND event_object_table = ".q($R))as$K){$Ki=trigger($K["trigger_name"],$R);$J[$Ki["Trigger"]]=array($Ki["Timing"],$Ki["Event"]);}return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE","INSERT OR UPDATE","INSERT OR UPDATE OF","DELETE OR INSERT","DELETE OR UPDATE","DELETE OR UPDATE OF","DELETE OR INSERT OR UPDATE","DELETE OR INSERT OR UPDATE OF"),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
routine($C,$U){$L=get_rows('SELECT routine_definition AS definition, LOWER(external_language) AS language, *
FROM information_schema.routines
WHERE routine_schema = current_schema() AND specific_name = '.q($C));$J=idx($L,0,array());$J["returns"]=array("type"=>$J["type_udt_name"]);$J["fields"]=get_rows('SELECT parameter_name AS field, data_type AS type, character_maximum_length AS length, parameter_mode AS inout
FROM information_schema.parameters
WHERE specific_schema = current_schema() AND specific_name = '.q($C).'
ORDER BY ordinal_position');return$J;}function
routines(){return
get_rows('SELECT specific_name AS "SPECIFIC_NAME", routine_type AS "ROUTINE_TYPE", routine_name AS "ROUTINE_NAME", type_udt_name AS "DTD_IDENTIFIER"
FROM information_schema.routines
WHERE routine_schema = current_schema()
ORDER BY SPECIFIC_NAME');}function
routine_languages(){return
get_vals("SELECT LOWER(lanname) FROM pg_catalog.pg_language");}function
routine_id($C,$K){$J=array();foreach($K["fields"]as$m){$y=$m["length"];$J[]=$m["type"].($y?"($y)":"");}return
idf_escape($C)."(".implode(", ",$J).")";}function
last_id($I){$K=(is_object($I)?$I->fetch_row():array());return($K?$K[0]:0);}function
explain($f,$H){return$f->query("EXPLAIN $H");}function
found_rows($S,$Z){if(preg_match("~ rows=([0-9]+)~",get_val("EXPLAIN SELECT * FROM ".idf_escape($S["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$Ug))return$Ug[1];}function
types(){return
get_key_vals("SELECT oid, typname
FROM pg_type
WHERE typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
AND typtype IN ('b','d','e')
AND typelem = 0");}function
type_values($t){$zc=get_vals("SELECT enumlabel FROM pg_enum WHERE enumtypid = $t ORDER BY enumsortorder");return($zc?"'".implode("', '",array_map('addslashes',$zc))."'":"");}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){return
get_val("SELECT current_schema()");}function
set_schema($kh,$g=null){if(!$g)$g=connection();$J=$g->query("SET search_path TO ".idf_escape($kh));driver()->setUserTypes(types());return$J;}function
foreign_keys_sql($R){$J="";$P=table_status1($R);$bd=foreign_keys($R);ksort($bd);foreach($bd
as$ad=>$Zc)$J
.="ALTER TABLE ONLY ".idf_escape($P['nspname']).".".idf_escape($P['Name'])." ADD CONSTRAINT ".idf_escape($ad)." $Zc[definition] ".($Zc['deferrable']?'DEFERRABLE':'NOT DEFERRABLE').";\n";return($J?"$J\n":$J);}function
create_sql($R,$_a,$Vh){$ah=array();$yh=array();$P=table_status1($R);if(is_view($P)){$oj=view($R);return
rtrim("CREATE VIEW ".idf_escape($R)." AS $oj[select]",";");}$n=fields($R);if(count($P)<2||empty($n))return
false;$J="CREATE TABLE ".idf_escape($P['nspname']).".".idf_escape($P['Name'])." (\n    ";foreach($n
as$m){$fg=idf_escape($m['field']).' '.$m['full_type'].default_value($m).($m['null']?"":" NOT NULL");$ah[]=$fg;if(preg_match('~nextval\(\'([^\']+)\'\)~',$m['default'],$Le)){$xh=$Le[1];$Kh=first(get_rows((min_version(10)?"SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = ".q(idf_unescape($xh)):"SELECT * FROM $xh"),null,"-- "));$yh[]=($Vh=="DROP+CREATE"?"DROP SEQUENCE IF EXISTS $xh;\n":"")."CREATE SEQUENCE $xh INCREMENT $Kh[increment_by] MINVALUE $Kh[min_value] MAXVALUE $Kh[max_value]".($_a&&$Kh['last_value']?" START ".($Kh["last_value"]+1):"")." CACHE $Kh[cache_value];";}}if(!empty($yh))$J=implode("\n\n",$yh)."\n\n$J";$G="";foreach(indexes($R)as$Ud=>$v){if($v['type']=='PRIMARY'){$G=$Ud;$ah[]="CONSTRAINT ".idf_escape($Ud)." PRIMARY KEY (".implode(', ',array_map('Adminer\idf_escape',$v['columns'])).")";}}foreach(driver()->checkConstraints($R)as$rb=>$tb)$ah[]="CONSTRAINT ".idf_escape($rb)." CHECK $tb";$J
.=implode(",\n    ",$ah)."\n) WITH (oids = ".($P['Oid']?'true':'false').");";if($P['Comment'])$J
.="\n\nCOMMENT ON TABLE ".idf_escape($P['nspname']).".".idf_escape($P['Name'])." IS ".q($P['Comment']).";";foreach($n
as$Tc=>$m){if($m['comment'])$J
.="\n\nCOMMENT ON COLUMN ".idf_escape($P['nspname']).".".idf_escape($P['Name']).".".idf_escape($Tc)." IS ".q($m['comment']).";";}foreach(get_rows("SELECT indexdef FROM pg_catalog.pg_indexes WHERE schemaname = current_schema() AND tablename = ".q($R).($G?" AND indexname != ".q($G):""),null,"-- ")as$K)$J
.="\n\n$K[indexdef];";return
rtrim($J,';');}function
truncate_sql($R){return"TRUNCATE ".table($R);}function
trigger_sql($R){$P=table_status1($R);$J="";foreach(triggers($R)as$Ji=>$Ii){$Ki=trigger($Ji,$P['Name']);$J
.="\nCREATE TRIGGER ".idf_escape($Ki['Trigger'])." $Ki[Timing] $Ki[Event] ON ".idf_escape($P["nspname"]).".".idf_escape($P['Name'])." $Ki[Type] $Ki[Statement];;\n";}return$J;}function
use_sql($Kb){return"\connect ".idf_escape($Kb);}function
show_variables(){return
get_rows("SHOW ALL");}function
process_list(){return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".(min_version(9.2)?"pid":"procpid"));}function
convert_field($m){}function
unconvert_field($m,$J){return$J;}function
support($Rc){return
preg_match('~^(check|database|table|columns|sql|indexes|descidx|comment|view|'.(min_version(9.3)?'materializedview|':'').'scheme|'.(min_version(11)?'procedure|':'').'routine|sequence|trigger|type|variables|drop_col'.(connection()->flavor=='cockroach'?'':'|processlist').'|kill|dump)$~',$Rc);}function
kill_process($X){return
queries("SELECT pg_terminate_backend(".number($X).")");}function
connection_id(){return"SELECT pg_backend_pid()";}function
max_connections(){return
get_val("SHOW max_connections");}}add_driver("oracle","Oracle (beta)");if(isset($_GET["oracle"])){define('Adminer\DRIVER',"oracle");if(extension_loaded("oci8")&&$_GET["ext"]!="pdo"){class
Db
extends
SqlDb{var$extension="oci8";var$_current_db;private$link;function
_error($_c,$l){if(ini_bool("html_errors"))$l=html_entity_decode(strip_tags($l));$l=preg_replace('~^[^:]*: ~','',$l);$this->error=$l;}function
attach($N,$V,$F){$this->link=@oci_new_connect($V,$F,$N,"AL32UTF8");if($this->link){$this->server_info=oci_server_version($this->link);return'';}$l=oci_error();return$l["message"];}function
quote($Q){return"'".str_replace("'","''",$Q)."'";}function
select_db($Kb){$this->_current_db=$Kb;return
true;}function
query($H,$Ri=false){$I=oci_parse($this->link,$H);$this->error="";if(!$I){$l=oci_error($this->link);$this->errno=$l["code"];$this->error=$l["message"];return
false;}set_error_handler(array($this,'_error'));$J=@oci_execute($I);restore_error_handler();if($J){if(oci_num_fields($I))return
new
Result($I);$this->affected_rows=oci_num_rows($I);oci_free_statement($I);}return$J;}}class
Result{var$num_rows;private$result,$offset=1;function
__construct($I){$this->result=$I;}private
function
convert($K){foreach((array)$K
as$x=>$X){if(is_a($X,'OCILob')||is_a($X,'OCI-Lob'))$K[$x]=$X->load();}return$K;}function
fetch_assoc(){return$this->convert(oci_fetch_assoc($this->result));}function
fetch_row(){return$this->convert(oci_fetch_row($this->result));}function
fetch_field(){$d=$this->offset++;$J=new
\stdClass;$J->name=oci_field_name($this->result,$d);$J->type=oci_field_type($this->result,$d);$J->charsetnr=(preg_match("~raw|blob|bfile~",$J->type)?63:0);return$J;}function
__destruct(){oci_free_statement($this->result);}}}elseif(extension_loaded("pdo_oci")){class
Db
extends
PdoDb{var$extension="PDO_OCI";var$_current_db;function
attach($N,$V,$F){return$this->dsn("oci:dbname=//$N;charset=AL32UTF8",$V,$F);}function
select_db($Kb){$this->_current_db=$Kb;return
true;}}}class
Driver
extends
SqlDriver{static$Mc=array("OCI8","PDO_OCI");static$oe="oracle";var$insertFunctions=array("date"=>"current_date","timestamp"=>"current_timestamp",);var$editFunctions=array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",);var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");var$functions=array("length","lower","round","upper");var$grouping=array("avg","count","count distinct","max","min","sum");function
__construct(Db$f){parent::__construct($f);$this->types=array('Numbers'=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),'Date and time'=>array("date"=>10,"timestamp"=>29,"interval year"=>12,"interval day"=>28),'Strings'=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),'Binary'=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),);}function
begin(){return
true;}function
insertUpdate($R,array$L,array$G){foreach($L
as$O){$Zi=array();$Z=array();foreach($O
as$x=>$X){$Zi[]="$x = $X";if(isset($G[idf_unescape($x)]))$Z[]="$x = $X";}if(!(($Z&&queries("UPDATE ".table($R)." SET ".implode(", ",$Zi)." WHERE ".implode(" AND ",$Z))&&connection()->affected_rows)||queries("INSERT INTO ".table($R)." (".implode(", ",array_keys($O)).") VALUES (".implode(", ",$O).")")))return
false;}return
true;}function
hasCStyleEscapes(){return
true;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
get_databases($dd){return
get_vals("SELECT DISTINCT tablespace_name FROM (
SELECT tablespace_name FROM user_tablespaces
UNION SELECT tablespace_name FROM all_tables WHERE tablespace_name IS NOT NULL
)
ORDER BY 1");}function
limit($H,$Z,$z,$D=0,$vh=" "){return($D?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $H$Z) t WHERE rownum <= ".($z+$D).") WHERE rnum > $D":($z?" * FROM (SELECT $H$Z) WHERE rownum <= ".($z+$D):" $H$Z"));}function
limit1($R,$H,$Z,$vh="\n"){return" $H$Z";}function
db_collation($j,$hb){return
get_val("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
logged_user(){return
get_val("SELECT USER FROM DUAL");}function
get_current_db(){$j=connection()->_current_db?:DB;unset(connection()->_current_db);return$j;}function
where_owner($yg,$Zf="owner"){if(!$_GET["ns"])return'';return"$yg$Zf = sys_context('USERENV', 'CURRENT_SCHEMA')";}function
views_table($e){$Zf=where_owner('');return"(SELECT $e FROM all_views WHERE ".($Zf?:"rownum < 0").")";}function
tables_list(){$oj=views_table("view_name");$Zf=where_owner(" AND ");return
get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = ".q(DB)."$Zf
UNION SELECT view_name, 'view' FROM $oj
ORDER BY 1");}function
count_tables($i){$J=array();foreach($i
as$j)$J[$j]=get_val("SELECT COUNT(*) FROM all_tables WHERE tablespace_name = ".q($j));return$J;}function
table_status($C=""){$J=array();$oh=q($C);$j=get_current_db();$oj=views_table("view_name");$Zf=where_owner(" AND ");foreach(get_rows('SELECT table_name "Name", \'table\' "Engine", avg_row_len * num_rows "Data_length", num_rows "Rows" FROM all_tables WHERE tablespace_name = '.q($j).$Zf.($C!=""?" AND table_name = $oh":"")."
UNION SELECT view_name, 'view', 0, 0 FROM $oj".($C!=""?" WHERE view_name = $oh":"")."
ORDER BY 1")as$K)$J[$K["Name"]]=$K;return$J;}function
is_view($S){return$S["Engine"]=="view";}function
fk_support($S){return
true;}function
fields($R){$J=array();$Zf=where_owner(" AND ");foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($R)."$Zf ORDER BY column_id")as$K){$U=$K["DATA_TYPE"];$y="$K[DATA_PRECISION],$K[DATA_SCALE]";if($y==",")$y=$K["CHAR_COL_DECL_LENGTH"];$J[$K["COLUMN_NAME"]]=array("field"=>$K["COLUMN_NAME"],"full_type"=>$U.($y?"($y)":""),"type"=>strtolower($U),"length"=>$y,"default"=>$K["DATA_DEFAULT"],"null"=>($K["NULLABLE"]=="Y"),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1),);}return$J;}function
indexes($R,$g=null){$J=array();$Zf=where_owner(" AND ","aic.table_owner");foreach(get_rows("SELECT aic.*, ac.constraint_type, atc.data_default
FROM all_ind_columns aic
LEFT JOIN all_constraints ac ON aic.index_name = ac.constraint_name AND aic.table_name = ac.table_name AND aic.index_owner = ac.owner
LEFT JOIN all_tab_cols atc ON aic.column_name = atc.column_name AND aic.table_name = atc.table_name AND aic.index_owner = atc.owner
WHERE aic.table_name = ".q($R)."$Zf
ORDER BY ac.constraint_type, aic.column_position",$g)as$K){$Ud=$K["INDEX_NAME"];$jb=$K["DATA_DEFAULT"];$jb=($jb?trim($jb,'"'):$K["COLUMN_NAME"]);$J[$Ud]["type"]=($K["CONSTRAINT_TYPE"]=="P"?"PRIMARY":($K["CONSTRAINT_TYPE"]=="U"?"UNIQUE":"INDEX"));$J[$Ud]["columns"][]=$jb;$J[$Ud]["lengths"][]=($K["CHAR_LENGTH"]&&$K["CHAR_LENGTH"]!=$K["COLUMN_LENGTH"]?$K["CHAR_LENGTH"]:null);$J[$Ud]["descs"][]=($K["DESCEND"]&&$K["DESCEND"]=="DESC"?'1':null);}return$J;}function
view($C){$oj=views_table("view_name, text");$L=get_rows('SELECT text "select" FROM '.$oj.' WHERE view_name = '.q($C));return
reset($L);}function
collations(){return
array();}function
information_schema($j){return
get_schema()=="INFORMATION_SCHEMA";}function
error(){return
h(connection()->error);}function
explain($f,$H){$f->query("EXPLAIN PLAN FOR $H");return$f->query("SELECT * FROM plan_table");}function
found_rows($S,$Z){}function
auto_increment(){return"";}function
alter_table($R,$C,$n,$fd,$mb,$uc,$c,$_a,$ig){$b=$ec=array();$Sf=($R?fields($R):array());foreach($n
as$m){$X=$m[1];if($X&&$m[0]!=""&&idf_escape($m[0])!=$X[0])queries("ALTER TABLE ".table($R)." RENAME COLUMN ".idf_escape($m[0])." TO $X[0]");$Rf=$Sf[$m[0]];if($X&&$Rf){$wf=process_field($Rf,$Rf);if($X[2]==$wf[2])$X[2]="";}if($X)$b[]=($R!=""?($m[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($R!=""?")":"");else$ec[]=idf_escape($m[0]);}if($R=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$b)."\n)");return(!$b||queries("ALTER TABLE ".table($R)."\n".implode("\n",$b)))&&(!$ec||queries("ALTER TABLE ".table($R)." DROP (".implode(", ",$ec).")"))&&($R==$C||queries("ALTER TABLE ".table($R)." RENAME TO ".table($C)));}function
alter_indexes($R,$b){$ec=array();$Ig=array();foreach($b
as$X){if($X[0]!="INDEX"){$X[2]=preg_replace('~ DESC$~','',$X[2]);$h=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");array_unshift($Ig,"ALTER TABLE ".table($R).$h);}elseif($X[2]=="DROP")$ec[]=idf_escape($X[1]);else$Ig[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R)." (".implode(", ",$X[2]).")";}if($ec)array_unshift($Ig,"DROP INDEX ".implode(", ",$ec));foreach($Ig
as$H){if(!queries($H))return
false;}return
true;}function
foreign_keys($R){$J=array();$H="SELECT c_list.CONSTRAINT_NAME as NAME,
c_src.COLUMN_NAME as SRC_COLUMN,
c_dest.OWNER as DEST_DB,
c_dest.TABLE_NAME as DEST_TABLE,
c_dest.COLUMN_NAME as DEST_COLUMN,
c_list.DELETE_RULE as ON_DELETE
FROM ALL_CONSTRAINTS c_list, ALL_CONS_COLUMNS c_src, ALL_CONS_COLUMNS c_dest
WHERE c_list.CONSTRAINT_NAME = c_src.CONSTRAINT_NAME
AND c_list.R_CONSTRAINT_NAME = c_dest.CONSTRAINT_NAME
AND c_list.CONSTRAINT_TYPE = 'R'
AND c_src.TABLE_NAME = ".q($R);foreach(get_rows($H)as$K)$J[$K['NAME']]=array("db"=>$K['DEST_DB'],"table"=>$K['DEST_TABLE'],"source"=>array($K['SRC_COLUMN']),"target"=>array($K['DEST_COLUMN']),"on_delete"=>$K['ON_DELETE'],"on_update"=>null,);return$J;}function
truncate_tables($T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views($pj){return
apply_queries("DROP VIEW",$pj);}function
drop_tables($T){return
apply_queries("DROP TABLE",$T);}function
last_id($I){return
0;}function
schemas(){$J=get_vals("SELECT DISTINCT owner FROM dba_segments WHERE owner IN (SELECT username FROM dba_users WHERE default_tablespace NOT IN ('SYSTEM','SYSAUX')) ORDER BY 1");return($J?:get_vals("SELECT DISTINCT owner FROM all_tables WHERE tablespace_name = ".q(DB)." ORDER BY 1"));}function
get_schema(){return
get_val("SELECT sys_context('USERENV', 'SESSION_USER') FROM dual");}function
set_schema($mh,$g=null){if(!$g)$g=connection();return$g->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($mh));}function
show_variables(){return
get_rows('SELECT name, display_value FROM v$parameter');}function
show_status(){$J=array();$L=get_rows('SELECT * FROM v$instance');foreach(reset($L)as$x=>$X)$J[]=array($x,$X);return$J;}function
process_list(){return
get_rows('SELECT
	sess.process AS "process",
	sess.username AS "user",
	sess.schemaname AS "schema",
	sess.status AS "status",
	sess.wait_class AS "wait_class",
	sess.seconds_in_wait AS "seconds_in_wait",
	sql.sql_text AS "sql_text",
	sess.machine AS "machine",
	sess.port AS "port"
FROM v$session sess LEFT OUTER JOIN v$sql sql
ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');}function
convert_field($m){}function
unconvert_field($m,$J){return$J;}function
support($Rc){return
preg_match('~^(columns|database|drop_col|indexes|descidx|processlist|scheme|sql|status|table|variables|view)$~',$Rc);}}add_driver("mssql","MS SQL");if(isset($_GET["mssql"])){define('Adminer\DRIVER',"mssql");if(extension_loaded("sqlsrv")&&$_GET["ext"]!="pdo"){class
Db
extends
SqlDb{var$extension="sqlsrv";private$link,$result;private
function
get_error(){$this->error="";foreach(sqlsrv_errors()as$l){$this->errno=$l["code"];$this->error
.="$l[message]\n";}$this->error=rtrim($this->error);}function
attach($N,$V,$F){$sb=array("UID"=>$V,"PWD"=>$F,"CharacterSet"=>"UTF-8");$Qh=adminer()->connectSsl();if(isset($Qh["Encrypt"]))$sb["Encrypt"]=$Qh["Encrypt"];if(isset($Qh["TrustServerCertificate"]))$sb["TrustServerCertificate"]=$Qh["TrustServerCertificate"];$j=adminer()->database();if($j!="")$sb["Database"]=$j;$this->link=@sqlsrv_connect(preg_replace('~:~',',',$N),$sb);if($this->link){$Yd=sqlsrv_server_info($this->link);$this->server_info=$Yd['SQLServerVersion'];}else$this->get_error();return($this->link?'':$this->error);}function
quote($Q){$Si=strlen($Q)!=strlen(utf8_decode($Q));return($Si?"N":"")."'".str_replace("'","''",$Q)."'";}function
select_db($Kb){return$this->query(use_sql($Kb));}function
query($H,$Ri=false){$I=sqlsrv_query($this->link,$H);$this->error="";if(!$I){$this->get_error();return
false;}return$this->store_result($I);}function
multi_query($H){$this->result=sqlsrv_query($this->link,$H);$this->error="";if(!$this->result){$this->get_error();return
false;}return
true;}function
store_result($I=null){if(!$I)$I=$this->result;if(!$I)return
false;if(sqlsrv_field_metadata($I))return
new
Result($I);$this->affected_rows=sqlsrv_rows_affected($I);return
true;}function
next_result(){return$this->result?!!sqlsrv_next_result($this->result):false;}}class
Result{var$num_rows;private$result,$offset=0,$fields;function
__construct($I){$this->result=$I;}private
function
convert($K){foreach((array)$K
as$x=>$X){if(is_a($X,'DateTime'))$K[$x]=$X->format("Y-m-d H:i:s");}return$K;}function
fetch_assoc(){return$this->convert(sqlsrv_fetch_array($this->result,SQLSRV_FETCH_ASSOC));}function
fetch_row(){return$this->convert(sqlsrv_fetch_array($this->result,SQLSRV_FETCH_NUMERIC));}function
fetch_field(){if(!$this->fields)$this->fields=sqlsrv_field_metadata($this->result);$m=$this->fields[$this->offset++];$J=new
\stdClass;$J->name=$m["Name"];$J->type=($m["Type"]==1?254:15);$J->charsetnr=0;return$J;}function
seek($D){for($s=0;$s<$D;$s++)sqlsrv_fetch($this->result);}function
__destruct(){sqlsrv_free_stmt($this->result);}}function
last_id($I){return
get_val("SELECT SCOPE_IDENTITY()");}function
explain($f,$H){$f->query("SET SHOWPLAN_ALL ON");$J=$f->query($H);$f->query("SET SHOWPLAN_ALL OFF");return$J;}}else{abstract
class
MssqlDb
extends
PdoDb{function
select_db($Kb){return$this->query(use_sql($Kb));}function
lastInsertId(){return$this->pdo->lastInsertId();}}function
last_id($I){return
connection()->lastInsertId();}function
explain($f,$H){}if(extension_loaded("pdo_sqlsrv")){class
Db
extends
MssqlDb{var$extension="PDO_SQLSRV";function
attach($N,$V,$F){return$this->dsn("sqlsrv:Server=".str_replace(":",",",$N),$V,$F);}}}elseif(extension_loaded("pdo_dblib")){class
Db
extends
MssqlDb{var$extension="PDO_DBLIB";function
attach($N,$V,$F){return$this->dsn("dblib:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\d)~',';port=\1',$N)),$V,$F);}}}}class
Driver
extends
SqlDriver{static$Mc=array("SQLSRV","PDO_SQLSRV","PDO_DBLIB");static$oe="mssql";var$insertFunctions=array("date|time"=>"getdate");var$editFunctions=array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",);var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");var$functions=array("len","lower","round","upper");var$grouping=array("avg","count","count distinct","max","min","sum");var$generated=array("PERSISTED","VIRTUAL");var$onActions="NO ACTION|CASCADE|SET NULL|SET DEFAULT";static
function
connect($N,$V,$F){if($N=="")$N="localhost:1433";return
parent::connect($N,$V,$F);}function
__construct(Db$f){parent::__construct($f);$this->types=array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20),'Date and time'=>array("date"=>10,"smalldatetime"=>19,"datetime"=>19,"datetime2"=>19,"time"=>8,"datetimeoffset"=>10),'Strings'=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823),'Binary'=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),);}function
insertUpdate($R,array$L,array$G){$n=fields($R);$Zi=array();$Z=array();$O=reset($L);$e="c".implode(", c",range(1,count($O)));$Oa=0;$ce=array();foreach($O
as$x=>$X){$Oa++;$C=idf_unescape($x);if(!$n[$C]["auto_increment"])$ce[$x]="c$Oa";if(isset($G[$C]))$Z[]="$x = c$Oa";else$Zi[]="$x = c$Oa";}$kj=array();foreach($L
as$O)$kj[]="(".implode(", ",$O).")";if($Z){$Nd=queries("SET IDENTITY_INSERT ".table($R)." ON");$J=queries("MERGE ".table($R)." USING (VALUES\n\t".implode(",\n\t",$kj)."\n) AS source ($e) ON ".implode(" AND ",$Z).($Zi?"\nWHEN MATCHED THEN UPDATE SET ".implode(", ",$Zi):"")."\nWHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($Nd?$O:$ce)).") VALUES (".($Nd?$e:implode(", ",$ce)).");");if($Nd)queries("SET IDENTITY_INSERT ".table($R)." OFF");}else$J=queries("INSERT INTO ".table($R)." (".implode(", ",array_keys($O)).") VALUES\n".implode(",\n",$kj));return$J;}function
begin(){return
queries("BEGIN TRANSACTION");}function
tableHelp($C,$me=false){$Ee=array("sys"=>"catalog-views/sys-","INFORMATION_SCHEMA"=>"information-schema-views/",);$_=$Ee[get_schema()];if($_)return"relational-databases/system-$_".preg_replace('~_~','-',strtolower($C))."-transact-sql";}}function
idf_escape($u){return"[".str_replace("]","]]",$u)."]";}function
table($u){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($u);}function
get_databases($dd){return
get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");}function
limit($H,$Z,$z,$D=0,$vh=" "){return($z?" TOP (".($z+$D).")":"")." $H$Z";}function
limit1($R,$H,$Z,$vh="\n"){return
limit($H,$Z,1,0,$vh);}function
db_collation($j,$hb){return
get_val("SELECT collation_name FROM sys.databases WHERE name = ".q($j));}function
logged_user(){return
get_val("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables($i){$J=array();foreach($i
as$j){connection()->select_db($j);$J[$j]=get_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$J;}function
table_status($C=""){$J=array();foreach(get_rows("SELECT ao.name AS Name, ao.type_desc AS Engine, (SELECT value FROM fn_listextendedproperty(default, 'SCHEMA', schema_name(schema_id), 'TABLE', ao.name, null, null)) AS Comment
FROM sys.all_objects AS ao
WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$K)$J[$K["Name"]]=$K;return$J;}function
is_view($S){return$S["Engine"]=="VIEW";}function
fk_support($S){return
true;}function
fields($R){$ob=get_key_vals("SELECT objname, cast(value as varchar(max)) FROM fn_listextendedproperty('MS_DESCRIPTION', 'schema', ".q(get_schema()).", 'table', ".q($R).", 'column', NULL)");$J=array();$ci=get_val("SELECT object_id FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') AND name = ".q($R));foreach(get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name, t.name type, d.definition [default], d.name default_constraint, i.is_primary_key
FROM sys.all_columns c
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.object_id
LEFT JOIN sys.index_columns ic ON c.object_id = ic.object_id AND c.column_id = ic.column_id
LEFT JOIN sys.indexes i ON ic.object_id = i.object_id AND ic.index_id = i.index_id
WHERE c.object_id = ".q($ci))as$K){$U=$K["type"];$y=(preg_match("~char|binary~",$U)?intval($K["max_length"])/($U[0]=='n'?2:1):($U=="decimal"?"$K[precision],$K[scale]":""));$J[$K["name"]]=array("field"=>$K["name"],"full_type"=>$U.($y?"($y)":""),"type"=>$U,"length"=>$y,"default"=>(preg_match("~^\('(.*)'\)$~",$K["default"],$B)?str_replace("''","'",$B[1]):$K["default"]),"default_constraint"=>$K["default_constraint"],"null"=>$K["is_nullable"],"auto_increment"=>$K["is_identity"],"collation"=>$K["collation_name"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1),"primary"=>$K["is_primary_key"],"comment"=>$ob[$K["name"]],);}foreach(get_rows("SELECT * FROM sys.computed_columns WHERE object_id = ".q($ci))as$K){$J[$K["name"]]["generated"]=($K["is_persisted"]?"PERSISTED":"VIRTUAL");$J[$K["name"]]["default"]=$K["definition"];}return$J;}function
indexes($R,$g=null){$J=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($R),$g)as$K){$C=$K["name"];$J[$C]["type"]=($K["is_primary_key"]?"PRIMARY":($K["is_unique"]?"UNIQUE":"INDEX"));$J[$C]["lengths"]=array();$J[$C]["columns"][$K["key_ordinal"]]=$K["column_name"];$J[$C]["descs"][$K["key_ordinal"]]=($K["is_descending_key"]?'1':null);}return$J;}function
view($C){return
array("select"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU','',get_val("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($C))));}function
collations(){$J=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$c)$J[preg_replace('~_.*~','',$c)][]=$c;return$J;}function
information_schema($j){return
get_schema()=="INFORMATION_SCHEMA";}function
error(){return
nl_br(h(preg_replace('~^(\[[^]]*])+~m','',connection()->error)));}function
create_database($j,$c){return
queries("CREATE DATABASE ".idf_escape($j).(preg_match('~^[a-z0-9_]+$~i',$c)?" COLLATE $c":""));}function
drop_databases($i){return
queries("DROP DATABASE ".implode(", ",array_map('Adminer\idf_escape',$i)));}function
rename_database($C,$c){if(preg_match('~^[a-z0-9_]+$~i',$c))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $c");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($C));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".number($_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($R,$C,$n,$fd,$mb,$uc,$c,$_a,$ig){$b=array();$ob=array();$Sf=fields($R);foreach($n
as$m){$d=idf_escape($m[0]);$X=$m[1];if(!$X)$b["DROP"][]=" COLUMN $d";else{$X[1]=preg_replace("~( COLLATE )'(\\w+)'~",'\1\2',$X[1]);$ob[$m[0]]=$X[5];unset($X[5]);if(preg_match('~ AS ~',$X[3]))unset($X[1],$X[2]);if($m[0]=="")$b["ADD"][]="\n  ".implode("",$X).($R==""?substr($fd[$X[0]],16+strlen($X[0])):"");else{$k=$X[3];unset($X[3]);unset($X[6]);if($d!=$X[0])queries("EXEC sp_rename ".q(table($R).".$d").", ".q(idf_unescape($X[0])).", 'COLUMN'");$b["ALTER COLUMN ".implode("",$X)][]="";$Rf=$Sf[$m[0]];if(default_value($Rf)!=$k){if($Rf["default"]!==null)$b["DROP"][]=" ".idf_escape($Rf["default_constraint"]);if($k)$b["ADD"][]="\n $k FOR $d";}}}}if($R=="")return
queries("CREATE TABLE ".table($C)." (".implode(",",(array)$b["ADD"])."\n)");if($R!=$C)queries("EXEC sp_rename ".q(table($R)).", ".q($C));if($fd)$b[""]=$fd;foreach($b
as$x=>$X){if(!queries("ALTER TABLE ".table($C)." $x".implode(",",$X)))return
false;}foreach($ob
as$x=>$X){$mb=substr($X,9);queries("EXEC sp_dropextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($C).", @level2type = N'Column', @level2name = ".q($x));queries("EXEC sp_addextendedproperty
@name = N'MS_Description',
@value = $mb,
@level0type = N'Schema',
@level0name = ".q(get_schema()).",
@level1type = N'Table',
@level1name = ".q($C).",
@level2type = N'Column',
@level2name = ".q($x));}return
true;}function
alter_indexes($R,$b){$v=array();$ec=array();foreach($b
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY")$ec[]=idf_escape($X[1]);else$v[]=idf_escape($X[1])." ON ".table($R);}elseif(!queries(($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R):"ALTER TABLE ".table($R)." ADD PRIMARY KEY")." (".implode(", ",$X[2]).")"))return
false;}return(!$v||queries("DROP INDEX ".implode(", ",$v)))&&(!$ec||queries("ALTER TABLE ".table($R)." DROP ".implode(", ",$ec)));}function
found_rows($S,$Z){}function
foreign_keys($R){$J=array();$Cf=array("CASCADE","NO ACTION","SET NULL","SET DEFAULT");foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($R).", @fktable_owner = ".q(get_schema()))as$K){$p=&$J[$K["FK_NAME"]];$p["db"]=$K["PKTABLE_QUALIFIER"];$p["ns"]=$K["PKTABLE_OWNER"];$p["table"]=$K["PKTABLE_NAME"];$p["on_update"]=$Cf[$K["UPDATE_RULE"]];$p["on_delete"]=$Cf[$K["DELETE_RULE"]];$p["source"][]=$K["FKCOLUMN_NAME"];$p["target"][]=$K["PKCOLUMN_NAME"];}return$J;}function
truncate_tables($T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views($pj){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$pj)));}function
drop_tables($T){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$T)));}function
move_tables($T,$pj,$mi){return
apply_queries("ALTER SCHEMA ".idf_escape($mi)." TRANSFER",array_merge($T,$pj));}function
trigger($C,$R){if($C=="")return
array();$L=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($C));$J=reset($L);if($J)$J["Statement"]=preg_replace('~^.+\s+AS\s+~isU','',$J["text"]);return$J;}function
triggers($R){$J=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($R))as$K)$J[$K["name"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("AS"),);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){if($_GET["ns"]!="")return$_GET["ns"];return
get_val("SELECT SCHEMA_NAME()");}function
set_schema($kh){$_GET["ns"]=$kh;return
true;}function
create_sql($R,$_a,$Vh){if(is_view(table_status1($R))){$oj=view($R);return"CREATE VIEW ".table($R)." AS $oj[select]";}$n=array();$G=false;foreach(fields($R)as$C=>$m){$X=process_field($m,$m);if($X[6])$G=true;$n[]=implode("",$X);}foreach(indexes($R)as$C=>$v){if(!$G||$v["type"]!="PRIMARY"){$e=array();foreach($v["columns"]as$x=>$X)$e[]=idf_escape($X).($v["descs"][$x]?" DESC":"");$C=idf_escape($C);$n[]=($v["type"]=="INDEX"?"INDEX $C":"CONSTRAINT $C ".($v["type"]=="UNIQUE"?"UNIQUE":"PRIMARY KEY"))." (".implode(", ",$e).")";}}foreach(driver()->checkConstraints($R)as$C=>$Va)$n[]="CONSTRAINT ".idf_escape($C)." CHECK ($Va)";return"CREATE TABLE ".table($R)." (\n\t".implode(",\n\t",$n)."\n)";}function
foreign_keys_sql($R){$n=array();foreach(foreign_keys($R)as$fd)$n[]=ltrim(format_foreign_key($fd));return($n?"ALTER TABLE ".table($R)." ADD\n\t".implode(",\n\t",$n).";\n\n":"");}function
truncate_sql($R){return"TRUNCATE TABLE ".table($R);}function
use_sql($Kb){return"USE ".idf_escape($Kb);}function
trigger_sql($R){$J="";foreach(triggers($R)as$C=>$Ki)$J
.=create_trigger(" ON ".table($R),trigger($C,$R)).";";return$J;}function
convert_field($m){}function
unconvert_field($m,$J){return$J;}function
support($Rc){return
preg_match('~^(check|comment|columns|database|drop_col|dump|indexes|descidx|scheme|sql|table|trigger|view|view_trigger)$~',$Rc);}}class
Adminer{static$ee;var$error='';function
name(){return"<a href='https://www.adminer.org/'".target_blank()." id='h1'><img src='".h(preg_replace("~\\?.*~","",ME)."?file=logo.png&version=5.2.0")."' width='24' height='24' alt='' id='logo'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
connectSsl(){}function
permanentLogin($h=false){return
password_file($h);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
serverName($N){return
h($N);}function
database(){return
DB;}function
databases($dd=true){return
get_databases($dd);}function
pluginsLinks(){}function
operators(){return
driver()->operators;}function
schemas(){return
schemas();}function
queryTimeout(){return
2;}function
headers(){}function
csp(array$Db){return$Db;}function
head($Hb=null){return
true;}function
css(){$J=array();foreach(array("","-dark")as$df){$o="adminer$df.css";if(file_exists($o))$J[]="$o?v=".crc32(file_get_contents($o));}return$J;}function
loginForm(){echo"<table class='layout'>\n",adminer()->loginFormField('driver','<tr><th>'.'System'.'<td>',html_select("auth[driver]",SqlDriver::$dc,DRIVER,"loginDriver(this);")),adminer()->loginFormField('server','<tr><th>'.'Server'.'<td>','<input name="auth[server]" value="'.h(SERVER).'" title="hostname[:port]" placeholder="localhost" autocapitalize="off">'),adminer()->loginFormField('username','<tr><th>'.'Username'.'<td>','<input name="auth[username]" id="username" autofocus value="'.h($_GET["username"]).'" autocomplete="username" autocapitalize="off">'.script("const authDriver = qs('#username').form['auth[driver]']; authDriver && authDriver.onchange();")),adminer()->loginFormField('password','<tr><th>'.'Password'.'<td>','<input type="password" name="auth[password]" autocomplete="current-password">'),adminer()->loginFormField('db','<tr><th>'.'Database'.'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'),"</table>\n","<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
loginFormField($C,$Dd,$Y){return$Dd.$Y."\n";}function
login($Fe,$F){if($F=="")return
sprintf('Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.',target_blank());return
true;}function
tableName(array$bi){return
h($bi["Name"]);}function
fieldName(array$m,$Lf=0){$U=$m["full_type"];$mb=$m["comment"];return'<span title="'.h($U.($mb!=""?($U?": ":"").$mb:'')).'">'.h($m["field"]).'</span>';}function
selectLinks(array$bi,$O=""){echo'<p class="links">';$Ee=array("select"=>'Select data');if(support("table")||support("indexes"))$Ee["table"]='Show structure';$me=false;if(support("table")){$me=is_view($bi);if($me)$Ee["view"]='Alter view';else$Ee["create"]='Alter table';}if($O!==null)$Ee["edit"]='New item';$C=$bi["Name"];foreach($Ee
as$x=>$X)echo" <a href='".h(ME)."$x=".urlencode($C).($x=="edit"?$O:"")."'".bold(isset($_GET[$x])).">$X</a>";echo
doc_link(array(JUSH=>driver()->tableHelp($C,$me)),"?"),"\n";}function
foreignKeys($R){return
foreign_keys($R);}function
backwardKeys($R,$ai){return
array();}function
backwardKeysPrint(array$Da,array$K){}function
selectQuery($H,$Rh,$Pc=false){$J="</p>\n";if(!$Pc&&($sj=driver()->warnings())){$t="warnings";$J=", <a href='#$t'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$t');","")."$J<div id='$t' class='hidden'>\n$sj</div>\n";}return"<p><code class='jush-".JUSH."'>".h(str_replace("\n"," ",$H))."</code> <span class='time'>(".format_time($Rh).")</span>".(support("sql")?" <a href='".h(ME)."sql=".urlencode($H)."'>".'Edit'."</a>":"").$J;}function
sqlCommandQuery($H){return
shorten_utf8(trim($H),1000);}function
sqlPrintAfter(){}function
rowDescription($R){return"";}function
rowDescriptions(array$L,array$gd){return$L;}function
selectLink($X,array$m){}function
selectVal($X,$_,array$m,$Vf){$J=($X===null?"<i>NULL</i>":(preg_match("~char|binary|boolean~",$m["type"])&&!preg_match("~var~",$m["type"])?"<code>$X</code>":(preg_match('~json~',$m["type"])?"<code class='jush-js'>$X</code>":$X)));if(preg_match('~blob|bytea|raw|file~',$m["type"])&&!is_utf8($X))$J="<i>".lang_format(array('%d byte','%d bytes'),strlen($Vf))."</i>";return($_?"<a href='".h($_)."'".(is_url($_)?target_blank():"").">$J</a>":$J);}function
editVal($X,array$m){return$X;}function
config(){return
array();}function
tableStructurePrint(array$n,$bi=null){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr><th>".'Column'."<td>".'Type'.(support("comment")?"<td>".'Comment':"")."</thead>\n";$Uh=driver()->structuredTypes();foreach($n
as$m){echo"<tr><th>".h($m["field"]);$U=h($m["full_type"]);$c=h($m["collation"]);echo"<td><span title='$c'>".(in_array($U,(array)$Uh['User types'])?"<a href='".h(ME.'type='.urlencode($U))."'>$U</a>":$U.($c&&isset($bi["Collation"])&&$c!=$bi["Collation"]?" $c":""))."</span>",($m["null"]?" <i>NULL</i>":""),($m["auto_increment"]?" <i>".'Auto Increment'."</i>":"");$k=h($m["default"]);echo(isset($m["default"])?" <span title='".'Default value'."'>[<b>".($m["generated"]?"<code class='jush-".JUSH."'>$k</code>":$k)."</b>]</span>":""),(support("comment")?"<td>".h($m["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint(array$w){echo"<table>\n";foreach($w
as$C=>$v){ksort($v["columns"]);$Ag=array();foreach($v["columns"]as$x=>$X)$Ag[]="<i>".h($X)."</i>".($v["lengths"][$x]?"(".$v["lengths"][$x].")":"").($v["descs"][$x]?" DESC":"");echo"<tr title='".h($C)."'><th>$v[type]<td>".implode(", ",$Ag)."\n";}echo"</table>\n";}function
selectColumnsPrint(array$M,array$e){print_fieldset("select",'Select',$M);$s=0;$M[""]=array();foreach($M
as$x=>$X){$X=idx($_GET["columns"],$x,array());$d=select_input(" name='columns[$s][col]'",$e,$X["col"],($x!==""?"selectFieldChange":"selectAddRow"));echo"<div>".(driver()->functions||driver()->grouping?html_select("columns[$s][fun]",array(-1=>"")+array_filter(array('Functions'=>driver()->functions,'Aggregation'=>driver()->grouping)),$X["fun"]).on_help("event.target.value && event.target.value.replace(/ |\$/, '(') + ')'",1).script("qsl('select').onchange = function () { helpClose();".($x!==""?"":" qsl('select, input', this.parentNode).onchange();")." };","")."($d)":$d)."</div>\n";$s++;}echo"</div></fieldset>\n";}function
selectSearchPrint(array$Z,array$e,array$w){print_fieldset("search",'Search',$Z);foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT")echo"<div>(<i>".implode("</i>, <i>",array_map('Adminer\h',$v["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$s]' value='".h($_GET["fulltext"][$s])."'>",script("qsl('input').oninput = selectFieldChange;",""),checkbox("boolean[$s]",1,isset($_GET["boolean"][$s]),"BOOL"),"</div>\n";}$Sa="this.parentNode.firstChild.onchange();";foreach(array_merge((array)$_GET["where"],array(array()))as$s=>$X){if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],adminer()->operators())))echo"<div>".select_input(" name='where[$s][col]'",$e,$X["col"],($X?"selectFieldChange":"selectAddRow"),"(".'anywhere'.")"),html_select("where[$s][op]",adminer()->operators(),$X["op"],$Sa),"<input type='search' name='where[$s][val]' value='".h($X["val"])."'>",script("mixin(qsl('input'), {oninput: function () { $Sa }, onkeydown: selectSearchKeydown, onsearch: selectSearchSearch});",""),"</div>\n";}echo"</div></fieldset>\n";}function
selectOrderPrint(array$Lf,array$e,array$w){print_fieldset("sort",'Sort',$Lf);$s=0;foreach((array)$_GET["order"]as$x=>$X){if($X!=""){echo"<div>".select_input(" name='order[$s]'",$e,$X,"selectFieldChange"),checkbox("desc[$s]",1,isset($_GET["desc"][$x]),'descending')."</div>\n";$s++;}}echo"<div>".select_input(" name='order[$s]'",$e,"","selectAddRow"),checkbox("desc[$s]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".'Limit'."</legend><div>","<input type='number' name='limit' class='size' value='".intval($z)."'>",script("qsl('input').oninput = selectFieldChange;",""),"</div></fieldset>\n";}function
selectLengthPrint($si){if($si!==null)echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($si)."'>","</div></fieldset>\n";}function
selectActionPrint(array$w){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script".nonce().">\n","const indexColumns = ";$e=array();foreach($w
as$v){$Gb=reset($v["columns"]);if($v["type"]!="FULLTEXT"&&$Gb)$e[$Gb]=1;}$e[""]=1;foreach($e
as$x=>$X)json_row($x);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint(array$rc,array$e){}function
selectColumnsProcess(array$e,array$w){$M=array();$sd=array();foreach((array)$_GET["columns"]as$x=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],driver()->functions)||in_array($X["fun"],driver()->grouping)))){$M[$x]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],driver()->grouping))$sd[]=$M[$x];}}return
array($M,$sd);}function
selectSearchProcess(array$n,array$w){$J=array();foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT"&&$_GET["fulltext"][$s]!="")$J[]="MATCH (".implode(", ",array_map('Adminer\idf_escape',$v["columns"])).") AGAINST (".q($_GET["fulltext"][$s]).(isset($_GET["boolean"][$s])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$x=>$X){if("$X[col]$X[val]"!=""&&in_array($X["op"],adminer()->operators())){$yg="";$pb=" $X[op]";if(preg_match('~IN$~',$X["op"])){$Rd=process_length($X["val"]);$pb
.=" ".($Rd!=""?$Rd:"(NULL)");}elseif($X["op"]=="SQL")$pb=" $X[val]";elseif($X["op"]=="LIKE %%")$pb=" LIKE ".adminer()->processInput(idx($n,$X["col"],array()),"%$X[val]%");elseif($X["op"]=="ILIKE %%")$pb=" ILIKE ".adminer()->processInput($n[$X["col"]],"%$X[val]%");elseif($X["op"]=="FIND_IN_SET"){$yg="$X[op](".q($X["val"]).", ";$pb=")";}elseif(!preg_match('~NULL$~',$X["op"]))$pb
.=" ".adminer()->processInput($n[$X["col"]],$X["val"]);if($X["col"]!="")$J[]=$yg.driver()->convertSearch(idf_escape($X["col"]),$X,$n[$X["col"]]).$pb;else{$ib=array();foreach($n
as$C=>$m){if(isset($m["privileges"]["where"])&&(preg_match('~^[-\d.'.(preg_match('~IN$~',$X["op"])?',':'').']+$~',$X["val"])||!preg_match('~'.number_type().'|bit~',$m["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||preg_match('~char|text|enum|set~',$m["type"]))&&(!preg_match('~date|timestamp~',$m["type"])||preg_match('~^\d+-\d+-\d+~',$X["val"])))$ib[]=$yg.driver()->convertSearch(idf_escape($C),$X,$m).$pb;}$J[]=($ib?"(".implode(" OR ",$ib).")":"1 = 0");}}}return$J;}function
selectOrderProcess(array$n,array$w){$J=array();foreach((array)$_GET["order"]as$x=>$X){if($X!="")$J[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$x])?" DESC":"");}return$J;}function
selectLimitProcess(){return(isset($_GET["limit"])?intval($_GET["limit"]):50);}function
selectLengthProcess(){return(isset($_GET["text_length"])?"$_GET[text_length]":"100");}function
selectEmailProcess(array$Z,array$gd){return
false;}function
selectQueryBuild(array$M,array$Z,array$sd,array$Lf,$z,$E){return"";}function
messageQuery($H,$ti,$Pc=false){restart_session();$Fd=&get_session("queries");if(!idx($Fd,$_GET["db"]))$Fd[$_GET["db"]]=array();if(strlen($H)>1e6)$H=preg_replace('~[\x80-\xFF]+$~','',substr($H,0,1e6))."\n…";$Fd[$_GET["db"]][]=array($H,time(),$ti);$Nh="sql-".count($Fd[$_GET["db"]]);$J="<a href='#$Nh' class='toggle'>".'SQL command'."</a>\n";if(!$Pc&&($sj=driver()->warnings())){$t="warnings-".count($Fd[$_GET["db"]]);$J="<a href='#$t' class='toggle'>".'Warnings'."</a>, $J<div id='$t' class='hidden'>\n$sj</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $J<div id='$Nh' class='hidden'><pre><code class='jush-".JUSH."'>".shorten_utf8($H,1000)."</code></pre>".($ti?" <span class='time'>($ti)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($Fd[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
editRowPrint($R,array$n,$K,$Zi){}function
editFunctions(array$m){$J=($m["null"]?"NULL/":"");$Zi=isset($_GET["select"])||where($_GET);foreach(array(driver()->insertFunctions,driver()->editFunctions)as$x=>$nd){if(!$x||(!isset($_GET["call"])&&$Zi)){foreach($nd
as$mg=>$X){if(!$mg||preg_match("~$mg~",$m["type"]))$J
.="/$X";}}if($x&&$nd&&!preg_match('~set|blob|bytea|raw|file|bool~',$m["type"]))$J
.="/SQL";}if($m["auto_increment"]&&!$Zi)$J='Auto Increment';return
explode("/",$J);}function
editInput($R,array$m,$ya,$Y){if($m["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$ya value='-1' checked><i>".'original'."</i></label> ":"").($m["null"]?"<label><input type='radio'$ya value=''".($Y!==null||isset($_GET["select"])?"":" checked")."><i>NULL</i></label> ":"").enum_input("radio",$ya,$m,$Y,$Y===0?0:null);return"";}function
editHint($R,array$m,$Y){return"";}function
processInput(array$m,$Y,$r=""){if($r=="SQL")return$Y;$C=$m["field"];$J=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$r))$J="$r()";elseif(preg_match('~^current_(date|timestamp)$~',$r))$J=$r;elseif(preg_match('~^([+-]|\|\|)$~',$r))$J=idf_escape($C)." $r $J";elseif(preg_match('~^[+-] interval$~',$r))$J=idf_escape($C)." $r ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i",$Y)?$Y:$J);elseif(preg_match('~^(addtime|subtime|concat)$~',$r))$J="$r(".idf_escape($C).", $J)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$r))$J="$r($J)";return
unconvert_field($m,$J);}function
dumpOutput(){$J=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$J['gz']='gzip';return$J;}function
dumpFormat(){return(support("dump")?array('sql'=>'SQL'):array())+array('csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($j){}function
dumpTable($R,$Vh,$me=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($Vh)dump_csv(array_keys(fields($R)));}else{if($me==2){$n=array();foreach(fields($R)as$C=>$m)$n[]=idf_escape($C)." $m[full_type]";$h="CREATE TABLE ".table($R)." (".implode(", ",$n).")";}else$h=create_sql($R,$_POST["auto_increment"],$Vh);set_utf8mb4($h);if($Vh&&$h){if($Vh=="DROP+CREATE"||$me==1)echo"DROP ".($me==2?"VIEW":"TABLE")." IF EXISTS ".table($R).";\n";if($me==1)$h=remove_definer($h);echo"$h;\n\n";}}}function
dumpData($R,$Vh,$H){if($Vh){$Ne=(JUSH=="sqlite"?0:1048576);$n=array();$Od=false;if($_POST["format"]=="sql"){if($Vh=="TRUNCATE+INSERT")echo
truncate_sql($R).";\n";$n=fields($R);if(JUSH=="mssql"){foreach($n
as$m){if($m["auto_increment"]){echo"SET IDENTITY_INSERT ".table($R)." ON;\n";$Od=true;break;}}}}$I=connection()->query($H,1);if($I){$ce="";$Na="";$re=array();$od=array();$Xh="";$Sc=($R!=''?'fetch_assoc':'fetch_row');$_b=0;while($K=$I->$Sc()){if(!$re){$kj=array();foreach($K
as$X){$m=$I->fetch_field();if(idx($n[$m->name],'generated')){$od[$m->name]=true;continue;}$re[]=$m->name;$x=idf_escape($m->name);$kj[]="$x = VALUES($x)";}$Xh=($Vh=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$kj):"").";\n";}if($_POST["format"]!="sql"){if($Vh=="table"){dump_csv($re);$Vh="INSERT";}dump_csv($K);}else{if(!$ce)$ce="INSERT INTO ".table($R)." (".implode(", ",array_map('Adminer\idf_escape',$re)).") VALUES";foreach($K
as$x=>$X){if($od[$x]){unset($K[$x]);continue;}$m=$n[$x];$K[$x]=($X!==null?unconvert_field($m,preg_match(number_type(),$m["type"])&&!preg_match('~\[~',$m["full_type"])&&is_numeric($X)?$X:q(($X===false?0:$X))):"NULL");}$ih=($Ne?"\n":" ")."(".implode(",\t",$K).")";if(!$Na)$Na=$ce.$ih;elseif(JUSH=='mssql'?$_b%1000!=0:strlen($Na)+4+strlen($ih)+strlen($Xh)<$Ne)$Na
.=",$ih";else{echo$Na.$Xh;$Na=$ce.$ih;}}$_b++;}if($Na)echo$Na.$Xh;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",connection()->error)."\n";if($Od)echo"SET IDENTITY_INSERT ".table($R)." OFF;\n";}}function
dumpFilename($Md){return
friendly_url($Md!=""?$Md:(SERVER!=""?SERVER:"localhost"));}function
dumpHeaders($Md,$ff=false){$Yf=$_POST["output"];$Kc=(preg_match('~sql~',$_POST["format"])?"sql":($ff?"tar":"csv"));header("Content-Type: ".($Yf=="gz"?"application/x-gzip":($Kc=="tar"?"application/x-tar":($Kc=="sql"||$Yf!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($Yf=="gz"){ob_start(function($Q){return
gzencode($Q);},1e6);}return$Kc;}function
dumpFooter(){if($_POST["format"]=="sql")echo"-- ".gmdate("Y-m-d H:i:s e")."\n";}function
importServerPath(){return"adminer.sql";}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");return
true;}function
navigation($cf){echo"<h1>".adminer()->name()." <span class='version'>".VERSION;$nf=$_COOKIE["adminer_version"];echo" <a href='https://www.adminer.org/#download'".target_blank()." id='version'>".(version_compare(VERSION,$nf)<0?h($nf):"")."</a>","</span></h1>\n";if($cf=="auth"){$Yf="";foreach((array)$_SESSION["pwds"]as$mj=>$_h){foreach($_h
as$N=>$hj){$C=h(get_setting("vendor-$mj-$N")?:get_driver($mj));foreach($hj
as$V=>$F){if($F!==null){$Nb=$_SESSION["db"][$mj][$N][$V];foreach(($Nb?array_keys($Nb):array(""))as$j)$Yf
.="<li><a href='".h(auth_url($mj,$N,$V,$j))."'>($C) ".h($V.($N!=""?"@".adminer()->serverName($N):"").($j!=""?" - $j":""))."</a>\n";}}}}if($Yf)echo"<ul id='logins'>\n$Yf</ul>\n".script("mixin(qs('#logins'), {onmouseover: menuOver, onmouseout: menuOut});");}else{$T=array();if($_GET["ns"]!==""&&!$cf&&DB!=""){connection()->select_db(DB);$T=table_status('',true);}adminer()->syntaxHighlighting($T);adminer()->databasesPrint($cf);$ia=array();if(DB==""||!$cf){if(support("sql")){$ia[]="<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>";$ia[]="<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>";}$ia[]="<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>";}$Sd=$_GET["ns"]!==""&&!$cf&&DB!="";if($Sd)$ia[]='<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>";echo($ia?"<p class='links'>\n".implode("\n",$ia)."\n":"");if($Sd){if($T)adminer()->tablesPrint($T);else
echo"<p class='message'>".'No tables.'."</p>\n";}}}function
syntaxHighlighting(array$T){echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=5.2.0",true);if(support("sql")){echo"<script".nonce().">\n";if($T){$Ee=array();foreach($T
as$R=>$U)$Ee[]=preg_quote($R,'/');echo"var jushLinks = { ".JUSH.": [ '".js_escape(ME).(support("table")?"table=":"select=")."\$&', /\\b(".implode("|",$Ee).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.".JUSH.";\n";if(isset($_GET["sql"])||isset($_GET["trigger"])||isset($_GET["check"])){$ii=array_fill_keys(array_keys($T),array());foreach(driver()->allFields()as$R=>$n){foreach($n
as$m)$ii[$R][]=$m["field"];}echo"addEventListener('DOMContentLoaded', () => { autocompleter = jush.autocompleteSql('".idf_escape("")."', ".json_encode($ii)."); });\n";}}echo"</script>\n";}echo
script("syntaxHighlighting('".preg_replace('~^(\d\.?\d).*~s','\1',connection()->server_info)."', '".connection()->flavor."');");}function
databasesPrint($cf){$i=adminer()->databases();if(DB&&$i&&!in_array(DB,$i))array_unshift($i,DB);echo"<form action=''>\n<p id='dbs'>\n";hidden_fields_get();$Lb=script("mixin(qsl('select'), {onmousedown: dbMouseDown, onchange: dbChange});");echo"<label title='".'Database'."'>".'DB'.": ".($i?html_select("db",array(""=>"")+$i,DB).$Lb:"<input name='db' value='".h(DB)."' autocapitalize='off' size='19'>\n")."</label>","<input type='submit' value='".'Use'."'".($i?" class='hidden'":"").">\n";if(support("scheme")){if($cf!="db"&&DB!=""&&connection()->select_db(DB)){echo"<br><label>".'Schema'.": ".html_select("ns",array(""=>"")+adminer()->schemas(),$_GET["ns"])."$Lb</label>";if($_GET["ns"]!="")set_schema($_GET["ns"]);}}foreach(array("import","sql","schema","dump","privileges")as$X){if(isset($_GET[$X])){echo
input_hidden($X);break;}}echo"</p></form>\n";}function
tablesPrint(array$T){echo"<ul id='tables'>".script("mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});");foreach($T
as$R=>$P){$C=adminer()->tableName($P);if($C!="")echo'<li><a href="'.h(ME).'select='.urlencode($R).'"'.bold($_GET["select"]==$R||$_GET["edit"]==$R,"select")." title='".'Select data'."'>".'select'."</a> ",(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($R).'"'.bold(in_array($R,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"],$_GET["check"],$_GET["view"])),(is_view($P)?"view":"structure"))." title='".'Show structure'."'>$C</a>":"<span>$C</span>")."\n";}echo"</ul>\n";}}class
Plugins{private
static$ta=array('dumpFormat'=>true,'dumpOutput'=>true,'editRowPrint'=>true,'editFunctions'=>true,'config'=>true);var$plugins;var$error='';private$hooks=array();function
__construct($rg){if($rg===null){$rg=array();$Ha="adminer-plugins";if(is_dir($Ha)){foreach(glob("$Ha/*.php")as$o)$Td=include_once"./$o";}$Ed=" href='https://www.adminer.org/plugins/#use'".target_blank();if(file_exists("$Ha.php")){$Td=include_once"./$Ha.php";if(is_array($Td)){foreach($Td
as$qg)$rg[get_class($qg)]=$qg;}else$this->error
.=sprintf('%s must <a%s>return an array</a>.',"<b>$Ha.php</b>",$Ed)."<br>";}foreach(get_declared_classes()as$cb){if(!$rg[$cb]&&preg_match('~^Adminer\w~i',$cb)){$Sg=new
\ReflectionClass($cb);$ub=$Sg->getConstructor();if($ub&&$ub->getNumberOfRequiredParameters())$this->error
.=sprintf('<a%s>Configure</a> %s in %s.',$Ed,"<b>$cb</b>","<b>$Ha.php</b>")."<br>";else$rg[$cb]=new$cb;}}}$this->plugins=$rg;$la=new
Adminer;$rg[]=$la;$Sg=new
\ReflectionObject($la);foreach($Sg->getMethods()as$af){foreach($rg
as$qg){$C=$af->getName();if(method_exists($qg,$C))$this->hooks[$C][]=$qg;}}}function
__call($C,array$dg){$ua=array();foreach($dg
as$x=>$X)$ua[]=&$dg[$x];$J=null;foreach($this->hooks[$C]as$qg){$Y=call_user_func_array(array($qg,$C),$ua);if($Y!==null){if(!self::$ta[$C])return$Y;$J=$Y+(array)$J;}}return$J;}}abstract
class
Plugin{protected$translations=array();function
description(){return$this->lang('');}function
screenshot(){return"";}protected
function
lang($u,$sf=null){$ua=func_get_args();$ua[0]=idx($this->translations[LANG],$u)?:$u;return
call_user_func_array('Adminer\lang_format',$ua);}}Adminer::$ee=(function_exists('adminer_object')?adminer_object():(is_dir("adminer-plugins")||file_exists("adminer-plugins.php")?new
Plugins(null):new
Adminer));SqlDriver::$dc=array("server"=>"MySQL / MariaDB")+SqlDriver::$dc;if(!defined('Adminer\DRIVER')){define('Adminer\DRIVER',"server");if(extension_loaded("mysqli")&&$_GET["ext"]!="pdo"){class
Db
extends
\MySQLi{static$ee;var$extension="MySQLi",$flavor='';function
__construct(){parent::init();}function
attach($N,$V,$F){mysqli_report(MYSQLI_REPORT_OFF);list($Id,$sg)=explode(":",$N,2);$Qh=adminer()->connectSsl();if($Qh)$this->ssl_set($Qh['key'],$Qh['cert'],$Qh['ca'],'','');$J=@$this->real_connect(($N!=""?$Id:ini_get("mysqli.default_host")),($N.$V!=""?$V:ini_get("mysqli.default_user")),($N.$V.$F!=""?$F:ini_get("mysqli.default_pw")),null,(is_numeric($sg)?intval($sg):ini_get("mysqli.default_port")),(is_numeric($sg)?$sg:null),($Qh?($Qh['verify']!==false?2048:64):0));$this->options(MYSQLI_OPT_LOCAL_INFILE,false);return($J?'':$this->error);}function
set_charset($Ua){if(parent::set_charset($Ua))return
true;parent::set_charset('utf8');return$this->query("SET NAMES $Ua");}function
next_result(){return
self::more_results()&&parent::next_result();}function
quote($Q){return"'".$this->escape_string($Q)."'";}}}elseif(extension_loaded("mysql")&&!((ini_bool("sql.safe_mode")||ini_bool("mysql.allow_local_infile"))&&extension_loaded("pdo_mysql"))){class
Db
extends
SqlDb{private$link;function
attach($N,$V,$F){if(ini_bool("mysql.allow_local_infile"))return
sprintf('Disable %s or enable %s or %s extensions.',"'mysql.allow_local_infile'","MySQLi","PDO_MySQL");$this->link=@mysql_connect(($N!=""?$N:ini_get("mysql.default_host")),("$N$V"!=""?$V:ini_get("mysql.default_user")),("$N$V$F"!=""?$F:ini_get("mysql.default_password")),true,131072);if(!$this->link)return
mysql_error();$this->server_info=mysql_get_server_info($this->link);return'';}function
set_charset($Ua){if(function_exists('mysql_set_charset')){if(mysql_set_charset($Ua,$this->link))return
true;mysql_set_charset('utf8',$this->link);}return$this->query("SET NAMES $Ua");}function
quote($Q){return"'".mysql_real_escape_string($Q,$this->link)."'";}function
select_db($Kb){return
mysql_select_db($Kb,$this->link);}function
query($H,$Ri=false){$I=@($Ri?mysql_unbuffered_query($H,$this->link):mysql_query($H,$this->link));$this->error="";if(!$I){$this->errno=mysql_errno($this->link);$this->error=mysql_error($this->link);return
false;}if($I===true){$this->affected_rows=mysql_affected_rows($this->link);$this->info=mysql_info($this->link);return
true;}return
new
Result($I);}}class
Result{var$num_rows;private$result;private$offset=0;function
__construct($I){$this->result=$I;$this->num_rows=mysql_num_rows($I);}function
fetch_assoc(){return
mysql_fetch_assoc($this->result);}function
fetch_row(){return
mysql_fetch_row($this->result);}function
fetch_field(){$J=mysql_fetch_field($this->result,$this->offset++);$J->orgtable=$J->table;$J->charsetnr=($J->blob?63:0);return$J;}function
__destruct(){mysql_free_result($this->result);}}}elseif(extension_loaded("pdo_mysql")){class
Db
extends
PdoDb{var$extension="PDO_MySQL";function
attach($N,$V,$F){$Jf=array(\PDO::MYSQL_ATTR_LOCAL_INFILE=>false);$Qh=adminer()->connectSsl();if($Qh){if($Qh['key'])$Jf[\PDO::MYSQL_ATTR_SSL_KEY]=$Qh['key'];if($Qh['cert'])$Jf[\PDO::MYSQL_ATTR_SSL_CERT]=$Qh['cert'];if($Qh['ca'])$Jf[\PDO::MYSQL_ATTR_SSL_CA]=$Qh['ca'];if(isset($Qh['verify']))$Jf[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=$Qh['verify'];}return$this->dsn("mysql:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\d)~',';port=\1',$N)),$V,$F,$Jf);}function
set_charset($Ua){return$this->query("SET NAMES $Ua");}function
select_db($Kb){return$this->query("USE ".idf_escape($Kb));}function
query($H,$Ri=false){$this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,!$Ri);return
parent::query($H,$Ri);}}}class
Driver
extends
SqlDriver{static$Mc=array("MySQLi","MySQL","PDO_MySQL");static$oe="sql";var$unsigned=array("unsigned","zerofill","unsigned zerofill");var$operators=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","FIND_IN_SET","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");var$functions=array("char_length","date","from_unixtime","lower","round","floor","ceil","sec_to_time","time_to_sec","upper");var$grouping=array("avg","count","count distinct","group_concat","max","min","sum");static
function
connect($N,$V,$F){$f=parent::connect($N,$V,$F);if(is_string($f)){if(function_exists('iconv')&&!is_utf8($f)&&strlen($ih=iconv("windows-1250","utf-8",$f))>strlen($f))$f=$ih;return$f;}$f->set_charset(charset($f));$f->query("SET sql_quote_show_create = 1, autocommit = 1");$f->flavor=(preg_match('~MariaDB~',$f->server_info)?'maria':'mysql');add_driver(DRIVER,($f->flavor=='maria'?"MariaDB":"MySQL"));return$f;}function
__construct(Db$f){parent::__construct($f);$this->types=array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Geometry'=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),);$this->insertFunctions=array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",);$this->editFunctions=array(number_type()=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",);if(min_version('5.7.8',10.2,$f))$this->types['Strings']["json"]=4294967295;if(min_version('',10.7,$f)){$this->types['Strings']["uuid"]=128;$this->insertFunctions['uuid']='uuid';}if(min_version(9,'',$f)){$this->types['Numbers']["vector"]=16383;$this->insertFunctions['vector']='string_to_vector';}if(min_version(5.7,10.2,$f))$this->generated=array("STORED","VIRTUAL");}function
unconvertFunction(array$m){return(preg_match("~binary~",$m["type"])?"<code class='jush-sql'>UNHEX</code>":($m["type"]=="bit"?doc_link(array('sql'=>'bit-value-literals.html'),"<code>b''</code>"):(preg_match("~geometry|point|linestring|polygon~",$m["type"])?"<code class='jush-sql'>GeomFromText</code>":"")));}function
insert($R,array$O){return($O?parent::insert($R,$O):queries("INSERT INTO ".table($R)." ()\nVALUES ()"));}function
insertUpdate($R,array$L,array$G){$e=array_keys(reset($L));$yg="INSERT INTO ".table($R)." (".implode(", ",$e).") VALUES\n";$kj=array();foreach($e
as$x)$kj[$x]="$x = VALUES($x)";$Xh="\nON DUPLICATE KEY UPDATE ".implode(", ",$kj);$kj=array();$y=0;foreach($L
as$O){$Y="(".implode(", ",$O).")";if($kj&&(strlen($yg)+$y+strlen($Y)+strlen($Xh)>1e6)){if(!queries($yg.implode(",\n",$kj).$Xh))return
false;$kj=array();$y=0;}$kj[]=$Y;$y+=strlen($Y)+2;}return
queries($yg.implode(",\n",$kj).$Xh);}function
slowQuery($H,$ui){if(min_version('5.7.8','10.1.2')){if($this->conn->flavor=='maria')return"SET STATEMENT max_statement_time=$ui FOR $H";elseif(preg_match('~^(SELECT\b)(.+)~is',$H,$B))return"$B[1] /*+ MAX_EXECUTION_TIME(".($ui*1000).") */ $B[2]";}}function
convertSearch($u,array$X,array$m){return(preg_match('~char|text|enum|set~',$m["type"])&&!preg_match("~^utf8~",$m["collation"])&&preg_match('~[\x80-\xFF]~',$X['val'])?"CONVERT($u USING ".charset($this->conn).")":$u);}function
warnings(){$I=$this->conn->query("SHOW WARNINGS");if($I&&$I->num_rows){ob_start();print_select_result($I);return
ob_get_clean();}}function
tableHelp($C,$me=false){$He=($this->conn->flavor=='maria');if(information_schema(DB))return
strtolower("information-schema-".($He?"$C-table/":str_replace("_","-",$C)."-table.html"));if(DB=="mysql")return($He?"mysql$C-table/":"system-schema.html");}function
hasCStyleEscapes(){static$Pa;if($Pa===null){$Oh=get_val("SHOW VARIABLES LIKE 'sql_mode'",1,$this->conn);$Pa=(strpos($Oh,'NO_BACKSLASH_ESCAPES')===false);}return$Pa;}function
engines(){$J=array();foreach(get_rows("SHOW ENGINES")as$K){if(preg_match("~YES|DEFAULT~",$K["Support"]))$J[]=$K["Engine"];}return$J;}}function
idf_escape($u){return"`".str_replace("`","``",$u)."`";}function
table($u){return
idf_escape($u);}function
get_databases($dd){$J=get_session("dbs");if($J===null){$H="SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME";$J=($dd?slow_query($H):get_vals($H));restart_session();set_session("dbs",$J);stop_session();}return$J;}function
limit($H,$Z,$z,$D=0,$vh=" "){return" $H$Z".($z?$vh."LIMIT $z".($D?" OFFSET $D":""):"");}function
limit1($R,$H,$Z,$vh="\n"){return
limit($H,$Z,1,0,$vh);}function
db_collation($j,array$hb){$J=null;$h=get_val("SHOW CREATE DATABASE ".idf_escape($j),1);if(preg_match('~ COLLATE ([^ ]+)~',$h,$B))$J=$B[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$h,$B))$J=$hb[$B[1]][-1];return$J;}function
logged_user(){return
get_val("SELECT USER()");}function
tables_list(){return
get_key_vals("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");}function
count_tables(array$i){$J=array();foreach($i
as$j)$J[$j]=count(get_vals("SHOW TABLES IN ".idf_escape($j)));return$J;}function
table_status($C="",$Qc=false){$J=array();foreach(get_rows($Qc?"SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($C!=""?"AND TABLE_NAME = ".q($C):"ORDER BY Name"):"SHOW TABLE STATUS".($C!=""?" LIKE ".q(addcslashes($C,"%_\\")):""))as$K){if($K["Engine"]=="InnoDB")$K["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\1',$K["Comment"]);if(!isset($K["Engine"]))$K["Comment"]="";if($C!="")$K["Name"]=$C;$J[$K["Name"]]=$K;}return$J;}function
is_view(array$S){return$S["Engine"]===null;}function
fk_support(array$S){return
preg_match('~InnoDB|IBMDB2I'.(min_version(5.6)?'|NDB':'').'~i',$S["Engine"]);}function
fields($R){$He=(connection()->flavor=='maria');$J=array();foreach(get_rows("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".q($R)." ORDER BY ORDINAL_POSITION")as$K){$m=$K["COLUMN_NAME"];$U=$K["COLUMN_TYPE"];$pd=$K["GENERATION_EXPRESSION"];$Nc=$K["EXTRA"];preg_match('~^(VIRTUAL|PERSISTENT|STORED)~',$Nc,$od);preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',$U,$Ke);$k=$K["COLUMN_DEFAULT"];if($k!=""){$le=preg_match('~text|json~',$Ke[1]);if(!$He&&$le)$k=preg_replace("~^(_\w+)?('.*')$~",'\2',stripslashes($k));if($He||$le){$k=($k=="NULL"?null:preg_replace_callback("~^'(.*)'$~",function($B){return
stripslashes(str_replace("''","'",$B[1]));},$k));}if(!$He&&preg_match('~binary~',$Ke[1])&&preg_match('~^0x(\w*)$~',$k,$B))$k=pack("H*",$B[1]);}$J[$m]=array("field"=>$m,"full_type"=>$U,"type"=>$Ke[1],"length"=>$Ke[2],"unsigned"=>ltrim($Ke[3].$Ke[4]),"default"=>($od?($He?$pd:stripslashes($pd)):$k),"null"=>($K["IS_NULLABLE"]=="YES"),"auto_increment"=>($Nc=="auto_increment"),"on_update"=>(preg_match('~\bon update (\w+)~i',$Nc,$B)?$B[1]:""),"collation"=>$K["COLLATION_NAME"],"privileges"=>array_flip(explode(",","$K[PRIVILEGES],where,order")),"comment"=>$K["COLUMN_COMMENT"],"primary"=>($K["COLUMN_KEY"]=="PRI"),"generated"=>($od[1]=="PERSISTENT"?"STORED":$od[1]),);}return$J;}function
indexes($R,$g=null){$J=array();foreach(get_rows("SHOW INDEX FROM ".table($R),$g)as$K){$C=$K["Key_name"];$J[$C]["type"]=($C=="PRIMARY"?"PRIMARY":($K["Index_type"]=="FULLTEXT"?"FULLTEXT":($K["Non_unique"]?($K["Index_type"]=="SPATIAL"?"SPATIAL":"INDEX"):"UNIQUE")));$J[$C]["columns"][]=$K["Column_name"];$J[$C]["lengths"][]=($K["Index_type"]=="SPATIAL"?null:$K["Sub_part"]);$J[$C]["descs"][]=null;}return$J;}function
foreign_keys($R){static$mg='(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';$J=array();$Ab=get_val("SHOW CREATE TABLE ".table($R),1);if($Ab){preg_match_all("~CONSTRAINT ($mg) FOREIGN KEY ?\\(((?:$mg,? ?)+)\\) REFERENCES ($mg)(?:\\.($mg))? \\(((?:$mg,? ?)+)\\)(?: ON DELETE (".driver()->onActions."))?(?: ON UPDATE (".driver()->onActions."))?~",$Ab,$Le,PREG_SET_ORDER);foreach($Le
as$B){preg_match_all("~$mg~",$B[2],$Ih);preg_match_all("~$mg~",$B[5],$mi);$J[idf_unescape($B[1])]=array("db"=>idf_unescape($B[4]!=""?$B[3]:$B[4]),"table"=>idf_unescape($B[4]!=""?$B[4]:$B[3]),"source"=>array_map('Adminer\idf_unescape',$Ih[0]),"target"=>array_map('Adminer\idf_unescape',$mi[0]),"on_delete"=>($B[6]?:"RESTRICT"),"on_update"=>($B[7]?:"RESTRICT"),);}}return$J;}function
view($C){return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU','',get_val("SHOW CREATE VIEW ".table($C),1)));}function
collations(){$J=array();foreach(get_rows("SHOW COLLATION")as$K){if($K["Default"])$J[$K["Charset"]][-1]=$K["Collation"];else$J[$K["Charset"]][]=$K["Collation"];}ksort($J);foreach($J
as$x=>$X)sort($J[$x]);return$J;}function
information_schema($j){return($j=="information_schema")||(min_version(5.5)&&$j=="performance_schema");}function
error(){return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",connection()->error));}function
create_database($j,$c){return
queries("CREATE DATABASE ".idf_escape($j).($c?" COLLATE ".q($c):""));}function
drop_databases(array$i){$J=apply_queries("DROP DATABASE",$i,'Adminer\idf_escape');restart_session();set_session("dbs",null);return$J;}function
rename_database($C,$c){$J=false;if(create_database($C,$c)){$T=array();$pj=array();foreach(tables_list()as$R=>$U){if($U=='VIEW')$pj[]=$R;else$T[]=$R;}$J=(!$T&&!$pj)||move_tables($T,$pj,$C);drop_databases($J?array(DB):array());}return$J;}function
auto_increment(){$Aa=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$v){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$v["columns"],true)){$Aa="";break;}if($v["type"]=="PRIMARY")$Aa=" UNIQUE";}}return" AUTO_INCREMENT$Aa";}function
alter_table($R,$C,array$n,array$fd,$mb,$uc,$c,$_a,$ig){$b=array();foreach($n
as$m){if($m[1]){$k=$m[1][3];if(preg_match('~ GENERATED~',$k)){$m[1][3]=(connection()->flavor=='maria'?"":$m[1][2]);$m[1][2]=$k;}$b[]=($R!=""?($m[0]!=""?"CHANGE ".idf_escape($m[0]):"ADD"):" ")." ".implode($m[1]).($R!=""?$m[2]:"");}else$b[]="DROP ".idf_escape($m[0]);}$b=array_merge($b,$fd);$P=($mb!==null?" COMMENT=".q($mb):"").($uc?" ENGINE=".q($uc):"").($c?" COLLATE ".q($c):"").($_a!=""?" AUTO_INCREMENT=$_a":"");if($R=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$b)."\n)$P$ig");if($R!=$C)$b[]="RENAME TO ".table($C);if($P)$b[]=ltrim($P);return($b||$ig?queries("ALTER TABLE ".table($R)."\n".implode(",\n",$b).$ig):true);}function
alter_indexes($R,$b){$Ta=array();foreach($b
as$X)$Ta[]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($R).implode(",",$Ta));}function
truncate_tables(array$T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views(array$pj){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$pj)));}function
drop_tables(array$T){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$T)));}function
move_tables(array$T,array$pj,$mi){$Wg=array();foreach($T
as$R)$Wg[]=table($R)." TO ".idf_escape($mi).".".table($R);if(!$Wg||queries("RENAME TABLE ".implode(", ",$Wg))){$Sb=array();foreach($pj
as$R)$Sb[table($R)]=view($R);connection()->select_db($mi);$j=idf_escape(DB);foreach($Sb
as$C=>$oj){if(!queries("CREATE VIEW $C AS ".str_replace(" $j."," ",$oj["select"]))||!queries("DROP VIEW $j.$C"))return
false;}return
true;}return
false;}function
copy_tables(array$T,array$pj,$mi){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($T
as$R){$C=($mi==DB?table("copy_$R"):idf_escape($mi).".".table($R));if(($_POST["overwrite"]&&!queries("\nDROP TABLE IF EXISTS $C"))||!queries("CREATE TABLE $C LIKE ".table($R))||!queries("INSERT INTO $C SELECT * FROM ".table($R)))return
false;foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")))as$K){$Ki=$K["Trigger"];if(!queries("CREATE TRIGGER ".($mi==DB?idf_escape("copy_$Ki"):idf_escape($mi).".".idf_escape($Ki))." $K[Timing] $K[Event] ON $C FOR EACH ROW\n$K[Statement];"))return
false;}}foreach($pj
as$R){$C=($mi==DB?table("copy_$R"):idf_escape($mi).".".table($R));$oj=view($R);if(($_POST["overwrite"]&&!queries("DROP VIEW IF EXISTS $C"))||!queries("CREATE VIEW $C AS $oj[select]"))return
false;}return
true;}function
trigger($C,$R){if($C=="")return
array();$L=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($C));return
reset($L);}function
triggers($R){$J=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")))as$K)$J[$K["Trigger"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($C,$U){$ra=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$Jh="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$wc=driver()->enumLength;$Pi="((".implode("|",array_merge(array_keys(driver()->types()),$ra)).")\\b(?:\\s*\\(((?:[^'\")]|$wc)++)\\))?"."\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";$mg="$Jh*(".($U=="FUNCTION"?"":driver()->inout).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$Pi";$h=get_val("SHOW CREATE $U ".idf_escape($C),2);preg_match("~\\(((?:$mg\\s*,?)*)\\)\\s*".($U=="FUNCTION"?"RETURNS\\s+$Pi\\s+":"")."(.*)~is",$h,$B);$n=array();preg_match_all("~$mg\\s*,?~is",$B[1],$Le,PREG_SET_ORDER);foreach($Le
as$cg)$n[]=array("field"=>str_replace("``","`",$cg[2]).$cg[3],"type"=>strtolower($cg[5]),"length"=>preg_replace_callback("~$wc~s",'Adminer\normalize_enum',$cg[6]),"unsigned"=>strtolower(preg_replace('~\s+~',' ',trim("$cg[8] $cg[7]"))),"null"=>true,"full_type"=>$cg[4],"inout"=>strtoupper($cg[1]),"collation"=>strtolower($cg[9]),);return
array("fields"=>$n,"comment"=>get_val("SELECT ROUTINE_COMMENT FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = ".q($C)),)+($U!="FUNCTION"?array("definition"=>$B[11]):array("returns"=>array("type"=>$B[12],"length"=>$B[13],"unsigned"=>$B[15],"collation"=>$B[16]),"definition"=>$B[17],"language"=>"SQL",));}function
routines(){return
get_rows("SELECT ROUTINE_NAME AS SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()");}function
routine_languages(){return
array();}function
routine_id($C,array$K){return
idf_escape($C);}function
last_id($I){return
get_val("SELECT LAST_INSERT_ID()");}function
explain(Db$f,$H){return$f->query("EXPLAIN ".(min_version(5.1)&&!min_version(5.7)?"PARTITIONS ":"").$H);}function
found_rows(array$S,array$Z){return($Z||$S["Engine"]!="InnoDB"?null:$S["Rows"]);}function
create_sql($R,$_a,$Vh){$J=get_val("SHOW CREATE TABLE ".table($R),1);if(!$_a)$J=preg_replace('~ AUTO_INCREMENT=\d+~','',$J);return$J;}function
truncate_sql($R){return"TRUNCATE ".table($R);}function
use_sql($Kb){return"USE ".idf_escape($Kb);}function
trigger_sql($R){$J="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")),null,"-- ")as$K)$J
.="\nCREATE TRIGGER ".idf_escape($K["Trigger"])." $K[Timing] $K[Event] ON ".table($K["Table"])." FOR EACH ROW\n$K[Statement];;\n";return$J;}function
show_variables(){return
get_rows("SHOW VARIABLES");}function
show_status(){return
get_rows("SHOW STATUS");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
convert_field(array$m){if(preg_match("~binary~",$m["type"]))return"HEX(".idf_escape($m["field"]).")";if($m["type"]=="bit")return"BIN(".idf_escape($m["field"])." + 0)";if(preg_match("~geometry|point|linestring|polygon~",$m["type"]))return(min_version(8)?"ST_":"")."AsWKT(".idf_escape($m["field"]).")";}function
unconvert_field(array$m,$J){if(preg_match("~binary~",$m["type"]))$J="UNHEX($J)";if($m["type"]=="bit")$J="CONVERT(b$J, UNSIGNED)";if(preg_match("~geometry|point|linestring|polygon~",$m["type"])){$yg=(min_version(8)?"ST_":"");$J=$yg."GeomFromText($J, $yg"."SRID($m[field]))";}return$J;}function
support($Rc){return!preg_match("~scheme|sequence|type|view_trigger|materializedview".(min_version(8)?"":"|descidx".(min_version(5.1)?"":"|event|partitioning")).(min_version('8.0.16','10.2.1')?"":"|check")."~",$Rc);}function
kill_process($X){return
queries("KILL ".number($X));}function
connection_id(){return"SELECT CONNECTION_ID()";}function
max_connections(){return
get_val("SELECT @@max_connections");}function
types(){return
array();}function
type_values($t){return"";}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($kh,$g=null){return
true;}}define('Adminer\JUSH',Driver::$oe);define('Adminer\SERVER',$_GET[DRIVER]);define('Adminer\DB',$_GET["db"]);define('Adminer\ME',preg_replace('~\?.*~','',relative_uri()).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').($_GET["ext"]?"ext=".urlencode($_GET["ext"]).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));function
page_header($wi,$l="",$Ma=array(),$xi=""){page_headers();if(is_ajax()&&$l){page_messages($l);exit;}if(!ob_get_level())ob_start('ob_gzhandler',4096);$yi=$wi.($xi!=""?": $xi":"");$zi=strip_tags($yi.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".adminer()->name());echo'<!DOCTYPE html>
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>',$zi,'</title>
<link rel="stylesheet" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=5.2.0"),'">
';$Eb=adminer()->css();$Ad=false;$zd=false;foreach($Eb
as$o){if(strpos($o,"adminer.css")!==false)$Ad=true;if(strpos($o,"adminer-dark.css")!==false)$zd=true;}$Hb=($Ad?($zd?null:false):($zd?:null));$Te=" media='(prefers-color-scheme: dark)'";if($Hb!==false)echo"<link rel='stylesheet'".($Hb?"":$Te)." href='".h(preg_replace("~\\?.*~","",ME)."?file=dark.css&version=5.2.0")."'>\n";echo"<meta name='color-scheme' content='".($Hb===null?"light dark":($Hb?"dark":"light"))."'>\n",script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=5.2.0");if(adminer()->head($Hb))echo"<link rel='icon' href='data:image/gif;base64,R0lGODlhEAAQAJEAAAQCBPz+/PwCBAROZCH5BAEAAAAALAAAAAAQABAAAAI2hI+pGO1rmghihiUdvUBnZ3XBQA7f05mOak1RWXrNq5nQWHMKvuoJ37BhVEEfYxQzHjWQ5qIAADs='>\n","<link rel='apple-touch-icon' href='".h(preg_replace("~\\?.*~","",ME)."?file=logo.png&version=5.2.0")."'>\n";foreach($Eb
as$X)echo"<link rel='stylesheet'".(preg_match('~-dark\.~',$X)&&!$Hb?$Te:"")." href='".h($X)."'>\n";echo"\n<body class='".'ltr'." nojs'>\n";$o=get_temp_dir()."/adminer.version";if(!$_COOKIE["adminer_version"]&&function_exists('openssl_verify')&&file_exists($o)&&filemtime($o)+86400>time()){$nj=unserialize(file_get_contents($o));$Gg="-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwqWOVuF5uw7/+Z70djoK
RlHIZFZPO0uYRezq90+7Amk+FDNd7KkL5eDve+vHRJBLAszF/7XKXe11xwliIsFs
DFWQlsABVZB3oisKCBEuI71J4kPH8dKGEWR9jDHFw3cWmoH3PmqImX6FISWbG3B8
h7FIx3jEaw5ckVPVTeo5JRm/1DZzJxjyDenXvBQ/6o9DgZKeNDgxwKzH+sw9/YCO
jHnq1cFpOIISzARlrHMa/43YfeNRAm/tsBXjSxembBPo7aQZLAWHmaj5+K19H10B
nCpz9Y++cipkVEiKRGih4ZEvjoFysEOdRLj6WiD/uUNky4xGeA6LaJqh5XpkFkcQ
fQIDAQAB
-----END PUBLIC KEY-----
";if(openssl_verify($nj["version"],base64_decode($nj["signature"]),$Gg)==1)$_COOKIE["adminer_version"]=$nj["version"];}echo
script("mixin(document.body, {onkeydown: bodyKeydown, onclick: bodyClick".(isset($_COOKIE["adminer_version"])?"":", onload: partial(verifyVersion, '".VERSION."', '".js_escape(ME)."', '".get_token()."')")."});
document.body.classList.replace('nojs', 'js');
const offlineMessage = '".js_escape('You are offline.')."';
const thousandsSeparator = '".js_escape(',')."';"),"<div id='help' class='jush-".JUSH." jsonly hidden'></div>\n",script("mixin(qs('#help'), {onmouseover: () => { helpOpen = 1; }, onmouseout: helpMouseout});"),"<div id='content'>\n","<span id='menuopen' class='jsonly'>".icon("move","","menu","")."</span>".script("qs('#menuopen').onclick = event => { qs('#foot').classList.toggle('foot'); event.stopPropagation(); }");if($Ma!==null){$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?:".").'">'.get_driver(DRIVER).'</a> » ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$N=adminer()->serverName(SERVER);$N=($N!=""?$N:'Server');if($Ma===false)echo"$N\n";else{echo"<a href='".h($_)."' accesskey='1' title='Alt+Shift+1'>$N</a> » ";if($_GET["ns"]!=""||(DB!=""&&is_array($Ma)))echo'<a href="'.h($_."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> » ';if(is_array($Ma)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> » ';foreach($Ma
as$x=>$X){$Ub=(is_array($X)?$X[1]:h($X));if($Ub!="")echo"<a href='".h(ME."$x=").urlencode(is_array($X)?$X[0]:$X)."'>$Ub</a> » ";}}echo"$wi\n";}}echo"<h2>$yi</h2>\n","<div id='ajaxstatus' class='jsonly hidden'></div>\n";restart_session();page_messages($l);$i=&get_session("dbs");if(DB!=""&&$i&&!in_array(DB,$i,true))$i=null;stop_session();define('Adminer\PAGE_HEADER',1);}function
page_headers(){header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach(adminer()->csp(csp())as$Db){$Cd=array();foreach($Db
as$x=>$X)$Cd[]="$x $X";header("Content-Security-Policy: ".implode("; ",$Cd));}adminer()->headers();}function
csp(){return
array(array("script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self'","frame-src"=>"https://www.adminer.org","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",),);}function
get_nonce(){static$pf;if(!$pf)$pf=base64_encode(rand_string());return$pf;}function
page_messages($l){$aj=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$Ze=idx($_SESSION["messages"],$aj);if($Ze){echo"<div class='message'>".implode("</div>\n<div class='message'>",$Ze)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$aj]);}if($l)echo"<div class='error'>$l</div>\n";if(adminer()->error)echo"<div class='error'>".adminer()->error."</div>\n";}function
page_footer($cf=""){echo"</div>\n\n<div id='foot' class='foot'>\n<div id='menu'>\n";adminer()->navigation($cf);echo"</div>\n";if($cf!="auth")echo'<form action="" method="post">
<p class="logout">
<span>',h($_GET["username"])."\n",'</span>
<input type="submit" name="logout" value="Logout" id="logout">
',input_token(),'</form>
';echo"</div>\n\n",script("setupSubmitHighlight(document);");}function
int32($hf){while($hf>=2147483648)$hf-=4294967296;while($hf<=-2147483649)$hf+=4294967296;return(int)$hf;}function
long2str(array$W,$rj){$ih='';foreach($W
as$X)$ih
.=pack('V',$X);if($rj)return
substr($ih,0,end($W));return$ih;}function
str2long($ih,$rj){$W=array_values(unpack('V*',str_pad($ih,4*ceil(strlen($ih)/4),"\0")));if($rj)$W[]=strlen($ih);return$W;}function
xxtea_mx($yj,$xj,$Yh,$pe){return
int32((($yj>>5&0x7FFFFFF)^$xj<<2)+(($xj>>3&0x1FFFFFFF)^$yj<<4))^int32(($Yh^$xj)+($pe^$yj));}function
encrypt_string($Th,$x){if($Th=="")return"";$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($Th,true);$hf=count($W)-1;$yj=$W[$hf];$xj=$W[0];$Hg=floor(6+52/($hf+1));$Yh=0;while($Hg-->0){$Yh=int32($Yh+0x9E3779B9);$lc=$Yh>>2&3;for($ag=0;$ag<$hf;$ag++){$xj=$W[$ag+1];$gf=xxtea_mx($yj,$xj,$Yh,$x[$ag&3^$lc]);$yj=int32($W[$ag]+$gf);$W[$ag]=$yj;}$xj=$W[0];$gf=xxtea_mx($yj,$xj,$Yh,$x[$ag&3^$lc]);$yj=int32($W[$hf]+$gf);$W[$hf]=$yj;}return
long2str($W,false);}function
decrypt_string($Th,$x){if($Th=="")return"";if(!$x)return
false;$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($Th,false);$hf=count($W)-1;$yj=$W[$hf];$xj=$W[0];$Hg=floor(6+52/($hf+1));$Yh=int32($Hg*0x9E3779B9);while($Yh){$lc=$Yh>>2&3;for($ag=$hf;$ag>0;$ag--){$yj=$W[$ag-1];$gf=xxtea_mx($yj,$xj,$Yh,$x[$ag&3^$lc]);$xj=int32($W[$ag]-$gf);$W[$ag]=$xj;}$yj=$W[$hf];$gf=xxtea_mx($yj,$xj,$Yh,$x[$ag&3^$lc]);$xj=int32($W[0]-$gf);$W[0]=$xj;$Yh=int32($Yh-0x9E3779B9);}return
long2str($W,true);}$og=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($x)=explode(":",$X);$og[$x]=$X;}}function
add_invalid_login(){$Fa=get_temp_dir()."/adminer.invalid";foreach(glob("$Fa*")?:array($Fa)as$o){$q=file_open_lock($o);if($q)break;}if(!$q)$q=file_open_lock("$Fa-".rand_string());if(!$q)return;$he=unserialize(stream_get_contents($q));$ti=time();if($he){foreach($he
as$ie=>$X){if($X[0]<$ti)unset($he[$ie]);}}$ge=&$he[adminer()->bruteForceKey()];if(!$ge)$ge=array($ti+30*60,0);$ge[1]++;file_write_unlock($q,serialize($he));}function
check_invalid_login(array&$og){$he=array();foreach(glob(get_temp_dir()."/adminer.invalid*")as$o){$q=file_open_lock($o);if($q){$he=unserialize(stream_get_contents($q));file_unlock($q);break;}}$ge=idx($he,adminer()->bruteForceKey(),array());$of=($ge[1]>29?$ge[0]-time():0);if($of>0)auth_error(lang_format(array('Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'),ceil($of/60)),$og);}$za=$_POST["auth"];if($za){session_regenerate_id();$mj=$za["driver"];$N=$za["server"];$V=$za["username"];$F=(string)$za["password"];$j=$za["db"];set_password($mj,$N,$V,$F);$_SESSION["db"][$mj][$N][$V][$j]=true;if($za["permanent"]){$x=implode("-",array_map('base64_encode',array($mj,$N,$V,$j)));$Bg=adminer()->permanentLogin(true);$og[$x]="$x:".base64_encode($Bg?encrypt_string($F,$Bg):"");cookie("adminer_permanent",implode(" ",$og));}if(count($_POST)==1||DRIVER!=$mj||SERVER!=$N||$_GET["username"]!==$V||DB!=$j)redirect(auth_url($mj,$N,$V,$j));}elseif($_POST["logout"]&&(!$_SESSION["token"]||verify_token())){foreach(array("pwds","db","dbs","queries")as$x)set_session($x,null);unset_permanent($og);redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.'.' '.'Thanks for using Adminer, consider <a href="https://www.adminer.org/en/donation/">donating</a>.');}elseif($og&&!$_SESSION["pwds"]){session_regenerate_id();$Bg=adminer()->permanentLogin();foreach($og
as$x=>$X){list(,$bb)=explode(":",$X);list($mj,$N,$V,$j)=array_map('base64_decode',explode("-",$x));set_password($mj,$N,$V,decrypt_string(base64_decode($bb),$Bg));$_SESSION["db"][$mj][$N][$V][$j]=true;}}function
unset_permanent(array&$og){foreach($og
as$x=>$X){list($mj,$N,$V,$j)=array_map('base64_decode',explode("-",$x));if($mj==DRIVER&&$N==SERVER&&$V==$_GET["username"]&&$j==DB)unset($og[$x]);}cookie("adminer_permanent",implode(" ",$og));}function
auth_error($l,array&$og){$Ah=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$Ah]||$_GET[$Ah])&&!$_SESSION["token"])$l='Session expired, please login again.';else{restart_session();add_invalid_login();$F=get_password();if($F!==null){if($F===false)$l
.=($l?'<br>':'').sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.',target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent($og);}}if(!$_COOKIE[$Ah]&&$_GET[$Ah]&&ini_bool("session.use_only_cookies"))$l='Session support must be enabled.';$dg=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?:rand_string()),$dg["lifetime"]);if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);page_header('Login',$l,null);echo"<form action='' method='post'>\n","<div>";if(hidden_fields($_POST,array("auth")))echo"<p class='message'>".'The action will be performed after successful login with the same credentials.'."\n";echo"</div>\n";adminer()->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists('Adminer\Db')){unset($_SESSION["pwds"][DRIVER]);unset_permanent($og);page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",Driver::$Mc)),false);page_footer("auth");exit;}$f='';if(isset($_GET["username"])&&is_string(get_password())){list($Id,$sg)=explode(":",SERVER,2);if(preg_match('~^\s*([-+]?\d+)~',$sg,$B)&&($B[1]<1024||$B[1]>65535))auth_error('Connecting to privileged ports is not allowed.',$og);check_invalid_login($og);$Cb=adminer()->credentials();$f=Driver::connect($Cb[0],$Cb[1],$Cb[2]);if(is_object($f)){Db::$ee=$f;Driver::$ee=new
Driver($f);if($f->flavor)save_settings(array("vendor-".DRIVER."-".SERVER=>get_driver(DRIVER)));}}$Fe=null;if(!is_object($f)||($Fe=adminer()->login($_GET["username"],get_password()))!==true){$l=(is_string($f)?nl_br(h($f)):(is_string($Fe)?$Fe:'Invalid credentials.')).(preg_match('~^ | $~',get_password())?'<br>'.'There is a space in the input password which might be the cause.':'');auth_error($l,$og);}if($_POST["logout"]&&$_SESSION["token"]&&!verify_token()){page_header('Logout','Invalid CSRF token. Send the form again.');page_footer("db");exit;}if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);stop_session(true);if($za&&$_POST["token"])$_POST["token"]=get_token();$l='';if($_POST){if(!verify_token()){$Zd="max_input_vars";$Re=ini_get($Zd);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$x){$X=ini_get($x);if($X&&(!$Re||$X<$Re)){$Zd=$x;$Re=$X;}}}$l=(!$_POST["token"]&&$Re?sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"'$Zd'"):'Invalid CSRF token. Send the form again.'.' '.'If you did not send this request from Adminer then close this page.');}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$l=sprintf('Too big POST data. Reduce the data or increase the %s configuration directive.',"'post_max_size'");if(isset($_GET["sql"]))$l
.=' '.'You can upload a big SQL file via FTP and import it from server.';}function
print_select_result($I,$g=null,array$Pf=array(),$z=0){$Ee=array();$w=array();$e=array();$Ka=array();$Qi=array();$J=array();for($s=0;(!$z||$s<$z)&&($K=$I->fetch_row());$s++){if(!$s){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr>";for($ne=0;$ne<count($K);$ne++){$m=$I->fetch_field();$C=$m->name;$Of=(isset($m->orgtable)?$m->orgtable:"");$Nf=(isset($m->orgname)?$m->orgname:$C);if($Pf&&JUSH=="sql")$Ee[$ne]=($C=="table"?"table=":($C=="possible_keys"?"indexes=":null));elseif($Of!=""){if(isset($m->table))$J[$m->table]=$Of;if(!isset($w[$Of])){$w[$Of]=array();foreach(indexes($Of,$g)as$v){if($v["type"]=="PRIMARY"){$w[$Of]=array_flip($v["columns"]);break;}}$e[$Of]=$w[$Of];}if(isset($e[$Of][$Nf])){unset($e[$Of][$Nf]);$w[$Of][$Nf]=$ne;$Ee[$ne]=$Of;}}if($m->charsetnr==63)$Ka[$ne]=true;$Qi[$ne]=$m->type;echo"<th".($Of!=""||$m->name!=$Nf?" title='".h(($Of!=""?"$Of.":"").$Nf)."'":"").">".h($C).($Pf?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($C),'mariadb'=>"explain/#the-columns-in-explain-select",)):"");}echo"</thead>\n";}echo"<tr>";foreach($K
as$x=>$X){$_="";if(isset($Ee[$x])&&!$e[$Ee[$x]]){if($Pf&&JUSH=="sql"){$R=$K[array_search("table=",$Ee)];$_=ME.$Ee[$x].urlencode($Pf[$R]!=""?$Pf[$R]:$R);}else{$_=ME."edit=".urlencode($Ee[$x]);foreach($w[$Ee[$x]]as$fb=>$ne)$_
.="&where".urlencode("[".bracket_escape($fb)."]")."=".urlencode($K[$ne]);}}elseif(is_url($X))$_=$X;if($X===null)$X="<i>NULL</i>";elseif($Ka[$x]&&!is_utf8($X))$X="<i>".lang_format(array('%d byte','%d bytes'),strlen($X))."</i>";else{$X=h($X);if($Qi[$x]==254)$X="<code>$X</code>";}if($_)$X="<a href='".h($_)."'".(is_url($_)?target_blank():'').">$X</a>";echo"<td".($Qi[$x]<=9||$Qi[$x]==246?" class='number'":"").">$X";}}echo($s?"</table>\n</div>":"<p class='message'>".'No rows.')."\n";return$J;}function
referencable_primary($th){$J=array();foreach(table_status('',true)as$di=>$R){if($di!=$th&&fk_support($R)){foreach(fields($di)as$m){if($m["primary"]){if($J[$di]){unset($J[$di]);break;}$J[$di]=$m;}}}}return$J;}function
textarea($C,$Y,$L=10,$ib=80){echo"<textarea name='".h($C)."' rows='$L' cols='$ib' class='sqlarea jush-".JUSH."' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
select_input($ya,array$Jf,$Y="",$Df="",$pg=""){$li=($Jf?"select":"input");return"<$li$ya".($Jf?"><option value=''>$pg".optionlist($Jf,$Y,true)."</select>":" size='10' value='".h($Y)."' placeholder='$pg'>").($Df?script("qsl('$li').onchange = $Df;",""):"");}function
json_row($x,$X=null){static$Xc=true;if($Xc)echo"{";if($x!=""){echo($Xc?"":",")."\n\t\"".addcslashes($x,"\r\n\t\"\\/").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\/").'"':'null');$Xc=false;}else{echo"\n}\n";$Xc=true;}}function
edit_type($x,array$m,array$hb,array$hd=array(),array$Oc=array()){$U=$m["type"];echo"<td><select name='".h($x)."[type]' class='type' aria-labelledby='label-type'>";if($U&&!array_key_exists($U,driver()->types())&&!isset($hd[$U])&&!in_array($U,$Oc))$Oc[]=$U;$Uh=driver()->structuredTypes();if($hd)$Uh['Foreign keys']=$hd;echo
optionlist(array_merge($Oc,$Uh),$U),"</select><td>","<input name='".h($x)."[length]' value='".h($m["length"])."' size='3'".(!$m["length"]&&preg_match('~var(char|binary)$~',$U)?" class='required'":"")." aria-labelledby='label-length'>","<td class='options'>",($hb?"<input list='collations' name='".h($x)."[collation]'".(preg_match('~(char|text|enum|set)$~',$U)?"":" class='hidden'")." value='".h($m["collation"])."' placeholder='(".'collation'.")'>":''),(driver()->unsigned?"<select name='".h($x)."[unsigned]'".(!$U||preg_match(number_type(),$U)?"":" class='hidden'").'><option>'.optionlist(driver()->unsigned,$m["unsigned"]).'</select>':''),(isset($m['on_update'])?"<select name='".h($x)."[on_update]'".(preg_match('~timestamp|datetime~',$U)?"":" class='hidden'").'>'.optionlist(array(""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"),(preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?"CURRENT_TIMESTAMP":$m["on_update"])).'</select>':''),($hd?"<select name='".h($x)."[on_delete]'".(preg_match("~`~",$U)?"":" class='hidden'")."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",driver()->onActions),$m["on_delete"])."</select> ":" ");}function
get_partitions_info($R){$ld="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($R);$I=connection()->query("SELECT PARTITION_METHOD, PARTITION_EXPRESSION, PARTITION_ORDINAL_POSITION $ld ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");$J=array();list($J["partition_by"],$J["partition"],$J["partitions"])=$I->fetch_row();$jg=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $ld AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$J["partition_names"]=array_keys($jg);$J["partition_values"]=array_values($jg);return$J;}function
process_length($y){$yc=driver()->enumLength;return(preg_match("~^\\s*\\(?\\s*$yc(?:\\s*,\\s*$yc)*+\\s*\\)?\\s*\$~",$y)&&preg_match_all("~$yc~",$y,$Le)?"(".implode(",",$Le[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$y)));}function
process_type(array$m,$gb="COLLATE"){return" $m[type]".process_length($m["length"]).(preg_match(number_type(),$m["type"])&&in_array($m["unsigned"],driver()->unsigned)?" $m[unsigned]":"").(preg_match('~char|text|enum|set~',$m["type"])&&$m["collation"]?" $gb ".(JUSH=="mssql"?$m["collation"]:q($m["collation"])):"");}function
process_field(array$m,array$Oi){if($m["on_update"])$m["on_update"]=str_ireplace("current_timestamp()","CURRENT_TIMESTAMP",$m["on_update"]);return
array(idf_escape(trim($m["field"])),process_type($Oi),($m["null"]?" NULL":" NOT NULL"),default_value($m),(preg_match('~timestamp|datetime~',$m["type"])&&$m["on_update"]?" ON UPDATE $m[on_update]":""),(support("comment")&&$m["comment"]!=""?" COMMENT ".q($m["comment"]):""),($m["auto_increment"]?auto_increment():null),);}function
default_value(array$m){$k=$m["default"];$od=$m["generated"];return($k===null?"":(in_array($od,driver()->generated)?(JUSH=="mssql"?" AS ($k)".($od=="VIRTUAL"?"":" $od")."":" GENERATED ALWAYS AS ($k) $od"):" DEFAULT ".(!preg_match('~^GENERATED ~i',$k)&&(preg_match('~char|binary|text|json|enum|set~',$m["type"])||preg_match('~^(?![a-z])~i',$k))?(JUSH=="sql"&&preg_match('~text|json~',$m["type"])?"(".q($k).")":q($k)):str_ireplace("current_timestamp()","CURRENT_TIMESTAMP",(JUSH=="sqlite"?"($k)":$k)))));}function
type_class($U){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$x=>$X){if(preg_match("~$x|$X~",$U))return" class='$x'";}}function
edit_fields(array$n,array$hb,$U="TABLE",array$hd=array()){$n=array_values($n);$Pb=(($_POST?$_POST["defaults"]:get_setting("defaults"))?"":" class='hidden'");$nb=(($_POST?$_POST["comments"]:get_setting("comments"))?"":" class='hidden'");echo"<thead><tr>\n",($U=="PROCEDURE"?"<td>":""),"<th id='label-name'>".($U=="TABLE"?'Column name':'Parameter name'),"<td id='label-type'>".'Type'."<textarea id='enum-edit' rows='4' cols='12' wrap='off' style='display: none;'></textarea>".script("qs('#enum-edit').onblur = editingLengthBlur;"),"<td id='label-length'>".'Length',"<td>".'Options';if($U=="TABLE")echo"<td id='label-null'>NULL\n","<td><input type='radio' name='auto_increment_col' value=''><abbr id='label-ai' title='".'Auto Increment'."'>AI</abbr>",doc_link(array('sql'=>"example-auto-increment.html",'mariadb'=>"auto_increment/",'sqlite'=>"autoinc.html",'pgsql'=>"datatype-numeric.html#DATATYPE-SERIAL",'mssql'=>"t-sql/statements/create-table-transact-sql-identity-property",)),"<td id='label-default'$Pb>".'Default value',(support("comment")?"<td id='label-comment'$nb>".'Comment':"");echo"<td>".icon("plus","add[".(support("move_col")?0:count($n))."]","+",'Add next'),"</thead>\n<tbody>\n",script("mixin(qsl('tbody'), {onclick: editingClick, onkeydown: editingKeydown, oninput: editingInput});");foreach($n
as$s=>$m){$s++;$Qf=$m[($_POST?"orig":"field")];$ac=(isset($_POST["add"][$s-1])||(isset($m["field"])&&!idx($_POST["drop_col"],$s)))&&(support("drop_col")||$Qf=="");echo"<tr".($ac?"":" style='display: none;'").">\n",($U=="PROCEDURE"?"<td>".html_select("fields[$s][inout]",explode("|",driver()->inout),$m["inout"]):"")."<th>";if($ac)echo"<input name='fields[$s][field]' value='".h($m["field"])."' data-maxlength='64' autocapitalize='off' aria-labelledby='label-name'>";echo
input_hidden("fields[$s][orig]",$Qf);edit_type("fields[$s]",$m,$hb,$hd);if($U=="TABLE")echo"<td>".checkbox("fields[$s][null]",1,$m["null"],"","","block","label-null"),"<td><label class='block'><input type='radio' name='auto_increment_col' value='$s'".($m["auto_increment"]?" checked":"")." aria-labelledby='label-ai'></label>","<td$Pb>".(driver()->generated?html_select("fields[$s][generated]",array_merge(array("","DEFAULT"),driver()->generated),$m["generated"])." ":checkbox("fields[$s][generated]",1,$m["generated"],"","","","label-default")),"<input name='fields[$s][default]' value='".h($m["default"])."' aria-labelledby='label-default'>",(support("comment")?"<td$nb><input name='fields[$s][comment]' value='".h($m["comment"])."' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'>":"");echo"<td>",(support("move_col")?icon("plus","add[$s]","+",'Add next')." ".icon("up","up[$s]","↑",'Move up')." ".icon("down","down[$s]","↓",'Move down')." ":""),($Qf==""||support("drop_col")?icon("cross","drop_col[$s]","x",'Remove'):"");}}function
process_fields(array&$n){$D=0;if($_POST["up"]){$xe=0;foreach($n
as$x=>$m){if(key($_POST["up"])==$x){unset($n[$x]);array_splice($n,$xe,0,array($m));break;}if(isset($m["field"]))$xe=$D;$D++;}}elseif($_POST["down"]){$jd=false;foreach($n
as$x=>$m){if(isset($m["field"])&&$jd){unset($n[key($_POST["down"])]);array_splice($n,$D,0,array($jd));break;}if(key($_POST["down"])==$x)$jd=$m;$D++;}}elseif($_POST["add"]){$n=array_values($n);array_splice($n,key($_POST["add"]),0,array(array()));}elseif(!$_POST["drop_col"])return
false;return
true;}function
normalize_enum(array$B){$X=$B[0];return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($X[0].$X[0],$X[0],substr($X,1,-1))),'\\'))."'";}function
grant($qd,array$Dg,$e,$Af){if(!$Dg)return
true;if($Dg==array("ALL PRIVILEGES","GRANT OPTION"))return($qd=="GRANT"?queries("$qd ALL PRIVILEGES$Af WITH GRANT OPTION"):queries("$qd ALL PRIVILEGES$Af")&&queries("$qd GRANT OPTION$Af"));return
queries("$qd ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$e, ",$Dg).$e).$Af);}function
drop_create($ec,$h,$gc,$pi,$ic,$A,$Ye,$We,$Xe,$yf,$lf){if($_POST["drop"])query_redirect($ec,$A,$Ye);elseif($yf=="")query_redirect($h,$A,$Xe);elseif($yf!=$lf){$Bb=queries($h);queries_redirect($A,$We,$Bb&&queries($ec));if($Bb)queries($gc);}else
queries_redirect($A,$We,queries($pi)&&queries($ic)&&queries($ec)&&queries($h));}function
create_trigger($Af,array$K){$vi=" $K[Timing] $K[Event]".(preg_match('~ OF~',$K["Event"])?" $K[Of]":"");return"CREATE TRIGGER ".idf_escape($K["Trigger"]).(JUSH=="mssql"?$Af.$vi:$vi.$Af).rtrim(" $K[Type]\n$K[Statement]",";").";";}function
create_routine($eh,array$K){$O=array();$n=(array)$K["fields"];ksort($n);foreach($n
as$m){if($m["field"]!="")$O[]=(preg_match("~^(".driver()->inout.")\$~",$m["inout"])?"$m[inout] ":"").idf_escape($m["field"]).process_type($m,"CHARACTER SET");}$Rb=rtrim($K["definition"],";");return"CREATE $eh ".idf_escape(trim($K["name"]))." (".implode(", ",$O).")".($eh=="FUNCTION"?" RETURNS".process_type($K["returns"],"CHARACTER SET"):"").($K["language"]?" LANGUAGE $K[language]":"").(JUSH=="pgsql"?" AS ".q($Rb):"\n$Rb;");}function
remove_definer($H){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\1)',logged_user()).'`~','\1',$H);}function
format_foreign_key(array$p){$j=$p["db"];$qf=$p["ns"];return" FOREIGN KEY (".implode(", ",array_map('Adminer\idf_escape',$p["source"])).") REFERENCES ".($j!=""&&$j!=$_GET["db"]?idf_escape($j).".":"").($qf!=""&&$qf!=$_GET["ns"]?idf_escape($qf).".":"").idf_escape($p["table"])." (".implode(", ",array_map('Adminer\idf_escape',$p["target"])).")".(preg_match("~^(".driver()->onActions.")\$~",$p["on_delete"])?" ON DELETE $p[on_delete]":"").(preg_match("~^(".driver()->onActions.")\$~",$p["on_update"])?" ON UPDATE $p[on_update]":"");}function
tar_file($o,$_i){$J=pack("a100a8a8a8a12a12",$o,644,0,0,decoct($_i->size),decoct(time()));$ab=8*32;for($s=0;$s<strlen($J);$s++)$ab+=ord($J[$s]);$J
.=sprintf("%06o",$ab)."\0 ";echo$J,str_repeat("\0",512-strlen($J));$_i->send();echo
str_repeat("\0",511-($_i->size+511)%512);}function
ini_bytes($Zd){$X=ini_get($Zd);switch(strtolower(substr($X,-1))){case'g':$X=(int)$X*1024;case'm':$X=(int)$X*1024;case'k':$X=(int)$X*1024;}return$X;}function
doc_link(array$lg,$qi="<sup>?</sup>"){$zh=connection()->server_info;$nj=preg_replace('~^(\d\.?\d).*~s','\1',$zh);$cj=array('sql'=>"https://dev.mysql.com/doc/refman/$nj/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/".(connection()->flavor=='cockroach'?"current":$nj)."/",'mssql'=>"https://learn.microsoft.com/en-us/sql/",'oracle'=>"https://www.oracle.com/pls/topic/lookup?ctx=db".preg_replace('~^.* (\d+)\.(\d+)\.\d+\.\d+\.\d+.*~s','\1\2',$zh)."&id=",);if(connection()->flavor=='maria'){$cj['sql']="https://mariadb.com/kb/en/";$lg['sql']=(isset($lg['mariadb'])?$lg['mariadb']:str_replace(".html","/",$lg['sql']));}return($lg[JUSH]?"<a href='".h($cj[JUSH].$lg[JUSH].(JUSH=='mssql'?"?view=sql-server-ver$nj":""))."'".target_blank().">$qi</a>":"");}function
db_size($j){if(!connection()->select_db($j))return"?";$J=0;foreach(table_status()as$S)$J+=$S["Data_length"]+$S["Index_length"];return
format_number($J);}function
set_utf8mb4($h){static$O=false;if(!$O&&preg_match('~\butf8mb4~i',$h)){$O=true;echo"SET NAMES ".charset(connection()).";\n\n";}}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(!(DB!=""?connection()->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}if(DB!=""){header("HTTP/1.1 404 Not Found");page_header('Database'.": ".h(DB),'Invalid database.',true);}else{if($_POST["db"]&&!$l)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$l,false);echo"<p class='links'>\n";foreach(array('database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',)as$x=>$X){if(support($x))echo"<a href='".h(ME)."$x='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',get_driver(DRIVER),"<b>".h(connection()->server_info)."</b>","<b>".connection()->extension."</b>")."\n","<p>".sprintf('Logged as: %s',"<b>".h(logged_user())."</b>")."\n";$i=adminer()->databases();if($i){$mh=support("scheme");$hb=collations();echo"<form action='' method='post'>\n","<table class='checkable odds'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),"<thead><tr>".(support("database")?"<td>":"")."<th>".'Database'.(get_session("dbs")!==null?" - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>":"")."<td>".'Collation'."<td>".'Tables'."<td>".'Size'." - <a href='".h(ME)."dbsize=1'>".'Compute'."</a>".script("qsl('a').onclick = partial(ajaxSetHtml, '".js_escape(ME)."script=connect');","")."</thead>\n";$i=($_GET["dbsize"]?count_tables($i):array_flip($i));foreach($i
as$j=>$T){$dh=h(ME)."db=".urlencode($j);$t=h("Db-".$j);echo"<tr>".(support("database")?"<td>".checkbox("db[]",$j,in_array($j,(array)$_POST["db"]),"","","",$t):""),"<th><a href='$dh' id='$t'>".h($j)."</a>";$c=h(db_collation($j,$hb));echo"<td>".(support("database")?"<a href='$dh".($mh?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$c</a>":$c),"<td align='right'><a href='$dh&amp;schema=' id='tables-".h($j)."' title='".'Database schema'."'>".($_GET["dbsize"]?$T:"?")."</a>","<td align='right' id='size-".h($j)."'>".($_GET["dbsize"]?db_size($j):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n".input_hidden("all").script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^db/)); };")."<input type='submit' name='drop' value='".'Drop'."'>".confirm()."\n"."</div></fieldset>\n"."</div></div>\n":""),input_token(),"</form>\n",script("tableCheck();");}if(isset(adminer()->plugins)&&is_array(adminer()->plugins)){echo"<div class='plugins'>\n","<h3>".'Loaded plugins'."</h3>\n<ul>\n";foreach(adminer()->plugins
as$qg){$Vb=(method_exists($qg,'description')?$qg->description():"");if(!$Vb){$Sg=new
\ReflectionObject($qg);if(preg_match('~^/[\s*]+(.+)~',$Sg->getDocComment(),$B))$Vb=$B[1];}$nh=(method_exists($qg,'screenshot')?$qg->screenshot():"");echo"<li><b>".get_class($qg)."</b>".h($Vb?": $Vb":"").($nh?" (<a href='".h($nh)."'".target_blank().">".'screenshot'."</a>)":"")."\n";}echo"</ul>\n";adminer()->pluginsLinks();echo"</div>\n";}}page_footer("db");exit;}if(support("scheme")){if(DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirect(preg_replace('~ns=[^&]*&~','',ME)."ns=".get_schema());if(!set_schema($_GET["ns"])){header("HTTP/1.1 404 Not Found");page_header('Schema'.": ".h($_GET["ns"]),'Invalid schema.',true);page_footer("ns");exit;}}}class
TmpFile{private$handler;var$size;function
__construct(){$this->handler=tmpfile();}function
write($wb){$this->size+=strlen($wb);fwrite($this->handler,$wb);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$n=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$M=array(idf_escape($_GET["field"]));$I=driver()->select($a,$M,array(where($_GET,$n)),$M);$K=($I?$I->fetch_row():array());echo
driver()->value($K[0],$n[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$n=fields($a);if(!$n)$l=error()?:'No tables.';$S=table_status1($a);$C=adminer()->tableName($S);page_header(($n&&is_view($S)?$S['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".($C!=""?$C:h($a)),$l);$ch=array();foreach($n
as$x=>$m)$ch+=$m["privileges"];adminer()->selectLinks($S,(isset($ch["insert"])||!support("table")?"":null));$mb=$S["Comment"];if($mb!="")echo"<p class='nowrap'>".'Comment'.": ".h($mb)."\n";if($n)adminer()->tableStructurePrint($n,$S);if(support("indexes")&&driver()->supportsIndex($S)){echo"<h3 id='indexes'>".'Indexes'."</h3>\n";$w=indexes($a);if($w)adminer()->tableIndexesPrint($w);echo'<p class="links"><a href="'.h(ME).'indexes='.urlencode($a).'">'.'Alter indexes'."</a>\n";}if(!is_view($S)){if(fk_support($S)){echo"<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$hd=foreign_keys($a);if($hd){echo"<table>\n","<thead><tr><th>".'Source'."<td>".'Target'."<td>".'ON DELETE'."<td>".'ON UPDATE'."<td></thead>\n";foreach($hd
as$C=>$p){echo"<tr title='".h($C)."'>","<th><i>".implode("</i>, <i>",array_map('Adminer\h',$p["source"]))."</i>";$_=($p["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($p["db"]),ME):($p["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($p["ns"]),ME):ME));echo"<td><a href='".h($_."table=".urlencode($p["table"]))."'>".($p["db"]!=""&&$p["db"]!=DB?"<b>".h($p["db"])."</b>.":"").($p["ns"]!=""&&$p["ns"]!=$_GET["ns"]?"<b>".h($p["ns"])."</b>.":"").h($p["table"])."</a>","(<i>".implode("</i>, <i>",array_map('Adminer\h',$p["target"]))."</i>)","<td>".h($p["on_delete"]),"<td>".h($p["on_update"]),'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($C)).'">'.'Alter'.'</a>',"\n";}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'foreign='.urlencode($a).'">'.'Add foreign key'."</a>\n";}if(support("check")){echo"<h3 id='checks'>".'Checks'."</h3>\n";$Wa=driver()->checkConstraints($a);if($Wa){echo"<table>\n";foreach($Wa
as$x=>$X)echo"<tr title='".h($x)."'>","<td><code class='jush-".JUSH."'>".h($X),"<td><a href='".h(ME.'check='.urlencode($a).'&name='.urlencode($x))."'>".'Alter'."</a>","\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'check='.urlencode($a).'">'.'Create check'."</a>\n";}}if(support(is_view($S)?"view_trigger":"trigger")){echo"<h3 id='triggers'>".'Triggers'."</h3>\n";$Ni=triggers($a);if($Ni){echo"<table>\n";foreach($Ni
as$x=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($x)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($x))."'>".'Alter'."</a>\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'trigger='.urlencode($a).'">'.'Add trigger'."</a>\n";}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$fi=array();$gi=array();$ca=($_GET["schema"]?:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ca,$Le,PREG_SET_ORDER);foreach($Le
as$s=>$B){$fi[$B[1]]=array($B[2],$B[3]);$gi[]="\n\t'".js_escape($B[1])."': [ $B[2], $B[3] ]";}$Ci=0;$Ga=-1;$kh=array();$Rg=array();$Ae=array();$sa=driver()->allFields();foreach(table_status('',true)as$R=>$S){if(is_view($S))continue;$tg=0;$kh[$R]["fields"]=array();foreach($sa[$R]as$m){$tg+=1.25;$m["pos"]=$tg;$kh[$R]["fields"][$m["field"]]=$m;}$kh[$R]["pos"]=($fi[$R]?:array($Ci,0));foreach(adminer()->foreignKeys($R)as$X){if(!$X["db"]){$ze=$Ga;if(idx($fi[$R],1)||idx($fi[$X["table"]],1))$ze=min(idx($fi[$R],1,0),idx($fi[$X["table"]],1,0))-1;else$Ga-=.1;while($Ae[(string)$ze])$ze-=.0001;$kh[$R]["references"][$X["table"]][(string)$ze]=array($X["source"],$X["target"]);$Rg[$X["table"]][$R][(string)$ze]=$X["target"];$Ae[(string)$ze]=true;}}$Ci=max($Ci,$kh[$R]["pos"][0]+2.5+$tg);}echo'<div id="schema" style="height: ',$Ci,'em;">
<script',nonce(),'>
qs(\'#schema\').onselectstart = () => false;
const tablePos = {',implode(",",$gi)."\n",'};
const em = qs(\'#schema\').offsetHeight / ',$Ci,';
document.onmousemove = schemaMousemove;
document.onmouseup = partialArg(schemaMouseup, \'',js_escape(DB),'\');
</script>
';foreach($kh
as$C=>$R){echo"<div class='table' style='top: ".$R["pos"][0]."em; left: ".$R["pos"][1]."em;'>",'<a href="'.h(ME).'table='.urlencode($C).'"><b>'.h($C)."</b></a>",script("qsl('div').onmousedown = schemaMousedown;");foreach($R["fields"]as$m){$X='<span'.type_class($m["type"]).' title="'.h($m["type"].($m["length"]?"($m[length])":"").($m["null"]?" NULL":'')).'">'.h($m["field"]).'</span>';echo"<br>".($m["primary"]?"<i>$X</i>":$X);}foreach((array)$R["references"]as$ni=>$Tg){foreach($Tg
as$ze=>$Og){$_e=$ze-idx($fi[$C],1);$s=0;foreach($Og[0]as$Ih)echo"\n<div class='references' title='".h($ni)."' id='refs$ze-".($s++)."' style='left: $_e"."em; top: ".$R["fields"][$Ih]["pos"]."em; padding-top: .5em;'>"."<div style='border-top: 1px solid gray; width: ".(-$_e)."em;'></div></div>";}}foreach((array)$Rg[$C]as$ni=>$Tg){foreach($Tg
as$ze=>$e){$_e=$ze-idx($fi[$C],1);$s=0;foreach($e
as$mi)echo"\n<div class='references arrow' title='".h($ni)."' id='refd$ze-".($s++)."' style='left: $_e"."em; top: ".$R["fields"][$mi]["pos"]."em;'>"."<div style='height: .5em; border-bottom: 1px solid gray; width: ".(-$_e)."em;'></div>"."</div>";}}echo"\n</div>\n";}foreach($kh
as$C=>$R){foreach((array)$R["references"]as$ni=>$Tg){foreach($Tg
as$ze=>$Og){$bf=$Ci;$Pe=-10;foreach($Og[0]as$x=>$Ih){$ug=$R["pos"][0]+$R["fields"][$Ih]["pos"];$vg=$kh[$ni]["pos"][0]+$kh[$ni]["fields"][$Og[1][$x]]["pos"];$bf=min($bf,$ug,$vg);$Pe=max($Pe,$ug,$vg);}echo"<div class='references' id='refl$ze' style='left: $ze"."em; top: $bf"."em; padding: .5em 0;'><div style='border-right: 1px solid gray; margin-top: 1px; height: ".($Pe-$bf)."em;'></div></div>\n";}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".urlencode($ca)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$l){save_settings(array_intersect_key($_POST,array_flip(array("output","format","db_style","types","routines","events","table_style","auto_increment","triggers","data_style"))),"adminer_export");$T=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Kc=dump_headers((count($T)==1?key($T):DB),(DB==""||count($T)>1));$ke=preg_match('~sql~',$_POST["format"]);if($ke){echo"-- Adminer ".VERSION." ".get_driver(DRIVER)." ".str_replace("\n"," ",connection()->server_info)." dump\n\n";if(JUSH=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
".($_POST["data_style"]?"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";connection()->query("SET time_zone = '+00:00'");connection()->query("SET sql_mode = ''");}}$Vh=$_POST["db_style"];$i=array(DB);if(DB==""){$i=$_POST["databases"];if(is_string($i))$i=explode("\n",rtrim(str_replace("\r","",$i),"\n"));}foreach((array)$i
as$j){adminer()->dumpDatabase($j);if(connection()->select_db($j)){if($ke&&preg_match('~CREATE~',$Vh)&&($h=get_val("SHOW CREATE DATABASE ".idf_escape($j),1))){set_utf8mb4($h);if($Vh=="DROP+CREATE")echo"DROP DATABASE IF EXISTS ".idf_escape($j).";\n";echo"$h;\n";}if($ke){if($Vh)echo
use_sql($j).";\n\n";$Xf="";if($_POST["types"]){foreach(types()as$t=>$U){$zc=type_values($t);if($zc)$Xf
.=($Vh!='DROP+CREATE'?"DROP TYPE IF EXISTS ".idf_escape($U).";;\n":"")."CREATE TYPE ".idf_escape($U)." AS ENUM ($zc);\n\n";else$Xf
.="-- Could not export type $U\n\n";}}if($_POST["routines"]){foreach(routines()as$K){$C=$K["ROUTINE_NAME"];$eh=$K["ROUTINE_TYPE"];$h=create_routine($eh,array("name"=>$C)+routine($K["SPECIFIC_NAME"],$eh));set_utf8mb4($h);$Xf
.=($Vh!='DROP+CREATE'?"DROP $eh IF EXISTS ".idf_escape($C).";;\n":"")."$h;\n\n";}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$K){$h=remove_definer(get_val("SHOW CREATE EVENT ".idf_escape($K["Name"]),3));set_utf8mb4($h);$Xf
.=($Vh!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($K["Name"]).";;\n":"")."$h;;\n\n";}}echo($Xf&&JUSH=='sql'?"DELIMITER ;;\n\n$Xf"."DELIMITER ;\n\n":$Xf);}if($_POST["table_style"]||$_POST["data_style"]){$pj=array();foreach(table_status('',true)as$C=>$S){$R=(DB==""||in_array($C,(array)$_POST["tables"]));$Ib=(DB==""||in_array($C,(array)$_POST["data"]));if($R||$Ib){$_i=null;if($Kc=="tar"){$_i=new
TmpFile;ob_start(array($_i,'write'),1e5);}adminer()->dumpTable($C,($R?$_POST["table_style"]:""),(is_view($S)?2:0));if(is_view($S))$pj[]=$C;elseif($Ib){$n=fields($C);adminer()->dumpData($C,$_POST["data_style"],"SELECT *".convert_fields($n,$n)." FROM ".table($C));}if($ke&&$_POST["triggers"]&&$R&&($Ni=trigger_sql($C)))echo"\nDELIMITER ;;\n$Ni\nDELIMITER ;\n";if($Kc=="tar"){ob_end_flush();tar_file((DB!=""?"":"$j/")."$C.csv",$_i);}elseif($ke)echo"\n";}}if(function_exists('Adminer\foreign_keys_sql')){foreach(table_status('',true)as$C=>$S){$R=(DB==""||in_array($C,(array)$_POST["tables"]));if($R&&!is_view($S))echo
foreign_keys_sql($C);}}foreach($pj
as$oj)adminer()->dumpTable($oj,$_POST["table_style"],1);if($Kc=="tar")echo
pack("x512");}}}adminer()->dumpFooter();exit;}page_header('Export',$l,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table class="layout">
';$Mb=array('','USE','DROP+CREATE','CREATE');$hi=array('','DROP+CREATE','CREATE');$Jb=array('','TRUNCATE+INSERT','INSERT');if(JUSH=="sql")$Jb[]='INSERT+UPDATE';$K=get_settings("adminer_export");if(!$K)$K=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");if(!isset($K["events"])){$K["routines"]=$K["events"]=($_GET["dump"]=="");$K["triggers"]=$K["table_style"];}echo"<tr><th>".'Output'."<td>".html_radios("output",adminer()->dumpOutput(),$K["output"])."\n","<tr><th>".'Format'."<td>".html_radios("format",adminer()->dumpFormat(),$K["format"])."\n",(JUSH=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$Mb,$K["db_style"]).(support("type")?checkbox("types",1,$K["types"],'User types'):"").(support("routine")?checkbox("routines",1,$K["routines"],'Routines'):"").(support("event")?checkbox("events",1,$K["events"],'Events'):"")),"<tr><th>".'Tables'."<td>".html_select('table_style',$hi,$K["table_style"]).checkbox("auto_increment",1,$K["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$K["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$Jb,$K["data_style"]),'</table>
<p><input type="submit" value="Export">
',input_token(),'
<table>
',script("qsl('table').onclick = dumpClick;");$zg=array();if(DB!=""){$Ya=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$Ya>".'Tables'."</label>".script("qs('#check-tables').onclick = partial(formCheck, /^tables\\[/);",""),"<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$Ya></label>".script("qs('#check-data').onclick = partial(formCheck, /^data\\[/);",""),"</thead>\n";$pj="";$ji=tables_list();foreach($ji
as$C=>$U){$yg=preg_replace('~_.*~','',$C);$Ya=($a==""||$a==(substr($a,-1)=="%"?"$yg%":$C));$Ag="<tr><td>".checkbox("tables[]",$C,$Ya,$C,"","block");if($U!==null&&!preg_match('~table~i',$U))$pj
.="$Ag\n";else
echo"$Ag<td align='right'><label class='block'><span id='Rows-".h($C)."'></span>".checkbox("data[]",$C,$Ya)."</label>\n";$zg[$yg]++;}echo$pj;if($ji)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-databases'".($a==""?" checked":"").">".'Database'."</label>",script("qs('#check-databases').onclick = partial(formCheck, /^databases\\[/);",""),"</thead>\n";$i=adminer()->databases();if($i){foreach($i
as$j){if(!information_schema($j)){$yg=preg_replace('~_.*~','',$j);echo"<tr><td>".checkbox("databases[]",$j,$a==""||$a=="$yg%",$j,"","block")."\n";$zg[$yg]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$Xc=true;foreach($zg
as$x=>$X){if($x!=""&&$X>1){echo($Xc?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$x%")."'>".h($x)."</a>";$Xc=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$I=connection()->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$qd=$I;if(!$I)$I=connection()->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo
input_hidden("db",DB),($qd?"":input_hidden("grant")),"<table class='odds'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<th></thead>\n";while($K=$I->fetch_assoc())echo'<tr><td>'.h($K["User"])."<td>".h($K["Host"]).'<td><a href="'.h(ME.'user='.urlencode($K["User"]).'&host='.urlencode($K["Host"])).'">'.'Edit'."</a>\n";if(!$qd||DB!="")echo"<tr><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){if(!$l&&$_POST["export"]){save_settings(array("output"=>$_POST["output"],"format"=>$_POST["format"]),"adminer_import");dump_headers("sql");adminer()->dumpTable("","");adminer()->dumpData("","table",$_POST["query"]);adminer()->dumpFooter();exit;}restart_session();$Gd=&get_session("queries");$Fd=&$Gd[DB];if(!$l&&$_POST["clear"]){$Fd=array();redirect(remove_from_uri("history"));}stop_session();page_header((isset($_GET["import"])?'Import':'SQL command'),$l);if(!$l&&$_POST){$q=false;if(!isset($_GET["import"]))$H=$_POST["query"];elseif($_POST["webfile"]){$Mh=adminer()->importServerPath();$q=@fopen((file_exists($Mh)?$Mh:"compress.zlib://$Mh.gz"),"rb");$H=($q?fread($q,1e6):false);}else$H=get_file("sql_file",true,";");if(is_string($H)){if(function_exists('memory_get_usage')&&($Ue=ini_bytes("memory_limit"))!="-1")@ini_set("memory_limit",max($Ue,strval(2*strlen($H)+memory_get_usage()+8e6)));if($H!=""&&strlen($H)<1e6){$Hg=$H.(preg_match("~;[ \t\r\n]*\$~",$H)?"":";");if(!$Fd||first(end($Fd))!=$Hg){restart_session();$Fd[]=array($Hg,time());set_session("queries",$Gd);stop_session();}}$Jh="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$Tb=";";$D=0;$tc=true;$g=connect();if($g&&DB!=""){$g->select_db(DB);if($_GET["ns"]!="")set_schema($_GET["ns"],$g);}$lb=0;$Ac=array();$eg='[\'"'.(JUSH=="sql"?'`#':(JUSH=="sqlite"?'`[':(JUSH=="mssql"?'[':''))).']|/\*|--'.(JUSH=='sql'?' ':'').'|$'.(JUSH=="pgsql"?'|\$[^$]*\$':'');$Di=microtime(true);$ma=get_settings("adminer_import");$kc=adminer()->dumpFormat();unset($kc["sql"]);while($H!=""){if(!$D&&preg_match("~^$Jh*+DELIMITER\\s+(\\S+)~i",$H,$B)){$Tb=preg_quote($B[1]);$H=substr($H,strlen($B[0]));}elseif(!$D&&JUSH=='pgsql'&&preg_match("~^($Jh*+COPY\\s+)[^;]+\\s+FROM\\s+stdin;~i",$H,$B)){$Tb="\n\\\\\\.\r?\n";$D=strlen($B[0]);}else{preg_match("($Tb\\s*|$eg)",$H,$B,PREG_OFFSET_CAPTURE,$D);list($jd,$tg)=$B[0];if(!$jd&&$q&&!feof($q))$H
.=fread($q,1e5);else{if(!$jd&&rtrim($H)=="")break;$D=$tg+strlen($jd);if($jd&&!preg_match("(^$Tb)",$jd)){$Qa=driver()->hasCStyleEscapes()||(JUSH=="pgsql"&&($tg>0&&strtolower($H[$tg-1])=="e"));$mg=($jd=='/*'?'\*/':($jd=='['?']':(preg_match('~^-- |^#~',$jd)?"\n":preg_quote($jd).($Qa?'|\\\\.':''))));while(preg_match("($mg|\$)s",$H,$B,PREG_OFFSET_CAPTURE,$D)){$ih=$B[0][0];if(!$ih&&$q&&!feof($q))$H
.=fread($q,1e5);else{$D=$B[0][1]+strlen($ih);if(!$ih||$ih[0]!="\\")break;}}}else{$tc=false;$Hg=substr($H,0,$tg+($Tb[0]=="\n"?3:0));$lb++;$Ag="<pre id='sql-$lb'><code class='jush-".JUSH."'>".adminer()->sqlCommandQuery($Hg)."</code></pre>\n";if(JUSH=="sqlite"&&preg_match("~^$Jh*+ATTACH\\b~i",$Hg,$B)){echo$Ag,"<p class='error'>".'ATTACH queries are not supported.'."\n";$Ac[]=" <a href='#sql-$lb'>$lb</a>";if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$Ag;ob_flush();flush();}$Rh=microtime(true);if(connection()->multi_query($Hg)&&$g&&preg_match("~^$Jh*+USE\\b~i",$Hg))$g->query($Hg);do{$I=connection()->store_result();if(connection()->error){echo($_POST["only_errors"]?$Ag:""),"<p class='error'>".'Error in query'.(connection()->errno?" (".connection()->errno.")":"").": ".error()."\n";$Ac[]=" <a href='#sql-$lb'>$lb</a>";if($_POST["error_stops"])break
2;}else{$ti=" <span class='time'>(".format_time($Rh).")</span>".(strlen($Hg)<1000?" <a href='".h(ME)."sql=".urlencode(trim($Hg))."'>".'Edit'."</a>":"");$oa=connection()->affected_rows;$sj=($_POST["only_errors"]?"":driver()->warnings());$tj="warnings-$lb";if($sj)$ti
.=", <a href='#$tj'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$tj');","");$Ic=null;$Pf=null;$Jc="explain-$lb";if(is_object($I)){$z=$_POST["limit"];$Pf=print_select_result($I,$g,array(),$z);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n";$rf=$I->num_rows;echo"<p class='sql-footer'>".($rf?($z&&$rf>$z?sprintf('%d / ',$z):"").lang_format(array('%d row','%d rows'),$rf):""),$ti;if($g&&preg_match("~^($Jh|\\()*+SELECT\\b~i",$Hg)&&($Ic=explain($g,$Hg)))echo", <a href='#$Jc'>Explain</a>".script("qsl('a').onclick = partial(toggle, '$Jc');","");$t="export-$lb";echo", <a href='#$t'>".'Export'."</a>".script("qsl('a').onclick = partial(toggle, '$t');","")."<span id='$t' class='hidden'>: ".html_select("output",adminer()->dumpOutput(),$ma["output"])." ".html_select("format",$kc,$ma["format"]).input_hidden("query",$Hg)."<input type='submit' name='export' value='".'Export'."'>".input_token()."</span>\n"."</form>\n";}}else{if(preg_match("~^$Jh*+(CREATE|DROP|ALTER)$Jh++(DATABASE|SCHEMA)\\b~i",$Hg)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h(connection()->info)."'>".lang_format(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$oa)."$ti\n";}echo($sj?"<div id='$tj' class='hidden'>\n$sj</div>\n":"");if($Ic){echo"<div id='$Jc' class='hidden explain'>\n";print_select_result($Ic,$g,$Pf);echo"</div>\n";}}$Rh=microtime(true);}while(connection()->next_result());}$H=substr($H,$D);$D=0;}}}}if($tc)echo"<p class='message'>".'No commands to execute.'."\n";elseif($_POST["only_errors"])echo"<p class='message'>".lang_format(array('%d query executed OK.','%d queries executed OK.'),$lb-count($Ac))," <span class='time'>(".format_time($Di).")</span>\n";elseif($Ac&&$lb>1)echo"<p class='error'>".'Error in query'.": ".implode("",$Ac)."\n";}else
echo"<p class='error'>".upload_error($H)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';$Gc="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$Hg=$_GET["sql"];if($_POST)$Hg=$_POST["query"];elseif($_GET["history"]=="all")$Hg=$Fd;elseif($_GET["history"]!="")$Hg=idx($Fd[$_GET["history"]],0);echo"<p>";textarea("query",$Hg,20);echo
script(($_POST?"":"qs('textarea').focus();\n")."qs('#form').onsubmit = partial(sqlSubmit, qs('#form'), '".js_escape(remove_from_uri("sql|limit|error_stops|only_errors|history"))."');"),"<p>";adminer()->sqlPrintAfter();echo"$Gc\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{echo"<fieldset><legend>".'File upload'."</legend><div>";$wd=(extension_loaded("zlib")?"[.gz]":"");echo(ini_bool("file_uploads")?"SQL$wd (&lt; ".ini_get("upload_max_filesize")."B): <input type='file' name='sql_file[]' multiple>\n$Gc":'File uploads are disabled.'),"</div></fieldset>\n";$Qd=adminer()->importServerPath();if($Qd)echo"<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>".h($Qd)."$wd</code>"),' <input type="submit" name="webfile" value="'.'Run file'.'">',"</div></fieldset>\n";echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])||$_GET["error_stops"]),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])||$_GET["only_errors"]),'Show only errors')."\n",input_token();if(!isset($_GET["import"])&&$Fd){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($Fd);$X;$X=prev($Fd)){$x=key($Fd);list($Hg,$ti,$oc)=$X;echo'<a href="'.h(ME."sql=&history=$x").'">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$ti)."'>".@date("H:i:s",$ti)."</span>"." <code class='jush-".JUSH."'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$Hg)))),80,"</code>").($oc?" <span class='time'>($oc)</span>":"")."<br>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$n=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$n):""):where($_GET,$n));$Zi=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($n
as$C=>$m){if(!isset($m["privileges"][$Zi?"update":"insert"])||adminer()->fieldName($m)==""||$m["generated"])unset($n[$C]);}if($_POST&&!$l&&!isset($_GET["select"])){$A=$_POST["referer"];if($_POST["insert"])$A=($Zi?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$A))$A=ME."select=".urlencode($a);$w=indexes($a);$Ui=unique_array($_GET["where"],$w);$Kg="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($A,'Item has been deleted.',driver()->delete($a,$Kg,$Ui?0:1));else{$O=array();foreach($n
as$C=>$m){$X=process_input($m);if($X!==false&&$X!==null)$O[idf_escape($C)]=$X;}if($Zi){if(!$O)redirect($A);queries_redirect($A,'Item has been updated.',driver()->update($a,$O,$Kg,$Ui?0:1));if(is_ajax()){page_headers();page_messages($l);exit;}}else{$I=driver()->insert($a,$O);$ye=($I?last_id($I):0);queries_redirect($A,sprintf('Item%s has been inserted.',($ye?" $ye":"")),$I);}}}$K=null;if($_POST["save"])$K=(array)$_POST["fields"];elseif($Z){$M=array();foreach($n
as$C=>$m){if(isset($m["privileges"]["select"])){$wa=($_POST["clone"]&&$m["auto_increment"]?"''":convert_field($m));$M[]=($wa?"$wa AS ":"").idf_escape($C);}}$K=array();if(!support("table"))$M=array("*");if($M){$I=driver()->select($a,$M,array($Z),$M,array(),(isset($_GET["select"])?2:1));if(!$I)$l=error();else{$K=$I->fetch_assoc();if(!$K)$K=false;}if(isset($_GET["select"])&&(!$K||$I->fetch_assoc()))$K=null;}}if(!support("table")&&!$n){if(!$Z){$I=driver()->select($a,array("*"),array(),array("*"));$K=($I?$I->fetch_assoc():false);if(!$K)$K=array(driver()->primary=>"");}if($K){foreach($K
as$x=>$X){if(!$Z)$K[$x]=null;$n[$x]=array("field"=>$x,"null"=>($x!=driver()->primary),"auto_increment"=>($x==driver()->primary));}}}edit_form($a,$n,$K,$Zi,$l);}elseif(isset($_GET["create"])){$a=$_GET["create"];$gg=array();foreach(array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST')as$x)$gg[$x]=$x;$Qg=referencable_primary($a);$hd=array();foreach($Qg
as$di=>$m)$hd[str_replace("`","``",$di)."`".str_replace("`","``",$m["field"])]=$di;$Sf=array();$S=array();if($a!=""){$Sf=fields($a);$S=table_status1($a);if(count($S)<2)$l='No tables.';}$K=$_POST;$K["fields"]=(array)$K["fields"];if($K["auto_increment_col"])$K["fields"][$K["auto_increment_col"]]["auto_increment"]=true;if($_POST)save_settings(array("comments"=>$_POST["comments"],"defaults"=>$_POST["defaults"]));if($_POST&&!process_fields($K["fields"])&&!$l){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables(array($a)));else{$n=array();$sa=array();$dj=false;$fd=array();$Rf=reset($Sf);$qa=" FIRST";foreach($K["fields"]as$x=>$m){$p=$hd[$m["type"]];$Oi=($p!==null?$Qg[$p]:$m);if($m["field"]!=""){if(!$m["generated"])$m["default"]=null;$Fg=process_field($m,$Oi);$sa[]=array($m["orig"],$Fg,$qa);if(!$Rf||$Fg!==process_field($Rf,$Rf)){$n[]=array($m["orig"],$Fg,$qa);if($m["orig"]!=""||$qa)$dj=true;}if($p!==null)$fd[idf_escape($m["field"])]=($a!=""&&JUSH!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$hd[$m["type"]],'source'=>array($m["field"]),'target'=>array($Oi["field"]),'on_delete'=>$m["on_delete"],));$qa=" AFTER ".idf_escape($m["field"]);}elseif($m["orig"]!=""){$dj=true;$n[]=array($m["orig"]);}if($m["orig"]!=""){$Rf=next($Sf);if(!$Rf)$qa="";}}$ig="";if(support("partitioning")){if(isset($gg[$K["partition_by"]])){$dg=array();foreach($K
as$x=>$X){if(preg_match('~^partition~',$x))$dg[$x]=$X;}foreach($dg["partition_names"]as$x=>$C){if($C==""){unset($dg["partition_names"][$x]);unset($dg["partition_values"][$x]);}}if($dg!=get_partitions_info($a)){$jg=array();if($dg["partition_by"]=='RANGE'||$dg["partition_by"]=='LIST'){foreach($dg["partition_names"]as$x=>$C){$Y=$dg["partition_values"][$x];$jg[]="\n  PARTITION ".idf_escape($C)." VALUES ".($dg["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$ig
.="\nPARTITION BY $dg[partition_by]($dg[partition])";if($jg)$ig
.=" (".implode(",",$jg)."\n)";elseif($dg["partitions"])$ig
.=" PARTITIONS ".(+$dg["partitions"]);}}elseif(preg_match("~partitioned~",$S["Create_options"]))$ig
.="\nREMOVE PARTITIONING";}$Ve='Table has been altered.';if($a==""){cookie("adminer_engine",$K["Engine"]);$Ve='Table has been created.';}$C=trim($K["name"]);queries_redirect(ME.(support("table")?"table=":"select=").urlencode($C),$Ve,alter_table($a,$C,(JUSH=="sqlite"&&($dj||$fd)?$sa:$n),$fd,($K["Comment"]!=$S["Comment"]?$K["Comment"]:null),($K["Engine"]&&$K["Engine"]!=$S["Engine"]?$K["Engine"]:""),($K["Collation"]&&$K["Collation"]!=$S["Collation"]?$K["Collation"]:""),($K["Auto_increment"]!=""?number($K["Auto_increment"]):""),$ig));}}page_header(($a!=""?'Alter table':'Create table'),$l,array("table"=>$a),h($a));if(!$_POST){$Qi=driver()->types();$K=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($Qi["int"])?"int":(isset($Qi["integer"])?"integer":"")),"on_update"=>"")),"partition_names"=>array(""),);if($a!=""){$K=$S;$K["name"]=$a;$K["fields"]=array();if(!$_GET["auto_increment"])$K["Auto_increment"]="";foreach($Sf
as$m){$m["generated"]=$m["generated"]?:(isset($m["default"])?"DEFAULT":"");$K["fields"][]=$m;}if(support("partitioning")){$K+=get_partitions_info($a);$K["partition_names"][]="";$K["partition_values"][]="";}}}$hb=collations();if(is_array(reset($hb)))$hb=call_user_func_array('array_merge',array_values($hb));$vc=driver()->engines();foreach($vc
as$uc){if(!strcasecmp($uc,$K["Engine"])){$K["Engine"]=$uc;break;}}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name'.": <input name='name'".($a==""&&!$_POST?" autofocus":"")." data-maxlength='64' value='".h($K["name"])."' autocapitalize='off'>\n",($vc?html_select("Engine",array(""=>"(".'engine'.")")+$vc,$K["Engine"]).on_help("event.target.value",1).script("qsl('select').onchange = helpClose;")."\n":"");if($hb)echo"<datalist id='collations'>".optionlist($hb)."</datalist>\n",(preg_match("~sqlite|mssql~",JUSH)?"":"<input list='collations' name='Collation' value='".h($K["Collation"])."' placeholder='(".'collation'.")'>\n");echo"<input type='submit' value='".'Save'."'>\n";}if(support("columns")){echo"<div class='scrollable'>\n","<table id='edit-fields' class='nowrap'>\n";edit_fields($K["fields"],$hb,"TABLE",$hd);echo"</table>\n",script("editFields();"),"</div>\n<p>\n",'Auto Increment'.": <input type='number' name='Auto_increment' class='size' value='".h($K["Auto_increment"])."'>\n",checkbox("defaults",1,($_POST?$_POST["defaults"]:get_setting("defaults")),'Default values',"columnShow(this.checked, 5)","jsonly");$ob=($_POST?$_POST["comments"]:get_setting("comments"));echo(support("comment")?checkbox("comments",1,$ob,'Comment',"editingCommentsClick(this, true);","jsonly").' '.(preg_match('~\n~',$K["Comment"])?"<textarea name='Comment' rows='2' cols='20'".($ob?"":" class='hidden'").">".h($K["Comment"])."</textarea>":'<input name="Comment" value="'.h($K["Comment"]).'" data-maxlength="'.(min_version(5.5)?2048:60).'"'.($ob?"":" class='hidden'").'>'):''),'<p>
<input type="submit" value="Save">
';}echo'
';if($a!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));if(support("partitioning")){$hg=preg_match('~RANGE|LIST~',$K["partition_by"]);print_fieldset("partition",'Partition by',$K["partition_by"]);echo"<p>".html_select("partition_by",array(""=>"")+$gg,$K["partition_by"]).on_help("event.target.value.replace(/./, 'PARTITION BY \$&')",1).script("qsl('select').onchange = partitionByChange;"),"(<input name='partition' value='".h($K["partition"])."'>)\n",'Partitions'.": <input type='number' name='partitions' class='size".($hg||!$K["partition_by"]?" hidden":"")."' value='".h($K["partitions"])."'>\n","<table id='partition-table'".($hg?"":" class='hidden'").">\n","<thead><tr><th>".'Partition name'."<th>".'Values'."</thead>\n";foreach($K["partition_names"]as$x=>$X)echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'" autocapitalize="off">',($x==count($K["partition_names"])-1?script("qsl('input').oninput = partitionNameChange;"):''),'<td><input name="partition_values[]" value="'.h(idx($K["partition_values"],$x)).'">';echo"</table>\n</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$Vd=array("PRIMARY","UNIQUE","INDEX");$S=table_status1($a,true);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$S["Engine"]))$Vd[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$S["Engine"]))$Vd[]="SPATIAL";$w=indexes($a);$G=array();if(JUSH=="mongo"){$G=$w["_id_"];unset($Vd[0]);unset($w["_id_"]);}$K=$_POST;if($K)save_settings(array("index_options"=>$K["options"]));if($_POST&&!$l&&!$_POST["add"]&&!$_POST["drop_col"]){$b=array();foreach($K["indexes"]as$v){$C=$v["name"];if(in_array($v["type"],$Vd)){$e=array();$Ce=array();$Wb=array();$O=array();ksort($v["columns"]);foreach($v["columns"]as$x=>$d){if($d!=""){$y=idx($v["lengths"],$x);$Ub=idx($v["descs"],$x);$O[]=idf_escape($d).($y?"(".(+$y).")":"").($Ub?" DESC":"");$e[]=$d;$Ce[]=($y?:null);$Wb[]=$Ub;}}$Hc=$w[$C];if($Hc){ksort($Hc["columns"]);ksort($Hc["lengths"]);ksort($Hc["descs"]);if($v["type"]==$Hc["type"]&&array_values($Hc["columns"])===$e&&(!$Hc["lengths"]||array_values($Hc["lengths"])===$Ce)&&array_values($Hc["descs"])===$Wb){unset($w[$C]);continue;}}if($e)$b[]=array($v["type"],$C,$O);}}foreach($w
as$C=>$Hc)$b[]=array($Hc["type"],$C,"DROP");if(!$b)redirect(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),'Indexes have been altered.',alter_indexes($a,$b));}page_header('Indexes',$l,array("table"=>$a),h($a));$n=array_keys(fields($a));if($_POST["add"]){foreach($K["indexes"]as$x=>$v){if($v["columns"][count($v["columns"])]!="")$K["indexes"][$x]["columns"][]="";}$v=end($K["indexes"]);if($v["type"]||array_filter($v["columns"],'strlen'))$K["indexes"][]=array("columns"=>array(1=>""));}if(!$K){foreach($w
as$x=>$v){$w[$x]["name"]=$x;$w[$x]["columns"][]="";}$w[]=array("columns"=>array(1=>""));$K["indexes"]=$w;}$Ce=(JUSH=="sql"||JUSH=="mssql");$Dh=($_POST?$_POST["options"]:get_setting("index_options"));echo'
<form action="" method="post">
<div class="scrollable">
<table class="nowrap">
<thead><tr>
<th id="label-type">Index Type
<th><input type="submit" class="wayoff">','Columns'.($Ce?"<span class='idxopts".($Dh?"":" hidden")."'> (".'length'.")</span>":"");if($Ce||support("descidx"))echo
checkbox("options",1,$Dh,'Options',"indexOptionsShow(this.checked)","jsonly")."\n";echo'<th id="label-name">Name
<th><noscript>',icon("plus","add[0]","+",'Add next'),'</noscript>
</thead>
';if($G){echo"<tr><td>PRIMARY<td>";foreach($G["columns"]as$x=>$d)echo
select_input(" disabled",$n,$d),"<label><input disabled type='checkbox'>".'descending'."</label> ";echo"<td><td>\n";}$ne=1;foreach($K["indexes"]as$v){if(!$_POST["drop_col"]||$ne!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$ne][type]",array(-1=>"")+$Vd,$v["type"],($ne==count($K["indexes"])?"indexesAddRow.call(this);":""),"label-type"),"<td>";ksort($v["columns"]);$s=1;foreach($v["columns"]as$x=>$d){echo"<span>".select_input(" name='indexes[$ne][columns][$s]' title='".'Column'."'",($n?array_combine($n,$n):$n),$d,"partial(".($s==count($v["columns"])?"indexesAddColumn":"indexesChangeColumn").", '".js_escape(JUSH=="sql"?"":$_GET["indexes"]."_")."')"),"<span class='idxopts".($Dh?"":" hidden")."'>",($Ce?"<input type='number' name='indexes[$ne][lengths][$s]' class='size' value='".h(idx($v["lengths"],$x))."' title='".'Length'."'>":""),(support("descidx")?checkbox("indexes[$ne][descs][$s]",1,idx($v["descs"],$x),'descending'):""),"</span> </span>";$s++;}echo"<td><input name='indexes[$ne][name]' value='".h($v["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n","<td>".icon("cross","drop_col[$ne]","x",'Remove').script("qsl('button').onclick = partial(editingRemoveRow, 'indexes\$1[type]');");}$ne++;}echo'</table>
</div>
<p>
<input type="submit" value="Save">
',input_token(),'</form>
';}elseif(isset($_GET["database"])){$K=$_POST;if($_POST&&!$l&&!$_POST["add"]){$C=trim($K["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif(DB!==$C){if(DB!=""){$_GET["db"]=$C;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".urlencode($C),'Database has been renamed.',rename_database($C,$K["collation"]));}else{$i=explode("\n",str_replace("\r","",$C));$Wh=true;$xe="";foreach($i
as$j){if(count($i)==1||$j!=""){if(!create_database($j,$K["collation"]))$Wh=false;$xe=$j;}}restart_session();set_session("dbs",null);queries_redirect(ME."db=".urlencode($xe),'Database has been created.',$Wh);}}else{if(!$K["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($C).(preg_match('~^[a-z0-9_]+$~i',$K["collation"])?" COLLATE $K[collation]":""),substr(ME,0,-1),'Database has been altered.');}}page_header(DB!=""?'Alter database':'Create database',$l,array(),h(DB));$hb=collations();$C=DB;if($_POST)$C=$K["name"];elseif(DB!="")$K["collation"]=db_collation(DB,$hb);elseif(JUSH=="sql"){foreach(get_vals("SHOW GRANTS")as$qd){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$qd,$B)&&$B[1]){$C=stripcslashes(idf_unescape("`$B[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add"]||strpos($C,"\n")?'<textarea autofocus name="name" rows="10" cols="40">'.h($C).'</textarea><br>':'<input name="name" autofocus value="'.h($C).'" data-maxlength="64" autocapitalize="off">')."\n".($hb?html_select("collation",array(""=>"(".'collation'.")")+$hb,$K["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mariadb'=>"supported-character-sets-and-collations/",'mssql'=>"relational-databases/system-functions/sys-fn-helpcollations-transact-sql",)):""),'<input type="submit" value="Save">
';if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',DB))."\n";elseif(!$_POST["add"]&&$_GET["db"]=="")echo
icon("plus","add[0]","+",'Add next')."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["scheme"])){$K=$_POST;if($_POST&&!$l){$_=preg_replace('~ns=[^&]*&~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$_,'Schema has been dropped.');else{$C=trim($K["name"]);$_
.=urlencode($C);if($_GET["ns"]=="")query_redirect("CREATE SCHEMA ".idf_escape($C),$_,'Schema has been created.');elseif($_GET["ns"]!=$C)query_redirect("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($C),$_,'Schema has been altered.');else
redirect($_);}}page_header($_GET["ns"]!=""?'Alter schema':'Create schema',$l);if(!$K)$K["name"]=$_GET["ns"];echo'
<form action="" method="post">
<p><input name="name" autofocus value="',h($K["name"]),'" autocapitalize="off">
<input type="submit" value="Save">
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$_GET["ns"]))."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["call"])){$ba=($_GET["name"]?:$_GET["call"]);page_header('Call'.": ".h($ba),$l);$eh=routine($_GET["call"],(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$Rd=array();$Xf=array();foreach($eh["fields"]as$s=>$m){if(substr($m["inout"],-3)=="OUT")$Xf[$s]="@".idf_escape($m["field"])." AS ".idf_escape($m["field"]);if(!$m["inout"]||substr($m["inout"],0,2)=="IN")$Rd[]=$s;}if(!$l&&$_POST){$Ra=array();foreach($eh["fields"]as$x=>$m){$X="";if(in_array($x,$Rd)){$X=process_input($m);if($X===false)$X="''";if(isset($Xf[$x]))connection()->query("SET @".idf_escape($m["field"])." = $X");}$Ra[]=(isset($Xf[$x])?"@".idf_escape($m["field"]):$X);}$H=(isset($_GET["callf"])?"SELECT":"CALL")." ".table($ba)."(".implode(", ",$Ra).")";$Rh=microtime(true);$I=connection()->multi_query($H);$oa=connection()->affected_rows;echo
adminer()->selectQuery($H,$Rh,!$I);if(!$I)echo"<p class='error'>".error()."\n";else{$g=connect();if($g)$g->select_db(DB);do{$I=connection()->store_result();if(is_object($I))print_select_result($I,$g);else
echo"<p class='message'>".lang_format(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$oa)." <span class='time'>".@date("H:i:s")."</span>\n";}while(connection()->next_result());if($Xf)print_select_result(connection()->query("SELECT ".implode(", ",$Xf)));}}echo'
<form action="" method="post">
';if($Rd){echo"<table class='layout'>\n";foreach($Rd
as$x){$m=$eh["fields"][$x];$C=$m["field"];echo"<tr><th>".adminer()->fieldName($m);$Y=idx($_POST["fields"],$C);if($Y!=""){if($m["type"]=="set")$Y=implode(",",$Y);}input($m,$Y,idx($_POST["function"],$C,""));echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="Call">
',input_token(),'</form>

<pre>
';function
pre_tr($ih){return
preg_replace('~^~m','<tr>',preg_replace('~\|~','<td>',preg_replace('~\|$~m',"",rtrim($ih))));}$R='(\+--[-+]+\+\n)';$K='(\| .* \|\n)';echo
preg_replace_callback("~^$R?$K$R?($K*)$R?~m",function($B){$Yc=pre_tr($B[2]);return"<table>\n".($B[1]?"<thead>$Yc</thead>\n":$Yc).pre_tr($B[4])."\n</table>";},preg_replace('~(\n(    -|mysql)&gt; )(.+)~',"\\1<code class='jush-sql'>\\3</code>",preg_replace('~(.+)\n---+\n~',"<b>\\1</b>\n",h($eh['comment']))));echo'</pre>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$C=$_GET["name"];$K=$_POST;if($_POST&&!$l&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){if(!$_POST["drop"]){$K["source"]=array_filter($K["source"],'strlen');ksort($K["source"]);$mi=array();foreach($K["source"]as$x=>$X)$mi[$x]=$K["target"][$x];$K["target"]=$mi;}if(JUSH=="sqlite")$I=recreate_table($a,$a,array(),array(),array(" $C"=>($K["drop"]?"":" ".format_foreign_key($K))));else{$b="ALTER TABLE ".table($a);$I=($C==""||queries("$b DROP ".(JUSH=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($C)));if(!$K["drop"])$I=queries("$b ADD".format_foreign_key($K));}queries_redirect(ME."table=".urlencode($a),($K["drop"]?'Foreign key has been dropped.':($C!=""?'Foreign key has been altered.':'Foreign key has been created.')),$I);if(!$K["drop"])$l='Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.';}page_header('Foreign key',$l,array("table"=>$a),h($a));if($_POST){ksort($K["source"]);if($_POST["add"])$K["source"][]="";elseif($_POST["change"]||$_POST["change-js"])$K["target"]=array();}elseif($C!=""){$hd=foreign_keys($a);$K=$hd[$C];$K["source"][]="";}else{$K["table"]=$a;$K["source"]=array("");}echo'
<form action="" method="post">
';$Ih=array_keys(fields($a));if($K["db"]!="")connection()->select_db($K["db"]);if($K["ns"]!=""){$Tf=get_schema();set_schema($K["ns"]);}$Pg=array_keys(array_filter(table_status('',true),'Adminer\fk_support'));$mi=array_keys(fields(in_array($K["table"],$Pg)?$K["table"]:reset($Pg)));$Df="this.form['change-js'].value = '1'; this.form.submit();";echo"<p><label>".'Target table'.": ".html_select("table",$Pg,$K["table"],$Df)."</label>\n";if(support("scheme")){$lh=array_filter(adminer()->schemas(),function($kh){return!preg_match('~^information_schema$~i',$kh);});echo"<label>".'Schema'.": ".html_select("ns",$lh,$K["ns"]!=""?$K["ns"]:$_GET["ns"],$Df)."</label>";if($K["ns"]!="")set_schema($Tf);}elseif(JUSH!="sqlite"){$Nb=array();foreach(adminer()->databases()as$j){if(!information_schema($j))$Nb[]=$j;}echo"<label>".'DB'.": ".html_select("db",$Nb,$K["db"]!=""?$K["db"]:$_GET["db"],$Df)."</label>";}echo
input_hidden("change-js"),'<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table>
<thead><tr><th id="label-source">Source<th id="label-target">Target</thead>
';$ne=0;foreach($K["source"]as$x=>$X){echo"<tr>","<td>".html_select("source[".(+$x)."]",array(-1=>"")+$Ih,$X,($ne==count($K["source"])-1?"foreignAddRow.call(this);":""),"label-source"),"<td>".html_select("target[".(+$x)."]",$mi,idx($K["target"],$x),"","label-target");$ne++;}echo'</table>
<p>
<label>ON DELETE: ',html_select("on_delete",array(-1=>"")+explode("|",driver()->onActions),$K["on_delete"]),'</label>
<label>ON UPDATE: ',html_select("on_update",array(-1=>"")+explode("|",driver()->onActions),$K["on_update"]),'</label>
',doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'mariadb'=>"foreign-keys/",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-REFERENCES",'mssql'=>"t-sql/statements/create-table-transact-sql",'oracle'=>"SQLRF01111",)),'<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';if($C!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));echo
input_token(),'</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$K=$_POST;$Uf="VIEW";if(JUSH=="pgsql"&&$a!=""){$P=table_status1($a);$Uf=strtoupper($P["Engine"]);}if($_POST&&!$l){$C=trim($K["name"]);$wa=" AS\n$K[select]";$A=ME."table=".urlencode($C);$Ve='View has been altered.';$U=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$C&&JUSH!="sqlite"&&$U=="VIEW"&&$Uf=="VIEW")query_redirect((JUSH=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($C).$wa,$A,$Ve);else{$oi=$C."_adminer_".uniqid();drop_create("DROP $Uf ".table($a),"CREATE $U ".table($C).$wa,"DROP $U ".table($C),"CREATE $U ".table($oi).$wa,"DROP $U ".table($oi),($_POST["drop"]?substr(ME,0,-1):$A),'View has been dropped.',$Ve,'View has been created.',$a,$C);}}if(!$_POST&&$a!=""){$K=view($a);$K["name"]=$a;$K["materialized"]=($Uf!="VIEW");if(!$l)$l=error();}page_header(($a!=""?'Alter view':'Create view'),$l,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($K["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$K["materialized"],'Materialized view'):""),'<p>';textarea("select",$K["select"]);echo'<p>
<input type="submit" value="Save">
';if($a!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));echo
input_token(),'</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$fe=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Sh=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$K=$_POST;if($_POST&&!$l){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($K["INTERVAL_FIELD"],$fe)&&isset($Sh[$K["STATUS"]])){$jh="\nON SCHEDULE ".($K["INTERVAL_VALUE"]?"EVERY ".q($K["INTERVAL_VALUE"])." $K[INTERVAL_FIELD]".($K["STARTS"]?" STARTS ".q($K["STARTS"]):"").($K["ENDS"]?" ENDS ".q($K["ENDS"]):""):"AT ".q($K["STARTS"]))." ON COMPLETION".($K["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$jh.($aa!=$K["EVENT_NAME"]?"\nRENAME TO ".idf_escape($K["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($K["EVENT_NAME"]).$jh)."\n".$Sh[$K["STATUS"]]." COMMENT ".q($K["EVENT_COMMENT"]).rtrim(" DO\n$K[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$l);if(!$K&&$aa!=""){$L=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$K=reset($L);}echo'
<form action="" method="post">
<table class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($K["EVENT_NAME"]),'" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$K[EXECUTE_AT]$K[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($K["ENDS"]),'">
<tr><th>Every<td><input type="number" name="INTERVAL_VALUE" value="',h($K["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$fe,$K["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$Sh,$K["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($K["EVENT_COMMENT"]),'" data-maxlength="64">
<tr><th><td>',checkbox("ON_COMPLETION","PRESERVE",$K["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$K["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="Save">
';if($aa!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$aa));echo
input_token(),'</form>
';}elseif(isset($_GET["procedure"])){$ba=($_GET["name"]?:$_GET["procedure"]);$eh=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$K=$_POST;$K["fields"]=(array)$K["fields"];if($_POST&&!process_fields($K["fields"])&&!$l){$Qf=routine($_GET["procedure"],$eh);$oi="$K[name]_adminer_".uniqid();foreach($K["fields"]as$x=>$m){if($m["field"]=="")unset($K["fields"][$x]);}drop_create("DROP $eh ".routine_id($ba,$Qf),create_routine($eh,$K),"DROP $eh ".routine_id($K["name"],$K),create_routine($eh,array("name"=>$oi)+$K),"DROP $eh ".routine_id($oi,$K),substr(ME,0,-1),'Routine has been dropped.','Routine has been altered.','Routine has been created.',$ba,$K["name"]);}page_header(($ba!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($ba):(isset($_GET["function"])?'Create function':'Create procedure')),$l);if(!$_POST){if($ba=="")$K["language"]="sql";else{$K=routine($_GET["procedure"],$eh);$K["name"]=$ba;}}$hb=get_vals("SHOW CHARACTER SET");sort($hb);$fh=routine_languages();echo($hb?"<datalist id='collations'>".optionlist($hb)."</datalist>":""),'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($K["name"]),'" data-maxlength="64" autocapitalize="off">
',($fh?"<label>".'Language'.": ".html_select("language",$fh,$K["language"])."</label>\n":""),'<input type="submit" value="Save">
<div class="scrollable">
<table class="nowrap">
';edit_fields($K["fields"],$hb,$eh);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",$K["returns"],$hb,array(),(JUSH=="pgsql"?array("void","trigger"):array()));}echo'</table>
',script("editFields();"),'</div>
<p>';textarea("definition",$K["definition"]);echo'<p>
<input type="submit" value="Save">
';if($ba!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$ba));echo
input_token(),'</form>
';}elseif(isset($_GET["sequence"])){$da=$_GET["sequence"];$K=$_POST;if($_POST&&!$l){$_=substr(ME,0,-1);$C=trim($K["name"]);if($_POST["drop"])query_redirect("DROP SEQUENCE ".idf_escape($da),$_,'Sequence has been dropped.');elseif($da=="")query_redirect("CREATE SEQUENCE ".idf_escape($C),$_,'Sequence has been created.');elseif($da!=$C)query_redirect("ALTER SEQUENCE ".idf_escape($da)." RENAME TO ".idf_escape($C),$_,'Sequence has been altered.');else
redirect($_);}page_header($da!=""?'Alter sequence'.": ".h($da):'Create sequence',$l);if(!$K)$K["name"]=$da;echo'
<form action="" method="post">
<p><input name="name" value="',h($K["name"]),'" autocapitalize="off">
<input type="submit" value="Save">
';if($da!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$da))."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["type"])){$ea=$_GET["type"];$K=$_POST;if($_POST&&!$l){$_=substr(ME,0,-1);if($_POST["drop"])query_redirect("DROP TYPE ".idf_escape($ea),$_,'Type has been dropped.');else
query_redirect("CREATE TYPE ".idf_escape(trim($K["name"]))." $K[as]",$_,'Type has been created.');}page_header($ea!=""?'Alter type'.": ".h($ea):'Create type',$l);if(!$K)$K["as"]="AS ";echo'
<form action="" method="post">
<p>
';if($ea!=""){$Qi=driver()->types();$zc=type_values($Qi[$ea]);if($zc)echo"<code class='jush-".JUSH."'>ENUM (".h($zc).")</code>\n<p>";echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$ea))."\n";}else{echo'Name'.": <input name='name' value='".h($K['name'])."' autocapitalize='off'>\n",doc_link(array('pgsql'=>"datatype-enum.html",),"?");textarea("as",$K["as"]);echo"<p><input type='submit' value='".'Save'."'>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["check"])){$a=$_GET["check"];$C=$_GET["name"];$K=$_POST;if($K&&!$l){if(JUSH=="sqlite")$I=recreate_table($a,$a,array(),array(),array(),"",array(),"$C",($K["drop"]?"":$K["clause"]));else{$I=($C==""||queries("ALTER TABLE ".table($a)." DROP CONSTRAINT ".idf_escape($C)));if(!$K["drop"])$I=queries("ALTER TABLE ".table($a)." ADD".($K["name"]!=""?" CONSTRAINT ".idf_escape($K["name"]):"")." CHECK ($K[clause])");}queries_redirect(ME."table=".urlencode($a),($K["drop"]?'Check has been dropped.':($C!=""?'Check has been altered.':'Check has been created.')),$I);}page_header(($C!=""?'Alter check'.": ".h($C):'Create check'),$l,array("table"=>$a));if(!$K){$Za=driver()->checkConstraints($a);$K=array("name"=>$C,"clause"=>$Za[$C]);}echo'
<form action="" method="post">
<p>';if(JUSH!="sqlite")echo'Name'.': <input name="name" value="'.h($K["name"]).'" data-maxlength="64" autocapitalize="off"> ';echo
doc_link(array('sql'=>"create-table-check-constraints.html",'mariadb'=>"constraint/",'pgsql'=>"ddl-constraints.html#DDL-CONSTRAINTS-CHECK-CONSTRAINTS",'mssql'=>"relational-databases/tables/create-check-constraints",'sqlite'=>"lang_createtable.html#check_constraints",),"?"),'<p>';textarea("clause",$K["clause"]);echo'<p><input type="submit" value="Save">
';if($C!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));echo
input_token(),'</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$C="$_GET[name]";$Mi=trigger_options();$K=(array)trigger($C,$a)+array("Trigger"=>$a."_bi");if($_POST){if(!$l&&in_array($_POST["Timing"],$Mi["Timing"])&&in_array($_POST["Event"],$Mi["Event"])&&in_array($_POST["Type"],$Mi["Type"])){$Af=" ON ".table($a);$ec="DROP TRIGGER ".idf_escape($C).(JUSH=="pgsql"?$Af:"");$A=ME."table=".urlencode($a);if($_POST["drop"])query_redirect($ec,$A,'Trigger has been dropped.');else{if($C!="")queries($ec);queries_redirect($A,($C!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($Af,$_POST)));if($C!="")queries(create_trigger($Af,$K+array("Type"=>reset($Mi["Type"]))));}}$K=$_POST;}page_header(($C!=""?'Alter trigger'.": ".h($C):'Create trigger'),$l,array("table"=>$a));echo'
<form action="" method="post" id="form">
<table class="layout">
<tr><th>Time<td>',html_select("Timing",$Mi["Timing"],$K["Timing"],"triggerChange(/^".preg_quote($a,"/")."_[ba][iud]$/, '".js_escape($a)."', this.form);"),'<tr><th>Event<td>',html_select("Event",$Mi["Event"],$K["Event"],"this.form['Timing'].onchange();"),(in_array("UPDATE OF",$Mi["Event"])?" <input name='Of' value='".h($K["Of"])."' class='hidden'>":""),'<tr><th>Type<td>',html_select("Type",$Mi["Type"],$K["Type"]),'</table>
<p>Name: <input name="Trigger" value="',h($K["Trigger"]),'" data-maxlength="64" autocapitalize="off">
',script("qs('#form')['Timing'].onchange();"),'<p>';textarea("Statement",$K["Statement"]);echo'<p>
<input type="submit" value="Save">
';if($C!="")echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));echo
input_token(),'</form>
';}elseif(isset($_GET["user"])){$fa=$_GET["user"];$Dg=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$K){foreach(explode(",",($K["Privilege"]=="Grant option"?"":$K["Context"]))as$xb)$Dg[$xb][$K["Privilege"]]=$K["Comment"];}$Dg["Server Admin"]+=$Dg["File access on server"];$Dg["Databases"]["Create routine"]=$Dg["Procedures"]["Create routine"];unset($Dg["Procedures"]["Create routine"]);$Dg["Columns"]=array();foreach(array("Select","Insert","Update","References")as$X)$Dg["Columns"][$X]=$Dg["Tables"][$X];unset($Dg["Server Admin"]["Usage"]);foreach($Dg["Tables"]as$x=>$X)unset($Dg["Databases"][$x]);$kf=array();if($_POST){foreach($_POST["objects"]as$x=>$X)$kf[$X]=(array)$kf[$X]+idx($_POST["grants"],$x,array());}$rd=array();$zf="";if(isset($_GET["host"])&&($I=connection()->query("SHOW GRANTS FOR ".q($fa)."@".q($_GET["host"])))){while($K=$I->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$K[0],$B)&&preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~',$B[1],$Le,PREG_SET_ORDER)){foreach($Le
as$X){if($X[1]!="USAGE")$rd["$B[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$K[0]))$rd["$B[2]$X[2]"]["GRANT OPTION"]=true;}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$K[0],$B))$zf=$B[1];}}if($_POST&&!$l){$_f=(isset($_GET["host"])?q($fa)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $_f",ME."privileges=",'User has been dropped.');else{$mf=q($_POST["user"])."@".q($_POST["host"]);$kg=$_POST["pass"];if($kg!=''&&!$_POST["hashed"]&&!min_version(8)){$kg=get_val("SELECT PASSWORD(".q($kg).")");$l=!$kg;}$Bb=false;if(!$l){if($_f!=$mf){$Bb=queries((min_version(5)?"CREATE USER":"GRANT USAGE ON *.* TO")." $mf IDENTIFIED BY ".(min_version(8)?"":"PASSWORD ").q($kg));$l=!$Bb;}elseif($kg!=$zf)queries("SET PASSWORD FOR $mf = ".q($kg));}if(!$l){$bh=array();foreach($kf
as$tf=>$qd){if(isset($_GET["grant"]))$qd=array_filter($qd);$qd=array_keys($qd);if(isset($_GET["grant"]))$bh=array_diff(array_keys(array_filter($kf[$tf],'strlen')),$qd);elseif($_f==$mf){$xf=array_keys((array)$rd[$tf]);$bh=array_diff($xf,$qd);$qd=array_diff($qd,$xf);unset($rd[$tf]);}if(preg_match('~^(.+)\s*(\(.*\))?$~U',$tf,$B)&&(!grant("REVOKE",$bh,$B[2]," ON $B[1] FROM $mf")||!grant("GRANT",$qd,$B[2]," ON $B[1] TO $mf"))){$l=true;break;}}}if(!$l&&isset($_GET["host"])){if($_f!=$mf)queries("DROP USER $_f");elseif(!isset($_GET["grant"])){foreach($rd
as$tf=>$bh){if(preg_match('~^(.+)(\(.*\))?$~U',$tf,$B))grant("REVOKE",array_keys($bh),$B[2]," ON $B[1] FROM $mf");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),!$l);if($Bb)connection()->query("DROP USER $mf");}}page_header((isset($_GET["host"])?'Username'.": ".h("$fa@$_GET[host]"):'Create user'),$l,array("privileges"=>array('','Privileges')));$K=$_POST;if($K)$rd=$kf;else{$K=$_GET+array("host"=>get_val("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$K["pass"]=$zf;if($zf!="")$K["hashed"]=true;$rd[(DB==""||$rd?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',h($K["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',h($K["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($K["pass"]),'" autocomplete="new-password">
',($K["hashed"]?"":script("typePassword(qs('#pass'));")),(min_version(8)?"":checkbox("hashed",1,$K["hashed"],'Hashed',"typePassword(this.form['pass'], this.checked);")),'</table>

',"<table class='odds'>\n","<thead><tr><th colspan='2'>".'Privileges'.doc_link(array('sql'=>"grant.html#priv_level"));$s=0;foreach($rd
as$tf=>$qd){echo'<th>'.($tf!="*.*"?"<input name='objects[$s]' value='".h($tf)."' size='10' autocapitalize='off'>":input_hidden("objects[$s]","*.*")."*.*");$s++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Columns"=>'Column',"Procedures"=>'Routine',)as$xb=>$Ub){foreach((array)$Dg[$xb]as$Cg=>$mb){echo"<tr><td".($Ub?">$Ub<td":" colspan='2'").' lang="en" title="'.h($mb).'">'.h($Cg);$s=0;foreach($rd
as$tf=>$qd){$C="'grants[$s][".h(strtoupper($Cg))."]'";$Y=$qd[strtoupper($Cg)];if($xb=="Server Admin"&&$tf!=(isset($rd["*.*"])?"*.*":".*"))echo"<td>";elseif(isset($_GET["grant"]))echo"<td><select name=$C><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else
echo"<td align='center'><label class='block'>","<input type='checkbox' name=$C value='1'".($Y?" checked":"").($Cg=="All privileges"?" id='grants-$s-all'>":">".($Cg=="Grant option"?"":script("qsl('input').onclick = function () { if (this.checked) formUncheck('grants-$s-all'); };"))),"</label>";$s++;}}}echo"</table>\n",'<p>
<input type="submit" value="Save">
';if(isset($_GET["host"]))echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',"$fa@$_GET[host]"));echo
input_token(),'</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")){if($_POST&&!$l){$te=0;foreach((array)$_POST["kill"]as$X){if(kill_process($X))$te++;}queries_redirect(ME."processlist=",lang_format(array('%d process has been killed.','%d processes have been killed.'),$te),$te||!$_POST["kill"]);}}page_header('Process list',$l);echo'
<form action="" method="post">
<div class="scrollable">
<table class="nowrap checkable odds">
',script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");$s=-1;foreach(process_list()as$s=>$K){if(!$s){echo"<thead><tr lang='en'>".(support("kill")?"<th>":"");foreach($K
as$x=>$X)echo"<th>$x".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($x),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"REFRN30223",));echo"</thead>\n";}echo"<tr>".(support("kill")?"<td>".checkbox("kill[]",$K[JUSH=="sql"?"Id":"pid"],0):"");foreach($K
as$x=>$X)echo"<td>".((JUSH=="sql"&&$x=="Info"&&preg_match("~Query|Killed~",$K["Command"])&&$X!="")||(JUSH=="pgsql"&&$x=="current_query"&&$X!="<IDLE>")||(JUSH=="oracle"&&$x=="sql_text"&&$X!="")?"<code class='jush-".JUSH."'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($K["db"]!=""?"db=".urlencode($K["db"])."&":"")."sql=".urlencode($X)).'">'.'Clone'.'</a>':h($X));echo"\n";}echo'</table>
</div>
<p>
';if(support("kill"))echo($s+1)."/".sprintf('%d in total',max_connections()),"<p><input type='submit' value='".'Kill'."'>\n";echo
input_token(),'</form>
',script("tableCheck();");}elseif(isset($_GET["select"])){$a=$_GET["select"];$S=table_status1($a);$w=indexes($a);$n=fields($a);$hd=column_foreign_keys($a);$vf=$S["Oid"];$na=get_settings("adminer_import");$ch=array();$e=array();$ph=array();$Mf=array();$si="";foreach($n
as$x=>$m){$C=adminer()->fieldName($m);$if=html_entity_decode(strip_tags($C),ENT_QUOTES);if(isset($m["privileges"]["select"])&&$C!=""){$e[$x]=$if;if(is_shortable($m))$si=adminer()->selectLengthProcess();}if(isset($m["privileges"]["where"])&&$C!="")$ph[$x]=$if;if(isset($m["privileges"]["order"])&&$C!="")$Mf[$x]=$if;$ch+=$m["privileges"];}list($M,$sd)=adminer()->selectColumnsProcess($e,$w);$M=array_unique($M);$sd=array_unique($sd);$je=count($sd)<count($M);$Z=adminer()->selectSearchProcess($n,$w);$Lf=adminer()->selectOrderProcess($n,$w);$z=adminer()->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Vi=>$K){$wa=convert_field($n[key($K)]);$M=array($wa?:idf_escape(key($K)));$Z[]=where_check($Vi,$n);$J=driver()->select($a,$M,$Z,$M);if($J)echo
first($J->fetch_row());}exit;}$G=$Xi=null;foreach($w
as$v){if($v["type"]=="PRIMARY"){$G=array_flip($v["columns"]);$Xi=($M?$G:array());foreach($Xi
as$x=>$X){if(in_array(idf_escape($x),$M))unset($Xi[$x]);}break;}}if($vf&&!$G){$G=$Xi=array($vf=>0);$w[]=array("type"=>"PRIMARY","columns"=>array($vf));}if($_POST&&!$l){$vj=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$Za=array();foreach($_POST["check"]as$Va)$Za[]=where_check($Va,$n);$vj[]="((".implode(") OR (",$Za)."))";}$vj=($vj?"\nWHERE ".implode(" AND ",$vj):"");if($_POST["export"]){save_settings(array("output"=>$_POST["output"],"format"=>$_POST["format"]),"adminer_import");dump_headers($a);adminer()->dumpTable($a,"");$ld=($M?implode(", ",$M):"*").convert_fields($e,$n,$M)."\nFROM ".table($a);$ud=($sd&&$je?"\nGROUP BY ".implode(", ",$sd):"").($Lf?"\nORDER BY ".implode(", ",$Lf):"");$H="SELECT $ld$vj$ud";if(is_array($_POST["check"])&&!$G){$Ti=array();foreach($_POST["check"]as$X)$Ti[]="(SELECT".limit($ld,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$n).$ud,1).")";$H=implode(" UNION ALL ",$Ti);}adminer()->dumpData($a,"table",$H);adminer()->dumpFooter();exit;}if(!adminer()->selectEmailProcess($Z,$hd)){if($_POST["save"]||$_POST["delete"]){$I=true;$oa=0;$O=array();if(!$_POST["delete"]){foreach($_POST["fields"]as$C=>$X){$X=process_input($n[$C]);if($X!==null&&($_POST["clone"]||$X!==false))$O[idf_escape($C)]=($X!==false?$X:idf_escape($C));}}if($_POST["delete"]||$O){$H=($_POST["clone"]?"INTO ".table($a)." (".implode(", ",array_keys($O)).")\nSELECT ".implode(", ",$O)."\nFROM ".table($a):"");if($_POST["all"]||($G&&is_array($_POST["check"]))||$je){$I=($_POST["delete"]?driver()->delete($a,$vj):($_POST["clone"]?queries("INSERT $H$vj".driver()->insertReturning($a)):driver()->update($a,$O,$vj)));$oa=connection()->affected_rows;if(is_object($I))$oa+=$I->num_rows;}else{foreach((array)$_POST["check"]as$X){$uj="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$n);$I=($_POST["delete"]?driver()->delete($a,$uj,1):($_POST["clone"]?queries("INSERT".limit1($a,$H,$uj)):driver()->update($a,$O,$uj,1)));if(!$I)break;$oa+=connection()->affected_rows;}}}$Ve=lang_format(array('%d item has been affected.','%d items have been affected.'),$oa);if($_POST["clone"]&&$I&&$oa==1){$ye=last_id($I);if($ye)$Ve=sprintf('Item%s has been inserted.'," $ye");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$Ve,$I);if(!$_POST["delete"]){$wg=(array)$_POST["fields"];edit_form($a,array_intersect_key($n,$wg),$wg,!$_POST["clone"],$l);page_footer();exit;}}elseif(!$_POST["import"]){if(!$_POST["val"])$l='Ctrl+click on a value to modify it.';else{$I=true;$oa=0;foreach($_POST["val"]as$Vi=>$K){$O=array();foreach($K
as$x=>$X){$x=bracket_escape($x,true);$O[idf_escape($x)]=(preg_match('~char|text~',$n[$x]["type"])||$X!=""?adminer()->processInput($n[$x],$X):"NULL");}$I=driver()->update($a,$O," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($Vi,$n),($je||$G?0:1)," ");if(!$I)break;$oa+=connection()->affected_rows;}queries_redirect(remove_from_uri(),lang_format(array('%d item has been affected.','%d items have been affected.'),$oa),$I);}}elseif(!is_string($Vc=get_file("csv_file",true)))$l=upload_error($Vc);elseif(!preg_match('~~u',$Vc))$l='File must be in UTF-8 encoding.';else{save_settings(array("output"=>$na["output"],"format"=>$_POST["separator"]),"adminer_import");$I=true;$ib=array_keys($n);preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$Vc,$Le);$oa=count($Le[0]);driver()->begin();$vh=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$L=array();foreach($Le[0]as$x=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$vh]*)$vh~",$X.$vh,$Me);if(!$x&&!array_diff($Me[1],$ib)){$ib=$Me[1];$oa--;}else{$O=array();foreach($Me[1]as$s=>$fb)$O[idf_escape($ib[$s])]=($fb==""&&$n[$ib[$s]]["null"]?"NULL":q(preg_match('~^".*"$~s',$fb)?str_replace('""','"',substr($fb,1,-1)):$fb));$L[]=$O;}}$I=(!$L||driver()->insertUpdate($a,$L,$G));if($I)driver()->commit();queries_redirect(remove_from_uri("page"),lang_format(array('%d row has been imported.','%d rows have been imported.'),$oa),$I);driver()->rollback();}}}$di=adminer()->tableName($S);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $di",$l);$O=null;if(isset($ch["insert"])||!support("table")){$dg=array();foreach((array)$_GET["where"]as$X){if(isset($hd[$X["col"]])&&count($hd[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&(is_array($X["val"])||!preg_match('~[_%]~',$X["val"])))))$dg["set"."[".bracket_escape($X["col"])."]"]=$X["val"];}$O=$dg?"&".http_build_query($dg):"";}adminer()->selectLinks($S,$O);if(!$e&&support("table"))echo"<p class='error'>".'Unable to select the table'.($n?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?input_hidden("db",DB).(isset($_GET["ns"])?input_hidden("ns",$_GET["ns"]):""):""),input_hidden("select",$a),"</div>\n";adminer()->selectColumnsPrint($M,$e);adminer()->selectSearchPrint($Z,$ph,$w);adminer()->selectOrderPrint($Lf,$Mf,$w);adminer()->selectLimitPrint($z);adminer()->selectLengthPrint($si);adminer()->selectActionPrint($w);echo"</form>\n";$E=$_GET["page"];$kd=null;if($E=="last"){$kd=get_val(count_rows($a,$Z,$je,$sd));$E=floor(max(0,intval($kd)-1)/$z);}$qh=$M;$td=$sd;if(!$qh){$qh[]="*";$yb=convert_fields($e,$n,$M);if($yb)$qh[]=substr($yb,2);}foreach($M
as$x=>$X){$m=$n[idf_unescape($X)];if($m&&($wa=convert_field($m)))$qh[$x]="$wa AS $X";}if(!$je&&$Xi){foreach($Xi
as$x=>$X){$qh[]=idf_escape($x);if($td)$td[]=idf_escape($x);}}$I=driver()->select($a,$qh,$Z,$td,$Lf,$z,$E,true);if(!$I)echo"<p class='error'>".error()."\n";else{if(JUSH=="mssql"&&$E)$I->seek($z*$E);$sc=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$L=array();while($K=$I->fetch_assoc()){if($E&&JUSH=="oracle")unset($K["RNUM"]);$L[]=$K;}if($_GET["page"]!="last"&&$z&&$sd&&$je&&JUSH=="sql")$kd=get_val(" SELECT FOUND_ROWS()");if(!$L)echo"<p class='message'>".'No rows.'."\n";else{$Ea=adminer()->backwardKeys($a,$di);echo"<div class='scrollable'>","<table id='table' class='nowrap checkable odds'>",script("mixin(qs('#table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true), onkeydown: editingKeydown});"),"<thead><tr>".(!$sd&&$M?"":"<td><input type='checkbox' id='all-page' class='jsonly'>".script("qs('#all-page').onclick = partial(formCheck, /check/);","")." <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".'Modify'."</a>");$jf=array();$nd=array();reset($M);$Mg=1;foreach($L[0]as$x=>$X){if(!isset($Xi[$x])){$X=idx($_GET["columns"],key($M))?:array();$m=$n[$M?($X?$X["col"]:current($M)):$x];$C=($m?adminer()->fieldName($m,$Mg):($X["fun"]?"*":h($x)));if($C!=""){$Mg++;$jf[$x]=$C;$d=idf_escape($x);$Jd=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($x);$Ub="&desc%5B0%5D=1";echo"<th id='th[".h(bracket_escape($x))."]'>".script("mixin(qsl('th'), {onmouseover: partial(columnMouse), onmouseout: partial(columnMouse, ' hidden')});","");$md=apply_sql_function($X["fun"],$C);$Hh=isset($m["privileges"]["order"])||$md;echo($Hh?'<a href="'.h($Jd.($Lf[0]==$d||$Lf[0]==$x||(!$Lf&&$je&&$sd[0]==$d)?$Ub:'')).'">'."$md</a>":$md),"<span class='column hidden'>";if($Hh)echo"<a href='".h($Jd.$Ub)."' title='".'descending'."' class='text'> ↓</a>";if(!$X["fun"]&&isset($m["privileges"]["where"]))echo'<a href="#fieldset-search" title="'.'Search'.'" class="text jsonly"> =</a>',script("qsl('a').onclick = partial(selectSearch, '".js_escape($x)."');");echo"</span>";}$nd[$x]=$X["fun"];next($M);}}$Ce=array();if($_GET["modify"]){foreach($L
as$K){foreach($K
as$x=>$X)$Ce[$x]=max($Ce[$x],min(40,strlen(utf8_decode($X))));}}echo($Ea?"<th>".'Relations':"")."</thead>\n";if(is_ajax())ob_end_clean();foreach(adminer()->rowDescriptions($L,$hd)as$hf=>$K){$Ui=unique_array($L[$hf],$w);if(!$Ui){$Ui=array();foreach($L[$hf]as$x=>$X){if(!preg_match('~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~',$x))$Ui[$x]=$X;}}$Vi="";foreach($Ui
as$x=>$X){$m=(array)$n[$x];if((JUSH=="sql"||JUSH=="pgsql")&&preg_match('~char|text|enum|set~',$m["type"])&&strlen($X)>64){$x=(strpos($x,'(')?$x:idf_escape($x));$x="MD5(".(JUSH!='sql'||preg_match("~^utf8~",$m["collation"])?$x:"CONVERT($x USING ".charset(connection()).")").")";$X=md5($X);}$Vi
.="&".($X!==null?urlencode("where[".bracket_escape($x)."]")."=".urlencode($X===false?"f":$X):"null%5B%5D=".urlencode($x));}echo"<tr>".(!$sd&&$M?"":"<td>".checkbox("check[]",substr($Vi,1),in_array(substr($Vi,1),(array)$_POST["check"])).($je||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Vi)."' class='edit'>".'edit'."</a>"));foreach($K
as$x=>$X){if(isset($jf[$x])){$m=(array)$n[$x];$X=driver()->value($X,$m);if($X!=""&&(!isset($sc[$x])||$sc[$x]!=""))$sc[$x]=(is_mail($X)?$jf[$x]:"");$_="";if(preg_match('~blob|bytea|raw|file~',$m["type"])&&$X!="")$_=ME.'download='.urlencode($a).'&field='.urlencode($x).$Vi;if(!$_&&$X!==null){foreach((array)$hd[$x]as$p){if(count($hd[$x])==1||end($p["source"])==$x){$_="";foreach($p["source"]as$s=>$Ih)$_
.=where_link($s,$p["target"][$s],$L[$hf][$Ih]);$_=($p["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\1'.urlencode($p["db"]),ME):ME).'select='.urlencode($p["table"]).$_;if($p["ns"])$_=preg_replace('~([?&]ns=)[^&]+~','\1'.urlencode($p["ns"]),$_);if(count($p["source"])==1)break;}}}if($x=="COUNT(*)"){$_=ME."select=".urlencode($a);$s=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Ui))$_
.=where_link($s++,$W["col"],$W["val"],$W["op"]);}foreach($Ui
as$pe=>$W)$_
.=where_link($s++,$pe,$W);}$Kd=select_value($X,$_,$m,$si);$t=h("val[$Vi][".bracket_escape($x)."]");$xg=idx(idx($_POST["val"],$Vi),bracket_escape($x));$nc=!is_array($K[$x])&&is_utf8($Kd)&&$L[$hf][$x]==$K[$x]&&!$nd[$x]&&!$m["generated"];$qi=preg_match('~text|json|lob~',$m["type"]);echo"<td id='$t'".(preg_match(number_type(),$m["type"])&&($X===null||is_numeric(strip_tags($Kd)))?" class='number'":"");if(($_GET["modify"]&&$nc&&$X!==null)||$xg!==null){$xd=h($xg!==null?$xg:$K[$x]);echo">".($qi?"<textarea name='$t' cols='30' rows='".(substr_count($K[$x],"\n")+1)."'>$xd</textarea>":"<input name='$t' value='$xd' size='$Ce[$x]'>");}else{$Ge=strpos($Kd,"<i>…</i>");echo" data-text='".($Ge?2:($qi?1:0))."'".($nc?"":" data-warning='".h('Use edit link to modify this value.')."'").">$Kd";}}}if($Ea)echo"<td>";adminer()->backwardKeysPrint($Ea,$L[$hf]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){if($L||$E){$Fc=true;if($_GET["page"]!="last"){if(!$z||(count($L)<$z&&($L||!$E)))$kd=($E?$E*$z:0)+count($L);elseif(JUSH!="sql"||!$je){$kd=($je?false:found_rows($S,$Z));if(intval($kd)<max(1e4,2*($E+1)*$z))$kd=first(slow_query(count_rows($a,$Z,$je,$sd)));else$Fc=false;}}$bg=($z&&($kd===false||$kd>$z||$E));if($bg)echo(($kd===false?count($L)+1:$kd-$E*$z)>$z?'<p><a href="'.h(remove_from_uri("page")."&page=".($E+1)).'" class="loadmore">'.'Load more data'.'</a>'.script("qsl('a').onclick = partial(selectLoadMore, $z, '".'Loading'."…');",""):''),"\n";echo"<div class='footer'><div>\n";if($bg){$Oe=($kd===false?$E+(count($L)>=$z?2:1):floor(($kd-1)/$z));echo"<fieldset>";if(JUSH!="simpledb"){echo"<legend><a href='".h(remove_from_uri("page"))."'>".'Page'."</a></legend>",script("qsl('a').onclick = function () { pageClick(this.href, +prompt('".'Page'."', '".($E+1)."')); return false; };"),pagination(0,$E).($E>5?" …":"");for($s=max(1,$E-4);$s<min($Oe,$E+5);$s++)echo
pagination($s,$E);if($Oe>0)echo($E+5<$Oe?" …":""),($Fc&&$kd!==false?pagination($Oe,$E):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$Oe'>".'last'."</a>");}else
echo"<legend>".'Page'."</legend>",pagination(0,$E).($E>1?" …":""),($E?pagination($E,$E):""),($Oe>$E?pagination($E+1,$E).($Oe>$E+1?" …":""):"");echo"</fieldset>\n";}echo"<fieldset>","<legend>".'Whole result'."</legend>";$bc=($Fc?"":"~ ").$kd;$Ef="const checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$bc' : checked); selectCount('selected2', this.checked || !checked ? '$bc' : checked);";echo
checkbox("all",1,0,($kd!==false?($Fc?"":"~ ").lang_format(array('%d row','%d rows'),$kd):""),$Ef)."\n","</fieldset>\n";if(adminer()->selectCommandPrint())echo'<fieldset',($_GET["modify"]?'':' class="jsonly"'),'><legend>Modify</legend><div>
<input type="submit" value="Save"',($_GET["modify"]?'':' title="'.'Ctrl+click on a value to modify it.'.'"'),'>
</div></fieldset>
<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete">',confirm(),'</div></fieldset>
';$id=adminer()->dumpFormat();foreach((array)$_GET["columns"]as$d){if($d["fun"]){unset($id['sql']);break;}}if($id){print_fieldset("export",'Export'." <span id='selected2'></span>");$Yf=adminer()->dumpOutput();echo($Yf?html_select("output",$Yf,$na["output"])." ":""),html_select("format",$id,$na["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}adminer()->selectEmailPrint(array_filter($sc,'strlen'),$e);echo"</div></div>\n";}if(adminer()->selectImportPrint())echo"<div>","<a href='#import'>".'Import'."</a>",script("qsl('a').onclick = partial(toggle, 'import');",""),"<span id='import'".($_POST["import"]?"":" class='hidden'").">: ","<input type='file' name='csv_file'> ",html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$na["format"])," <input type='submit' name='import' value='".'Import'."'>","</span>","</div>";echo
input_token(),"</form>\n",(!$sd&&$M?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$P=isset($_GET["status"]);page_header($P?'Status':'Variables');$lj=($P?show_status():show_variables());if(!$lj)echo"<p class='message'>".'No rows.'."\n";else{echo"<table>\n";foreach($lj
as$K){echo"<tr>";$x=array_shift($K);echo"<th><code class='jush-".JUSH.($P?"status":"set")."'>".h($x)."</code>";foreach($K
as$X)echo"<td>".nl_br(h($X));}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Zh=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$C=>$S){json_row("Comment-$C",h($S["Comment"]));if(!is_view($S)){foreach(array("Engine","Collation")as$x)json_row("$x-$C",h($S[$x]));foreach($Zh+array("Auto_increment"=>0,"Rows"=>0)as$x=>$X){if($S[$x]!=""){$X=format_number($S[$x]);if($X>=0)json_row("$x-$C",($x=="Rows"&&$X&&$S["Engine"]==(JUSH=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Zh[$x]))$Zh[$x]+=($S["Engine"]!="InnoDB"||$x!="Data_free"?$S[$x]:0);}elseif(array_key_exists($x,$S))json_row("$x-$C","?");}}}foreach($Zh
as$x=>$X)json_row("sum-$x",format_number($X));json_row("");}elseif($_GET["script"]=="kill")connection()->query("KILL ".number($_POST["kill"]));else{foreach(count_tables(adminer()->databases())as$j=>$X){json_row("tables-$j",$X);json_row("size-$j",db_size($j));}json_row("");}exit;}else{$ki=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($ki&&!$l&&!$_POST["search"]){$I=true;$Ve="";if(JUSH=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$I=truncate_tables($_POST["tables"]);$Ve='Tables have been truncated.';}elseif($_POST["move"]){$I=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$Ve='Tables have been moved.';}elseif($_POST["copy"]){$I=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$Ve='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$I=drop_views($_POST["views"]);if($I&&$_POST["tables"])$I=drop_tables($_POST["tables"]);$Ve='Tables have been dropped.';}elseif(JUSH=="sqlite"&&$_POST["check"]){foreach((array)$_POST["tables"]as$R){foreach(get_rows("PRAGMA integrity_check(".q($R).")")as$K)$Ve
.="<b>".h($R)."</b>: ".h($K["integrity_check"])."<br>";}}elseif(JUSH!="sql"){$I=(JUSH=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?"":" ANALYZE"),$_POST["tables"]));$Ve='Tables have been optimized.';}elseif(!$_POST["tables"])$Ve='No tables.';elseif($I=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('Adminer\idf_escape',$_POST["tables"])))){while($K=$I->fetch_assoc())$Ve
.="<b>".h($K["Table"])."</b>: ".h($K["Msg_text"])."<br>";}queries_redirect(substr(ME,0,-1),$Ve,$I);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$l,true);if(adminer()->homepage()){if($_GET["ns"]!==""){echo"<h3 id='tables-views'>".'Tables and views'."</h3>\n";$ji=tables_list();if(!$ji)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>","<input type='search' name='query' value='".h($_POST["query"])."'>",script("qsl('input').onkeydown = partialArg(bodyKeydown, 'search');","")," <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if($_POST["search"]&&$_POST["query"]!=""){$_GET["where"][0]["op"]=driver()->convertOperator("LIKE %%");search_tables();}}echo"<div class='scrollable'>\n","<table class='nowrap checkable odds'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),'<thead><tr class="wrap">','<td><input id="check-all" type="checkbox" class="jsonly">'.script("qs('#check-all').onclick = partial(formCheck, /^(tables|views)\[/);",""),'<th>'.'Table','<td>'.'Engine'.doc_link(array('sql'=>'storage-engines.html')),'<td>'.'Collation'.doc_link(array('sql'=>'charset-charsets.html','mariadb'=>'supported-character-sets-and-collations/')),'<td>'.'Data Length'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT','oracle'=>'REFRN20286')),'<td>'.'Index Length'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT')),'<td>'.'Data Free'.doc_link(array('sql'=>'show-table-status.html')),'<td>'.'Auto Increment'.doc_link(array('sql'=>'example-auto-increment.html','mariadb'=>'auto_increment/')),'<td>'.'Rows'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'catalog-pg-class.html#CATALOG-PG-CLASS','oracle'=>'REFRN20286')),(support("comment")?'<td>'.'Comment'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-info.html#FUNCTIONS-INFO-COMMENT-TABLE')):''),"</thead>\n";$T=0;foreach($ji
as$C=>$U){$oj=($U!==null&&!preg_match('~table|sequence~i',$U));$t=h("Table-".$C);echo'<tr><td>'.checkbox(($oj?"views[]":"tables[]"),$C,in_array("$C",$ki,true),"","","",$t),'<th>'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".urlencode($C)."' title='".'Show structure'."' id='$t'>".h($C).'</a>':h($C));if($oj)echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($C).'" title="'.'Alter view'.'">'.(preg_match('~materialized~i',$U)?'Materialized view':'View').'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($C).'" title="'.'Select data'.'">?</a>';else{foreach(array("Engine"=>array(),"Collation"=>array(),"Data_length"=>array("create",'Alter table'),"Index_length"=>array("indexes",'Alter indexes'),"Data_free"=>array("edit",'New item'),"Auto_increment"=>array("auto_increment=1&create",'Alter table'),"Rows"=>array("select",'Select data'),)as$x=>$_){$t=" id='$x-".h($C)."'";echo($_?"<td align='right'>".(support("table")||$x=="Rows"||(support("indexes")&&$x!="Data_length")?"<a href='".h(ME."$_[0]=").urlencode($C)."'$t title='$_[1]'>?</a>":"<span$t>?</span>"):"<td id='$x-".h($C)."'>");}$T++;}echo(support("comment")?"<td id='Comment-".h($C)."'>":""),"\n";}echo"<tr><td><th>".sprintf('%d in total',count($ji)),"<td>".h(JUSH=="sql"?get_val("SELECT @@default_storage_engine"):""),"<td>".h(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$x)echo"<td align='right' id='sum-$x'>";echo"\n","</table>\n","</div>\n";if(!information_schema(DB)){echo"<div class='footer'><div>\n";$ij="<input type='submit' value='".'Vacuum'."'> ".on_help("'VACUUM'");$Hf="<input type='submit' name='optimize' value='".'Optimize'."'> ".on_help(JUSH=="sql"?"'OPTIMIZE TABLE'":"'VACUUM OPTIMIZE'");echo"<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>".(JUSH=="sqlite"?$ij."<input type='submit' name='check' value='".'Check'."'> ".on_help("'PRAGMA integrity_check'"):(JUSH=="pgsql"?$ij.$Hf:(JUSH=="sql"?"<input type='submit' value='".'Analyze'."'> ".on_help("'ANALYZE TABLE'").$Hf."<input type='submit' name='check' value='".'Check'."'> ".on_help("'CHECK TABLE'")."<input type='submit' name='repair' value='".'Repair'."'> ".on_help("'REPAIR TABLE'"):"")))."<input type='submit' name='truncate' value='".'Truncate'."'> ".on_help(JUSH=="sqlite"?"'DELETE'":"'TRUNCATE".(JUSH=="pgsql"?"'":" TABLE'")).confirm()."<input type='submit' name='drop' value='".'Drop'."'>".on_help("'DROP TABLE'").confirm()."\n";$i=(support("scheme")?adminer()->schemas():adminer()->databases());if(count($i)!=1&&JUSH!="sqlite"){$j=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p><label>".'Move to other database'.": ",($i?html_select("target",$i,$j):'<input name="target" value="'.h($j).'" autocapitalize="off">'),"</label> <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'> ".checkbox("overwrite",1,$_POST["overwrite"],'overwrite'):""),"\n";}echo"<input type='hidden' name='all' value=''>",script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^(tables|views)\[/));".(support("table")?" selectCount('selected2', formChecked(this, /^tables\[/) || $T);":"")." }"),input_token(),"</div></fieldset>\n","</div></div>\n";}echo"</form>\n",script("tableCheck();");}echo"<p class='links'><a href='".h(ME)."create='>".'Create table'."</a>\n",(support("view")?"<a href='".h(ME)."view='>".'Create view'."</a>\n":"");if(support("routine")){echo"<h3 id='routines'>".'Routines'."</h3>\n";$gh=routines();if($gh){echo"<table class='odds'>\n",'<thead><tr><th>'.'Name'.'<td>'.'Type'.'<td>'.'Return type'."<td></thead>\n";foreach($gh
as$K){$C=($K["SPECIFIC_NAME"]==$K["ROUTINE_NAME"]?"":"&name=".urlencode($K["ROUTINE_NAME"]));echo'<tr>','<th><a href="'.h(ME.($K["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($K["SPECIFIC_NAME"]).$C).'">'.h($K["ROUTINE_NAME"]).'</a>','<td>'.h($K["ROUTINE_TYPE"]),'<td>'.h($K["DTD_IDENTIFIER"]),'<td><a href="'.h(ME.($K["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($K["SPECIFIC_NAME"]).$C).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n";}if(support("sequence")){echo"<h3 id='sequences'>".'Sequences'."</h3>\n";$yh=get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema() ORDER BY sequence_name");if($yh){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."</thead>\n";foreach($yh
as$X)echo"<tr><th><a href='".h(ME)."sequence=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."sequence='>".'Create sequence'."</a>\n";}if(support("type")){echo"<h3 id='user-types'>".'User types'."</h3>\n";$gj=types();if($gj){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."</thead>\n";foreach($gj
as$X)echo"<tr><th><a href='".h(ME)."type=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links'><a href='".h(ME)."type='>".'Create type'."</a>\n";}if(support("event")){echo"<h3 id='events'>".'Events'."</h3>\n";$L=get_rows("SHOW EVENTS");if($L){echo"<table>\n","<thead><tr><th>".'Name'."<td>".'Schedule'."<td>".'Start'."<td>".'End'."<td></thead>\n";foreach($L
as$K)echo"<tr>","<th>".h($K["Name"]),"<td>".($K["Execute at"]?'At given time'."<td>".$K["Execute at"]:'Every'." ".$K["Interval value"]." ".$K["Interval field"]."<td>$K[Starts]"),"<td>$K[Ends]",'<td><a href="'.h(ME).'event='.urlencode($K["Name"]).'">'.'Alter'.'</a>';echo"</table>\n";$Dc=get_val("SELECT @@event_scheduler");if($Dc&&$Dc!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($Dc)."\n";}echo'<p class="links"><a href="'.h(ME).'event=">'.'Create event'."</a>\n";}if($ji)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}}}page_footer();