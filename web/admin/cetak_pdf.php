<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_role('Admin');
$pdo=db();
$f_bulan=trim($_GET['bulan']??'');$f_penyakit=(int)($_GET['penyakit']??0);
if($f_bulan===''){flash('error','Pilih bulan.');redirect('admin/monitoring.php');}
$where=["DATE_FORMAT(h.tgl_deteksi,'%Y-%m')=?"];$params=[$f_bulan];
if($f_penyakit>0){$where[]='h.id_penyakit=?';$params[]=$f_penyakit;}
$ws='WHERE '.implode(' AND ',$where);
$stmt=$pdo->prepare("SELECT h.*,u.nama_lengkap,p.nama_penyakit FROM history_deteksi h JOIN users u ON h.id_user=u.id_user JOIN penyakit p ON h.id_penyakit=p.id_penyakit $ws ORDER BY h.tgl_deteksi DESC");
$stmt->execute($params);$rows=$stmt->fetchAll();
require_once __DIR__.'/../lib/fpdf/fpdf.php';
$pdf=new FPDF('L','mm','A4');$pdf->SetAutoPageBreak(true,15);$pdf->AddPage();
$pdf->SetFont('Arial','B',14);$pdf->Cell(0,8,'Rekap Deteksi Penyakit Daun Jeruk',0,1,'C');
$pdf->SetFont('Arial','',10);$pdf->Cell(0,6,'Periode: '.$f_bulan.' | Cetak: '.date('d/m/Y H:i'),0,1,'C');$pdf->Ln(6);
$pdf->SetFont('Arial','B',9);$pdf->SetFillColor(46,125,50);$pdf->SetTextColor(255);
$w=[10,45,55,50,30,85];$h=['No','Tanggal','Petugas','Penyakit','Akurasi','File'];
for($i=0;$i<6;$i++)$pdf->Cell($w[$i],7,$h[$i],1,0,'C',true);$pdf->Ln();
$pdf->SetTextColor(0);$pdf->SetFont('Arial','',8);
foreach($rows as $n=>$r){$pdf->Cell($w[0],6,$n+1,1,0,'C');$pdf->Cell($w[1],6,date('d/m/Y H:i',strtotime($r['tgl_deteksi'])),1);$pdf->Cell($w[2],6,$r['nama_lengkap'],1);$pdf->Cell($w[3],6,$r['nama_penyakit'],1);$pdf->Cell($w[4],6,number_format($r['akurasi']*100,2).'%',1,0,'C');$pdf->Cell($w[5],6,$r['nama_file'],1);$pdf->Ln();}
$pdf->Ln(4);$pdf->SetFont('Arial','I',8);$pdf->Cell(0,5,'Total: '.count($rows).' data',0,1);
$pdf->Output('D','Laporan_'.$f_bulan.'.pdf');
