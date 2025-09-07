<?php
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$format = $_GET['format'] ?? 'csv';

// Fetch latest computed matches from DB or recompute from uploaded_awards and current criteria if you store them.
// For now, expect client to POST computed results here when wanting an export.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$raw = file_get_contents('php://input');
	$data = json_decode($raw, true);
	$rows = $data['rows'] ?? [];
	if (!is_array($rows)) $rows = [];

	if ($format === 'csv') {
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="awardmatch_results.csv"');
		$out = fopen('php://output', 'w');
		fputcsv($out, ['Award Name', 'Top Match 1', 'Score 1', 'Top Match 2', 'Score 2', 'Top Match 3', 'Score 3']);
		foreach ($rows as $r) {
			$tm = $r['top_matches'] ?? [];
			$cats = array_keys($tm);
			$vals = array_values($tm);
			fputcsv($out, [
				$r['name'] ?? '',
				$cats[0] ?? '', $vals[0] ?? '',
				$cats[1] ?? '', $vals[1] ?? '',
				$cats[2] ?? '', $vals[2] ?? ''
			]);
		}
		fclose($out);
		exit;
	}

	if ($format === 'pdf') {
		// Simple PDF using FPDF
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="awardmatch_results.pdf"');
		$pdf = new FPDF();
		$pdf->AddPage();
		$pdf->SetFont('Arial','B',14);
		$pdf->Cell(0,10,'AwardMatch Results',0,1,'C');
		$pdf->Ln(4);
		$pdf->SetFont('Arial','',10);
		foreach ($rows as $r) {
			$pdf->SetFont('Arial','B',11);
			$pdf->Cell(0,8,($r['name'] ?? ''),0,1);
			$pdf->SetFont('Arial','',10);
			$tm = $r['top_matches'] ?? [];
			$idx = 0;
			foreach ($tm as $k=>$v) {
				$pdf->Cell(0,6, sprintf("  %s: %s%%", ucfirst($k), $v),0,1);
				$idx++; if ($idx>=3) break;
			}
			$pdf->Ln(2);
		}
		$pdf->Output('I', 'awardmatch_results.pdf');
		exit;
	}
}

http_response_code(400);
header('Content-Type: application/json');
echo json_encode(['error' => 'bad_request']);


