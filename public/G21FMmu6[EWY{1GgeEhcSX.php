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
as$C=>$m){echo"<tr><th>".adminer()->fieldName($m);$k=idx($_GET["set"],bracket_escape($C));if($k===null){$k=$m["default"];if($m["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$k,$Ug))$k=$Ug[1];if(JUSH=="sql"&&preg_match('~binary~',$m["type"]))$k=bin2hex($k);}$Y=($K!==null?($K[$C]!=""&&JUSH=="sql"&&preg_match("~enum|set~",$m["type"])&&is_array($K[$C])?implode(",",$K[$C]):(is_bool($K[$C])?+$K[$C]:$K[$C])):(!$Zi&&$m["auto_increment"]?"":(isset($_GET["select"])?false:$k)));if(!$_POST["save"]&&is_string($Y))$Y=adminer()->editVal($Y,$m);$r=($_POST["save"]?idx($_POST["function"],$C,""):($Zi&&preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(!$_POST&&!$Zi&&$Y==$m["default"]&&preg_match('~^[\w.]+\(~',$Y))$r="SQL";if(preg_match("~time~",$m["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$r="now";}if($m["type"]=="uuid"&&$Y=="uuid()"){$Y="";$r="uuid";}if($Ba!==false)$Ba=($m["auto_increment"]||$r=="now"||$r=="uuid"?null:true);input($m,$Y,$r,$Ba);if($Ba)$Ba=false;echo"\n";}if(!support("table")&&!fields($R))echo"<tr>"."<th><input name='field_keys[]'>".script("qsl('input').oninput = fieldChange;")."<td class='function'>".html_select("field_funs[]",adminer()->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>"."\n";echo"</table>\n";}echo"<p>\n";if($n){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"]))echo"<input type='submit' name='insert' value='".($Zi?'Save and continue edit':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n",($Zi?script("qsl('input').onclick = function () { return !ajaxForm(this.form, '".'Saving'."â€¦', this); };"):"");}echo($Zi?"<input type='submit' name='delete' value='".'Delete'."'>".confirm()."\n":"");if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo
input_hidden("referer",(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"])),input_hidden("save",1),input_token(),"</form>\n";}function
shorten_utf8($Q,$y=80,$Xh=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$y).")($)?)u",$Q,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$y).")($)?)",$Q,$B);return
h($B[1]).$Xh.(isset($B[2])?"":"<i>â€¦</i>");}function
icon($Ld,$C,$Kd,$wi){return"<button type='submit' name='$C' title='".h($wi)."' class='icon icon-$Ld'><span>$Kd</span></button>";}if(isset($_GET["file"])){if(substr(VERSION,-4)!='-dev'){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");}@ini_set("zlib.output_compression",1);if($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("h:M‡±h´ÄgÌĞ±ÜÍŒ\"PÑiÒm„™cC³éˆŞd<Ìfóa¼ä:;NBˆqœR;1Lf³9ÈŞu7%©d\\;3ÍÇAĞä`%ŒEÃ!¨€¬e9&ã°‚r4˜M‚ÂA”Øv2\r&:©Î¦sœê“0ìÛ*3šMÃ¡…ºä-;šL‡C@èÌi:dt3-8a‘I\$Ã£°êe§Œ	Ë#9lT!Ñº…>˜e“\0ÊdÄÉdõC±ç:6\\c£A¾ÀrhÚM4ëk·ÔâÎZ|O+—“f9ÉÆXå±7h\"ì–Si¶¨ú¼|Ç+9èáÅÆ£©ÌĞ-4W~T:¹zkHá b{ Ğí&“Ñ”t†ª:Ü¸.K v8#\",7!pp2¡\0\\Á¼ ˜\$Îr7ƒ ŞŒ#€ği\"ôaÌT (Lî2#:\0Î¤ËxØ½ÌXFÇ‰‚dš&Îjv• ¨°Ú—£@düE¯ÛúÿÀ!,9.+˜`JáahbDP<Á°|\"Âèò¢¨Cpì>ÿË‘+b2	Lˆ¡é{óF’ˆÏQ´|™¦©ºr„ÁKl’ÉÔ_Æt=ÉÏğbÀK|©ÍŒ®ºª\r=ÓR¬>“ è0±£(ö¼¯kèb‰JU,PUumI.tèA-K”üXı4²z)MPĞçk’ºÙ3e`¾N>D#Â9\\ƒ(YTÛã@÷hL“Å1]È´ƒÍºNKÕ¶2\\73i/V¤¯lÛÅYÁÒ—BAœ/[JÖİÄ˜Ğ’\r;'²2^í…ªÔb¹Û£3éT=0Hö8\rí+6¹kf×CÏ]qÕW)‹ë˜ÆÂ²C•ÿ2`A–°¾82Á!À¾hmÇĞ²GD£‹ú¼2-C ö‹Yc`Â<¾s È6ù2µŠ¶9ˆu”æøyÌÒMgyí=,CZO~^3¥Òî0Ó2‹<¡Ğk0£…wM®{d#`ZÛ€ú‹ŒãŞº±ëù‚æ6¯C%»¼ª=Rq•Øğ¼_+ìµ-ÛK>ôÒ\n'GÀˆòA¡\$íú¡¡^j><ögf¾hømb*/\$\$lµÀØ´µg)Aj×ó wò#á£ƒƒ‡ÁÔÕõ…TNÕ]„Tÿ¾ã%ZÿùjJ–¦ªCf4ÆòázFà'ˆ*’ xª¹ª¬Öho&k\räÅ,­árÔ:>s(úLAsë€®¸5Ct‚¥©n†6¤ãË ll\\9DåÜ\\!‚mv‡\0ÊA{9`ˆ.¸â ×¡S’lg6¡àÛ!.2Á˜0‡PØÔ Ñi\r\$7‡w ¿â;G°Æ\$œ0âCIÃ^J\nçL·Pc¼Š'š*Eh°—äb‰”;µpŞBÃÛ(‰x‰:DÈLË.j±9AC@ I3jf’5sI`X}ÆÜÒ”#‚ºà7ŠT`d”¤jhH49S\nq”HJQ ØH`,F‡†PÊ0\\{ƒ¨m\rÌ~@20u!	\$†PoQ¥4ÇšÎ\nZLæM‚°BêÂk)@¶d³éSLÈpv’ Ïy²ĞƒBß“^oà„ä›*¾R™\"ÒıÓâ#€ºrÍ¥S;\r4ï&GùI©æT	°r’àê9=6’¡ÈQ’T\0“\0Äïf#¤ù=\$ÕŸ‘¢ŸÒH6†PæYƒ:®G\$œš‘ İ0è9:a”3Hz;GÃ\r!hJ±nĞ7‚	ıoYÿú¨¨¥ôWLvÌÛ“i|ŠùÄ%‡-â¤d\$‰pŒDä‘R2T\rÕpaU§øn—5rª„jÁ\$ƒr%D†©Ç)\$GÚƒBuÎà:²ø`¥(l€ àSD)I	ÀÖÁØ9ç*ê—\rtĞ2¡ ÆzI™§›g[Xûc,u\rvJ5?§ªÒÃ\"Š:„^‘.uàJùP“o\$t\$ƒ18„Ò\nnKÄT%EZ,6íDH„Vôó†ª¹i«&z¶«xpdrx*ƒ}ÊR25+Š’Ñ“fì2‡w»qş0X1Ï2dX‹ß¢èÌW©æÃ‹V8f\"ëƒq(uğ…E™ G“qM#Ğ°ñ#K•3WAŞv†YôÌç‰ÃeK]t¾˜]EÂëj=SXî™„@€ÏÓ‡\rŒÓ˜\$åÖ9ÂäÜ¬0ØP7\"D.åŒ<;ª‡Njæ=õºüÀå^èmmÚ’G¤68 ÆC’%v'ªyßÆk/‚^˜5šì3ä@Í.Ú›„kŠa’*—DºßÑ³‘:Ñ7ÿ•C}ıÄ`ø`í`)İ7Îõç­|	3Ğ iÕé¨½Âï4•\0.:®QßLƒçäØœÍ¨»Üfâ'™%äİ©M	¥¡ÙY3“\0Ç##tP6(‹BÏd§ ©Èoƒy¯6­|à5ş¸IH7 —âöæ„z?ù(ÑÚÅ–\$«RWT¼è°¦“:ˆû(ºá`rÏ¶i… ‚s‚=D\\ƒ˜,kr1ôÆÙ“2Ø`êñAä9˜¢&nÁ~ÍÌÒ¬£6;«vp ÓM#È]«¿ïàÉ´ÃÙØA˜’ÅiJ‚Œ.şÜá»tî»çÄîYsíOPwÄ¸¦mâæZ“‡İÿÀAUãÊ·JğN?Š€z»3\$PôqsÛU9,©ÖÊóó’#©µ5Pnb¦ãuNÑ{N`êé¥™šÛ°i—w\rbº&Eµ\\tg÷ğb¾a1‡+mÈpw#ÂvlÇıUÔö¡Ò\0ïe.(’wb@ïéş´\\°w…(…)´èEŸ¼¢;äZé]/&‘ËÃ|>Q¶\"c	<Fœ\rÑ7‚ÜÉåÏµ\\“'ÊSfe÷\rRŒ–ğŸVlo/.’’\nÖàFì±oè ·ehıÔeñÃj× ÈTÙsa4³2à2† Ö`oÎ\\¿A?ö]ğIoB[±{7' İQ%¨6ıs7\$Ã“ò‰‹~%ƒ»u)Õ5i ÷ÿ0¡#†¥£†\rÆfäËMX˜N\\É‡\0­¤ï,TüíôETo\0{oÄÂRˆ˜r‰^…§äîC @Z.C,şÂcû'°J-êBL \r€P«CD\"êb ^.\"ÚóàÊÀh€¤\0Øà€Ø\r\0Šà‚\n`‚	 Š š n o	€‚à”\r ´\r ¢0À` „0£¢	Á\rpš „	0À\n ’F@’`à V\0 \n€¢\r\0¤\n‚jÀÌ\n@ \0Ü\r Š\nÀ	 Ş\n@Ê@à\r\0ğ& ë\n@Ì @Æ àz­†Æ‚*Š©wĞq0g£5ğaPxGÂÕ…	•	\n¥\n­µ½\rpÅÍ\rpÓ\rÁ	Ğß0æ\rëğó`¢\r@ğ@†º ì‰à°é^\r ğ\0î\r d@î€ğ3 ­Ñ1Q9ÑAB„¨<®tÈ1N?ÑSÃá°v-‘ağƒp‹ğ“	pÛP§\n°¯°¿0Ç°Ï°Õ•\0ƒ@ÑĞíõ‘©Ñ°\r ^Àä\"i@\nÀ Œ6 ˜\0ê	 p\n€ò\n€š`Œ ˆqŞ’QDí¦BäM°d9ñTUq÷1û Ã2’	ò\n2²rR#°Ğ2+\r’/€Õ#‘¡@’ñ\" ÖQ\rÀÜ€˜\ràŒÀÈ@\n h\n€ã€ªíÀ†\0Ì`¨	ÀÆ@±!ñ;ñCoæUÒ‹2‘õò›ñe Qk ±p •àŸ!P±3Ñ!àƒr%ÒÁÛpÀ	 Í,Ğğ`ìğî\n `\n@êàff ° ª`Æ ’\nà¦@´	€âF#È`p€í# ‚äÿoÂÿ \\%Bl»Ï?çŞM-jPñór–¤3/Ó3*QlpÀ	pŸ\r`“=€Î\n\0_>±1’'ñ‘#\0ß>\0¡\rà”€Àäà˜„ ¢\n@â€ fÊ0Á'±@Ä€ŞÄÀÊÅ\0è\rd€FhµI\$ó`Ø”è,Üò¤¸é‰çCÍÈ•Pİ”T”>Ê7\0]EÌ£Ê‰DG¼©ÍAC´\\BMDÔˆò¥fmd—è(\rôOG FçÆiDN†ïÉœn2é4tÎ”wFt”íFŠí®ŞÙHšCÔˆu+°èÏ\$K¬6è”“Eä¤.AKÔL*1JK>º©èÈñMÔÁH¨ø\"GN„PjÄE´>ì ëH&5HÔ÷LM#EÍP‘c†¶c8‰æl €£¢,ÿ¢µCâ¦N€PtÇ@V tü\nşÔÖİ´õI	kGÎH	¤)D(™JPl„1jnğlÔ¬ÜJí~Ø*&ğn\\ÙÕ†HUfLk¯KôrFºìÇ<|HNx\\ NlêNäòôI¢Ö\0rzMtŠU|ZêšôÄ¸¦õºåÔ˜è\r“HCşÎ€B\"æ@ób¦cnœA —ÆJ9Ort´A4¸\rªÙ@hòÀA^`Û^¥V0Õ^!LòjUşğÛ.µ^\r\"±¬ì©kaõŒìlp‘‰d‘ Öş©}\0î¬ÖNï•àÔş•IGPÕöËUûYtyMPrä‹YÔÒEÚÔ¥xÄÔÄé6`×`jg´Şµ1SB²Ü‚°èòíÕXÖ8–V?Id[IµßQ.ŒåÖíİ`•ñi¬î‰Ì²vÔÉÂU)ÔœÎàÓ\n2PVœ~ãÌ ‰¢ˆŠ¦\"\"&§µr]-¢ áp*¨\0f\"†Kj`Ïq\nJã\"q¥F¬.öú\"@r«Â(³`òä3q>\"‚ôf‚Ø\r\$Ø­£ˆ ¢R1Ìªh&Hú`Z¬V	u+MoÊ¬¬\n3Jê\r Ä×2Iü ©D'×!S0W0J?yûpjZ.·\n\r ÷“pw–\"—-+ãzr!`Å|v2\nl¢f(¤m†<ƒÌ=âF\r©Q}ƒÏ~7æÌ\r·à#å½oÎ3÷ï}·ìØx<ø~×ıW¬øiEÃ£€à[‚8\n bjjë\r‚˜: ïƒØ¶)vÖê'{·ÕVçq\no{·±…)Cƒ˜‹àß‚˜i†Àê\rø%·é€ààÊC˜(œØkkôø‰‚‹ô4Ød”¾ ¿†øŠ‚jXLN÷(A—}xe‡ø‰|ø±wø´ÁG†€xhäí„Xx¦\r˜Ô%K¾ö…Ş¼oqƒx¸ˆ•˜»Š8s4e‡¸xÏ¤ínÓ*4Føc8~ŠhIp]Šâ{…åÎ%ù( ·Ó’øğ<åV÷à£ø†C¹B–Ø{wØıˆ¹O”€Æ£øù}¹Q‰8•[”×ñ•[Œ{“cT%ù&´Êo–·Á—¹:š*béE”`é™m–IŠYW™kš8›•Yo”™§šù—u¸)–¹™—Y5o™9—ãŞ¦÷’Ù‰‘¹<¦8(ù?œ\0[s×@*8·˜·}¹ßŸ9g›\rÓŸ¹—–¹”â\0Œ˜\n'wÂ±x)İŒÙ©šµšº—92·Z1ùï@[Iº+¢÷_“š5‚7=‚šD§qz!}ºK¤ùNdå£Ú3‚\0æã†qº+—C¡Øú¿Y_g‡8Øúy¸½‰Ú‰¨Kâ4Ú{‡ÙS¨8–2Z—zX\0Ï¨z©‡úS§ØªÚ±¹e« ¾\rª>¾:£§ù­‘ÚÇ¬´_¬ZÃ£’e»¬úµ¨®:ç¨ø¿œ•u„÷­{ÈUM‹—Úašƒ°ØíB«zÉˆû‰ãb2YSWJ(wOwÓwm› ØªZN÷l¶åË§CÌæ9å§í´€Ğæ8BDÊ¤6Œ©£Zy±x{ˆèæ;!©[mƒš¯¬Û{}»¸)¯¸#Î4¶[®´Å(½bˆ½ É˜¸«úÕ†›u¨û­«™«¹Ê,O¥\"Fª7y?»9£¼ÙndÑ}»±¹™{İs½{¹Š e´Ê¦>\"Öcc§‡¬d¤ŞÒcs{şÌvdCN½[Àû¹GM¿Cç“­ÉDE@");}elseif($_GET["file"]=="dark.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("h:M‡±h´ÄgÆÈh0ÁLĞàd91¢S!¤Û	Fƒ!°äv}0Ìfóa¼å	G2ÎNa'3IĞÊd•K%Ó	”Òm(\r&ãXèĞo;NBÉÄêy>2Sª*¶^#ŒÆQŒœĞ1=˜¥ÆJ¶W^„L£‘¡Ëo¡ˆÌÆc!Äf³­6»mâ¾a˜¯³l4&1Lf³9ÈŞu7VDc3Øn82IÎ†°Ê,:5ŒÊØr·P«ö1 Äm¡>5™W/™Fc©‡Dh2˜L‚\rN¯“ËİWo&Ähk€Şe2ÙŒĞÀb12Æ¼~0Ğ ”ãD}N¶\0úf4™M†C™”é­×ìn=´ˆpã©ZØ´²NÓ~Í;îŠÑ-C òæ%êz¢99PéÁã¤\"­Â‹²ß;‰\0fñª8à9¡pÔ:mÜ8ã„â@\nXà:rØ3#¨Øû­CÀ[¹Cxî#€ğI2\\š\"¡Àp“ÆÑÀ]#¨Ó5R¬r6Œ#ÃL7ğğß!Hƒ\$\$IRdÛ'Ë‰8]	§ƒxÜé+ò”¦>ÅC˜@-ã¨Û;ÃîÜbï<ä©2Ã”ğN4,„Œ«ã”ã-Mr¥6IcšX4¹aÊ†Ã5KETh@1@íÍR®K“9\r£(æ9Œ#8ËG¡CpwID5Ì2Øl\"_'ÓÊUBŒÌU¡9c@ÃG=C\nèÛSÈ0§Õàj®×7PUàÈ†£Û9J]®<×‹\nÆ²Ïƒzû?B÷Ô2—ÍÒÜ4\r/˜P\rÁM[X¡‚F‘_ìÿjŒ¬›HÓbnC&ŸÂ¡f%@cC^.2ã8¨×CÑ}^˜sw½LğÂ/ø5OÙM‘ä¸Ú³	*Xî?ŠbÍ.IgÊÔ&óaq„İŠ>‡çšFNå½-’`æy¬ä4¥s»áÓj\\&:ˆSaåP;ô¼†²H‘ëû”®XŒÎŞ¯Œéd¡kt?.´õ±,ZOÁ·@@8Z3©cŸ\"ÑèÃŸ\nØ=AšH1\\œZÏ^/kêÿÅÎƒLíuC\\ñc)0OüÃMÍïlpr†—7ä\rƒ‡q˜†Á¶ÙWRaÆŒ¡¥Øöc@Áwm’k/Û8£*?ÇÌè4ª5æ\\mŸ§¡kàù>d1nğëUQ#£§Üø¾wçæ†Ÿ«Lo&hÄªPrnR,5„Ÿ‡ôzƒ\"\$3»”dYH(p\rÂALACš)pTõPl²!\"L€´8ÀÂRà´&…\0µ“‡îZà±’0P8×ÆûÜã‡ÉJ	‡`Â¨e†0	®€Úœ1ûŠ	®D‘ÄJs°H‚³ˆ)™kÆ ¡[ÅóÔCÈy‚pjx,\rA‘…m!‡Ùœ<h1äœ");}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("':œÌ¢™Ğäi1ã³1Ôİ	4›ÍÀ£‰ÌQ6a&ó°Ç:OAIìäe:NFáD|İ!‘Ÿ†CyŒêm2ËÅ\"ã‰ÔÊr<”Ì±˜ÙÊ/C#‚‘Ùö:DbqSe‰JË¦CÜº\n\n¡œÇ±S\rZ“H\$RAÜS+XKvtdÜg:£í6Ÿ‰EvXÅ³j‘ÉmÒ©ej×2šM§©äúB«Ç&Ê®‹L§C°3„åQ0ÕLÆé-xè\nÓìD‘ÈÂyNaäPn:ç›¼äèsœÍƒ( cLÅÜ/õ£(Æ5{ŞôQy4œøg-–‚ı¢êi4ÚƒfĞÎ(ÕëbUıÏk·îo7Ü&ãºÃ¤ô*ACb’¾¢Ø`.‡­ŠÛ\rÎĞÜü»ÏÄú¼Í\n ©ChÒ<\r)`èØ¥`æ7¥CÊ’ŒÈâZùµãXÊ<QÅ1X÷¼‰@·0dp9EQüf¾°ÓFØ\r‰ä!ƒæ‹(hô£)‰Ã\np'#ÄŒ¤£HÌ(i*†r¸æ&<#¢æ7KÈÈ~Œ# È‡A:N6ã°Ê‹©lÕ,§\r”ôJPÎ3£!@Ò2>Cr¾¡¬h°N„á]¦(a0M3Í2”×6…ÔUæ„ãE2'!<·Â#3R<ğÛãXÒæÔCHÎ7ƒ#nä+±€a\$!èÜ2àPˆ0¤.°wd¡r:Yö¨éE²æ…!]„<¹šjâ¥ó@ß\\×pl§_\rÁZ¸€Ò“¬TÍ©ZÉsò3\"²~9À©³jã‰PØ)Q“Ybİ•DëYc¿`ˆzácµÑ¨ÌÛ'ë#t“BOh¢*2ÿ…<Å’Oêfg-Z£œˆÕ# è8aĞ^ú+r2b‰ø\\á~0©áş“¥ùàW©¸ÁŞnœÙp!#•`åëZö¸6¶12×Ã@é²kyÈÆ9\rìäB3çƒpŞ…î6°è<£!pïG¯9àn‘o›6s¿ğ#FØ3íÙàbA¨Ê6ñ9¦ıÀZ£#ÂŞ6ûÊ%?‡s¨È\"ÏÉ|Ø‚§)şbœJc\r»Œ½NŞsÉÛih8Ï‡¹æİŸè:Š;èúHåŞŒõu‹I5û@è1îªAèPaH^\$H×vãÖ@Ã›L~—¨ùb9'§ø¿±S?PĞ-¯˜ò˜0Cğ\nRòmÌ4‡ŞÓÈ“:ÀõÜÔ¸ï2òÌ4œµh(k\njIŠÈ6\"˜EYˆ#¹W’rª\r‘G8£@tĞáXÔ“âÌBS\nc0Ék‚C I\rÊ°<u`A!ó)ĞÔ2”ÖC¢\0=‡¾ æáäPˆ1‘Ó¢K!¹!†åŸpÄIsÑ,6âdÃéÉi1+°ÈâÔk‰€ê<•¸^	á\nÉ20´FÔ‰_\$ë)f\0 ¤C8E^¬Ä/3W!×)Œu™*äÔè&\$ê”2Y\n©]’„EkñDV¨\$ïJ²’‡xTse!RY» R™ƒ`=Lò¸ãàŞ«\nl_.!²V!Â\r\nHĞk²\$×`{1	|± °i<jRrPTG|‚w©4b´\r‰¡Ç4d¤,§E¡È6©äÏ<Ãh[N†q@Oi×>'Ñ©\rŠ¥ó—;¦]#“æ}Ğ0»ASIšJdÑA/QÁ´â¸µÂ@t\r¥UG‚Ä_G<éÍ<y-IÉzò„¤Ğ\" PÂàB\0ıíÀÈÁœq`‘ïvAƒˆaÌ¡Jå RäÊ®)Œ…JB.¦TÜñL¡îy¢÷ Cpp\0(7†cYY•a¨M€é1•em4Óc¢¸r£«S)oñÍà‚pæC!I†¼¾SÂœb0mìñ(d“EHœøš¸ß³„X‹ª£/¬•™P©èøyÆXé85ÈÒ\$+—Ö–»²gdè€öÎÎyİÜÏ³J×Øë ¢lE“¢urÌ,dCX}e¬ìÅ¥õ«mƒ]ˆĞ2 Ì½È(-z¦‚Zåú;Iöî¼\\Š) ,\n¤>ò)·¤æ\rVS\njx*w`â´·SFiÌÓd¯¼,»áĞZÂJFM}ĞŠ À†\\Z¾Pìİ`¹zØZûE]íd¤”ÉŸOëcmÔ]À ¬Á™•‚ƒ%ş\"w4Œ¥\n\$øÉzV¢SQDÛ:İ6«äG‹wMÔîS0B‰-sÆê)ã¾Zí¤cÇ2†˜Î´A;æ¥n©Wz/AÃZh G~cœc%Ë[ÉD£&lFRæ˜77|ªI„¢3¹íg0ÖLƒˆa½äcÃ0RJ‘2ÏÑ%“³ÃFáº SÃ ©L½^‘ trÚîÙtñÃ›¡Ê©;”Ç.å–šÅ”>ù€Ãá[®a‡N»¤Ï^Ã(!g—@1ğğó¢üN·zÔ<béİ–ŒäÛÑõO,ÛóCîuº¸D×tjŞ¹I;)®İ€é\nnäcºáÈ‚íˆW<sµ	Å\0÷hN¼PÓ9ÎØ{ue…¤utëµ•öè°ºó§½ 3ò‡î=ƒg¥ëº¸ÎÓJìÍºòWQ‡0ø•Øw9p-…Àº	ı§”øËğÙ'5»´\nOÛ÷e)MÈ)_kàz\0V´ÖÚúŞ;jîlîÎ\nÀ¦êçxÕPf-ä`CË.@&]#\0Ú¶pğyÍ–Æ›ŒtËdú¶ Ãó¼b}	G1·mßru™ßÀ*ñ_ÀxD²3Çq¼„BÓsQæ÷u€ús%ê\nª5s§ut½„Â{sòy¥€øNŸ¯4¥,J{4@®ş\0»’PÄÊÃ^ºš=“¯l„“²`èe~FÙ¡h3oé\"¤”q·R<iUT°[QàôUˆÇM6üT. ºê0'pe\\¼½ôŞ5ßÖÌ”pCe	Ù•Ô\"* M	”¨¦–D™ş±?ûhüØ2¡ĞãzU@7°CÓ4ıaµ²iE!fË\$üB¤…<œ9o*\$¯ælH™\$ Å@ààÊæ€P\rNÀYn<\$²	ÀQ…=F&¥ *@]\0ÊÏË W'dÖ z\$æĞjĞP[¢ö\$òä¯Ğ0#& _Ì`+†B)„wŒv%	âÔ›LcJ„€RSÀÂi`ÌÅ®	F€W	êË\nBP\nç\r\0}	ï¦®0²Zğ¸‚ò/`j\$«: §8ieüÀØÏ†xâ¹Â±îa ¬GnøsgO¢äU%VU°†@‚NÀ¤Ïúd+®(oJï†@XÆèàzM'FÙ£àWhV®I^Ù¢™1>İ@Ğ\"î¨¤‰ ÈQñR!‘\\¢`[¥¤«¨‰.Ø0fb†F;ëÂ‡çFpÏp/t`Â ô®(§ÀVé¸ø b“È²‰(€ˆHˆl‚œÁÎÔ¯1v­Ş‘€ğHĞï1Tï3ñ“q›àÉ1¦ÑªfË\nT\$°éàNq+Ëí`ŞvÖÇœï\rüVmûÇr°¨Ø'Ï¸±ñg%«\"Lˆm¼…‘(’(CLzˆ\"hâXØm= \\H\n0U‡‚ f&M\$¤g\$ñU`a\rPş>`Ë#gªhôî`†R4H€Ñ'ç©­³²GK;\"M¶Û¨TŒhµBEn\"b> Ú\rÀš©#›\0æ•N:í#_	QQ1{	f:BËÂáRª&àÜã)JµÄBr¹+ÂK.\$ĞPqõ-r®S%TIT&Qö·Ò{#2o(*P¯â5ï`„1H…®¢'	<Tğd±÷ª¾sÀì,NÚÊ ÒÉÔì^\r%ƒ3îĞ\r&à“4Bì/\0ĞkLH\$³4dÓ>ŠàÒ/³à¶µ€Hö€·* ºù3JÇĞ¥<†Hh©pú'‚çO/&ï2I.îx3V.¢s5Óe3íªÛZÛ(õ9E”g§;R—;±J½‘QÃ@ªÓvgz@¶“‚Şó†'dZ&Â,Uã²ßò¦F æb*²D‹òH! ä\r’;%‡x'G#°šÍ w‰Á#°Ö È2;#òBvÀXÉâ”aí\nb”{4K€G¦ß%°†ÒGuE`\\\rB\r\0¨-mW\rM\"¶#EôcFbFÕnzÓóÿ@4JÈÒ[\$Êë%2V”‹%ô&TÔV›ˆdÕ4hemN¯-;EÄ¾%E¥E´r <\"@»FÔPÂ€·L Üß­Ü4EÉğ°ÒÄz`ĞuŒ7éNŠ4¯Ë\0°F:hÎKœh/:\"™MÊZÔö\r+P4\r?¤™Sø™O;B©0\$FCEp‚ÇM\"%H4D´|€LN†FtEÑşgŠş°5å=J\r\"›Ş¼5³õ4à¾KñP\rbZà¨\r\"pEQ'DwKõW0î’g'…l\"hQFïC,ùCcŒ®òIHÒP hF]5µ& fŸTæÌiSTUS¨ÿîÉ[4™[uºNe–\$oüKìÜO àÿb\" 5ï\0›DÅ)EÒ%\"±]Âî/­âÈĞŒJ­6UÂdÿ‡`õña)V-0—DÓ”bMÍ)­šŠïÔ¯ØıÄ`Šæ%ñELtˆ˜+ìÛ6C7jëdµ¤:´V4Æ¡3î -ßR\rGòIT®…#¥<4-CgCP{V…\$'ëˆÓ÷gàûR@ä'Ğ²S=%À½óFñk: ¢k‘Ø9®²¤óe]aO¼ÒG9˜;îù-6Ûâ8WÀ¨*øx\"U‹®YlBïîöò¯ğÖ´°·	§ı\n‚îp®ğÉlšÉìÒZ–m\0ñ5¢òä®ğOqÌ¨ÌÍbÊW1s@ĞùKéº-pîûÆE¦Spw\nGWoQÓqG}vp‹w}q€ñqÓ\\Æ7ÆRZ÷@Ìì¡t‡ıtÆ;pG}w×€/%\"LE\0tÀhâ)§\r€àJÚ\\W@à	ç|D#S³¸ÆƒVÏâR±z‰2Ïõövµú©–‘	ã}¨’‡¢¯(¸\0y<¤X\r×İx±°‹q·<µœIsk1Sñ-Q4Yq8î#Şîv—îĞd.Ö¹S;qË!,'(òƒä<.è±J7Hç\"’š.³·¨ñuŒ°‡ü€#ÊQ\reƒrÀXv[¬h\$â{-éY °ûJBgé‰iM8¸”'Â\nÆ˜tDZ~/‹b‹ÖÕ8¸\$¸¸DbROÂOÆû`O5S>¸ö˜Î[ DÇê”¸¥ä€_3Xø)©À'éÄJd\rX»©¸UDìU X8ò•x¯-æ—…àPÌN` 	à¦\nŠZà‹”@Ra48§Ì:ø©\0éŠx°†ÖN§\\ê0%ãŒ·f“˜\\ ğ>\"@^\0ZxàZŸ\0ZaBr#åXÇğ\r•¨{•àË•¹flFb\0[–Şˆ\0[—6›˜	˜¢° ©=’â\n ¦WBøÆ\$'©kG´(\$yÌe9Ò(8Ù& h®îRÜ”ÙæoØÈ¼ Ç‡øƒ†Y£–4Øô7_’­dùã9'ı‘¢ú Üúï²ûz\r™ÙÖ  Ÿåğşv›G€èO8èØìMOh'æèXöS0³\0\0Ê	¸ı9s?‡öI¹MY¢8Ø 9ğ˜üä£HO“—,4	•xs‘‚P¤*G‡¢çc8·ªQÉ ø˜wB|Àz	@¦	à£9cÉK¤¤QGÄbFjÀXú’oSª\$ˆdFHÄ‚PÃ@Ñ§<å¶´Å,‚}ï®m£–rœÿ\"Å'k‹`Œ¡cà¡x‹¦e»C¨ÑCìì:¼ŞØ:XÌ ¹TŞÂÂ^´dÆÃ†qh¤ÎsÃ¹×LvÊÒ®0\r,4µ\r_vÔLòj¥jMáb[  ğƒlsÀŞ•Z°@øºäÁ¶;f”í`2Ycëeº'ƒMerÊÛF\$È!êê\n ¤	*0\rºAN»LP¥äjÙ“»»¿¼;Æ£VÓQ|(ğ‰3’†ÄÊ[p‰˜8óú¼|Ô^\räBf/DÆØÕÒ Bğ€_¶N5Mô© \$¼\naZĞ¦¶È€~ÀUlï¥eõrÅ§rÒ™Z®aZ³•¹ãøÕ£s8RÀGŒZŒ w®¢ªNœ_Æ±«YÏ£òm­‰âªÀ]’¦;ÆšLÚÿ‚º¶cø™€û°Å°ÆÚIÀQ3¹”Oã‡Ç|’y*`  ê5ÉÚ4ğ;&v8‘#¯Rô8+`XÍbVğ6¸Æ«i•3Fõ×EĞô„Øoc82ÛM­\"¶˜¹©G¦Wb\rOĞC¿VdèÓ­¤w\\äÍ¯*cSiÀQÒ¯“ã³R`úd7}	‚ºš)¢Ï´·,+bd§Û¹½FN£3¾¹L\\ãşeRn\$&\\rôê+dæÕ]O5kq,&\"DCU6j§pçÇÉ\\'‚@oµ~è5N=¨|”&è´!ÏÕBØwˆHÚyyz7Ï·(Çøâ½b5(3Öƒ_\0`zĞb®Ğ£r½‚8	ğ¢ZàvÈ8LË“·)²SİM<²*7\$›º\rRŒb·–âB%ıàÆ´Ds€zÏR>[‚Q½ŒĞ&Q«¨À¯¡Ì'\r‡ppÌz·/<‹‡}L¢#°Î•ÂĞâZ¹ã²\"tÆï\n„.4Şgæ«Pºp®Dìnà¥Ê¹NÈâFàd\0`^—åä\rnÈ‚×³#_âÄ w(ü2÷<7-ªXŞ¹\0··s¬ø,^¹hC,å!:×\rK„Ó.äİÓ¢¯Å¢ï¹ÔØ\\„ò+v˜Zàê\0§Q9eÊ›ËEöw?>°\$}£·D#ªğã cÓ0MV3½%Y»ÛÀ\rûÄtj5ÔÅ7¼ü{ÅšLz=­<ƒë8IøMõ°•õâGØÑÎŞLÅ\$’á2‰€{(ÿpe?uİ,Rïd*Xº4é®ı¿‡Í\0\"@Šˆš}<.@õ’	€ŞN²²\$î«XUjsİ/üî<>\"* è#\$Ôş÷Õ&CPI	ÿèt¿áùü¦î?è †´	ğOËÇ\\ Ì_èÎQ5YH@‹ŠÙbâÑcÑhî·ùæë±––…O0T©' 8¡wü»­öj+H€v_#º„íïì06ÈwÖœX†à»d+£Ü“\\Àå–\n\0	\\ğŸŸ>sî…ÓšA	PFöd8m'@š\nH´\0¬cèOwSßØ’—Yá`²ˆˆ¨¢R×ıDna\" ì™~Â?Ámğ†|@6ä½+ìGxV’ä\0°‰WƒÓ°’nw”„‘.¡Øƒb«Ÿ9Ã¸ˆEÈ|E·ÃÂ\rĞˆr¬\"Ğøx„‘¸-¸êŠâš\rN6n·\$Ò¬ı-BíHæ^Ó)â¥y&ãã×šW–Ç§àbv…Rì	¸¥³N\0°Ànâ	T„–`8X¬ğA\r:{Oş@\" Œ!Á¤\$KÂäqoĞËjYÖªJ´şÂíÜh}d<1IÇxdŠÊÎTT4NeeC0ä¥¿‡:D›FÚ5LŞ*::H”jZå—­FõRªMÖ€nS\n>POó[Œ\$V8;#‰K\\'ùBÖè»R®Ø¯°›RÑ_8Ájé*Ej \\~vÆÂĞvÄÛp@T€X‹\0002dE	…Hí‡Vğñ×D”\"Q'EDJB~A´ƒA¤Il*'\n¶Yå.è›+©9¾ñpg†ƒÒ/\"¸1—8Ä0„IAÊFCÈ¨ŠV*a™èPÀdÖĞ£5H\" AØå6İs¬YİØ;è¨È/¨¸0ãv}y˜\rÍƒâÎ×¥1…u\"Ë‹Šmãñ_º0ç„„`ß¯¿\\B1^\nk\r]lhø}]HBW`±—0½ê¨¹rFf€)”W,ÕÒ§]sm9'O¢xÔ½Í,ê9J8§£? 4ÉÉï¡\"Ò…èÛ½Ì<Ñ-S¨ÉÃşMÃ;ĞvÌñ6y|„ZòÁ‹¨%àa•#8¢ˆTC‘!pºË\nØïCZ(ï½wéØa– ·ˆÁ?9|€ó0<BL\r‰\nˆ]ÀPB0¤&‘+tÄHƒñÖ…àDx^÷î³,Lğ}[¦ÄBñx}½ĞruĞË\0¾€\0005‹åS@\"UØ”@Ü°\0€\$äÁŞ\"Ò ŸÄ]l/	ùíIâB4¯™.Â6 Â…ˆd7ˆ\r@=‘ªß¬¢ÕÛ*G jŒ¬Šüf`»:Hnì‘ÔbÄ€71Çê)C<@AÍY#°¦¡ëÑe’oâÖY!ÅÊI’DM¼\nlt¨“€/)˜\\43)®Ù2ï­É¸Ó)ÁŒ²f[ ppp1€µ©#“‰Ã¶p\0Ä§Å“l›À^{€„Aœ¤THå6ÖÊ«è\n\0PâH€.\r›’|ÀTFD0ŠS€y”ğÀÏ'1Ö´¤K’² dØµ±¯ÄBş”™Cç&Å)şW€s Hee+@4– r·“áÛš*Lp1<üf‚N–Y'­-	XKVa¦–L­¥ö\"›€Œ\"ìl•£q…É.YJHàm HV/lCŞ&àÀH)oÁ&\\2Äœ­%âáéz\n^Q(6ì˜D€ ÈûJq°–á«\00a#Ë6\0vr,»MÌú&A„Ôòìœ»‰9%YdBêhÀÖ!W\0êb\r{˜”Æ@Ç1¹‹I¬22AÚÚ)™H¾a@r’0GÉÜ7Dd.LM˜<˜ã2ĞÈË,k/™Meª¹œ}Ò’3ä=\0Ğ&É‹B‰ø\nPd.\"ÈñF3XÈSd(*¨J6 ä‡‹–F:¬×)1Â1á?lQ&Ïùµ¬h<JÍ‹¤f‡d–EÕº*ñx\n\0¼À.\"B -…#£ÀÎ—t¿IÎ«õ›Ğ	I8 ²’8dh	«èƒx€Ÿ§~°ƒ	L!K(úBXµ£-Èì‘hÎåc/Öræ×PÕIõ«NÊ2È|Éç×¶ŸÒ|\"µM‘'¡K,\\H°Ée5*o]4—ÒFP	2›Í<)ˆT¾“o˜À\n¢¸ØI¶Ú¢Ä!¨(øˆ‰_8Xrç;uŠúàØNJù„¡ˆé[rû˜DC:¸@ÁÍ³Àlœ\0©e\\*x@AÈ¡&í(‘5Ã×,ªŠ˜#1xÀ º!T D„ª­(QƒŸáDJ|D D:\0ÉAÙĞ¹Ô ÁbaEÓ?rn°²WkxŒøX=i‡,\$3[‚r™9B•Æ±§dã¡ş\0ºÔH‘4­«É<(zÊºô?àsIbJ©g UÂ\n(}¨Š›J\"à¦A™€BĞ19…~ÅIé#Ú\$¹‘%d  e\"µ`Àìátª¨•'O= À @\$µˆO”\nmT×o+Zäñ™ø-­„¢êßPF?Ò_…I¤JËX Ä£2Â¢ê-V¶;ª?2¥Áá0¡*P3Éªõë_T<E¥JÅ\\(İ2ô €Ø)êIQ‘Šé¬©·óÉRŒL&¥Í!È¯KÁiÑ†’t»¤°ÎKúHRl¢È¬Es“¶‰…¿¤DøŠxÇ´¬i¾ºÖ!faBÉñó¼FÔËe>€Vç©É-QjÂI‘Å7§˜ş\"%RhÈ g£áMŒ³ø«Õ-b£58RÂ‹¨„¯Ä*ã§9ÔÆêŠ°«·Ô9¤2Q0ı‡¬IR[üZ£İN\0÷ÇÂ20£¡ŒÂĞ\\[@áQ\0¤ÔJx„ùµ…äEC{©â\$lp1=\0·RĞ¾É>E~ßÆê×„ˆÑ:0À˜%€R+)\0°	Æ‘Qá@(\"¡_jT•X\0˜„ì\r1“\0P“9#\0”ÍôòH;Bª|À™²LöZ‘¼ÆŠ‹6ù/B’à\nB{ñğà|HÄ,á	*;œ(õ`Ê2@6ª>¡	å?P\0/„¹ó\0|\\ÅeBÜ`›’jq©U/\rc©üêÔÒ†¤6(N\0º/\$à\n8µj*U…\$›ñºŠy*³=¬;ˆ„ğŸ\$f¬â8XØBCEşœr\"/Ÿàª‚kÚ%\\9k§ùèBšœğ0§F­À(¬ğ'ôUôªµÆ®m¤@k‰T\0Õ¹EáÍsEhyòe\nä) )“b7ªã(W%,ÈJ¤r¨ó2D¶rhEùŸ\n0Qê3Š U9TPOÀŠÕô‘°8j|¤}ÃR<0‹Èâ™Zl ĞØTáö°ŒÈÙÚ*¯\$ÎÀU\rÛ\"¤.ª Ts~Ë~(ğ3€aº¨œ@ˆÕ+là`:Î`­:O…iùBXÁ?Ê„¦é7‰¾Lj|Í:n—K:Ø²}²\0İÉUMc`P%nn\n,ì4á™Q'%+H.è‹\"#GĞ3`¥¡İèİ\n1fg\0Ğœ'¼k¦²qxD<\"Œ,a|{~şó¸ÜC<S»i•Bï\nkNş ÖG³}’Óàk:„–Îî­ÀİgÛ)˜JD°ˆ•hÃ›f¢\"™kV~³ámM`HO”kD‹¬^ˆ0/tj«l³\rŒ!Ïf<ÀGôÛTºÕvµ#@­ek@2«wéı´0ÜÜ­tÄÙ€Ä¯1ÄuÌyvË%8±?1¼ÛÊlæ×xtÇœmp­›fK3ZÜJ£=\0@—^p·ÂÛ‘¹¶æ³ø]Ò²'ëtÙ¡@C·bëå\r[ÈãVôµ-½ÀËo“-œ¦İ e·}ÀéYªÜ	-é‡-m³I\0+ƒÍVßDÛ[B+€ç(-Ù4ä«>®qè–i>=½î‡/0-¦cL“pJ b\ndáò)â«#áGËs­·ä\"ÒQĞN“œøˆ`.úÈÔyÈEtPŠqÔI]ó¤ëJ8¼€»rWTÅÁIµè‹f÷aG„.ë–„7yçËlÙÕA€³7'¥1	âS€-ÙxI§œm·ËÂL:eÎ‰AÆWøİÎ¶EIİâ—Wz€Ô3Wòı°)*/)CÊÇÿx*c]ì%÷}½âÅ»_ÏÌIvÍ²½'˜\$U÷İS4k”5WÊJC®˜ 7*œb%<WC@Â“Æ	À¼©»c{Ş´«ò”¬3)Xò˜&&¢eLìI”å¢,Nì 2k#p5 €´f4«ˆöÇºëz¯#â½ä\\®ºà¡ûNøbÔUˆğoyğ€ÈSÕ4¾`qÓ~1–=ì8å‰¸*áOOJêC¡ñ®âÚè'Dd,@kLñ¹à¤ƒ÷”\\âj2Í©Äê±<³@_q÷2Ÿ\0‚Õ±Á)`˜˜Àêı•s°±óF\0¡ÓâÀÖ\n­‚Fš×<*Àx*•Àë`ÔàÁ-ƒŸ\røˆ‡|@ÑñÔ7ğH@w€óÿ‰H]µå˜\0¶àü_w¾µh0!Ës¢1Ï¾¦Ç¬„hW°€.Ãê=WªR*÷A_Æ”åEDÔ· ?1,UbÌ9=tÈ4Ã¨¤äWˆ¢^åäÙ;‘ßè±Ì@™ò(1<DâEÌ‚Hx©T()0zŠ`Ñ_Ğ;¨›ALé±)\nÌK[fˆH—Œ‰Wo—@bBKÀiMŠ±Ãd+ï>èvI¶(z:äİ.İ€À 9uiÑ¤DYÖâ¾ûÉO`ö®á]I\0Œ°RÄ†,K,÷¨ã6L¸Ä\"\"£1gª(•­†|T.,ñ9vb+\rk]u¶&è©|©åb£SÍÅd[¼,gêèaJº(CÄök¤”\rFØÂ“+	€ñŒ9âÂL©¹)Â)UAßB‰U†hÂgà’c3xñ-n9±úü»äxÈ®2¯´q¬ibÖrY7é€kÌyìfˆ, §¼àÎ)¬Ùª¤J:«NÂ8ÜRcly\nõ¼2ÅWô;¬.>Åv6Q#A0­ê{Î­iùï7~@VXÀ…¢^¿å11-É+Ïv|£Ü]Vf¸¢û.›{	áÒÀ\r·§;ê1lp°/ÙõuF‘Çd‰\$PĞ®0=@kSÆ0h›ÁÉˆÂœ@‘Ñ/*(OæV.•´G>‰(rËÎ!˜6àª÷…®òY=XZ@Â:²'&06kE|š“Ï'|H;“¼æNò€gÒ%ËW™+Âæ¯4ù;Íƒ¯¯'x|f©9­ÌÚ(O¨ğd¦§é·w%9]¦×f}ÌÃGÖÔÄs¦µçÂ¾óÓ…÷XM0ÍéŒ†gQ·ª¶8Ì„ù+O}¶Í0}’9„ÖĞŞ»–ßNhÎ/mgDé“s…°ü¦Äà\nÍ74å‹³P~}O)©UgÜ9ùÉÖj§8Pœ„İ¸Á(Ğ%ÄóöÛjŞ7oAB×Ği)ˆüKò„½Ùu¤ë´ …}s±1è=odİV[Ä´\n¬ç²zl€MĞ·r:F#{Öğ*#°xœÜÜ°¯<Ds½™k/mw :^æë¦âÉ1¿ÄÏD¨˜2ºz*Ñòn’ª%ôŞåÓÚiâÃ™ *Ê!8-·á¦tH•'í„ã\rÍĞºĞ4™äİ8`‚¿\"”¡»»i]’ZZœ>Z\0Ş¦9û”ìÚ+äŸ‚~†á\$Ş­„€LÄP\\ì‡XA©¬ èÀóŒÍišççzÒhÂ\$÷Â‹SMÚT'•š„1×èÏDÍâ	˜Ë5E©\0Ä\$ãttÔ®¥ì:\rMÆ·S¦šÓ––lsªˆAfÖKàk,N…lÛD^zz²dS˜®/rt²Nù>ıo%i¥½\0J¯B©po¢ÜR“™Ãê/Ö˜Ù«x\nyœ+«ì,e4‚Îq5Q'JDˆ]¿B@mÓ´ÈÃR§Ski~úÜÎ¶t0ç[ 1€z	••&×û^“\nOÕ¶²ÉV÷ëÀ³GV@T*ŞH9ÑÏ‰G0\0'Ö`Ñ°\r‡åûbQKsLd…*;\ná×æÁ.Ä”UNpà,Lâ@TRàe øb€œFÀø˜yŸn> IKÀ¶rGû	@Ù‚?cI’İ“u%GöOô1„ ÖCöh¦5Tüy„üI­Ù:\\0¼àX¥Ë>öÊŠ0ËŞ¾ûQB¶‡©EI/-LBTÚ!bïœ÷6ìÿk`jp\0K„„Â>kƒdšâÄ/•äISk.+*Íû¡R›|gR¡ıøW\\wùÂÓtà.)¤^Zc8ÕZ€~FÀ°SÇµÔSmÌ•;b>\0jz=î¢T'Á>Ìåq‹y}:»u§µ&åÀWºDQ¢Ïc-ªËşÇ6<[‡e÷x›Ø èĞî[ú¹L©\0wmùl°t•zëç<S€&ğådbÜxÍúoiâgK©\r`ÖÂµÔ?D5u@b‘„N¸àO•ğ¤·¤ˆíøYÔ[õŸè£Àñ{ÃNïœré‰ût±¾ó\0ïÅtMsšcBW?°*Dƒ.põ€¤'2•Ge\rp*#­e¹ĞêÚÅCıÓø\"³QI\nˆ‚hiøQÁ@Œ™á\rl	ˆß´à_.‡¤Êt*á^œøsÁ9ğ€ïWhqÕê¸~,¤áYÎ¸‚ÄdQsÂ¦\r‡BjºõDÿÇ¡ ñ<<T)C´\n¶Šø°Í&¹D{\rĞlÖğÑ-RãÊ\r@rk§é–Ï¢ø +ZíûïP¾ÛÖÎèéu8È¨ôÇ€ÚsãÙˆŠøóoç#äÊg€Èuï›¹\$F&\n-v\"PÜÎæ¶Ûjšnntë1ßV®§»¥öêAwbxß„ÄDÑ5áÍ-Ô0³aœ\0\r§/!ÈI¢Ñúí|/‰‚‚Şh…án„Gf-Mdnaˆ^(eïa´¤Â¨·YŞÏZ,†S€EöN‘ƒ\\§Õó›¸=Ò4~MÍ´¸\rÆëıÒFt•Å¦ñu\"|`ÑÒEá²ÀRózœÂDÌ`â{Äè@“k/KæY¹šŠ®3sJ¡äƒ¿5XGÍª”%®9)Qà £QÜäá¦1t•h¶ô!TRæ²ñÑHÂâÚQİ\rŸCåEÔ0—#wçG2ÂŞ/¾Ö/ç‚é=^ –/ÔºñÎÎÄÙËE’¬\0{+òü€t–+¨äqßĞ±ªæ–IÍt·|ú÷ÈÕvêğqª¹ÔˆÆŒ&Ï\r\\ëVß =–°EbÚënOÎrn›ê‘X({‡É¹uzK­¯`=:ø\núÄß÷\0ªêÇĞ[é%™:p”ˆq+¦ÔR’ldY”ë\"ÅÇ[VÏu{H-­ÁH×_ıâ¢8j‰ëV†Õ5€’à\"\0\"N?E;+°O~»wNÃ];Lœ'„‰íSOFˆÔêä»±Dæ-×!#sNÉ<Õêô Â¯Ñşmu³¤ÈóG¯8ûÎTn]¶¼Îá:úzIMnœ O°8ÀèÄz5…o\\57<ÅÍÅ²#8â¨ñé?sNîº•ÛLõ¸	}úxîÖ&4î†?ç[àz½–ôó³·¶Éı¡Œ<*W¸èİóÀe}{HZ‹§±,(<oÔoÀxW¨t¶2íĞİ# A*·¡»Ÿo\\ç¼R²}xH>NP¸|QÉš|x°'È-° ÛÅ2\0 İ?Æ¾2*\r|]tö•pá\"¢Ú²JuuXybŞD\nÊZ|„H7 _òW‘®şGuXyH>T\r¨G»äş˜Qlˆ¼ñ¨ÉƒÂçn!Îu'Ä*ºC5¸İ>Uª2!b	£9PwÂİ4åü›õá¢}yèWŞ|ñâa\$¾g†éêÁ óTÇUË¡&~9(\\*½!b_Ïùû€w±7\\£Çğ‹]=ß\\*ä­€@ğ#N7Íªè¯Á5QN`@<\0°6!‰9ÆÑl…¥\$ˆwI\$4 õ¾2–ë\$¥&‚Ğì.RZòà—³Y†›uyá¤³ìpå‡&SI®İ@¨EJiL€cõºV®1Fñ1…äZ\r\r¦‚àh“¡kÚ»öHHŠñË¿®ªªöˆKı§ ?xµâ-0\nÛêdÍN3Kó„CÓ59)Ä¾:B#¨ÌdN5A1”Æ‰šÆøÌOd[3Ú œáh–[s~)±9 DNâyøáñş>”âÀX±Ÿ'È½ÎÏHèòç,–î)Ú‚½\"Âeó0;\0Ëqeo>¦Û=®|«2¦G+B¨@z·ˆÏäøò@]}rQîÁÒ k/Š|íGñ:Ñ¯äW\0ça4>”ò^|õïƒìgİoûXEä9p…üÅLrg“A—Ä6¼˜p¿eúïÛÇ1ï´*Åëã½7ÚÀ[ö>]ı#ë?jB¨~Ö/¿}Å3ÿ:ûœU\$ğ?¼<•¿GüäaÿïÁ\n>0#!iƒ>.{A}'hQÿLwë~ŸW_¨îªTh#dÀÅÃ»–ªdŠŸFQ¸“µóâ*{æø\"‰\"¤P{õŸà}Ş4 N×ÕÓióŸ­Õ\r_ÅÊØÄe?l4À2¡?\nå—F™ú	åôqÎUï×Ä½°_İÿ`_üõÇàˆjı¬{_k_Ûo÷~ÿ¿c*#ÿ(´/ü!Dn¤Fÿ`ïü?@sôBÚ!®?;ÜEâ²úÿ“ş¾ÿ\0kş	ÿ*NıìD;¼õ°+d\nZZdB»À÷ ‹Š`B5æP\n8¬Öéàğ‡Ìc#ou½¤kßËŠM“İ¯w‡.ìªFÀJ¦ˆÈ!|®Äˆ2Fc‹Y).¬§ºôXHyò[ëê~ˆ†ù€#/™&¢£öã[À ÿñÂŒˆY@ı¨À(|\r\0,O¼ñ0YbÔÎ²Å¬ï\$0×ÓÛaË‘–À“É ˆA\$Çú0,Ë@ªÓ°>>9úÁ\\tiø<—\0ã—q\0Ä}@`ñ\0fVjƒ°­dß '(“‚†€	!_²nõ 0+c’´µiig8a]'=-¬B!(§Ø8†_İëÆx²j©Œµ”)\rH5HïƒYn	,f«rœí}-d\$òÖH ¬2né´†Ü›È=à-­d©“€FE-dáé¨a‚N_z4@”À[ènãŒ\$x!!i0Tª”ÊuÀ8ÌÉ¸…¼¯ş\0PZ8ZÁ¹†êcçàÁ®+ĞŠ‰AAF(äÁØÛ`mg*¸vS, Ç†ÜğKcAşÛ¬ &Ä¨9êÀ…ÁŠücİ0w•+ˆn€Î=›°)\$ë…ĞQğ~AŠÛaæ\0004\0uñ{Ä(´¤\$°­y	!°„B‹Û A<µa„‘Az ¨ÁZA4\$ZY9.aX\r•ˆdÚAˆLÂv|oOz|ßÂšZÜ(îeíZ£Ä†À");}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("v0œF£©ÌĞ==˜ÎFS	ĞÊ_6MÆ³˜èèr:™E‡CI´Êo:C„”Xc‚\ræØ„J(:=ŸE†¦a28¡xğ¸?Ä'ƒi°SANN‘ùğxs…NBáÌVl0›ŒçS	œËUl(D|Ò„çÊP¦À>šE†ã©¶yHchäÂ-3Eb“å ¸b½ßpEÁpÿ9.Š˜Ì~\n?Kb±iw|È`Ç÷d.¼x8EN¦ã!”Í2™‡3©ˆá\r‡ÑYÌèy6GFmY8o7\n\r³0²<d4˜E'¸\n#™\ròˆñ¸è.…C!Ä^tè(õÍbqHïÔ.…›¢sÿƒ2™N‚qÙ¤Ì9î‹¦÷À#{‡cëŞåµÁì3nÓ¸2»Ár¼:<ƒ+Ì9ˆCÈ¨®‰Ã\n<ô\r`Èö/bè\\š È!HØ2SÚ™F#8ĞˆÇIˆ78ÃK‘«*Úº!ÃÀèé‘ˆæ+¨¾:+¯›ù&2|¢:ã¢9ÊÁÚ:­ĞN§¶ãpA/#œÀ ˆ0Dá\\±'Ç1ØÓ‹ïª2a@¶¬+Jâ¼.£c,”ø£‚°1Œ¡@^.BàÜÑŒá`OK=`B‹ÎPè6’ Î>(ƒeK%! ^!Ï¬‰BÈáHS…s8^9Í3¤O1àÑ.Xj+†â¸îM	#+ÖF£:ˆ7SÚ\$0¾V(ÙFQÃ\r!Iƒä*¡X¶/ÌŠ˜¸ë•67=ÛªX3İ†Ø‡³ˆĞ^±ígf#WÕùg‹ğ¢8ß‹íhÆ7µ¡E©k\rÖÅ¹GÒ)íÏt…We4öVØ½Š…ó&7\0RôÈN!0Ü1Wİãy¢CPÊã!íåi|Àgn´Û.\rã0Ì9¿Aî‡İ¸¶…Û¶ø^×8vÁl\"bì|…yHYÈ2ê9˜0Òß…š.ı:yê¬áÚ6:²Ø¿·nû\0Qµ7áøbkü<\0òéæ¹¸è-îBè{³Á;Öù¤òã W³Ê Ï&Á/nå¥wíî2A×µ„‡˜ö¥AÁ0yu)¦­¬kLÆ¹tkÛ\0ø;Éd…=%m.ö×Åc5¨fì’ï¸*×@4‡İ Ò…¼cÿÆ¸Ü†|\"ë§³òh¸\\Úf¸PƒNÁğqû—ÈÁsŸfÎ~PˆÊpHp\n~ˆ«>T_³ÒQOQÏ\$ĞVßŞSpn1¤Êšœ }=©‚LëüJeuc¤ˆ©ˆØaA|;†È“Nšó-ºôZÑ@R¦§Í³‘ Î	Áú.¬¤2†Ğêè…ª`REŠéí^iP1&œ¸Şˆ(Š²\$ĞCÍY­5á¸Øƒø·axh@ÑÃ=Æ²â +>`€ş×¢Ğœ¯\r!˜b´“ğr€ö2pø(=¡İœø!˜es¯X4GòHhc íM‘S.—Ğ|YjHƒğzBàSVÀ 0æjä\nf\rà‚åÍÁD‘o”ğ%ø˜\\1ÿ“ÒMI`(Ò:“! -ƒ3=0äÔÍ è¬Sø¼ÓgW…e5¥ğzœ(h©ÖdårœÓ«„KiÊ@Y.¥áŒÈ\$@šsÑ±EI&çÃDf…SR}±ÅrÚ½?x\"¢@ng¬÷À™PI\\U‚€<ô5X\"E0‰—t8†Yé=‚`=£”>“Qñ4B’k ¬¸+p`ş(8/N´qSKõr¯ƒëÿiîO*[JœùRJY±&uÄÊ¢7¡¤‚³úØ#Ô>‰ÂÓXÃ»ë?AP‘òCDÁD…ò\$‚Ù’ÁõY¬´<éÕãµX[½d«d„å:¥ìa\$‚‹ˆ†¸Î üŠWç¨/É‚è¶!+eYIw=9ŒÂÍiÙ;q\r\nÿØ1è³•xÚ0]Q©<÷zI9~Wåı9RDŠKI6ƒÛL…íŞCˆz\"0NWŒWzH4½ x›gû×ª¯x&ÚF¿aÓƒ†è\\éxƒà=Ó^Ô“´şKH‘x‡¨Ù“0èEÃÒ‚Éšã§Xµk,ñ¼R‰ ~	àñÌ›ó—Nyº›Szú¨”6\0D	æ¡ìğØ†hs|.õò=I‚x}/ÂuNçƒü'R•åìn'‚|so8r•å£tèæéÈa¨\0°5†PòÖ dwÌŠÇÆÌ•q³¹Š€5(XµHp|K¬2`µ]FU’~!ÊØ=å Ê|ò,upê‚\\“ ¾C¨oçT¶eâ•™C‚}*¨¥f¢#’shpáÏ5æ‹›³®mZ‹xàçfn~v)DH4çe††v“ÉVªõbyò¶TÊÇÌ¥,´ôœ<Íy,ÖÌ«2¹ôÍz^÷¥” Kƒ˜2¢xo	ƒ ·•Ÿ2Ñ I”ùa½hõ~ ­cê€ejõ6­×)ÿ]¦Ô¢5×ÍdG×ŠEÎtË'Ná=VĞİÉœ@Ğşƒàb^åÌÚöp:k‡Ë1StTÔ™FF´—`´¾`øò{{Ôï­é4÷•7ÄpcPòØ·öõVÀì9ÂÙ‰Lt‰	M¶µ­Ò{öC©l±±n47sÉPL¬˜!ñ9{l a°Á‰–œ½!pG%Ü)<Á·2*ä<Œ9rV‘ÉÁß)å|·A†àÓÌIp=ã\n7d©>j^6š\09‘#»Õ—7·T[şµ¹i:ëüåXşDù'&8€/ÔÙÇÂú;å#—f”%ÒÇKj3ë§ö;ŸÆZ^]°âNQwºtÈ¬\$í÷€×Ò¹Ÿ€ï€Ç‡-±Î;ÿLãX„+‚ PÌ„Û:˜Nı¦îĞ \0Ç²…Pà‹yïjt>Â÷.[»<w\"|ÓÊso-¹;';íÇŸ÷í»¦t\rç½tŞ	ïIŸ¿ãòóÉT°Ä\nL\n)À†‚(Aûağ\" çà	Á&„PøÂ@O\nå¸«0†(M&²°b\0ˆü@‚@‚\n`Š=ÊìîÒø®*Ì”íÆ8è/îkHùFîù\"óF¤ùæÄÙB&Õ, <îüú4b®ÚeNŞ)FEOªğNSNŞìO¤ó\rÏ.xçÅ\"øÏkD\rŒ¤ °0÷p[…2RI0Z Â€ÄëÎà'ÌëĞfëixøP0dòğ¤|ÏhåO‘¬ÖmkHâÎ’ù¯7£Á\nnÍîÕ¦ÂeP\"î0xùPÁğÄï02ïn6úWÏ‡N[!°ï6ï°£\r.u\rpşíP¶ñ.(ÎmGt\roxò°Ğø1!\n©\rĞ:¸ z+îœlVÑ'¢ÓĞ|?PƒP‡‘:ó0õĞ íbTõÏaux`ÜÑco}°ÕO‰1WŒ³òq8ølùÀ\\©ªuëŒ@Â Ø\$NePKq¦úgÌA(¦mc‚L'`Bh\r-†!Íb`ñ×Âk Ê ¶Ùäş‡`NË0¡	§°©nN×`ú»D\0Ú@~¦ÄÆÀ`KâÃÂ]À×ñ¨|®¡€Ê¾àA#‹öiÔYåxfã¢\r‰4 ,vÌ\0Ş‹QÔÉ NÀñRoÎüìm´© 1©&Çª pšr °änpì6%ê%ly\rbØÊ•(¢S)')@¶Ş¯D²MIŒs {&±KHœ@d×l¶wf0Éíx§Ö6§ö~3OP½h0\"ä»DÎ+òA¬\$IÂ`b‹\$ Ç\$òRÅL¾Ì Q\"R%’¢ÖR©FVÀNy+F\n ¤	 †%fzŒƒ½*ñT¿ÄØMÉ¾ÀRŒ%@Ú6\"ìbNˆ5.rà\0æWàÄ¤d€¾4Å'l|9.#`äôåæ†€¶Ø£j6ëÎ¤Ãv ¶ÄÍvÚ¥¤\rh\rÈs7iŒ\"@¾\\DÅ°i8#q8„	Â\0Ö¶bL. ¶\rdTb@E àc2`P( B'ã€¶€º0 ¶/àô|Â–3ö³úë¦R.So*í¢áàcA)4K˜}é:S¨¾àºÁ\0O8ì©B@ä@ÀCC@ÂA'B\0N=Ï;S†7S»;óÃD	„ÚMW7sÎEDö\rÅ¨°p¹”<½DÈº‡9 ±}4 äõ¯_o.“rÔ‰I\r¥HQzíEsB¨¦\0eôJ”• ÆÁKwHtµJ4,^25h2ƒi%;Â=ĞÆÙLL6}“„7#w<ìlrTá;tPl76ƒP×rJÔ\n@ÊàŠ5\0P!`\\\r@Ş\"CĞ-\0RSH~Få€µÅ†O­@Ç­šÇäşg°¤)FÒ*h˜\0öpàCOu6«ÒYOƒRg w9BàÓšÁíL\"ä˜µ“_63gU5\r7,6\"ôè1ÎªƒçšyåçV‚%VÄŸWXÔÕ]OÔêJ†	#XQGIXÉ°ÔÓSqñ+É(ßê®q‚R•GH.l6[Rı0\0Ñ%H´¶C}Sr7¦“7õcYK‰£ú)õ.ÛCú¹rŞ;ôĞ¦)´M+ÿ3ÉÀ ÉÇ4µÀ|©Îª1ĞZJ`×‰5WŒ¬Lô›-Smx­çH‘„dR*Ş‡¦ÛJĞ¦\r€Øõ|52”–Àğ—-C-1RäR‹éªT`N¢e@'Æ¦*ª*`ø>£€˜\0|¢ğC!nE,¨ag”.€ËbµfÆÃ8Ó“_‚¸ªa`G¶¨Şpê`¤mî6ÆàRz†\0öàÖ[-#mOç1H\rd‰MôMNMqnMş‡nqˆÜ¶èR6ímôOn-t¤vøÆÃ¦\r ]`ö˜š-Ï`j¬®ãXÕMo¶]`OUÀAF€èœŒ37òpõ>'J'm('M=j9jV¨ZbBn<â@‚<çÀ¾fe¤:\0ìK(ú™N´õ²uNêõãí-!©—1vÒH(›QgôÂÂµ‰—xC‘<@’ ícâ[åc\\2o,5ÊËƒq0m}âi~+ÎÌe°Ñ¶–Ò*Ñ}×êÈ ù}àÆM×ÿ~­ÙÏ|—Ì˜\rîÄ æ@Ô\"hB¤\$Bã2İc\$g\$5b?Ê6!wÊÓ+~¸l1„ ¾†`ŞÁ	s„×Ş÷î÷	¨Ë.Çv7m¨Ec`Qƒecb6µ…´`¨\"&fì˜xµ\"â2êE~FzˆÛ\$„[/å±0,w~`u—Î>wß%˜¥ˆX©\$Ø¯vòVÌ\"-ŒRûŠµá%Wà÷çD¨@Ê€Vo£ç…²ŞøE@Íy‹Î×hµ½1‡…\"tĞ™„O”ÍĞ/™°ÀÍĞë![‘¬åÑ`:x}ƒ@]ƒ„bÍ Ô@ÅÎãí¯á˜5‡U(K˜y“øS˜˜˜çøí>8DÍ¸Á’ø»ywæ=–|T,â'LìùY“ìàÌÑ\\ÑLğÍŒ¼Ïd¼­‘.ŒĞÙÑ@ÂÑ’ÌŞÒ9<‘¸`9EƒØZ¸C”×²\\hØ=¸qRù`GGWƒX{œ˜5ˆ-L‡£RJ\$JP+Ó7X›‹Œ¯ulÓ˜hÌµ’ÇYüPàg¸ÒÔÚzŒàŞáuiwyLÛy èÒÙÇcYÌ7yF<öv\r¥ã57dôO™gŸk Yq£8êpú	†£˜\nøÀ*'œ9\"ñ`îÉ´w[¦Gƒ¹HD–y_]¹cÙiRëË–äo™w¦ø½§9	“{§ø]„Oİ8´šCƒ67”:IvÑS©ø…ª:_¤U7ª¢1¦z±¦Úµyy«¹‚Í¹‡§ÀM¨0Í¬ÿ…c0¹zËÂ?£özÖ7}W§'ñı°É5±£_eÆ¸ºÓzmÁl\nCX_²(¡šÃ’Õ{@Î}àX`SgBÕ¬DÊÖu‹ìÃ±û!Ûk~¹‡¶YÖOûvK‚\0ùc§r»rÔ(ê^`Ìn›Í;yì7¢z+‹{›¡øW¡:\$šM¹ÿºÚ\"Iû¥»š%˜om¹ù¢›Å 	‚ê,PK—ı€†9€ÙÁ´äşÊ…æû™g\n¨Ş¸a~¸Á»ñŒx­%Í~©¿€W¿Ø½·˜£ÀRùØÙŒ¸İ‹X§xÉÀØİ%›¬áz§SzÕ©X?¶y#}òøL);ì!ÖÜyß•Øsªè¯ú¥©ëÂàÙˆ†ˆÆ‰:–òªxˆz+UÔºƒÿµ|†ú1ªùu´ÜHOê'Ãåc¬ùËÄÏ©{©Üc®<g¥Õ/úèV:í‰ ÍÉŠ‡<˜ÕÂ\\3‹•Æåe\0å„ZüãTx”Zq\nl·ù½É_¾ÿ€™·I_¨Z‰¬ ù¬yÑÑ,Û™]™™¬Î9Í Ûš,íšŒû²Íšâk›:;›}-›¢›ì¢Ô™ÊE\0S~2¦Œ«‡\\	¬UÍºTV3ŒoõEÎ|êE¼ïÏ CÏmñÏ ¿½øI¾=Ñ\0ùĞÇz‡’kGÙ¹\0ÑÌÙ‘Ò9¤Í1	ÒyÒ½Òı¼Í=5Û›²ı<Ğ™µÙ]G›Ğ™ SÆÅcÕ‡Í!\rƒDRî]àP'ŞÍêá–ÚàÌpLtÇšÈH+`Ó¾½=ıe9Ú‡ÀQ{îª9_b\$5·‘läUzyàn—z`xb€kîM	¹3¬œ Z\rìÅüq]ü)Ö½{#¾c×æÀWI¶\rÄé8¶\r²¦3Öä©½a©ÅåSIå¼'Ã^a×~eéDç¨Ÿç§>oç2 NÜÓåŞP>cÎ™ç’Ãéà·ëô¦š^GæöäöèYêşÍ·æ~x™ŞÁî^¤¡Rg\\½‚\$+˜ÕPÕkY*4ş¢~¼Ö,¶ÅMİ¶ûW-‚hhG¿_IÔ‰v-Òê?ivú¢e>T\"\$•ã[Ô¸+,ÿ)ŸK´ø uôq?KWÄ\rkæL%ç}ètÔ»Á~©0õè|PkúöÕŸTÃ=º?hEŸn=Es÷~ÿ÷¢ºšxJH³K‡Vukï?X?÷”7úB)ú”ci‰÷Dëö£ó\r×>D'Ÿ,ÊŸˆ>vü@ÎXİ+\rr¸èÖ@\r­ûU©XŸº–àÆô×¯«•ÿÌÃÓ€öù1P>U,ï—3æGø°>>tÑ¥}«Ø\"=ŠD}<T²‹á¿Äé%†9Á›‚iåÊ«ªoÖ1³e]¾ÉÕh‰iñ&]¡|ßÈ*¤°ÁlÁšü1ÀD\r)XZRYÄlğõ\"¥E‰à/˜ùŞ8ó×²¬*ByKÊÌ4öÇ5±™×Nrz\\šp§Ó½9î¡Yz£JHä”S—Í>/å4CùßĞÀˆ¼&¶’Ÿ¨sCƒIá;Z,Û†b3¨›²\rÏ–ƒ€{Ô|v¤D\nÙŸNpÃ^Üô§®Ayô0azû<äôÔœ¼üMPS0Ú ®jew=¶Oozé4æ>h1¼áL%RãS¿	§³¦}åu82ÄÓğ¦´®9oªån«cM<®uÆ¶0\rípÍ~¨Aª\nj°QÄê¶3zŠä(à;ü3Eâaµ]°eUÁèl40Í,u±¥„‡fÀ÷fïŠÁH0İ†\$1€û‘õC±AÎfi«ÚşŠá‚Ãå¬‡Œ>ŸXc”Ê‚T\"¡…6pHg¯DàH³?è\"p¸l&ÂK/†ä?¡¼˜ø`2lÓmÏ*“ıTBÉK\"ÁïÏ‹“É €ñ\$P\"o²eVòk¨<ØÄoËäI…rá:«=õ(¾x2˜Ş¦—…*È€@=hCEöÕF6u+à,ZíYÚƒi‘µ´…rš^lP¦x,g°Ë*’È–ºñQE)1i«hJûŞ\"”IFâŸ¦“lŒYÒ|ïÕTf°V°}U°ñeo’	5Q?)‰†Ş\0c“˜Mà¯F‚Ê¼Ú‘j¯l­èªlP‹Šmğ4¬Ä\r* `â¨¸è«Á‘LMiqb‹´¸Vª)Q‰W¨¬R^a.>ÕÓàg	oÄxà\n\0P`@¨¡`\$4érŒÀZ#Hıˆ&¤ncv)¡lFø	 N€coXİFò9 g€À„ \"9à'4t£^Ğ!<	Š #½èğ0çæ¨#ÉhäG|\n€Bx\0³øóÇP T0ÀŸ¸òÇæ6\0TD~#ãé†<ÄÔãô@)Hàb1ÉI€[hìGj7ÑÒ_ÂĞ!0	àCX@«øâ\0œ\n1¼ğ@Z@\"Ç8 Nœ‹\0ŞÁjH\0 Eè£·#ØÛŠi:Â‘Ò¤€¡i#ŠhD¥üs#!¸ã“T²Lƒ\$\$©)ˆöBñ×Ìvãº) ¬ˆà-?h€ª¡cóP	 Oto\$p Ö…B7ñÁ’#„~ØŞD#À-0dbDHü	àRlJ?°\"`\n€A?\0T,(çRM’†“Üp\$ï\"™:IàSRz‘t†\$»‰'I>‰ì“œ¥äí'QM262P´£¥R‘€´	‘Ë\\«cxF&É69²^à	åpN)OHj:Q•ü~äy\0œG³yz¦FcøÄ¹)s*<mQˆ\n(2 ˆ2ƒê8ÂzTĞ#~[† ÒÏG?-ÂË1ë.P;’\\Š&¤‘Â5Ö\0mT·Qn)p\$°’è–\\¼¥šİévËÖZrğKÜ(c\nëYw€òàÀs/ÁgKú]nbÔÀe£.ğKÅÍPp:K®Cé{6arØ˜lÃÔÅ1)ˆå£/³¥İ04ÖKU²á—ÙS@x€‰™Ëœ©¥Q–´¾€vTÓÌ„óc.„¶ÊšÀ:„” 0™Ä·€L1Ú†T0b¨hşZLü]®A™q´VNõLÀ\nrÕ0‚V#\0€¦Ta§«ùêÂ’’ 2«`£`2,\rœ—-imMhSIT“xCñ,ä«L¼õ.IOtÑÎ	7Á_Íù»³TÈrg¦I™Kxq8gIÄÊqMãcDÅ¦A3	Â ¶r`\\å†UéÌL¼íÓœó‘'A9[Ä8PQ08ùŠš2Å1è« rBK`¢P%íTq	äCŒFñÁ€¾_šhQu<ƒC\n¡ñ@¹<yäÌ,&C´ó‚v0%PO\"x *¸ç¶”‚2Ô&ÜüúX)=p.\0ª{ãŸ(N'Ÿyä„ü.2\$şÑ>a¶‘&â™)¡›@\0ºOv€„Š #X´¹â\nNzà,øxbiù”ê¦›õ¡=iãĞZyRø ¤òg–3M‡V€sÉ\n\nq€¼ğ\0‘?€AhƒA;`™;m»Ğç¡(KAz\r™¼!ô ¨O\0EBJPœBŞ¡XO\0Á@êQ4ó–V0ÆkuXÂtYKÙ«h¹=n„ª{‚Ğ@¦f²=Î6d)}Ó*Àº†í“ÁÈ¨¢şğ 95ÈİFøB\0Nã'\0ÑµĞ¦£–€`)PË””o#Ú€íÆòFµ>Ø#‚ùBŠjQ1´rç*) /Rl	”qXzAÊ|Q¤9÷)Hî	Òƒ@=*À½JÚD€€À+ˆpÉé`ğ+d\n E‘¨¦¤Ø€öÊÌgâ(\0–P,€‚‘ôÌ¤ıé§IX‡Üt¦¤m©ªêkÓ6›4Û”\r.©·Kğ+Ÿàüß¤œm©›Ip&€‚œtÜ=:O¹KQM\0€	@O]<€¹MªzJLS@UœrãwJ¸‡Š•1Ú§ \n©\$)É¨	Ò¨@aêHª]ÓĞôğ¦­Bj.ù\0¸U §±ø\0ù°'Ç´\0BÈ\0+T’ƒ‘€ªWRÙY4	 T9Úé·O\nsSÒ9`N>å>©ù&ÑMFÔÔŠ§İ'…4ú’Óâ õ\n‘Œ—j@*‘T‚¥õ1pêf*šÔÜ•9©İLÀŒi@Ôö£5@¨Í/êQRª–Tv¢ÕU¨eHjFŠšU¥•cÍY@ŸVz´Œš­gæ§[iNú­Ô®UT¤%\"ªíR\n²R¬ÕhµZªXJ Òş¯56«EkUjÇÕâ°•¬eé1YšÃÖF7€?«ıe#oJ°Vn¦õW\rV@QYÉÖ§Ôô‘ı©>3¤Û€*Œ!ÛL±\0Lj\0IˆŠá)\nLd1P‡!šÓÜZ)¦×ç]Z!8uÔÌ¥o¼»Âb šå(ÔµáTBÅ&ª‘CV|€/%p÷Î)`È… \\ó•È®ÌHb\n‘LıS‰‡PGfÆó‡¶¹¡¹Jİs”‚‚ĞÔŠ¾ÉAZ…LÀR`<×Œ5Ê5]€ÊLw*:Ò¸@l`‡èÚï 'ˆfÁÆqÁ…EJ7HŞ/\n°m†‚\nÜo±ˆ \0Q`ğ9.Ã»±qÑ¬FûªÀ¸V-!É\nƒ‚-3X¼–5U]ì2\0¡…ÑÀ\r©Î!ÅŠd)šâÊÆ±\rXlb«,nÊ4qĞdK1Ùd¶!±wìLofËrW-šCvm \0Ôp¡	³×¦­±ÃÙ*É–x==“¬yd[Ù V‚²0 (çe+;®:ÊÑ7Í‘	\"b“,u\0{ª˜\n)àÊF<”à…\$İTĞ'RøìV²`¦ª2\\VJ •8¹ÀPÙÁÕÀ+`É‹î«;Wìd4áª FJJd¦R™”Î¦…4­KLÁ’Ô°dçŸµå¯Œl|€¢ª5.©…L€‚à\"U~¦`NàmUmQÜÇTzN²7‡U:‹€¡\0¨”v‚™[J¬Õ\0(ô»µèS'*[k>¦¶ÍÈ„«–T§8ª=£¸AN¥¼†W¸Â¡p ÔŠ½ôÿ¸toL¨ô YÖsXá®ìˆ²Å×,Å0‚İÆ\0À\nq€¦åñ¡ŠVÄL·ÅÎä‰ÆuÏl­s«€n\0]º í/iØ§1–ÿ½É —c€<¯KÀu“ŒÖz»¶âfTÅ‡m\$ò+[Å1hXB Û`€ºì7c.kv®Ì0öİ6à7j¥Ø‰5vĞ2]¼FöDõ‡ŸdKuÎÉeÒexvQğ†±´5B«[À—2å\"‹¹eÂE#^Yo¢tâåšlìişÄ7Šjætÿ]ËÌ[qM	dc†Ô}È­Õh«ÀÆ%Ì·PŠQü_péÛ¨¶êqkj¢ Ş»¨\\¬™eiˆ¢	 ôD8*Ğ´1³À@È+›å\næõ7«³âÔB2@xß4Ç&Ú\"ıÅW¦º›í<\n÷²Ñme÷’¾‹ 7ÀmKD;¸«é‰G;C‰fë´¿	¯d9+€Z@Åw±]ş.å«XwÎ }ü¤n_uÑÕ×QªÀ T¬cÔ‹xA5Á»(T†Ã-X *fÀÂ—ê\0õÎ\"¸nÀÃ*éê<ôW3©½po,¨p˜+s˜Ä2\"»Ì\"Ş‰çPÁMÑüœrûÎQ +ÁèùŒAƒ»«82r¦ÂÕpFp¡±àÍá@}®	ì—uû„fÇ7ğ£©Lõ|˜Ã—-§m?j>S„–¨p¹EÉz@>‚—`?\0\ncx.ÔÙ!a´£ê¾¯a¢˜Z+Ê«²%•—ˆM°ÜXwğ0ãz€ªDÄŠå¹ıÉÙhÓXáîİÑU‹í£J¤ªlM#|ÛS³`ã¬Jckİ~äŠEM^(eöÚáÑ¸À.yP…;‚+‹á••ÙçÀ@Ûˆ)™GbjÅqhÊ š+%ìzp=0\nmIÆ€+\nÀSø|Î’c@ä¢Æ pR¬Ş¸‡ÀHª6	\0w‰Œ¡°­_\"–8×r>üaw\nØÖ:¨ñ	\"\\q¬J„‚L¨w™ºO<|c§V\0xİ†à@|*¼Wƒk6pÿÂOpL”˜m¹[ı‡FTAŸ¸\nöC¨ ”¨R9]vDFĞ9’wŒû%Ô’ãF}<hãÏ#¢ÉÉ\0™N~Ãì‡ãø6'È¦JUV8v\0¹F­¤¬\nÚåR‡ı‡Ä;É.NÆâ3ì—\rÌs/ ë-Ä’Òåzxıp…[<ÓF]å.à:'ë°¯Ñ FG*q•6¦G1êGÜšä‰Ì3]²l@œdÃœääJ™;È˜ÛË\0007,Vc\r#íweÉPß\nqAĞZ^V‹zRŒ't|k“Œqq²‚Ãì»ÉÅkË>L²ß‘ñ›äØR9—g&fE\0R<½d›/£wÉñ•S•eËü¢°E7†cˆ–Qó*ec2e‡ÈHk2—á¿àc00Â¶oÆï—Ÿä;2!Iòjp©“Šç?YÈÍ93[”œ¹·(`eÊ(‹Hw”Œ 3£\"JÊá¦=œİ*¬»WÕËÙ<íaÌç32@sÑ2 7 –Û<Y[¸&y¢ÊëÊå¤™¤Ë¶^37gÂ‹‹6‚²Ğ ŒóL|Ç¬Øqé®öSsİ“Œø§>¹é#r©³ÒQ4Rh4šI¹éó×¡b“g½69«Ğ´4@wú«ã„ƒÎ.E3©œºè;/¨Ì¹q]1ŒWXÕÁ¾7qèÎœë”3%OöYs}Ä~ÀçÇf„&YÓã·1ÇÎ‘q÷]ÂAƒ\"|+~-2(¾,mcpã6s<ş(À6¼ÀeÃ4´á@é¸[mÓ½åñÆU,çË=9Åy ^’¦<2 H¡ó ’Šõ”¼x e#cı–XIÂW|”%°+i,ƒ*÷ÿ3¥Q\$á¡ñ˜KÓ\0ü=¢Ëxá2× ´Ô°“PšÂ7#È³Åó0VX‹ıS³B@Ø¦l.%Š\rÜ¥ÚHÒ§uŞ¼Z¸7pŞ²@‚â¶ŒÆÀõœ2=§]jn°ğÏ´é­YQ”±ZCÆ°ŠQ¬Agë8d‘^1<Ê\\ ágİQ[×~»PRíyH_Z¥V©Û†•ëËïH¸ÕÆ¾”2U“\rAQØÆÅwK:†\r…Ë\r£tØqgB.À%˜ØŞûøG„©±›*›c‚+FşÈ˜‰°¢bŞ&-¦GÕqb\0\nÈã¶)§1\n¯( r›0ÀˆVx\0ş(¢M¹Z£6X-·•'f2¹P^ÑÊÅáÖEŞ,&ÓbŞÈöu³Â°¸ˆCŠ8@ğÚÍ§lÔ8Á±Ú\rèDñ¿ìâÍĞ-˜³ğ;ík›ENÓ¶“³\r„Ì{-ÛÔWa]½‹lê z&ÕvÕŠ{í´HûZÚQ¯…µñøm‡M{c&Ù@[¶4î‰át°äc!y¹0:|ˆŞ†ä¶¡¶EãOtTr6¶ –eÀ\"‡x¤À|°ó}eµeÅ>cdn\"Ù2©CvR`:k>ŒXÆÖ òV&¤ë\"¾ûJØ¹s7q¸“‡OYÚàŞ\"à„×&‡ÿºÒÅ²D:VíëL’[ŞºŠY÷Øç£øBŞ mÅ È=´‘y[±\rËc÷vÒ]“SZãÒÌ&X3võQóg{=`k€éÀ-¬Ü\nE©pJÙ­»D×o~;9\\S'¤p4ákG|Zã[v/»­Ã‹`›½ ÀªUı›ïÈÆÓjh(Wª\\@9ª;\\)à xXC°\0äŞüÛ_\rŒ˜C¾O~ìßÊ¹D³ÂpíÜK<2h¾v8–&¥µ‚úâ@ñèàµ”qLœ<UÅuFn*ñ\\ªÂó#ÖÛÅB¯«³‹\\`*ş=\rŠV=B‰ÜœeÏ`8è\"à«¯:ãX8Â3áìÙìÃ|hÖ=İÆNr‹|iäW9\n3äüğbÚ0J§L3­B	)ö\\s*ÿaÅş?r3¼\n±½rH,ÀãËuÜzV@İÀ˜4=#ï€÷|Ü•ÈaÈ”–å¤Û}æ7Ğ\0ïiÏ&%ù–Q¾ãól!öS>L×†[ñ˜X¨!ÜÊÜ>–à=m•_Ûv\0D@ÆxòE\0ôÛ¾A ¸3®jDFúÖn²µ±¼”ìkwƒÔŞæ¸£\0ÖWÿZ™Û~ŸîL°®­&L+D£Ã#\0>@ü¶>vÊ;›p6\$+?C¹ˆQûá<RC!ÔÉ€…ÉM¡àÆ0yÛ\$!“Ğïrê/dè¦«ñ”1Wz{Ãç¯:B³5òctqÂøÛ¶ËèèŸ¾jí÷u¹7õ¿ÓN\rİ¢Éúi×N±¿¹ñ§ã2¡«È®´«¼éã¹w]J´8èñ§°A@óªÇtu44Í@¨}.é+™&Çª/«G[¾ó#\\FV'z¢Q¯Àï¼5{cğÌ]I%×{•uéµy€o^)lIfño­ãPçößGI‡ğ_9V½‰ë}†¶î[›—Ÿ)h@OÙqx·´\0.×ù•Å¼»çï´yüİw€{|îasTE‚.wO€ÜŞ¾œî€6-¾êpë8ë^×v¼Ç#çİVù«Ú\r¥“ˆÛêÆİ7N÷+º¾İ™ÁÈç>õKÌ¾k‡@WX°‰‰´…\\¹d{Y\\ôêOevh?ümõ7tmê†8±ÓÕ0ïl¯¸ı¶îON}c>|õƒZ½VÇNê…·Î­cÃ¬vÇ@WàRIktŒš}Òæ:@dV :Wü]hÈ[ú×—Í¹’cr\n÷)Àé!¼t÷iI‹í+‡º–&Ì‘\"|ºCÍÅãà¾ox¦(šp·Îfu”ƒ“N™²ıÀVR\nä\0xæ§9® 873Ç£™£Ê€V!ÂùÈ‡ñèBôã§2Ål\ršGØ6õY+(uº½5AÈüE·‹?»Áwv…»İÕµÁñ—9»É\\llw›©ïXú¹xî«é¿Áğ¡Dú°M>ã¼‚ƒ‡ğFÌüêH?3ôÿ¹»”Û)ÎˆÜ	}Ù+[€Ùor:·Îİw?¨¤ÅõÈ®)\0'DŠ»ü¾¾»·Nü“HÛé¸˜´»Ôİ/øSÃ¾Ó@<3µæÖx#ÃŞ yN™ÃÚD.Í?ù2 m,p\0Oèş¼&?ôh¹Ûæ>\rõ‹\\›	åw,=hªG'EÔ°#¶HÆĞû‹Ö_òß·Ê¤Ü¡jz£Õ\\‰Ì%pKÎ­&i|¥Zóöòk}]ïĞ‘„5Á°!¯5îT{»]Å(â7ÂŠOr/ûü>¥.&0‹½cë>°ú×àåªøÙ>Z.9\0ù>+@)˜×ÀT´ ¤Ã_Oğ5Á!İt4¥Şpk¦\"å¿§x¯]¾Z’”ütÄ\0<Ì@³àÄğc?/}çN›4A˜€£ä€p€C<•NãWƒZ\05ÑŒ’°)ß^úHÆ¿ôÁN}o£P–ØØ\\p6âO“.#ë÷oóú!T!Í\$K*nµ¯PqrÏúìä'‡Ç\0ÿúœ¾©¿VËøNE‡hÛF÷ü‡#€ßxM}~GğÀsP‹·E²¢*#ğ¸œAâ¯Ì„¯şõ(FŠŞç/ê!‹ŠügMûîo‚gæÉ©ü¯(yb«~ÓŞ|3öa@ËúÔşûòÿ½å'¥„•ï¿äş¬»Ş¤£‚Ôÿ™ÆŸëşÿó¡¬Ák3y‰'zÀ<“ÕÏ,¼¾,•ñ\${jŞváÄ¼)\n„ˆ§q\0éÊğúòÇzêåöA=”	[Ù ô=)sÚMm\0X˜Ñë·?Üˆøÿ \"\"€é\0€=é¯Vù»påò¼JİÍq¿„ükŞ‹ë½¶óıîo¸x.c‡€ğ6.ÙcmÃ¶<dÎ!7ÇØBâè\\Ş“°ÀhÔÇ2„ç“¨î\r<ğºäBHƒÜçó®Eó¹Âù&+.ÜÛP	,²º)0==·2-#'Îñ=°®3xåĞ1¢ÆX»Ìà‚fóºŒ¤¬3°®Â°@\$íè©w —J¹ï3@È_<\092®!£ÿ­,9rìé3 ´‚‹]‹”ºRƒÌ«A%ğ£ì¶è¸#Á\\¦2'?vŒóbB‡\nL˜/!€@ñ‘sà€Çü%DA&\$’WğE	zH`À+ˆºCÊPP‰%_‰,…‡3ıáƒ\0‘s@´¼§“Ç Û›´!ğ>`¼‡sšÏ64ä)3B°¼Ö„X@¬åúBha€ĞÔ{8 †	¡gA·\r=ãšå’›}ÃP‰\"læA@îş‹TÂïAüğ¥¬AØüÓÖz?\0@àÍ†/vØ[hyŒsü@¿Â	@Ûƒê2¼ @‡Aõ(ñğ„?\0í˜5bxx³nÍ&ëÃÆ7¼JkŞïşÓy\$«	4¸ñâ½¦÷L#§B@ÖËyEc€\\¸›Z­7ÂXÚ9½p<ƒ¾ğ#9¹t!ÜOt‡œJ0±M‘¶ñ+Y¯kÀæ‹ÛLX:‚ñÈyÎ×.ö[ü@Ø¦ÆZ;º¼Ü#¼Üîà>o8‹Hİ`MP#3ê\n8=©İ…`şz{Éèö\nRwLe‚2(!	à'À	Ô\0ãƒL)©ë“äœ˜à'mˆokBèŸ c™`jaàÂÒˆ}PÁ¸,-éé§|PÄf-(5âºIâ…&|0C„%²~)øŒFä3‰ùæ¬,Ì¡ëáÙà‡îú¸Ô‰J8³¶à‚â¾\rÙ\rXôâ³¦*Áï‰„Lİ›@.Dä\$«\$6 /€f¸Ù…v\nˆ*`£¾Î’€s€\\à|À>€H¼ûó‰ó1“D-gg‰jyğÆÂù˜Rpº¨\n/½Bt1Ú&ï4;á}Ã1Øİò'¾.ÅÃĞ.À¡CÛÙÑGjC d0ğûCÂ2<>\"ÌÃä1Æ4C 	™¢É¬2éò²´C0Ñ(\r*0Ôˆ(RJ\n\0d(ô0êÌ;`;‚ª¢¤>¨uÁÑbÑÃõ\r)TÇ¨I’apÊ(Ì3\rÃB1CáDC('aIÄJ‡@Rq§’¯|EîDaBâ2Do2€‚¦()Xjpæ…Éyha%€QœA1CüTJñ'¨¡<åA“¦PÚx -¨2¹@\$ş§äÑ|J¹DÜN¤M)è§† ú‚JÄ§TFcÆ5°Ÿò¼éúÄc¹\rDû<.C#¬G±*ƒ…3à•Ä‡\r.Ñ#CN<[q8Ã3è\rIâÃ¡úŠpïDó\$QééD}¼@ËÅ3äV0ÏBøn'€EP\0±Q\rœUQBnU±Ä%ÜR‘QÄ0Ÿ„H0ĞD;\$4‘O>ãg‘QC)ğRqS¨`0¢S5<B–Ù|OÑHÅ˜\rê E_äY±MEŸäDÓ¨%0`\0ú¡ÄF#[Å\rp>hAxx5­9¤p=‚å`7€ô+Z,xB‘€¶˜à\")0xà£(bn?Š+¢],?&¶ƒSøñÁ¬œa@3€^€à,	îA%ACz'àa&¤\\!¸`Æ*Ìc\$½Åõa8ñC1ìZ”‚ÓŒ`ë@\"¤dÑ†Ænà	F\0Âœ:~´À!a \0Êxé(–qË°ÃrÆ(Œi‘§F¡ñ©ŠL R\0¹ôĞç¯ÆÂlf	ÆÊ\rtlñ«FÔ5\0Cq‡.{°‘°,\0Oln¨Æç¤m«Æ×È?Ñ½˜é|iñÁFÍ¬m1ÃÆÚºê+X8|q‘¸‚§n±´€=€!¡jF!Ã1·5	±‡4®úwÓÆ&Ó[³QÖ€ODaÃÇe ŠåÆGošÊÊÆÁtu04ŠÃ”gÀÆB+ñ’F‘8\nQ”ÆWl7€Œƒê)ĞSQÀG\$qñÂÆ±Ğ[\0/Æ»ƒ€lÇ}ÌqÑºG!¼k 7Æ·òÄAÇGÀÙãñÙG­ÔjQóGµL}qïÆ\"ü~Q±GË\$nÑüG»ĞÉÆÇû{ûHäk‘öƒ€	Àº¤Ä”€ñëÆåÌqõH tkËãƒQ ¬~‘ÎGÏÜƒR¬ÛµIÜ\rwÑÑB„\$N4cÀŠÃ0-¸\rà7€Îób{€€àxÈ¡	\0Z	RFà5†×0\r2!FN\nìiaMGdÛqCf”4Ö{îœ#y!TF#Ãà_:Ó™2ä5À>C\\c¶áWDË\"´FƒD(üØğĞ‚xÄ7q—ÃŠ\rkèĞà…x£ ²7«Ÿ#Š£_DfèjpÖ&Æ–ˆ1‰²¦Î]»…ƒòş`)\0Û²DÃH\n€!ƒÚä‘\0ÌÜ`	À*Æp\$Š±€8RIÉ6	 ıÀ(\0’?x\n*D€b†4”ŠDÉ,!QÏá…œş:¨d€†‚²`+2K ‘ÙaŸ\0øM¨d…¨%†À><5&\"@¶\nÀã\0íH›0Œ%¢ÉŠ3 AR‚²\$| <€ø† àÉfÿ£í¬> +PL5Æ&¬œ@€\0†üœ 0Æ»'KmÎo„ãk€>H\"l‚j™È8èK}#‰_ ğÑ2?ˆ|µcB~ÃX\ni6Iä\nÜ=Î €\"É½(i¸r‡&Ìú3O²‰I ÀR‹>Œ˜,\0ñ(ÓvŒ£J@L’OÒ‡œz\rl¤ˆ˜‘)A2p¦+¤ô—a ™)Lm™ÊZL´¥î™ÔÀ,€(šÁ,„B·\$‘A¤\0Œ‘À!¨2D›0£¡Ir¾±šÂ+Ézœl™¸Š8p“ARƒ—&8Ì…ÀÈ¼X’ Âƒ´Ğ?Ò]‘^Ôr‡e‰—%èZë+Mc\$ÉŠ|ØJ‘«Šà²­²+(Ò»€ìT[Q Â£’šò_‚²l¸ÅEÿ^ \0•à1\0Ï,+èÉ\n\0É&±00š\nLÆ£€ê1‚hÉ’§²º£;ªéÈk\"šÄn-Ü´\0;ÊT:ª:’ÎZ€ˆ;cKa[—RØ\r CI1u%ì±éÉÈ•é%„{)è“\r<Kˆ£ <À†ˆú%hr×ıD¡€õK %Õ\0¶(àh•è­€êƒ4ÒªÆ¨[èĞQ€¦(?BW’8,› K¹,é2@&\0ø´ºRß>Œ•ùÙrm>ŒH™w#^2I\"mŸÇV1Cr(Ê`ñ\\’ÌŒ:åÒF‡eÌÛTÁH”¤hT=@64ç/»K,qÌü\"’ÎÂ&X +…°1«èÒ¨àü—e™€Æö[Ù >q0(C \$Ê1\\m‚İ:%ü¸áj\0é.ì©\0002\0Ÿ0›»€>.¡1@++¼†\"°ü¡“À2j@Ü…I23Ù“ Æ×1óNLš\r`\nÏÖÈ¼j\$Êä0HÀĞ7jËöÙlÒ²ñ/«U\"Ëë@;Ğ[¸¨;Ï K2ˆ`Ï&3`UÒ‚èÀ×ŒT¤!ÁÊ{(cã²æµ.`>³>Ë¢Ø[%9+À£âm…!)HŒ%\0¦J²³·4PÉ’ĞÊğ2dÊ¯ê€øÌÍRá‡33tÓìŠH˜óÉ‡•MJËôÎÁ&Í>Ûˆ¤Òÿ\rÉ*P`Òî­.H\0¶@ ³IÍ+4hI€Ù5Xe¢‚6Ô“`­Y5TÙ@6MZA…£É¤l²i: f³µ’Ï—ÿx`Ğ(Â'œ±Nv„^g Š6„Ë3;›òú«¥.ëÍÂe€‹0d'`‚ÌTÔ¿3/\nK7›x#Í(),Á!Íë7Ø!ÒîÍı7ÔËìÓMú\"4À¢İˆ•1¤Æ\$y6¼Á%x€ç7üËà>N%8¤ß€)Î,lãXÎ6¹ÔÌI8Êzİø@!,&\\§ ‡HQ BL\"Ü\$ª¯+´é8\$Şï+A8ëñsNlÓœçîÎvQ´Ü“ŸÍß6ì\nA\n7f<”ĞÓc&Ì°ân@7ËPˆh³zNh2HnóÎ5:¬êoÅË;:Ä¿S~™0H6ƒ×Ìl%x9s>ŒA%,tÍ>æê\0005ÎÖBQ%oNÜDŞ3²'Ãn3µ\$‰€Pzm.Üím8¬”Úğ ‘9”ğ«œöDğA¤A;8\rÓÆ€Ë<\09g˜.kd­^ÃòÆ‚B	Pdƒ–F¬`@Ú¬?+TôkFÌÎ0Õƒ¼©ke02C¬´kƒõ)S/aU68„âFkjæğm`'…ªh[-=ºşEÛË˜È¤ÓÖ»¸)2¥°ÀXd­³ã.à+ÓÖƒn\nò¥ ø„şÂ¼øî–„È\rÓÖÏÆ¢¥­Ş65Û€â­(Ì£ZR¾[/|µ2/c*Ì S¡Êa(dı8(³‹Ïı9m`*¤ı0a(ïª&Í>Ü¹@äMtØä„o†µ€çF<_üáFÌøc4öğšK—D¶Ršƒ¡4†Š\rª)°ö€0§9’,áR€!­B°KÍ&À:0#¦\$±ÒéÁ+#Ùaÿ¼¶h\\ŸáH,9\0[Ò¦Ë30#\0ûĞO2jÇ2¯Ìï'Î¨Ê DÓ‚å’•+Bât-l	¥ô+9+¶Ó‘¹ÊôD˜ô3\0Ê¬­A3{[Ò†]:X82Ä‚•€%R•-ÚFûŸSy X8Á‡ÌÁ	”—²&€Ë-­	`KƒZ’k5ïÄäbyÍŸ&ròå¤qDÌì €É„ 6´OM‰x 3ÇC;;­ÙŠX'¥”UÑ/>Ø ,<A>À ,Ñg\$RÃØÔr‰o£\0àY€ €c:Tü…ÿ‚\0”ÎTcI5\$iÁMˆà=³gAW%ÊG\09(ô\nœÚáMŠ	”€¶MpıóW<‘B”ÕÓfÑz%|Ù¨¢€ñ?4ØëQÕ?M 6QÙCÄı!ÿ v:¨5´^šı7‘à9Ñõ\"\0\r”}G„_ı4hTÜ­öMÊ	P2µ¦-mµ	^f¨ )¹\nLn¾ †\0ä+Ğ¬Ô:‘*|ÏAR\0È¨Ñ'˜ÊØ\rd—¡j\0îÿ”ßü†Õ@t)dË\nJõ“™“|@Ù@pĞÎdì˜Pc¼›4û´ (!\$ò`ˆf“€æ5¥ASMc‰Ïü%|òÔO-IüÙ³¥M…IäÙ¡À¢ŸM*Ë:Ût¸ÓfÒ¨Õ\"À9R0+È®`ÉœÜ#É¯L	5P5t“K¶ Oó Y;°…SN×íó\0€É@\\íkLÉ~DÇ3µƒoJDít¸£¥3µ¶ûLuµÎÖ\r\\Ç!­FTdeƒDF]I`Â4ú@0²H³˜tpœ2ªÌ'HùÄE¢ÒôîÚk¯,Ìí¤£D!¢”Ö†µMBònMğ\$DšÁÈ\n°à4MğA=bW„.la,˜‘K¼ßäé8õ8ë‚M ÓQjİ„/Häß\0ô®•a£œ7TïIMäßïÓ~e84OSÄ?½8´òÉNBÃ”åS«N0?TïÓœ)->€:SíN•=À!±©Oœê0	ÌH½<ôôËÂÕ8Œ›TO€\"!¤T\0½>óT!Oã`uŠÆ+-@4ïÓt½<ÍÒT\rPÌ‘µËPå=ôäÔD·u\0‡PŒÌK8ôÚSÿQD¼À7É¹'ôĞcX\rFæÕKĞ9Glî\nËQÀTà•R9R\$½ïÔˆµ4 `\r’Ê.»R`\\•'0	RRœ’O/D§å™Ê	P\nBÀí½6õ+310T¯šTÊ\nA0%ÜÌÂ›HÉ•7¡¼äg‹24 ²ÔïDØ[r5¤ì˜Rµ%:ˆ 2¬Ójh¡GS\$gmáö¥£QüÑ\0à‘”AÅR ¯ÕT¢ô¯ÜFYArBê@wï¬Hò@ûÁãÅÈ^\$àjy/RÑ\0'È`l[ „	VIà:Ì7)à,ˆZÒ''BÅ ¬Ó`±à#BËî°ÍXµ\\KLFü¥BHÇ.\rŒµq¨Ğ?0`QImú\rxò†^Çt‹ÏêM¼§¨êkD«vaêËvô‚“TïEÑ°pÊ”×O•LHaPö“½4ª …ŒÀ‚ÌíQĞÌ™Ñ« ~‚Iµ_Oäfµ4ğ6ƒ\n\0ëá­\0€ XEÂôÓBØCfTP±”DCî\0@àbX	b 0d—D6k,40ö(oUb½qYÅ6di“†\nU<P(ê´Õ<]T\$ùÖ?h\n`Ã¼©z8Hí¹Yğ\n€¦º¦•Ÿ:DD\0˜(ıêb*\n“`\\ ¦\$ ¶ø\n` £´“X	ÕŸ\0€8üªˆ¹=èñ\0ˆ`x ¹<>•\r7°ÊªëE’¡AÇ1I+’¢`ìÖ:mcõ‡¸¡hRÕPÅgYDCµ•YdCQg'á\r‘ª&hE:KÜD*\0v˜úé­È‘‰\0ÒMÎ?H (Â¬”bO¤ã(}U˜Ö½c‘Ö=YdUÀVJ=\rqJóŒGY‰~À6y#äUËÈñ[qæá°BÕ¹™[²‰iêÖò1ou¾×cY%pQ!EßÚxñ€_]õrQx€Û]AH5˜‚¢,b‘pD]uc ^ú„UßÅŸen\nÖÛÔcuÕWƒéüÑÆ¹X#å4Älu@€Û_ÈÍq†5}\\_5ø¨Ñí~±Ÿ™Õ_ÅR„%_õ€\0006X àÈïrkdamX,´w¶\r#ã`¹x(\"iÀ<·	!úÑ¶¤:0íƒ\rÃØ<9\"`À'aU†T…\$eDX_aŠ,\0<Xiaƒ¡g¤Ya`ÂÀ3‚¼R\r‡vXB+\r…ô¿Tm‡¶Xœ……Øh“i¶Ya£nè¤XOb(¨Ş fèV*¼Ùaxnò±ĞÀäXh0¬\0®Óc@Ğ\0;Ø´hP7Øëbxö=Xãa¸PÚƒëcõÔ~Ù	c56:Xâ¨:3ÎØhe‘àŠİÀˆ–ƒıd˜OîÍX^€<VLNUd­ˆIT‚0\0OöDX\0\0mv7˜ iQbİXiDØ\rvD!>ZC´ğZ€Œ‚ÉXhôyå(ØRGTUYöˆ°àzb·R4\$Õ†•j¹êÜ»Z>ÃdÕ”6,K ä ª)5öY€Gà/Çe,ĞZ´]Ù®PØ%C\"6öoG~-ø\rÀ<Øø+sÂ·j±ÄY£¼e@1YédMš!E@¤›ràÖ\0Z\\õƒ\0<YÿgÍŸv{!&eŠ€1YÜÓˆ:lYÍcAµV5fÕ›–rÙÔ¸õ¢¶pYª6öŒGAhÔvEWYÏhÈZ–…5i0[VmÚGiIUÉ7Hl‚\0[qÙ³i=¥à9ZUf;Ó¥ƒ\0‘ÅÖÚqfÅ¤–uÙ¨kcÈ Z,,•¤V¢ZSføhv¥¶•jœ–§Ù×K¢Có È~+-ªM¼Z[gLvV¬\$]jÔıÖ®¨ég=6±7.nÒ7‰<ˆ†¨r²Œ+t£¡*X}bm”¶²Z¸•Pvº¶½c}¢¶¾Zık½š5ÿ”+q8D“€[jÀ!¬£Z8˜a‰‡i¥Ÿ¡Z¾œm§`‰Gs@m¤	ÆÙµka–Y&3Ø+Ö;ƒaa #ÓãÛG0\nEÁÏ[Oa|‘Aƒˆú!ğ¯a[`°¼VÚTı¶âA[r\nÕK¹Û{m9ÚN\$İ¸vß€ÎÈ6\0€Œnå†–ØÛ”iv¶Ù§g\ršÖ”Z&}ÌY¾¸%§ĞdÚƒlõ­ïYÖrM¬Ë;Û.Ô§vôZ=g²Öî[@å²!\rK•Qİ½6ÏÛÌå­V½\$÷oˆ(¶îÛÙj€>¶üÛËpaTÜ\roX²V€Ä]Ávù[öÔm»mpÀ6ù¯RFZ–ÉÙ­p\0\r—\\’)\0¨P„\nÈ¬Ö€\\AqY6€Ïh`¶‡\\1q\rÃ@ÚC!ø\röà€İ0˜o\0¯80°Pƒô;@B€À\\:m¿VöÛpxb+£yq¸%W\\xmÇÍ«r\nL@:\\‰iÈãrÜpkıMG\$…lõÈÖ2m ›`Zk>óiW!\\µ?%Ê€Ü­jT}RÜ¿qlvW(ãe½…‚BÜãqıÊ÷&Ìm °)[h®•É7Ù×r¨?K€[W£xw\\Wq¨]¶€\0îXoIÛ1p-¤¶8‡ÙhM˜MÒYŠEP¶ä\\%o`¦	ÊØ!m{Ö Û_µvÃ×_İ·YX%U‚·\r²†Ñà€Š>\rÍ›V¾€æ­§w[Xä£„ v2˜UqxDH*ÜÛu°u¶–»@,m‚\$Ù\\I`Øà”¤,6E¦dÜtÁ’¼0öæ¶œ\\[v…Ø·l.§vM¶£¦]º#è­Ğ‰1mEİWdX/våˆØ\nŞ!õŞ–Ãİ×`½ØS•\0[dbëösİ…v%¤·w]óvsf…¯trG\0003İÌ#íÚƒå]­g]ßÎ^£¥á×&ÙİxàÕüŞxÅáW;h“dŞCv 6Ş3võäæô4çx˜9ŠŒÖM•â\"·°Ó˜´¹•7cä·˜;éyÕ•wš\0İy±ƒ7„Ùy5èw™	%imà÷–Ş”æõé–©İ¤íµé‹®=HºBÎä^zÉ:a=Ùezİ,×®»ézıİÙ9Ë0®İ	x%‘×¨^Ez•¼÷®XËxCWÙ©x-îW–Zuj…!j†EçÁXŞÉ{eï·Ş÷{»—¨ÚtğÑØ¶V„û½ğé_{Ñ	Æ\\|u½¦Š^7oU»—i‚¼'œ{²*HXyÕó9!Ö\0;XuÀíØ >U‚r\$ƒé{µ“À‚ß1dh>÷ƒv#Ù`/€ñ~ò·»ßU}İï—Æß{¥ÕJ-\rQ_!Ç5ï-9cHÊÖhXÂ@l21ÈõXÜµŒ°>Ğû€Ã¬‘‚Oª¢\0¤\0,Pº‰f\n`\\ \"€¬*GÀÂ²>Ø\nWñ\0V“bVj¢	]º¬÷ü€Š\neşÀ\$©d¥¥ı€«%‘\rÿ÷ù_Á¨3r²:Pj€mı\0±ƒ0ªÜ	#–*Ü#ö€¤8ıÃí€š†\0èÚ»µü˜\rßÎ`mı)ò_Ö\n£ú€£Zxş¨ìÊH ,71MüËP\0œ°	êg€¤:è\\ª˜€Š?şCõ€˜È@\"ßì¥˜à+“&>ØéI\0¾9i*¼*DJe€™€(xà@ûõÑàˆxV‡d@ßÛzGÁ­`Ê¦f\r_Í€­ı@ÑÊ6­¤Ùƒ’Q£íàë.\r`°àÛƒĞ*Î…\$G€á2X1àç„\rü`7Úµ·ÿ`ƒF\0\$¤L?v`*€™QWı€§Z*MCìàÀZF\0,aƒ5şØ\0 ”Q€,àÁƒx:`ÑƒVxC`ó…™2`#àA‚0¸GàÎĞ\\©A…Ê?“€!ßÿ†€«`€Š@˜\n`Ø`h)`Õ€èû˜7h?Î„àM„¾¸4àí„.¢TƒE°\nx…Ê@0xàr\n°)\\`}‚X\"­‚6	\0Ê?=şØI_õ†-ÿ¸\ráO€Î@Âáö†¸na`–Hjà\r†öwø\0ˆÒM*¾à›‚x	ø(à¦.\n¸Œà°vWıà»…6!Ø0â Ö\nNà‡\n)*wˆæ\nØâC‚âDx’Ë… 0©@ßíZb:ÃìáEZ•k˜^V±ZÒMUª¬¨‚<CñV¹‚åıÉ†´?€şWû`ù„şô€ªjF˜§â¾	I#…ø\0€Ø•Xœâ·ĞXT%ö*b\0ø¦x\"UbµŠ\r©à¿‰.Xe\0¦šVjˆâçŠæ\rV‰ş/x âJ¨ˆüJ‚bÿŠ–Ø»âoˆ†, b¡Œ8Áb}‡^(?âJ¦	2xÈµ\rbàv@\0•Îì?ÀÿRj`‰ é>£j*­ãôcAXjD_Ş>î6xÇà…kC÷‘\"=¸İ#j>õkCüË‹Tõ¸/KÜ?ºÛøÊ?Ò¢8Ğ¥%Yú; %ã„?pşøê€§Š¾:ãïa.-\nH‘ØıøìãSöØÖc]\0*ØØãĞ¦f3\0)coxûxëãy†V>˜ö\0V¨ ûƒò*ª?·£ï€’ıÑ‰È?0\nğãI&4ãü€‚& ø7ßá]&ANª'Š*Ü™á?6Kd*MĞx-c×‚=ƒôdA¶?¹`Å¾@¹\nà\r…îùÎ.?ƒîäA‡Ä¡ ,)8X€#\0˜¨ 	\n“£…Š¥ëp#j>èÿ\0&ĞµØÄa>*6€*âÆ·\"Õ·€“„ü“@ ÖÏ\0X=«	y\0·úd³’Ş\r\$dÇ> ™3‚­L»¡\$Écœ\n¶:úcY8ĞıHáä€>A˜ód]gxı`Å“æNÀ«dk]#÷À”Lè»”:6Øu©G}t\n‚©e‰öPø¯dıˆ…tzÜ¨½j *d¡Z5i\n°ß‹>Èçâ”ãXÉ…Ëˆş99åF?–Ux,É7’GÙTåH^9• äåª²YU¬J”ye_[&Wé6\$!9ÉAåm•†Wøïäï4Ù=	•öWgb€FXará…‚*OÀ+å³‡†4y<e£FAùeåˆ^,XIå¨>àüÙTe¾Z=ko€©—6Q9]ãj”X\nŠ‚eŞ?=şûeß’Jyzd6J¦(ÚàA˜6¸ì­‘’>y|eõ–¦/Š˜cô?Š¢9„f#~a¸Äæƒc9|æ7‘€û™„\0®”`	ÊƒeÌ?~\0X%Ş”Z‘9Gf%™^Fcúå?v)%’~9ä¦ÃNd²ƒ0ÕàkIW>®eø—£„?9+àÆ¡ &%Ğ×G™âMX/ƒ\n†¸[aÃ…¤6XœæY”öEø¡àü·&@™£Âj§JÉÍ„ş7à%âŸ˜–êFÒ	™¶ân~y¸*©à0¸j­Rş¸ëe®¨\ny®ã%”h*Ù>åSœFW b‰\n­ ÂâƒJÙ*eãê\n¶.->ØkHååVöh•®äà¨ıÙÏgŒJ8XdCh\n  äŞX	¹Ò\0ø¾Ará7ˆeı¹V±Š\rşYd€¾^©2æøB¢PV{œN1¡­d \nà\"ßá'’?ùU£mnyù)g›\0håã>r¥²‚ÛŒş*€Â¥·¤ãYëçsŸ kYÚ\$	†>nˆöâ©JÜãäBJ™ù çè?v|À«%gŸ¨0¹ü€V?WA \n”g÷ƒzî-À·X``È‘Øk@\"f	R9xXàäLı@*\0¾µ0ûÚd'‚ª78ìh<‘ö„%“X/\$Ô?n…7ùèCŒF…ïè6¶††Zæi\$n„ªbhO (0ªF¸¦jÃì\0‡‹Ò ¹ld´·6z³‚`ıÄ¸?¶‰÷ø*\n“VR#ú©2>‰I®©j™cûdµÆH¸mesœxû¸ì¤H>òDUÎå€¶†*Ø¬¼“f‚Ya#Ã™²8Y¹hæ¨’˜z7à~¥v?8ï\0Ÿ‹Şƒ¹T%?&AuÎãÅ.-Ä\0€²øş\$Éi	¤Z§CïèşÎG€¾©ÒLÙ\0\0ƒ¤^šJ¤`”y¤xÕÒ\$c¤Ş˜ÿh ‘…şšL-èL üšWä/¥Š¢9à\$ ?–FYg[/pü#ûæ›\\íşPi&·\n˜Še×E¦ \n²€Œ>Ís ##m¥öGÀ'Ä¦G©ÄÆGºAè¯ÆŠà\"æS‘ê°™ã>Ş8÷+ªê¥Ø\0û¡I5à¦©n‘ )i.L üÚNè§zLİ§ÆËpæR¥¦-˜µ£—^Ø£–©J²JV¤h©Ş`™Ò¤}F‘’K)À*i¨¤f›4bá‹zšf‰ –\n¥+\$­ÅÿÉF*øª&£Úi-Ãœ“Zå§a2xh*[“ˆû€/âö`øÛ£¤æ‡úšá”¤Lñi|©Ğ€\"#k›–“çb9¤ºJhªN\nš~Éàª8	?gËF'Ø–â7ˆî\nùY*¼“6K\nÖ­(6_‹rb*©Ö	ª™àIZæ­Y³‚Z«êšªfv®y1j¨#¡Hìª!«f£ıbèNC˜F*LnùCc›”2AUĞdµ“¢AXb_]™Ãõbõ]h•Îäë¡`ü	\0’©6R²–æÔ±2Kraz'®<¹rkKvqz9€‰§’Z©\r¨^Š™M×B”`üZAãy…,½ØQêô“èrRksšQ8ej×(69§eñ”Şy‘€¥™.âêº8ùCéK­ğø/\0†ú?¸kk¢?–U8êl?Öúè©‰®¦ù•¥¨’«Êˆèê8 /ä«“ş•º\n¶·\rsMÚ-EZ¾Jê™e…ö?¹¬fh·ÔXLá7™æ¥˜e\$ª\"Ü*‚g]™¢ ºth€¦ãXÎhÇZû¸Î\0²©’Ër-·ŸVH8—Ç h€,Ã§–v)dƒ‚hüØ×`¾úA>PÚê>½Áóö‚\$Í1	\n\$o7,› †Si9p:î„­1 ·eb<Ü¡ˆ8L© íj\\µgTÇ-†Éfk¼Pa@ÊxT¯· +eKÒ±LIkıÁRŠÊôş;0Ï˜\rSåªLÈ·KŞ\rÉ€€É¬]x*6ÇÕ³¡éÍËÕM!%Q>„‚[>l~nÏI7€Ï³µ!ClõE\0T¾ƒtd­!Ó¸£UG†{Rˆô\0Ú·p*VVì}´æÒ‡\$É…´æÒà<í2&Ò¬¢Ú…aA\0 ¸\nZ€¿‘®s¯…Tl\0<ª ?dü¥¢à\n`+8şú†É®¥Dƒøã‡€öZg*j¦f\"`Â€›€ş’û]V²hkU¢©¨frj–a¬>Û:óí¹{ogô·¦Ü;mä1•^ù\\#ÅŠ¾+ Âí´“Vİ`«mÌZ¥ãømÓ·¦){{mÛ©.6›|€‘·ŞÜ{v_í¸¸mYğ0æ€ßSíÄÓ:¬§ 9·8U)‹Êy&Öâş vöâû,Óâs,°@¨\0Ò4-É›\"„¸^Mòû—n+û–Ë-{şæ59Ê“¹°K€;Ò&@Ò³Jt\rDÆÁ>ÏoVÄÏÌ+\0¾Nnô”Î[*HD³\\½CP\"âQ(%€Ûª	)&	 /Õ¶fµOƒ\$ÚW,ÂQ, “î…±ğ…ÓOƒ»P!Û•îb1Ì˜ÌÏ†H—ÖÊAFƒÅ*¨ïtÓ\nMHüøsb€å»AjíÇ)âÔÕÂŒˆ¦kM¼R1FíDìèåã…K¼€:î¦\r^é»Ê”¼Ğlà1µ¥2àfItl˜²YIc+¬–É)…U%¸:áSÉ]%ÌÀÀ1€g\$°5·ƒò%|—’_>#˜SŞ#\$µâ2L‚k&4µA‡&A	 è !&L™ÂIœ¥`*êÉ£+®ò`â›ï,»V±M¶!}C¨\\x˜Ge„Ë¾Ó\$;0ïµk¢(ÚÄc»H‹¿lÛ€ûõ‡Ù¿tšÔºJ;36şÁ¢´ãQ†ş¢Ü‰;vÿ 3lÏ1IE\rî×\$%›û	_À@ë.„•PÀBû­\\»8±=u<ÀıP#ïµ»(?U\"‘6=Dô”\"‘NyšÛıÍ]Á0\rQÉ=€HDÙ`¤Ü†òc0,\0İŸ2†T˜Ì[0†ÏÁçóªk¾Ğ}œWó¾ÖÌ3÷oÓ³Óø\r?vÌ_T¿³	Ü%ÏİÀ–é{=ÂAÒôp/_ÏÜ‰_ÀpJ|*%µ\"ğ?3·oÿ¿·\r\0002€Ö/\0`>pãÀÿô9‡*}Ç”	ğ	£´»ÍZÄHk[Œ–ê¤Ë†‡\\+Cáæ‡pµ6\ri0ö|ä¥sßJe-zN)•ñ-Yˆ”=–‰Ä9h€6€È¬ÓæÂ8nîQ¯œZ)^<T\0ÃF8¤Ëq¼¬¦\\GY\ræ´J›@\r#ÎÜbÄöá…ŠLKıÁã7Ë)6³’§Ùsqní6Æ(—Hùt ÖgA·ÏGî(cg¼AÊ`\n…²T††uâ‹ËÃ_Ìå\\¹Ë¼¯u„—Ã+…îğ#e„ä`ıá=¡.½¸~â»­L8€;s·³Çœv^R\rÉ…!ŸXfë€éN–±äª»=ÇÉh à…¼©6†\0ëÈˆOµ]¸í-bBæ€ì?S îTÜ²!µß…%øanªIrê§`ú‚%»]ÅàÓ¹Ä˜’I†¯@:´gQœ›—ÊŠß'1€„ûÃ86R]‰ÉåØ–6Oãx\$jÓ.Qıx|šÁ6İ/XÆä»=rtÀkRzØõè•ü«;µMÇÂÂplW*ÛŒ²E |˜AqO\nÀ5<²‰	#ü¬…Ä8pF3Ğò±0PgÜm „Ÿ+’]ò¾%,UUqË8&•ï,gü«á,;o*h¡£7g¾ğQ@47H‚1QóÎGvM”ZûÄ÷‘ÎÏ.€;§ˆËqqÜh3ùíRÌ¶@9…«(bB‚0ìå¸¨C@©Ñ%Æã¡Tpå)ˆ›IJa!¾æ*ómUÇ7tÚ4¿ÍŞâµG„³¡ŒfLJ\rÈHMIÿË9)h†H O9•^ˆ}H˜`É³nÓÍ9àºíSg¨kW*íWÀFÏW*óÀ \\º‚2sÇ³ğ( ØsÏ±÷7|õÉ…µ/=Üöİ³×>ùóÈi\$nÏ=Üüsê	_<79s¿t)óy‚WÀG@»Jt]!ôóÍĞ'Aœ÷í=Ğ_A¼ÿ€ÛĞ/>@•ô1ÏßB“róóĞ·@¼ÿt9Ğ…!ÑíWÌ¨d›PÔ}´ÙáaµE’õm=µE»RíO&51qÎT= ÂŠ`_ıÒº	WT—ÛU\"×½~è(å”jNš\"E×ï#uİ¡†VBM•n>ò;Â‰åÒ¶ıBûí]€¤o|œæ/”cƒZˆqÉ[ÊÈtÕÒ»Z•ñğæø±²ÎôçÒ‡2=3ríÆ„4,m!=“_Óèg½3·*Öİ5§¹AO óÙÆÑD—Q¢Ôæ,r#8æü¼½*ô×lfëE’˜RŠ7SÂõAIÈ!ôAõJ<fÊ2.ôöN°J@ƒBØgU{­îÁèA„\$òùÔ¯T à˜R)0„sÓõ_v@\r|\0ÏÇxJ`9\0ğ2[Ù”Éu†'PA&ĞMÕ{Ş ‡Ñ	<R—]„º+›ü õwÖÕ–òE?qÏ}=Ù`1ÙƒŠæ‚\"½ì 7^#Õ]â1F‰5Á›èİsHm×F-9õá×¿PñªtÑÔg\\{õmÔ__}cî¶1Ù:\\–Ï×·Xı…ÓÕG^Uß!DaXv†í}=ß Nôøàˆ»Ø’r‰ñû×¿aİoôÀÖOW³0t÷CÅy'Ö^k×%-]=Œ¤1İæ°ÛÕ\\ì!‰~!½™J:O§OñğyWdİ5¬­Ó¸\"UÖ\n7R=Lu'ÚWW&ò;°ütö±~æS\0Æb0å‡JÚY\0006m22G ÂƒX@¦U(–Zom .õ×là-öÛxnFU_\n×m¢çvÛ\"ïlıcp†ı½õİ+x­=5õâfó—öğeRë¢°vêïmeŒœˆ)^-9§ÚBUßíÙôbI6lqñ­È/Mjáôá1ˆK‹£îü=¾Yÿ[ppË@¿o¢”7Ö0i¡SöúüVèT”vûÚÀa`8lh\n×l<€,­+ÇM}¬Uª7sıâWI_\$V\\;œ×n¶Ü™Üy‰€ú÷M¿qâøÇE½ïq÷&q—^=Õ„K×ıP=eÜ™ß\nì`‡ö	|‡|ı{ö	kèHvŞ#2ãZÈÙ7~İ8Ê`®=ÒöÓ?lİÃuïÍhaV	5ÈÏz&U^Ünô¨YX\$qãªR™ÙĞqı¶ñM»€jwà¦ï\rvÛÆ÷!¶U÷#'‚¦Uva?q]Ç€ç\"ÇÓ1T¬ƒ£eÜT™Gz\\Âf#1Ñ;uéİfNøA6F7†W‡§ÿtó-‡ ‹‚³ceÁ¡1İ®ÀO½Åûágsêçô|ÿQ]é+áN}ÇÌsŞ£Z]U^Ö)7qâo84İÁıc®Äâ}UxÓŞ¢ëû6øîşj°O…W»oÜ§UT|ÕmBóıÇ\rÜ|‹¶×ÉŞÍEñx6'VxUı\$t<Íç} \r/Iƒ,«l~,ø×zå’ß¹—“><øKÛw^Lså×¶õU(“£>‹a.døş\rÉõŞçm¾ùheP¡ïñyní¡>¡‡àd–\0003øÊ^<Ì9š)Üwm¶„ùwIX7\\éï{&\0ş\rß2C÷\0Tİà¢òÁ…À<Dø'qíø¼í2I€ÉåMËçNÈço•¬Óöçç%)\"yÛ&4š66-1áU½Çñ”8œê,÷êŒ5³B)m»­ù¤ùQh¾yºS[K9¿'ÿ…`©xGrZ’!MèWm¶8y£åT–\\eïjqë¨y,9=]NOÄ3_qöˆM±ã\nr÷î÷µk{tÉw&÷y*å”føàİÖõ»3øqT57ÆŸ3’¹»I…UmÉÿê/Eİàg)ˆİ‚I“º64aú@62K(ß¯{™ãNèM¨QÑãG1=1]â‡^;”™Ü8hŞ²uïÛ¿ŠÏTZ àÌ\"ùz4èÃ¾(yì¾(y™ØÎ|&\n5Ã:Ë«úuÚO¯bw?ã9Ç7ÀR|ˆŞû,kk×Èî‘¿~„Ñ[ïÚ\rğ/•K.7e½cMÊFñõœiuûÖ`›UÛ9È?òåûMqoŸt¹Ñx¤¶]K¶yßÆéıçw‘á?}uğ'³\0¬¼,	‡CòñBpÓíöÉ=ªÏ˜M—@ˆúd|ëüZq¥íÇ¹ã“<Ÿ}P+³|	\$ÜÏwú4Ä š‚Ö43LğÖ\$—½bw_*ˆK›Iµz?~»IzO²+çqkïx±¤é{ä¯¾‹¨\n\rG‰wŠ{•L‘”…KUï–ë~IÙ±ìO`E’î%ÉwR †û!Ö_¿Ê‚u/KS÷,£k}ƒ@u}ä1šß	x}ÅÃÜM8ñ\rØuS¿¿ü|o“4ÃÖe(Èb%n´\r¨œZ|9ñ—4tçñ×(ß[R\n÷Æ\0Ø|dÖHMÁU|=nY2ü=ï×|_ÙWÜîí?q«èñ6W—Ë—ò\"àüúpÅGÆ_\"üºÏAÓ·x%4_”ŞÒqİ3§”€ïíGE4üÌçµªé—o¹ìå^´gHŸÎ~Lüëäü _6v2İ—\0Ğš‚íÅ6}>uıØ Æ¡FöSÙ¥èä‰€ùéÏ´v}1°K½ô˜áòiv8LıéÏÉ¤éõÃôŸkŸK!ï÷X|E‡|„”{ÎŸlÑ²ê İıîÛ5ğU–ç^WE;ŞõÛ&xılÃºı§D\nvRï\ry¡ÖØ]£¥9ÊÏ²=ÆÑâ}“Ûß!í°Kı™Ö_Ú˜wÊb<ùäÙv—Õ×Û~÷“|€D šuƒÕ÷}~Èñ¥îŸoÕÏìĞ¿tü Jÿ>îƒÙ¿cv1­4T¶ĞŸqû#>ñˆİxıåê€\"Ÿz\röÙ=]îY|€W²mx 5´l}¶Í+Ÿı·Æ÷ÏÍ¥}·\rwiqìiÊ7 0òË|•ÿ„²Áÿ{vwØºß“~AÖ¼Âa½»é&ãÿ”XÆ‚J}¨ò³ù¨%Ÿ„ûÓë´Ÿ–şx_0ÇtÊùwå¬Lš4JS ‚¤f·„ŸCu¹gê\"ï}½KO ¼wxÚã¿¬îrt?·¼ùÍJ\0«P+Út!.;˜Fû'œÂ}%f?X¿ˆÍ]bïìÁ>Ê±6€QÕ[¥ÕP’3ş¥ô—ºÂHÑpg/ß¼’Êaâ¸‡ñÌ 53ÉƒµùÏêŞ {õÜoh;tñØ'ÉŸK{şà?ó=ÖpGó·¾L °‡Ò½Ö12ô½ñËeüï|`;õÂ‘¸bLIïĞ’?ÊwÈğGl4Mı=ÇÏöı¦}#ıîë4ÂI­üˆ7DÙêw¸İ¿v8\n÷!,öı¼X%¾™é6_\\P£şŸßëè÷Õ”ù¿cÀâõ‹Áõğıuãğ/çß¿÷ÉYƒtèÕg(Hÿ ˆÈZ/gü{mö(%œ+Ó·ÿŸhÿ&Í¦êø#P%âÿ£ºáw˜	qíÃ´weF‚S2Œ•iü‹é×`»¦½³€ÙÍÚˆ¼ÄÈ-ë`;Lü›¥Â»ÇWÿEt¿?Õ\nJ,Í/1­'ÜïuS˜<Jv±µz;ûgòÃë 	¬k€`÷aı´7Yk5^;¥Ë_\0005bXìä¬ûœ‹½/^jê<óø·‡Î¨‚L;’Vôh™gTI. ½ç\nè6(h£½—P]¶ruHùŞÉ¼h°U	ràâ Ññ\"|@¡İ>®(Ò@‚õ\"!0!…`È% ¥ØR—a*Ï¬\0iEC¬Er¼EnHºPÉ”:VìDh™I2x¦\n½«ƒ'ÜŠn@Â‘ÀİĞÕ¯Şc&Êu\r˜em(¼i+ÜDX\$TH\nõŠ“ä0P¯¹_#(#sÊ/«ã#!]b·Oêñ‡ID'®PtåyŠë€À\n4Šm*¹D^DòUì@`\r\$¡àÅ}DôQ}@¯&\r%}b„xæT@:7¶Wô,’1R@w]†@ €°P1²3˜ˆÆàD\$0G‹L”\rSk¬àl#\0_l'àe(ëí–Ã¬XĞ­°zÈë>&T<™c|\"p?Á/ÁYğ±N `Ô+EÖÅ‚Oİ-jbÇÀyĞD6¯[)QxjŞõË×…0]ùqs\"S¼@Ä A]>™FÂñÅ²0J@İ¬“%£±hgÇ\$á ’Áoà.½x”UÍ¯o\0A0\\ş¹XºŒ¥á¯	 ŸH,Z	ñè(+4W]ol¢˜qd%ãi€3A>[4¾\nKŸ\"KÒ«ğH[\$êäcğ4‘‘@é­±\\\r˜îÓ n@ò\0Æ\0½eÖQj\0kÖå£7YÎ¨8\$th\0PÔ‘bg\0;Ä•?,>HØøpY\"E‚aÖşF™rx²Po)¢\r¥-2uº´Éb0apSIßXš. ½\0OP&S}à¦ y9*JÜÂüÒ‹«óàg/ÒzŒØÁ*Á¥û*CVÀŸ_¸‘~ó6vm,””‡à-ÂÈ€?“`†°\rlÃğ17d†­e›X¦0¥,˜b€Wƒ„¬«¥k+ûÙ¸¯ñfÖÍÕZÓ8 m^[m4©ƒ®ÍÙ€1j&Q±Z:¤eæÊuŸc8<lmXÌ¶äiHH€ˆ¦)MqÆ+W\$hÅ«c†e¬Ø`ûÁÉ°GÑª–Ì/š\0Ğ„Û¹£Û*†­ÚC±ä„R`¥!À&5`U˜>2„M—ƒ\"XBL½˜äBcBRŒªë:ÖPÙ~3Gi›[DØCƒCıBch „ÙQ8EL½™?ÂdV­Ä#fdn!08WE¹‘¡H†‰LÚ´³edxÑ˜\\R²M0š52?eÑ½›C!8JÚZ7€Ph¸ËX[:â=`@#´l„°Ñv\\%òMz ó1ùe´I±ºµ2ÜŒ¶Ù.2âc±	aD!M€\ná\nÁÉ„æÇºœ!ØNpˆ ¼6„ÌZ+[VŒ°–!ÂgşÂz{ş¬¡CB0)‡Ê3'øNlÇg'…&ÆBÄ(–>‹Ø21…&fü'6PpœÙ0jŞÄÕ–”,Xÿ²-fc	f\$%ÂÚˆ!04¹„Ï&T+hM¬´!W2	_ÇA‘ã\"N¬°™1îdAu‘'ÈDP¯Xb«¢„LÈÙ‘s?ò<P³\0«\0R…\né˜KZfDàa±ødTÊbû¸S0¶ı²1dU\n‚£¸HLÙn¥\rhJ%°~2”P„Zg*!d„ËÉ’kGÆ/¡öËr4ÖjVHŒ[U6RõÚÁÃƒº®‚ë(Æ³ì¦™¹³lƒ²½›Ó7&S…EÙ41Óf°TM¡£ŞŒóÌ5ÁaÚÅ\\ã`ò˜Œø{Bb¼Ëh¥¶¬å×ûÂÎ…¡Z3AZ0É˜c2¿†RÆFƒ„ºp[ 7İ†x÷Â‹ëEªÕ>CCzşŸÄ?v„ ¹@-l`Çåá·»Oo3Û-ÂĞ\$”<¦zló‹T…hÌ ©ÓJnì˜ò67há\\Û4àıÀÂÛˆ*ˆn.••½°©³€íÇÎ€(gÆÌUó@¦{¥&!6+n]%`ì°\\If[Õ(Az’\n½ªaÆ÷\0jò¤5ovè¥&\nÑÍñ£·Éx–ß *úL¶ùÀİŞª8:fßI?jÊ2\$¯İ(=&oD’½½+}FôÉ-’]OÄ“¾¡˜‡\$MËÀ¸µ\n\0áâìí;›–9€I¬w…AyØ&ò`G[Í°şÜ™¹Cv4Ì'Ñ’“¸’¢”s“uhváD\$·bo(âyÅ\0g K°`Ÿj¸“IşİÑÆ™è|…\r‚ª\\*õ»ÊÉÄ©* Û¿ÃÕoMcºRÀ!¿¤ºU¨c„<‡Íæ`\rè¦Ú=ÇscßtÜÒ7Šˆª. @T˜N'Ò´blÙáÂ@f\")=aü8Ppœ™qşa­×Oœ3·ë[böåÖHW8ï [Š„cÀ:\$	HáÇx¢9*p/1Oh'#‘Ü±)h–©~«¿ˆƒ™B¹€fpÙö¼C°)i`‚ï(A}\0A	uù»5IÿÀ¨FM\0'©J\nÆÓÉvT¾[(ß!´\0Jô¾„…\\‰Øò¸@*q8OŞº:s5<àÂÜ¤Ù„\$‡sló}R‹Fâ—”§nˆê÷áá\$GT¯E\09<<qİŞ\"»g¡‰€9:˜ˆl-K8¡¸i—øFBç\$kéU\$\\Î‰#ê\nSİ„ÜBÿâHÃømEe.Ü<aÉ. ¥&\\r6ä}Èóˆ\$—ïz†8)(‰Xä‘úêK·‡.ù\\˜¨OIrlñ%Ë”4ÕO–¹F\r™iĞÁ\0é®b`pr6ÿTì–11\\±‰’Ih˜¯1ÓùÄÓ_†•Uqx'Ğ2\"l­}I¬äÑ“Ñ´NO\"p\r³pÎå!TDK Ñ.QIm9İNŒ³‘ßSƒÑÊaŸ¾qoÅ\0Xe’ï%!°Î\\l-öuvùã¦^¯ÔS:9I¢Àe’÷h N<[¡/e~Î¥êÒ¿ğJnkA>ÄúØëÇÚ\r'X	Ê‡mE‡µ¥ÇûÚCKïyŞ—*|®	µùıÅ(âğ@à¿ÌN\$İilÄR·³o“ˆÀ…”ãÊ)lSH¢®6^ÌÅ,Rœ~\\SØ¦qPŸ áŠ…æ(¼¤.DÇIºÇs™y«±x!ÕSÓÃÚYÓH^UWXëv›£†;Š¦šPtÜU·c\0—â®†cŠ³†*Uˆª‘ZEeŠ¾¿„\0äşpöœ–-6¥ #T—\"mT_;\rqÜ°ö+ÌWGëB7Ÿ;‹\$BBız(áb½8îXŠ±,WàK7–‡=D‹%˜ˆYoô8·;rIÅV’€åÎ£Æ	(iXàXŠ-\$ZŒñ?\0ıA8Tä¤T”ôJ„é¾ÑRQh‹pñ6P\\â¤“f\r@ëğ,= ­ñmNo§€‹„y\". ¸¸Î±Ã†ƒkuæá×|=¨g1t{·]K—Jü]H£P;¸Å‡µiÕ³`s1Rbğ:¢MH³ë¾H{Oœbõ:Ç\\Ø¼QØ\$\\\$\nïvóÃ¿xÔ]~*4_D0ÑîÅï^kè‚§¥±n¦EÁ‹˜ÙLT‘	1TÔH»HŒ’0ac—ã¯öhCÚG(ß²0‹ğ!‘…£	FY«Iôø»xÂ®±ã?	{ëº0ÜbHÂ‡ãÆ!©>0Ëú\\*”àL“ät‹ö‰àø,	 ÜG+‡ùÈ˜*G^1ÁoFA{ê½aËŒXÈ_À–/˜€süØÈ ]_V/P/.24dÊ¦_\r9ÅŒ¥2%h-…„Ï Á>šŒ¼ª2ĞÈÍ1EU>·ZBh–2üd`+1˜ÂFlu|I\rg*«˜Í”À–eóÕ|‚NøË•#5¤®|à÷3Ñ§(Ï‚ı£0F†€ˆ6)ûƒª4ÕÊ²àEø>©¦TTPcï×,éK\$³ÕînçY/àÀ<q‹R5÷‰®Óá'Qq¸¼Õò´k©ƒ_ÿ=¸Œ×ø,gWhé;ã[¦\0'I'x*˜&I5T {r‚Ì›q¡ÊMS÷?ûM°–6pÇ#:€è¿wdü‰²Cç\0ZÊ®\"‚¨\"Işú©DrÆÄ¸Ñ¸\néûI¬ú¨ Kƒ/ª›ó{DMºêX¶\0È¾( €´N¤a(ŒiŠ„éÄ\0ôéêliópĞì?~#åÎã}‡ú –^`¸öWpÀá>Ót‰cMö\rè/À2Lj‡.\0ŞÂ7¸”*X–‹7™© Å>7Z^v’üdh D¡’Ã·ŠJê¥Ğx[Ã¯a‡\$¨èv~•l:4gX(Â¨ˆCıGsH°à0W/nô¢À1´â=3ÁÇÚ`rÔ”tPNÁøÂ‚µì±nã¦:(pÆÖ[ÒPÆ±¶”B=Lq`šá÷³Ò`îÃ£¯ÇYqòã¸=‚âW3Éc@ä\nŠÊÿu6À0Í/¢@Û4æì  gĞ˜‘ÜÀüÇut´ì!ô‘ˆïo–ÁF‘Éîï%è6¥ß0”µ>^|Š*ô##i•ÍK‹cÇ€Í­.È`¾N'€k\0ä­ãÓ àÏcÏG RÌš.=+éPiæÜ~%‰wŠs–;ƒx¸¢Qã5/‹u|<š'šy…ğâb ­q &¾1 &ãx¶'=ßx°ö:H3àK@_n8	\"é°l|ÖÎn€‡‹ˆTŞLûò£€êJâÏ€e€ñûpf%.‰q\\Z;´êD8ç×0\\Á=Æ	\0ĞQ.0HBçUˆºGºÔM“u¸üà‰p½¸_ ëÕúÓ¨ˆdè:÷\$â.½Æ*ñv¡’ËGêow Wâ¬hÜï‚Aê¦fuãùí:Nùñ»uã\rV{¸'ğ7œË+ùÎ¡%ÆÿG˜ñF\0Ö¸×Jyâ9]8 /çãp¼³uã Ñdƒg€oM	’1ë MÖ;ù÷ÜQR˜H7~ö	•í<„Hnn½Ğœßsv½ßuU©®í>ÙœëÆ2J‚÷^/˜äEÕ`÷`\rzk—^0œ‚HPwÜ²RB+¯x£¢ß]FÍ+¤âôûİfã+á8Pn¥ 1ã»uÇh\0q ¯YÒ]ö¼>yKD¡Û8'}Ô/òÛ†Œ yŸõÅ\n¼›(Sø´ê1Cƒ1ÀP\$\n’>ãû·òïu¤LÈ‹wğí¼(!H­ŒGÚÇ<Jc\0005n<éå‡â¥\0hSg\"­ÕòÃÙÒ(¤V>?\0Ë\"¾E£«â}ÄUH¹‰âÆEšd7İ\n^¤_8et‰:ˆTo#CC˜I•#,j,|¡Ùbw)¥Ûo#J;WK ¢¤j¾/‘›#eù’Ôè™MÅ\0×˜‚îZ\0“İ`+ÀC@Èëupaî2êo9€ÁA£)z‘Üéùß\$uôkQmH÷6ú¼Qpx&ØÔ\"7Åó.†?\nTğlæ?ßj€x]Öö¨É²ÒL‹Ô%…’Q%!í3øØÑï¡dŒƒH€ˆŞr1ügè	bd5¾‘‘ş	ş¤j—‰Æ£'º‚nÉ\$2E5`…Ed—?¨éáû’m	Ï'¤\0¯¶²ÅSï·ï±n£|\nxŸ>T}&Íñ£d¤¾¨bä^4\\hçÕÚ¤¥ÆF’¤ÿÅØ¢Mi)\0”£¥C\\´éîŠnPòWÀˆ|ìZK–@\r’YÍªŠ7g#”Óc¹`HP]L¥hp>±@ğ¼#‰˜%:x’òíïÔS}çÒóIwŠ`´]Ãğ]õ\0›œ2€túìÎ(œ¨ı2­Gv~ÿĞs„ğÎ³™Hêı*œtÕk¢-#İ’L‘yì–I,.£Ü‘I«YP2Ê+Ìé	 Üİ·BUorBsièñ‰f]nI´“tö½Ùz{i7ˆH\$Ú½…MøQÜ³Ò¨“‚Ædt&GRQ'!ôõ‡HjÆ 8+ÀÁ®Ê#Åvğ-Uä\"p&‰É[º¼¥oP/—\"ïW	â/P\0		Ë+t&;n£¤ÀB™×1©Von	=0'ŠIğPêNÔà¾Rw@0¢ÖW’Q:Ú)p{’yUÅ«ÙE¤®ôü\n•u#¤ X«à–¯O‚ú¥}2 T¢ú“û(R„tdC¶Ñ’ÀàFpº­}@é(ËQãJ#%(•}°,µK+Á\"½t\"RØ'òÂùFR\0ÁdÊ4w€\$MbŒ£©F¢DÖ0,´Ó)0ìXğ¤Ò¢§X–°Ğ“t£X¯‹9ÑÙJ20,¥ErŒ–±d”µ)EŞéKËKWÓA^”RQ	]d\$†R‹B¼J/G–Ì\nêï9L‹®P?Jq~E)–êÉB’†¥5# ”\\Q\nQ)F1d²•%:Jxö~Pü¦ˆO¸ ƒd\n½~Ìc•úuäùB6e0[}¢[VüD¢Ú_á„¬Çø¤¤*ÉSŒac²i…ÂÇ\r‘T#¸[!y²<d˜ÑÍ‘ğ™F³ğÂUĞ¶ÈkdV4ÃK9¶0qàü%†<VHKLJD´»c¯\ri›1IˆJwàìÃ+a†ÈÑ™+	¶ l*eg1	hPÄİˆQ&!Œ,Ø“°Ác¿+X?D­†lBØ†ÁÆ…:rV«†ËşØWJä`ÀÃáŒü ²R¯Kr4G8U-ëVö‰íÚŞÁï*Ï“Ef ^Ú.”Ë:	!£{#öÍá24vië\nY¡A¦ƒŒ;[X´hlÒµ“Cf†‰šBÂ¬i–×M#.È\\v4©dÒĞ·©Y&—¬X‰29k…,¨ÃJÖl¤e²Wi²u¦ëMòÓì]™!)ĞÓœªó4ÆŸåIˆÜ‡íh\$[ACJ’9mC·•-*†Çô¤8}¶KMEY’]j*Z‰+%V£mHŠ¾BTÖËåK=zd™ëµ`Kİ-˜«1[’6Å¸Ú¸±ıjÇô¤ánI>€áµ}abñ¹RÖ­e¥’5’bİ@?·ÆO¬¤Yc‘ÚÒÓ©›SKˆ:jèY½²a·+VÑRÖ:l&YŸJÑhòZÍ°qL&-ÅTc¢5LTÙğUr¨áé.Ç¥7nB	d\\órË+–Ó¬ÿJÔ¤|\$dHD©nWË¯S`+’K%	/ÅÏ7RÜ66À>ê¢¶wn@æ=¼È7§FÏ[´·,#.yûó¦ç3Ï±Ï£CopjõÅ²’dáé2@•¶ˆoš“6\ry\"‡R\$¡û¥} w„İ˜ğôÄù ‡*E2€lã)²Ä¦o]Å&‚8–ÀJ*FŒj†Ñ\\¤uùÊ©%I‚±“'Éßäf”­Oéë>ÂìÈc¹iyéhœ”'iK¢GJs€@F¢Tv¤8ªS³\n…½7,M&„qÉghö3\n\$Ë@NFïÜÇóø¨Ïƒ¢DõƒNc0Ğ º=×Qq£â–‚ŞŒêÙãªË°ÒrŸT?}PşJcu—®³Dá¸ÔzUÜ´¤¤±#½½xNX‘ÁÅ¡·Õ_ï?ŸneqPİaSì¹3&‰³\n4Må!¬J:r‰9]TÈ¼ö­õ#qH½‹nÛ¥Y^ôæc¤k…·nòV·‘œ©ÖFäÇIˆñ,]£ÁWMòD”¨'WÇr#d®/vÁ&Í2‚v×°iR£ØI}V!)ô}D½ó&\0–¹*‰öN¬¹€’†`:F<-ö `şô,C¸\n4@²'(2.r€2‚ ^«Ò”8fŠ&ù@²wå@ÂW·ü¹\\Ë”O2J)Éí(ŠSÈ\$ù•€|¥>¡˜.q´W\0Ñ@ï¤9%8<`M¨0ÁM—\0b\0àˆp\$«}\09Ìá\0p\0Ô 9œ“7À\0003\0nC}l„ÎS‡ \r@\0006™Ö\0à´Ï€³:ÀÌç™û3ògH€ó:Î\0003™İ4€I S=æ~Í	š3ºhÍÙS;f|L÷8r\0ÈÌĞÀ ¦xLŞ\0q3´´ÑÙœ \rÀ€6\0a3ªh”Íğ €¬ëš=4‚gÜÒ™œIf˜Ìñ™¾\0ÜœÏ™Ÿ“IÍ\0o4*gág sMf—€3š4¦i ™¨Ó=æt€9š]4gÀ B&ŒMD™å3„„Õ9©:@Lö4p°9¤ó=&†Làš35|i¤“A¦šÍ`šÅ5Jk<Ói£ó?f‘M7š5zk”Ñy¢3\\€\0001\0m5Æhğ™³U¦vMšµ5ÈÌÓù°óG€\0005š5Êi`	¯7¦’Í'&™5 ¼ÏI©ó;\0\0005›	4~h4×YœNfÑLíšs3ŞkìÖI©D&”M6šq3‚k¬ÒI¬ \r	\0cš¤\0Âi,×„ómæ…M{›)5Fm¤Ó IæßLòšû4~hĞi°“l¦œM¨\0b\0ækŒÖ	¤“F&ËÍršÕ6fg¼ÒÉœ3?&’€8›c3¦jĞÉ§ógæáÍš|\0æn\\Îé¬S=æµ\0006›ƒ5òkìÙ9¤s`€ÍÑ\0Ä\0Ô	\$Ò¹s`¦ïM\0›7fü×òaÓ=À€4›=4jjTĞ‚ÏSufÊ\0004›Á4 ¤İ	¡\rìæÒÍ9›76‚g<İÀ³{§\0004\0m6nl¨IÀAfwÍìš\r7Êi¼Ø™»ót&ÅÍPšÃ6ŠnœâÙ¥SF&ÀM#œ#76i”ÒÉ¤³E¦wN28s8Fq¬ÎÀ“n'	Îš}5ªnÈI›óPfÉMï›)5o4Ö‰ºà¦tÎšÃ5lLâ‰¤€\rfqM¨\0a3ÖhĞI¶3f@M­š=4âjÓ)À³WgNlše4„átĞğs…æáÎ\"™ù3âså‰Ä³f&“N\0™ñ4Rp y±3vfŸÎ\0c8Fp¼Ş‰Ê“@&tÍÕO95*rTåÙ¦SRgLı™é8útTÏ	ºSK@NNšG4špüÛY·“C§)MÓœ•3òjÜ©àfúÎ‰œ\r6¶tÜ×	À“='Lòšq7lìÛiÎ³gföM™›É3ös4Ï)±3~&€NP›Å8:k,åùŸ“}'ON)œÁ4¤átá¹syf÷§œ™æŞÎrĞ)©SŠ&|M?›K96r´Ï	·sg4¬0u6ârDí	¤íìæ‹M'œ}6på¹ÙVæÄÎÙë9lÈYŞ çNÁœS9kDé	Ğó–¦“\0000³3æs´Ù‰¿¼fÌÎ‡œy4öxŞéó¥fèM’œ!7bsTàiÑÓZfµÏœï8~mlî9¡3 ˆŞMnœ¿<kÌÓùÏÓ«&ŠÍ[œ•;VkLÖùË“E§!Îş:L:yîy¡S]NMÔšv£\nw|×Ù´S¾§Íjš¥7Šo¬ê¹Ê“¾'Íœ!7pÔà9à3A¦¤Íş›‘<fpŒëÙ©Ó¯gbN¹›npækŒò)¤Ó¿¦ŸÏ›Ù6&w‰g©ãd&¿Î•œ9jgtçµsçFND35.{œÑ¹ä ¦¥MFÓ6\"ptîó…Ó­g\0Í-œ/:Ş|<Ô9¾Óßf’N˜›ñ>hÜë	ã“N&ÓÍ[™ë=†w<Şé­³ÌgwO›C=îkÒy§SR&“Mèœy=&zôÜ9 SÑ@Ïš‹=^lDİéÇÓ€§?Nh›?>†qÒyá3b&’Í¢9h,åyêsÀÏ’!9~viÃ™ËsCÀÏdù9–uÜ9ÃsÎfÛÌù›c=väøÙÓƒ§ÂO=œq6ºsìıy²Sá&×Î÷;^uæ©ñ³&áÏäš£9úlLõ™©'øM\$ŸÉ6fp4ê¹±Ó'|Î³š³6ÖiÓ‰Á3L¦pÏæ›W3vlÜÉßs?gäÎ‚9ªgãé§¢æ™M}œó:M½œóiÅóÍ¦ÄÏ°š_4n~ìû©øe§_Îìé4şoôú9ºÓ¶'óÎŠœ‡7Šl|Ø²³s¦ÆÎ³ù4Zjtí™¢óˆ&úÎƒÕ9ÎkÚ	ú\0003è	MC›K<6iÜÙÙşóéf‡ÍÏ “?Ên´İéùÓB§nLù ?ªp,ìYôÓË(PI›U7Zidü™ØsÍ§mNÜ›Ñ=ÚmtÏ‰¯³ªè5Läšs3¢‚ìã™³Ô¦§Oîœ7=Ânôëùù³ÙË=NíŸÓ6ºjäÙ¹ ³ÌçùÏœ‹:T³Ôôé¸DÃ§JÌçŸ]7i<â	Ç³ˆ&úN~<¡?\nƒö¹£Óñg	No›?’iäôS^gâNwŸ›=gìéYÏ³¥fšO<›‰>hüä©°ó£ç~P Ù@Öw¤ÒiáóÙ&|MáŸç3¢y„ß‰²T&¹M\$œ—@BvTòéÕ“w&“NÓ›ëB\"x4é‰ĞSgæÁP7œß5®oå\r3:'ÛÍÚ A†ƒ\\úIµ3CçİÍ™ÓBâ„Ìã	¨Ô5'PA\0Ç>j}mº3M¦‚ĞÚ™½4‚¼Úé¿\0¦§Îü™Ñ4®{¬ÿy»Óíh\$MH›¡5ú90é¿fÑÍÙšÕ;úh,îZSËh}M+œµ6fi\r™îT=&“Ğ WCÍ<ã{9¹T'gğ\$›ÈL:\$ßIÛ“§f–N¢Bz†9!0SµèMœ#;~x´Ö‰s™hQ\nÏC.sU¹ K(3Lí›ª7jnôÒÉï@¦¨¤M8‚püğéıTèmÑK›]4”	\$óú41h7Ïg¡16Ruğyª3±fvÎ^ _<*5 \$´§4N«œ‡9jgÅ©¸S §´Í’œeCân\\ÔÚ\"óc¨ºĞóš=9ÂsõÊSç›P¡a3¶i\$ĞùÓ³F¨>Ğ¿£D–üÚ¹ÃtæƒP!œE@&iå	ÑT`f„Ñ…›5–ŠÍ**“™çHNËš?=2s×¹Ö3¼è™O¡š‡>Jn¼Ği½ô§M5>rj4ïºzèÏx £3‚‚M©Ìói'ĞMbšÙ:h=\0I¤44¨ÇÍ/œ•;d	,úŠ+“³æøN¡¯Fú„Üáê%t-¨¹Ğ¾ Ó5†|=ùÎTe(„Noœç3ª\né=6³2ã!¬h¿Ø…şŒ³¨Ü¿}¢	möÚ!öî3Êƒ²Ï¸+6Ö8,¯ÊW5Ug,Á+æv˜XĞ·\n¤GşSa2Œ~šš1´¤\0¢45ÚC—ÓB„ÓÍ·#CfÔ†ArËG`µ+\"ëA&½L²ù±hƒí!–eJ”‰p\0:¤_+Ğİ£\$:FpÖ×øZT\ndl7{ğİ@!fİH„ô¢Ö0ÙCdTCHøÅ\$\0â`X9ø\$I	)ƒ“F–Ô–™zÒ\r)¡Ie¤›€\n`a(Ë…ÊÔm«{ºLûi33˜j:©Q£[9Æ2²Š°r¤èÏ`\nE#x94Œ©;0#j•IØ‘ñ²˜qùÛëËênÛ/Õ²õ(p#Âƒ©D¸\n¶ğ‡Ğ~ğª1¶UN\\ÙemI‚0ª‰F0ci?\n­–Ğ™\$ ­ŠvÒr¥#Gõˆ3k¶¹šş4W8ÄD?]*¸S”«A­1¥j[‚½!öC­e˜«¡jHúZ©of|@!x‡î…à’9›êI*XŒ;ayRËé	eŒŒ7v^`Ö¡z‡÷“*•\"bH`0­éj„‰ÜØPØ—phÁ­0 g°ÑÎ•ÓoØC2Ï©r€O¥ÚJJ—+\$6xll[vZ–€\rhŠÍ=€%gˆHÂ”†s ™ÎÒ¤—K\$ÓÆ‘h­<‹\0~¥\"˜-/‡ ØŒ<bK-˜Ğ¶Jíi38\0W-ä,0F·”ˆu€U\0„ØL›\\:CTÇYÕ1ˆeæ”§Ø&~Ìÿ\06Ì`Æ ›cÁôEtÂ3±f1’—K÷lÍ›®¥lĞÏôû·Ny+^b(®ŠÓVZK´…©/³’¦¡Iª“*RÚLôÕJ¼S*b(Ğ8¬İ/VrDs‰]±cnÛdL›0¬¥XSkhtRÒš5¶TÛŠˆÓp`jÇÊ7MÔÜÀà–ÁHh?vxtàX °c•˜H’36*WtÚÙºAà\0VÊ†’;£ÀïâÙ·Ó?`Ã*ñ›ò®ŒğÚ×Ñûg\"ÆHAäbPs›Î6<‚x]óDÚU\0ñJ¯{1Ì?p5¢RM\0¦ù^<¬p0²å)\r=3ÊD\n+îå]Z)ÂÌu¥zŸêxÒ®šr3Ãf€Œã>’“¤!¬…—9OXC4¦},©í³ëdÖİœT)–2-‡ŠwŠS,Å©³\\yYÌ¡XÂ]¥M	Ì‘BÙM½ai0&•½\n*–€€`	¤ˆi¾Sğ¤ÇMdL¥7*}m½ƒó0BƒxÓ¸©İ?ØKôi‘Óï„ÄHù•Õ@6Z¬	€IaMOŞÛ-XT¤™x€P§²\ràò¦%Mj	B\r¤›PbeBf?M)8°ëWD5\"\n„²ÇWö•bLª B†?KùRÚ¨\$ÄJ’ÅC¦VŒö)‘Sógy+\nŸÃÒGÌfÂÓB¨‘Pe‹µ<Ö[B\0©ÔB„(ÓŒ”K=†9ª(ÔYfİEÆ´âê'°aÂØ\n”Z‹u™¤›gµQ²­CšŒ´…©6bo¢ “o6àµáTN„çQêŸ¤\"ÆĞ–¼µ‚©Oá„İ(:øØÃ,¤\$Ã)•‰nÖ•\"Êb)mˆÇé…y*‚PŠjMËo©7\n\" ”\"ÆOuØø±·`U£J§¬m*;¨\\Öî¢5GèK#êCÔ¦]O%ŒŒ':ô›)­S{©Bd¥›=ÆÛœT¤¥fÂş ”#ê–°ËZTl•áQ²œ„/æD”»*6)—aÖÉê;	òKµÙBZ„Jš¦é2™TÉ!jÂÓ†e\n½Z0š•9é¤ÖFÚ¥èºBìˆ)\"Ó…chÓµ©•NÆ[U)ùÒ…¢ÑŞ ‹jŸğoª\nÔaõT ÚI²8Uª\0²¨D&¡%P(Uõ\nª}B»iR¡hZ…õj22 ¨UPÔ>ıAº]U<áhÔ<iKTU—An\nˆ5AáWÔJ¨ˆGş¨åMÈZ5Kª&BjªQQt”UE6fé&ê›ÔXªrÅˆ%SÖõƒúÓ³¨ÃTJT,:¦P«ê5Õ,¤Áj£}TêoU<©®2 Vx­e}Lª¥ )uUJ¨œÈ‚ªã9:UVÚÀ²©ı\n¾¤MVzAU#	T¥¦òÛÙ…EI:ªPµjJ‰’© Â%RŠ“•RöRªùV¥Wø[,ƒª”2(©YR¢“­X*°ÕF*WU©`ÎZª]!‡ô„êÂ³)£íV—ÅKŠ°µ;é6ÕT¦øÈ¦&ÊL•J*bÊ©•ÏJ’Uª°U3j¹€+©ŸV¶¦‹Rªšt€*ÖÔÖ…Ë’¦ÉXÚˆğéøÔã§éUí~û/j4şØ%Ã\$cCSº B\n—Ìªá2¦ƒãPÊ®cµ™Ôù«¢ÆÑ©ù&ª¬á°e¦\0Öâ®5]Š†õ*…Ô¨CPWE]zD²ÇjğÕë„İV1Ã[ª†jİÂª>Y¦\r^:¤t×!Ô@f÷W¶¯Ã\n¾0{k\0Btª}K¢¥SÊ½Uê¡Ô^ª+R°]`jÁµOÙ{Ô`mÑXFªõ!¨@õSaÕP¬5b£\\º¸,hj«Ôv`\rQâ°VÜ•†kÖ*©®Æ¢®ä!ŠµY«Õi¬U²¤uY:®í¹j¸•]¬oUÖ¤»\n„A÷\nY´„QY±İIš?0†*àB(©=XF¥`Z±l©jTÖP©WWÂ¥}_:–5‡Ù{T´¬¥Xê¥ Êj”ß!Ô»©áT6¬õ`Z˜°ªÓÖ«Y­| z¶,–ªÙÓ´„VÚ¬ª·56Ø?© n \r8ÓD&ìgêqC\0i.Î’´!JĞÁöØ¿/ò#f}“ëğşÀ@\$Ö’ª£WŸã¨YÔÀ—ùSÿbàÎşİ\\†.¦ŠWVd%Xh¢IV3à€#´ÖÄ1®­%`	lvÊxÒL„—	 ¥s&›ËşkV°Á7€©…öOOêˆRU%[›Ma•¬«ZRUc´Å\\ü%ªNÁöZb°féH‚µ!Hš¤x€/‘µ*ºH“³SŠÒ4œå…ÌdZ–·o\0\nm¬å]QûhæU›mšÓŒc¤©s[ÖZŒ­ÚŞ”°	E3¿\0œGö”8*NÕ½ÙáÑñ¦Õ[Ò“\\öÀ/° \0œ×FÑI†Ö\r¬šÌ¶h5[†±‡2Æë‰´«Y,R•>–cMeµ0g€%Z+SŠã>¥Öò«?\\¶·=m¢Q5É›±b­ÀÄ¦cDzæŒàS®qğs¶ŞuÉ*Ï€B­@ĞFº5n6@š!Ò›ªBÀ©ej*ª¤vjñ•,«³]:º¨šuÕ*\r•,bá+úº•Yúë•Ô+­Ğ•ı]]ˆ5v2>p¨£T#…P”Œ%¸duÚI1d‡NE ‘VrğŒ©[×O®×]†»íušíİªW]®ËMò¯Íxeh¬^ë·4gÄÚÄ”mÖ UãÙv´—¨êÅ‚¼¥yLÔşêôÃv¯ ÓÊ±›ÚVµçÚ›1Miß^¼­2æ\"5€˜ñR¼§hÇ®º=-¶iU¨+™×²#kZBº\r{hKŒ‰kØ–f”ÆÆ½Ã=ú÷Llk—RÔf•_–Û4&ÍÃıö¯.ÁŒ<êy´·kéTY©‹Tá\$m`õòOV©i_r—3]†iMzƒğ€M`«NaÃ]VXÕir‘¶¯Î­`©ÕDÚáôË˜Å…¥ÌÎî˜Åb6Å©«ö²ùSÆS2—Œ³ê®LlJƒW½mòĞmûDJ^ÂZvÜ\0›[¬©e|mÀŠ–VŒ¥ìÑş¸½¶b«¥Uh?,Q Ë9Ú»õ¿ƒïØ\"h> €å0V5§ëî1È°ZÄ¿SD¢å«Î4°fK—›2›4ÄÚ›1ò„R¶Á«9Ğ\nuÿ)‰×ùgˆN¼Ø}Á2”Æ_JAx®Vl‚½íiúéMH™ Ò=†½X(­ºIğp¶Ù)×Ü\$DÏ5 p}¡2u0éš€AbLÖª‹IŠÚ*Ö×÷µÛ\0¯	¦¢;bZæà\n\n‰«@´­¹Z¶8QÕAIYÑû¥OÚÃ£Öjä‰.Úh6ÁÙ·K¶lE°9^ä«Åˆ¶¦Ì¬	X\$»_rÁÅrêö´×X‰JUÙƒVYo4Ó×øæ\0¯N}«*™zkì\0š\nµ8\0‰]&ÄƒJaöknÔn¤4Ûi­}‰mÀ“MU6òb•U·•€â¢š˜È¤¢m²Ê¼éÒD%D)ì•]«\r](€pŒÙàî¶Ù±¥^% 5¢DÄŠ+¥Â–ÛÆ›éIÒ‘»X”Qú±Ê:œ‰(Ğ•ôé·0ÊjÍFUƒ\0*ôTÛ™B³Q§åHšŒ&špÒâ”3ğ£÷bš´ƒv@¬SWh4Ábµí7ÖXö*QW½²](?³/Êö”Ài¾U†¯¸ ¿“k2‘æ«ùYkAdm‰Ë2–)âŒÁ€As.l™‰Í’C†Æğ§ÕQ±?cŒ”)–)íw˜ÎThId]%-9ÂDÔ§X§²ø­×aŒ‘UıÖOI\0V§h×ªÉèR¬ìSé“R+¬|ÄåŠ½”Â‘C™Ø•²¡eL¨-9ë*¶,H+G*vİ«YV',\\KS46bŸe¦cCk,4ç)2V£®c[Î“ºÿáöì³Ycc£_š­ejzvTXX°Qi¼×²–›5z‹,	SÍh\\¬”•–V„6ZéÙB;­ Ğèªşöˆ52«ŞÙ”®•S*¾ã	“ ìÊpÓË…OÉ\0‹*µÊìª×Jc9NĞ³ƒ\0I¤Èê±:Ée=5@lÖd\0Éd–É8¸ 0® ,İEŠv·Ú=ŠkMˆ«cËÿŒo2…î9;+Ñ\n‚—&­ªNäË(³-•Û\"ğ(\nÃACr—€c@Êª¬\0\\MH\$ø 3€g¬ğˆ|y]<¢„aAŸ+ÌÍFmÅ1Í±š«­ µ\0c\0È\0¼Üù¬@\rŠ\n‡”B\0¸	  à§CP\0a(™hâÉçò(µ‰|´²|-Õ É¡6ƒ ÒHh\rËhH­¡@>–…ƒ…‚\0mhjBU¡ÙßVˆ-Z\0qh’ĞU¡°@mZ´.	%¢×tí@úyhVÑM¡{@ÇƒÂkZ8´]hÒĞm£ÛCvm Z´…h„†5F@†í\$Í’´—h.Ğ0{Iö„mZ'´h–Ğ5¢ËJö‘-\$Ú1´·hşÒí£d‰Öí0PšWiNÑÔ×Ôd–—í¡ÊKiŠĞ}¦Ù¬S—í4;œ‡5öÓ¨ûNö-Úd´ÛiŞÓ]§ÛIÀZy´ïhfÓe¤ËNö‡íDÚ´ïiJÔm¡Nö•­AZ%´ïijÔ­¥Éø€-<Î´áhúÓlà»O–˜í\$Nµj’Ñ,à»P¶¤mÎµjfÓà»Q–¬-PÚ#µXîp]©KU—'Ú˜µwjÆkí©ËVàË=(Âµ;=\nÓÌğ[SöŸ­&OµGiÆÖm¤V–´m#ZÓ´<Õ}«ûM³ÁmYÚÚµ©jBÖµ¡à¶®­hZ×Kjú×-®+X6²f¾ÚË´‘4šÓ=¬;^©ítÚµáj®×\r¯iµ6»&¿Zy™ÅiâÖ¯ûYö¨-&ZÿµélĞ]¯û_·­Ïíµİh–m°;Y–Ãmi[´#?¶Ø]¯PÇsûmlÛ\0¿?¶Öí±éı¶¸-‹ZŸÛk’ØE±{\\öfáÚ^µóln×m²ËY°í–Û¶'lªt=³)Õ–Î­Û)¶M5öÙ%²)¸öÎ­t[>¶`yBÙ•Ëi6Ä­\$Må¶mm6~E´›c¶Ñ­Î_¶{lŞÙüÒËi6Ê-¬;Ÿl>Òäø;i–‰gÁÛO¶È–ØÍµIğvÔí®€_Ÿm^ÒDø;h6Ú€’Û\\¶ãl®ÖÂëeÖÉm¯[1¶ólÎÙ}¶ëMVm½[i¶ÛmêÛe·{_¶ßæpÛ„¶‡n2Ú-¸yËö°­ÀÏ=¶Ái€,°p6æm±Ú\\·3mšÜğ[Z¶ß-	M´ónfÛ…¢[s6Ü×-KlIi\r±UÉöë­ÑÛ³Z/i*Ò»IÖîmÚ[¬·umrİÕ¹v6ŞíÛ-L¶ıiöŞ»uÓñm\r[Ã·\roÛe¼;jöğíß[Ë·o.Şºëiï-ÛÛJ·¹ohå¼Û{VÍ­ØÎT·ÅovŞ½«zvö­êÛİ·¯ovÕğ0\r3JçAMY×o–ß¿9ªS_¨,®c·o5Šß=¼iÎw\0M¥Q¸lÂw]Àr“î®»šÇp4œú+‚q.	ÍF¸+ofİ¼ä;‚s’®\rÛÊ¸3o’İtàëƒvúnÛë¸3o¶Ş4Ğ«ƒw-ãz¸'3Òá}Âvó:.Ü\$¸[pšámÂ‹€³c®Ü,¸4šàœáûˆ7­ãMa¸ƒo>İtÜ«ˆ7.Ü:¸3æâ\rÃë8®	Í-¸«qà,Ó‹Š·-ÛÍ™¸«q2âÄës?n*ÜR¸7àœÎ7.Lí¸ÓqrŞ4Î{7.3Üb¸4~ãMÆkÇ®	ÍN¸ûq²à\\Ô«·.Íè¸ûqÒãİÇk‚3`®>Üz™Ê–à…£’6-ï›JIpÉj`K†Ì(“\\“¹r>äPK}÷&gƒ\\‘·‰oúäÍ¬k’·'-xÛÈ[<£\näÅÉkg7*Ê:(Â¸mrÊäúŒ+”37îS\\šWnRÓÒ¥v²îa[á¹hîi]Ë“–ù®cÌå›rFg­Í”3T®h\\¾¸s6~-ÌK•íìîHÍ¹»rnå¬Ô;›·(n\\Ø&rFàÕÍ‹ƒ—:®@\\È™Ós¢çÎë–÷''\\è¹CpªçUËë…×6&ü\\‘¸asòæUË[†—?.yİ¹s9pKà0> ƒè~Pè¹…t -Ğ§i`®†Íqºl’è•ĞÉø³<.‡Ü£¹-7ÖäÄ›®‘Üí™ËqéÏ¤÷AndM]ºCr†â…Ò;—×.l\\Vºktšg\rĞ™OîHÜ^ºkræi%ÓÛ£×''A\\‘›½uéÔÏ+¥S7î–Lò¹s4\näÇË›®©İ8¹uNê]Èkªw.nF]S¹hàå¤Ç”.¤PºTyBêm	{¬3n.µİC(èyBêıÊ{u«Eè~\\Úºér¶æ\nÙê—YnYÜ®ºïu¦Ü]×å©”?.EPüºÜî‡åÕû{w`¥Mºívæ5ØÃfG.²ÜÌ»%u¾æ}ÌÙ£—'è§]a¹©vjhÅ ê=2¡Ò=YÊ•+bY”\$úÄlm*ŠVh\$I–©µÚâ¢\0ØØÖ¬YXF°ä!†9j®ÕÓ0*aR=|Û	Aö@\$°MdÉYÆ±LÆ3¤¬îã2Ü`Æ²°,3JÈĞ†„0–¬Se°]bÚ¬¶<îŞÕc×XşÄıdXµ£nîÓõ¬bËÚœ<¡\0Òİ	Ö_…»wœ…`JÊ\\XVj­õ*ZÚÍPë5V(»ËY¾Åg	M+I%¨aRÚ¬ËKZyLïk*´gÃ[Òpx7Ubjc2¸„GÎ£cGzı—†Yc3k»¿Q²ímáZ¾ìüìtT%hBÔ.¥•?†‚Aøí6¿¼LNí£\"ZŒUi\rBy²axÙ…MÛÈN`®ßÔ%gI µG¹U®æ0»œÃæÜ+¹wqo#0÷e»wB£eİ*‚WukÉŞ„p.îÍFËÊõ‰›r‘õ¨Oà…áûÊ×vÎ…ŞZlSOj¤¥7X8µ^\nFT±	ÎïK‹½—„ªY²Ò¨Ùaî»åç{Ì7š/\nÒc©ƒV¦%o¨R÷~XeTÉ»ùSïô)õtLçÈûT®?+\r–í)¹T-[]±{\$GPZ¤uà’MW‚êÖİ©¼1zv¿]>j°WˆjT4mÏx–õâ{¶5ko’]¼]z¦ñ…ÛZ°WmêÖÕM•Kxæïó\"Ç…0j”^?—#T¢òUäKÉ—qïHÕ‚½ƒwîmäë¹ìş¬,AÀ†\\Åz©EåB¨•VîìW˜«[^†ó-í{Ìö Sl¼×n¯ıX*¸·iÆÁÙ¼äÏ\n¬,-ÈQ÷¡jÅU¡©…V’·Ôªr˜–LªÁBå¯;]ZH]£*ÏÂÑ«Y±‚=zâ”t‡e¸ÒÔ†R\"œuâGÂo„^ægÁ—pz`öiÄ_µLÚÃ»M*±ğÚÂ^Ök/Nu8[°Ã‰S•—yµ‰Í9ºsT¹á1µøejÎ]\\=tª2S¸°OÊJÄË‰+“.\rÚtò¨ÁÈÌHñ‡ÖoæÃÈÎå(Kø?&ä D“.%u¾š’Ö_³ˆëd§ï¯G‡ğP&’dÎ×§ËpìÕC“z¦Í%€j`xÌ ´Z‡%ŞÎ°/³Œ=¾Öıœ›1D·7×”ßonÎ¡É¸|¼4òî‰Àã_xsô\r\"ûÂÊHn±SïÁª½¾ğlğíøxôWŞŒ¤®JÂÜ9Ä\"‚ØÑQCâ!·‰ØŞ~'£…³»WàâzÒ…†s	C‘˜´¬@¤7döı:S‹ğ	\\\nã_D[\$yáÜµşÂè¥bRH MJXÄ``€1€]F´áégùÏÉ j=I}†ë\\1âP0¥¦C=ß¾Û0QÜOn·ôˆ+¹3&T‚öùwöUáÕ¥¯:Lªè6üw[‰ÃuvãNÿ#’âd\r£Ö†=†¾Úİ5ÿ€w†1s€š<1P×}\n-ÿcx°““§—JéOà—´ñŞ+D”¦x½ñı°R¿”Ô3½\0!—ö°8¶k#ãº[@UHß S<Ú@(|7z/è7crª©Ö²wŠàª±o”åVúc£æÕø¯ 	Ó\\†Ÿ‘Û€”¾Ü&2;p\"÷Ÿğ\$§îÜ:Û&áó‹\\Ü^=C¥üë»bËhîÛÈßô¿+AÇÆ'lÂŸŸ`T—Ñ€\0Pné\0ÿ`cm1%€˜‘nÙ€º*|ÁÔ_»¶lx€6RŒîÍn85¨Œxœ_»ø¿b=ƒêğ#’5é×„¹Àû†civ|Ï0=<w6Ú8%ÎæÓšÖ‡>•¾¸G&¶ÓK¸[V„‘ ¸646¬kƒ@Ö§¹r3‚{âóQ…WÜ`™Ó‚~@ê‚Á3À:\n¿E‚Ì6q|‰¯Œà:ĞÂÁ“•\0aE\r€wTİ4ÖTô20b`*0`2~Ã\\›ÈßGÁ›ƒP<–‘eàŸHÄ`Ú‰³Lâ|l‘=Šœ“¥0‰¢£&ˆ›ñ5âoE‰¶©Pò±ô6ƒ½àÉo	ƒÙÿ\0L\0óÛoƒà\"êvŒø10`ÄÀL6(F„Èn£WƒÊ=9¼æ\$µ‘ÒX7œTŞq¼ (§Œø0Æ.;\\˜¬I<Q<’d~r¡„uºÖ(n²d‚†o8ú(dÌ%ë’Æ¿àÂ\\6+	ƒ¸ !êJâG\n4„ÎĞ¼\0€£XN° ;Â„	IL9i¬( ”ğ•HÛ¼ÎLƒs©\0GÍ0œ9:Â^äéÈ\rùXœ£bvŠÁ»…xkyÅ/ÆbF’¿»	È8Ya±XXÄ%eöSc»ùdÂbaqÆäG°`Ëí¸ZTÁNÂì\n¥}¬X\\p…ßN§`fü|D'‚0¢ºíläê°°şØØqÉ›‡Ãu‡ñIR„@ë÷øfaauÃ\0Ü?Œ3aƒ1;›ˆ	ÿ\r!>Ü3jNn£êÃƒëkÌWËC°ÔDpÍ~c\rN €°ÆàoÄ\"ÒÆ¹2]€ñIv;%Ø*V€NÿÌ¿ZÂğ²î\r`Ş/5Á\$ßùÃ ŞoÎ\\9Ç9A\n`·À¨Õù{ò±:Ë€v‘@†µ\\:Xë`Ö°¾¬FÃØÏ²K,=âJ	¸ÀÖ\0 LŒ¸~TØÃuR SØR¹xT¥EO/ˆúV€(¥Ô0ù“f!‡‡Î\n0—)6oDWÅ‰{)ˆP²¶8çË‡déÆ8V4t£ „«9a|¦XYÑP’P:¼´L²‡±ÁÄEgºÈ\"¶ç@ŠÂL?û]h¨„Òg=lW€/òL£Ò0\$h@Üˆ£W‚ì°¼‹×‡À¢Â\0s;zFğô Cä“ÁM\0SLm‰I%–%B•‹@2âI‰å‰8İHç˜'oQÏÈâL\nhÓêiĞ#8——@âZƒUG©~Ş%ÌDR|”ÇÇ¿!‚¨)P*ËC¡ãA‹@\rj]‰Ówë›â+Ìn*œ@›ÃkIaİ(apÜ‰¸Ãè/ÈU‹Æ8");}elseif($_GET["file"]=="logo.png"){header("Content-Type: image/png");echo"‰PNG\r\n\n\0\0\0\rIHDR\0\0\09\0\0\09\0\0\0~6¶\0\0\0000PLTE\0\0\0ƒ—­+NvYt“s‰£®¾´¾ÌÈÒÚü‘üsuüIJ÷ÓÔü/.üü¯±úüúC¥×\0\0\0tRNS\0@æØf\0\0\0	pHYs\0\0\0\0\0šœ\0\0´IDAT8Õ”ÍNÂ@ÇûEáìlÏ¶õ¤p6ˆG.\$=£¥Ç>á	w5r}‚z7²>€‘På#\$Œ³K¡j«7üİ¶¿ÌÎÌ?4m•„ˆÑ÷t&î~À3!0“0Šš^„½Af0Ş\"å½í,Êğ* ç4¼Œâo¥Eè³è×X(*YÓó¼¸	6	ïPcOW¢ÉÎÜŠm’¬rƒ0Ã~/ áL¨\rXj#ÖmÊÁújÀC€]G¦mæ\0¶}ŞË¬ß‘u¼A9ÀX£\nÔØ8¼V±YÄ+ÇD#¨iqŞnKQ8Jà1Q6²æY0§`•ŸP³bQ\\h”~>ó:pSÉ€£¦¼¢ØóGEõQ=îIÏ{’*Ÿ3ë2£7÷\neÊLèBŠ~Ğ/R(\$°)Êç‹ —ÁHQn€i•6J¶	<×-.–wÇÉªjêVm«êüm¿?SŞH ›vÃÌûñÆ©§İ\0àÖ^Õq«¶)ª—Û]÷‹U¹92Ñ,;ÿÇî'pøµ£!XËƒäÚÜÿLñD.»tÃ¦—ı/wÃÓäìR÷	w­dÓÖr2ïÆ¤ª4[=½E5÷S+ñ—c\0\0\0\0IEND®B`‚";}exit;}if($_GET["script"]=="version"){$o=get_temp_dir()."/adminer.version";@unlink($o);$q=file_open_lock($o);if($q)file_write_unlock($q,serialize(array("signature"=>$_POST["signature"],"version"=>$_POST["version"])));exit;}if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";if($_SERVER["HTTP_X_FORWARDED_PREFIX"])$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];define('Adminer\HTTPS',($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure"));@ini_set("session.use_trans_sid",'0');if(!defined("SID")){session_cache_limiter("");session_name("adminer_sid");session_set_cookie_params(0,preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),"",HTTPS,true);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$Wc);if(function_exists("get_magic_quotes_runtime")&&get_magic_quotes_runtime())set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("precision",'15');function
lang($u,$sf=null){$ua=func_get_args();$ua[0]=$u;return
call_user_func_array('Adminer\lang_format',$ua);}function
lang_format($Gi,$sf=null){if(is_array($Gi)){$tg=($sf==1?0:1);$Gi=$Gi[$tg];}$Gi=str_replace("'",'â€™',$Gi);$ua=func_get_args();array_shift($ua);$id=str_replace("%d","%s",$Gi);if($id!=$Gi)$ua[0]=format_number($sf);return
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
messageQuery($H,$ti,$Pc=false){restart_session();$Fd=&get_session("queries");if(!idx($Fd,$_GET["db"]))$Fd[$_GET["db"]]=array();if(strlen($H)>1e6)$H=preg_replace('~[\x80-\xFF]+$~','',substr($H,0,1e6))."\nâ€¦";$Fd[$_GET["db"]][]=array($H,time(),$ti);$Nh="sql-".count($Fd[$_GET["db"]]);$J="<a href='#$Nh' class='toggle'>".'SQL command'."</a>\n";if(!$Pc&&($sj=driver()->warnings())){$t="warnings-".count($Fd[$_GET["db"]]);$J="<a href='#$t' class='toggle'>".'Warnings'."</a>, $J<div id='$t' class='hidden'>\n$sj</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $J<div id='$Nh' class='hidden'><pre><code class='jush-".JUSH."'>".shorten_utf8($H,1000)."</code></pre>".($ti?" <span class='time'>($ti)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($Fd[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
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
const thousandsSeparator = '".js_escape(',')."';"),"<div id='help' class='jush-".JUSH." jsonly hidden'></div>\n",script("mixin(qs('#help'), {onmouseover: () => { helpOpen = 1; }, onmouseout: helpMouseout});"),"<div id='content'>\n","<span id='menuopen' class='jsonly'>".icon("move","","menu","")."</span>".script("qs('#menuopen').onclick = event => { qs('#foot').classList.toggle('foot'); event.stopPropagation(); }");if($Ma!==null){$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?:".").'">'.get_driver(DRIVER).'</a> Â» ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$N=adminer()->serverName(SERVER);$N=($N!=""?$N:'Server');if($Ma===false)echo"$N\n";else{echo"<a href='".h($_)."' accesskey='1' title='Alt+Shift+1'>$N</a> Â» ";if($_GET["ns"]!=""||(DB!=""&&is_array($Ma)))echo'<a href="'.h($_."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> Â» ';if(is_array($Ma)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> Â» ';foreach($Ma
as$x=>$X){$Ub=(is_array($X)?$X[1]:h($X));if($Ub!="")echo"<a href='".h(ME."$x=").urlencode(is_array($X)?$X[0]:$X)."'>$Ub</a> Â» ";}}echo"$wi\n";}}echo"<h2>$yi</h2>\n","<div id='ajaxstatus' class='jsonly hidden'></div>\n";restart_session();page_messages($l);$i=&get_session("dbs");if(DB!=""&&$i&&!in_array(DB,$i,true))$i=null;stop_session();define('Adminer\PAGE_HEADER',1);}function
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
input_hidden("fields[$s][orig]",$Qf);edit_type("fields[$s]",$m,$hb,$hd);if($U=="TABLE")echo"<td>".checkbox("fields[$s][null]",1,$m["null"],"","","block","label-null"),"<td><label class='block'><input type='radio' name='auto_increment_col' value='$s'".($m["auto_increment"]?" checked":"")." aria-labelledby='label-ai'></label>","<td$Pb>".(driver()->generated?html_select("fields[$s][generated]",array_merge(array("","DEFAULT"),driver()->generated),$m["generated"])." ":checkbox("fields[$s][generated]",1,$m["generated"],"","","","label-default")),"<input name='fields[$s][default]' value='".h($m["default"])."' aria-labelledby='label-default'>",(support("comment")?"<td$nb><input name='fields[$s][comment]' value='".h($m["comment"])."' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'>":"");echo"<td>",(support("move_col")?icon("plus","add[$s]","+",'Add next')." ".icon("up","up[$s]","â†‘",'Move up')." ".icon("down","down[$s]","â†“",'Move down')." ":""),($Qf==""||support("drop_col")?icon("cross","drop_col[$s]","x",'Remove'):"");}}function
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
as$x=>$X){$qh[]=idf_escape($x);if($td)$td[]=idf_escape($x);}}$I=driver()->select($a,$qh,$Z,$td,$Lf,$z,$E,true);if(!$I)echo"<p class='error'>".error()."\n";else{if(JUSH=="mssql"&&$E)$I->seek($z*$E);$sc=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$L=array();while($K=$I->fetch_assoc()){if($E&&JUSH=="oracle")unset($K["RNUM"]);$L[]=$K;}if($_GET["page"]!="last"&&$z&&$sd&&$je&&JUSH=="sql")$kd=get_val(" SELECT FOUND_ROWS()");if(!$L)echo"<p class='message'>".'No rows.'."\n";else{$Ea=adminer()->backwardKeys($a,$di);echo"<div class='scrollable'>","<table id='table' class='nowrap checkable odds'>",script("mixin(qs('#table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true), onkeydown: editingKeydown});"),"<thead><tr>".(!$sd&&$M?"":"<td><input type='checkbox' id='all-page' class='jsonly'>".script("qs('#all-page').onclick = partial(formCheck, /check/);","")." <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".'Modify'."</a>");$jf=array();$nd=array();reset($M);$Mg=1;foreach($L[0]as$x=>$X){if(!isset($Xi[$x])){$X=idx($_GET["columns"],key($M))?:array();$m=$n[$M?($X?$X["col"]:current($M)):$x];$C=($m?adminer()->fieldName($m,$Mg):($X["fun"]?"*":h($x)));if($C!=""){$Mg++;$jf[$x]=$C;$d=idf_escape($x);$Jd=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($x);$Ub="&desc%5B0%5D=1";echo"<th id='th[".h(bracket_escape($x))."]'>".script("mixin(qsl('th'), {onmouseover: partial(columnMouse), onmouseout: partial(columnMouse, ' hidden')});","");$md=apply_sql_function($X["fun"],$C);$Hh=isset($m["privileges"]["order"])||$md;echo($Hh?'<a href="'.h($Jd.($Lf[0]==$d||$Lf[0]==$x||(!$Lf&&$je&&$sd[0]==$d)?$Ub:'')).'">'."$md</a>":$md),"<span class='column hidden'>";if($Hh)echo"<a href='".h($Jd.$Ub)."' title='".'descending'."' class='text'> â†“</a>";if(!$X["fun"]&&isset($m["privileges"]["where"]))echo'<a href="#fieldset-search" title="'.'Search'.'" class="text jsonly"> =</a>',script("qsl('a').onclick = partial(selectSearch, '".js_escape($x)."');");echo"</span>";}$nd[$x]=$X["fun"];next($M);}}$Ce=array();if($_GET["modify"]){foreach($L
as$K){foreach($K
as$x=>$X)$Ce[$x]=max($Ce[$x],min(40,strlen(utf8_decode($X))));}}echo($Ea?"<th>".'Relations':"")."</thead>\n";if(is_ajax())ob_end_clean();foreach(adminer()->rowDescriptions($L,$hd)as$hf=>$K){$Ui=unique_array($L[$hf],$w);if(!$Ui){$Ui=array();foreach($L[$hf]as$x=>$X){if(!preg_match('~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~',$x))$Ui[$x]=$X;}}$Vi="";foreach($Ui
as$x=>$X){$m=(array)$n[$x];if((JUSH=="sql"||JUSH=="pgsql")&&preg_match('~char|text|enum|set~',$m["type"])&&strlen($X)>64){$x=(strpos($x,'(')?$x:idf_escape($x));$x="MD5(".(JUSH!='sql'||preg_match("~^utf8~",$m["collation"])?$x:"CONVERT($x USING ".charset(connection()).")").")";$X=md5($X);}$Vi
.="&".($X!==null?urlencode("where[".bracket_escape($x)."]")."=".urlencode($X===false?"f":$X):"null%5B%5D=".urlencode($x));}echo"<tr>".(!$sd&&$M?"":"<td>".checkbox("check[]",substr($Vi,1),in_array(substr($Vi,1),(array)$_POST["check"])).($je||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Vi)."' class='edit'>".'edit'."</a>"));foreach($K
as$x=>$X){if(isset($jf[$x])){$m=(array)$n[$x];$X=driver()->value($X,$m);if($X!=""&&(!isset($sc[$x])||$sc[$x]!=""))$sc[$x]=(is_mail($X)?$jf[$x]:"");$_="";if(preg_match('~blob|bytea|raw|file~',$m["type"])&&$X!="")$_=ME.'download='.urlencode($a).'&field='.urlencode($x).$Vi;if(!$_&&$X!==null){foreach((array)$hd[$x]as$p){if(count($hd[$x])==1||end($p["source"])==$x){$_="";foreach($p["source"]as$s=>$Ih)$_
.=where_link($s,$p["target"][$s],$L[$hf][$Ih]);$_=($p["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\1'.urlencode($p["db"]),ME):ME).'select='.urlencode($p["table"]).$_;if($p["ns"])$_=preg_replace('~([?&]ns=)[^&]+~','\1'.urlencode($p["ns"]),$_);if(count($p["source"])==1)break;}}}if($x=="COUNT(*)"){$_=ME."select=".urlencode($a);$s=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Ui))$_
.=where_link($s++,$W["col"],$W["val"],$W["op"]);}foreach($Ui
as$pe=>$W)$_
.=where_link($s++,$pe,$W);}$Kd=select_value($X,$_,$m,$si);$t=h("val[$Vi][".bracket_escape($x)."]");$xg=idx(idx($_POST["val"],$Vi),bracket_escape($x));$nc=!is_array($K[$x])&&is_utf8($Kd)&&$L[$hf][$x]==$K[$x]&&!$nd[$x]&&!$m["generated"];$qi=preg_match('~text|json|lob~',$m["type"]);echo"<td id='$t'".(preg_match(number_type(),$m["type"])&&($X===null||is_numeric(strip_tags($Kd)))?" class='number'":"");if(($_GET["modify"]&&$nc&&$X!==null)||$xg!==null){$xd=h($xg!==null?$xg:$K[$x]);echo">".($qi?"<textarea name='$t' cols='30' rows='".(substr_count($K[$x],"\n")+1)."'>$xd</textarea>":"<input name='$t' value='$xd' size='$Ce[$x]'>");}else{$Ge=strpos($Kd,"<i>â€¦</i>");echo" data-text='".($Ge?2:($qi?1:0))."'".($nc?"":" data-warning='".h('Use edit link to modify this value.')."'").">$Kd";}}}if($Ea)echo"<td>";adminer()->backwardKeysPrint($Ea,$L[$hf]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){if($L||$E){$Fc=true;if($_GET["page"]!="last"){if(!$z||(count($L)<$z&&($L||!$E)))$kd=($E?$E*$z:0)+count($L);elseif(JUSH!="sql"||!$je){$kd=($je?false:found_rows($S,$Z));if(intval($kd)<max(1e4,2*($E+1)*$z))$kd=first(slow_query(count_rows($a,$Z,$je,$sd)));else$Fc=false;}}$bg=($z&&($kd===false||$kd>$z||$E));if($bg)echo(($kd===false?count($L)+1:$kd-$E*$z)>$z?'<p><a href="'.h(remove_from_uri("page")."&page=".($E+1)).'" class="loadmore">'.'Load more data'.'</a>'.script("qsl('a').onclick = partial(selectLoadMore, $z, '".'Loading'."â€¦');",""):''),"\n";echo"<div class='footer'><div>\n";if($bg){$Oe=($kd===false?$E+(count($L)>=$z?2:1):floor(($kd-1)/$z));echo"<fieldset>";if(JUSH!="simpledb"){echo"<legend><a href='".h(remove_from_uri("page"))."'>".'Page'."</a></legend>",script("qsl('a').onclick = function () { pageClick(this.href, +prompt('".'Page'."', '".($E+1)."')); return false; };"),pagination(0,$E).($E>5?" â€¦":"");for($s=max(1,$E-4);$s<min($Oe,$E+5);$s++)echo
pagination($s,$E);if($Oe>0)echo($E+5<$Oe?" â€¦":""),($Fc&&$kd!==false?pagination($Oe,$E):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$Oe'>".'last'."</a>");}else
echo"<legend>".'Page'."</legend>",pagination(0,$E).($E>1?" â€¦":""),($E?pagination($E,$E):""),($Oe>$E?pagination($E+1,$E).($Oe>$E+1?" â€¦":""):"");echo"</fieldset>\n";}echo"<fieldset>","<legend>".'Whole result'."</legend>";$bc=($Fc?"":"~ ").$kd;$Ef="const checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$bc' : checked); selectCount('selected2', this.checked || !checked ? '$bc' : checked);";echo
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