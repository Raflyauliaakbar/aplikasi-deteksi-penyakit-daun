<?php
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): void { if(strpos($path,'http')!==0) $path=rtrim(BASE_URL,'/').'/'.ltrim($path,'/'); header('Location: '.$path); exit; }
function url(string $path=''): string { return rtrim(BASE_URL,'/').'/'.ltrim($path,'/'); }
function flash(string $type, string $msg): void { $_SESSION['_flash'][]=['type'=>$type,'msg'=>$msg]; }
function get_flashes(): array { $f=$_SESSION['_flash']??[]; unset($_SESSION['_flash']); return $f; }
function render_flashes(): string { $out=''; foreach(get_flashes() as $f){ $cls=match($f['type']){'success'=>'success','error'=>'danger','warning'=>'warning',default=>'info'}; $out.='<div class="alert alert-'.$cls.' alert-dismissible fade show">'.e($f['msg']).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'; } return $out; }
function csrf_token(): string { if(empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function csrf_verify(): void { $g=$_POST['_csrf']??''; if(!hash_equals(csrf_token(),(string)$g)){http_response_code(419);die('Token CSRF tidak valid.');} }
function format_tgl(?string $d): string { if(!$d)return '-'; $b=['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; $t=strtotime($d); return date('d',$t).' '.$b[(int)date('n',$t)].' '.date('Y, H:i',$t); }
function format_tgl_short(?string $d): string { return $d?date('d/m/Y',strtotime($d)):'-'; }
function format_akurasi(float $v): string { return number_format($v*100,2).'%'; }
function safe_filename(string $o): string { $e=strtolower(pathinfo($o,PATHINFO_EXTENSION)); $e=preg_replace('/[^a-z0-9]/','',$e)?:'jpg'; return date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$e; }
function penyakit_badge(string $n): string { $m=['Healthy'=>'success','Canker'=>'danger','HLB'=>'danger','Greasy Spot'=>'warning text-dark','Melanose'=>'warning text-dark','Sooty Mold'=>'dark']; $c=$m[$n]??'secondary'; return '<span class="badge bg-'.$c.'">'.e($n).'</span>'; }
function nama_bulan(int $n): string { $b=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; return $b[$n]??''; }
