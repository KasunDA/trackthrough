<?php
require_once 'fpdf/fpdf.php';
require_once 'BaseController.php';

class TaskPdf extends FPDF {
	public $title;

	/* Abhilash 6.1.15 */
	function SetTitle($title, $isUTF8=false) {
		// Title of document
		if($isUTF8) {
			$title = $this->_UTF8toUTF16($title);
		}
			
		$this->title = $title;
	}

	function FancyTable($header, $data) {

		$this->SetTextColor(0, 0, 0);
		$this->SetFont('Arial', '', 10);
		$this->Line($this->x, $this->y, $this->x, $this->y + 20);
		$this->Line(190, $this->y, 190, $this->y + 20);
		$this->Image('./resources/images/date.png', 22, $this->y + 2, 5, 5);
		$this->Image('./resources/images/user.png', 68, $this->y + 2, 5, 5);
		$this->Rect(176, $this->y + 2, 12, 6);
		$w = array (
			8,
			38,
			8,
			102,
			14
		);
		for ($i = 0; $i < count($header); $i++)
			$this->Cell($w[$i], 10, $header[$i], 'T', 0, 'L');
		$this->Ln();
		$this->setX(22);
		$this->Cell(array_sum($w) - 4, 0, '', 'T');
	}

	// Page header
	function Header() {
		// Logo

		$this->Image('./resources/images/logo.png', 10, 6);
		$this->SetFont('Arial', '', 9);
		// Move to the right
		$this->Cell(50);
		// Title
		$this->SetTextColor(0, 0, 0);
		/*if ($this->title == "Issues (All)") {
			$this->Cell(30, 5, $this->title, 0, 0);
			$this->SetFillColor(255, 204, 153);
			$this->Cell(5, 5, '', 0, 0, 'C', true);
			$this->Cell(25, 5, $this->count_open, 0, 0);
			$this->SetFillColor(234, 234, 234);
			$this->Cell(5, 5, '', 0, 0, 'C', true);
			$this->Cell(20, 5, $this->count_closed, 0, 0);
			$this->Cell(20, 5, $this->count_total, 0, 0);
		}
		if ($this->title == "Issues(Open)") {
			$this->Cell(30, 5, $this->title, 0, 0);
			$this->SetFillColor(255, 204, 153);
			$this->Cell(5, 5, '', 0, 0, 'C', true);
			$this->Cell(20, 5, $this->count_total, 0, 0);
		}
		if ($this->title == "Issues(Closed)") {
			$this->Cell(30, 5, $this->title, 0, 0);
			$this->SetFillColor(234, 234, 234);
			$this->Cell(5, 5, '', 0, 0, 'C', true);
			$this->Cell(20, 5, $this->count_total, 0, 0);
		}*/
		$this->SetFont('Arial', '', 8);

		$this->Cell(0, 5, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R');

		$this->Line(10, 16, 200, 16);
		// Line break
		$this->Ln(10);

	}

	
	// Page footer
	function Footer() {
		// Position at 1.5 cm from bottom
		$this->SetY(-15);
		// Arial italic 8
		$this->SetFont('Arial', 'I', 14);
		// Page number

	}

	function content($content, $content_border) {
		if ($content['task_status'] == "Open") {
			$this->Image('./resources/images/open_status.png', 22, $this->y + 1, 3, 3);
		}
		elseif ($content['task_status'] == "In progress") {
			$this->Image('./resources/images/inprogress_status.png', 22, $this->y + 1, 3, 3);
		}
		elseif ($content['task_status'] == "Review Pending") {
			$this->Image('./resources/images/pending_status.png', 22, $this->y + 1, 3, 3);
		}
		elseif ($content['task_status'] == "Closed") {
			$this->Image('./resources/images/closed_status.png', 22, $this->y + 1, 3, 3);
		} else {
			$this->Image('./resources/images/viewonly_status.png', 22, $this->y + 1, 3, 3);
		}
		$this->SetFont('Arial', '', 10);
		$this->SetTextColor(204, 0, 0);
		$align = 'L';
		if (isset ($content['task_title'])) {
			$b = 'LR';
			$this->MultiCell(0, 5, $content['task_title'], $b);
		}

		$this->SetTextColor(0, 0, 0);
		if (isset ($content['task_description'])) {
			$this->AddFont('verdana', '', '../font/verdana.php');
			$this->SetFont('verdana', '', 9);
			$task_description = $content['task_description'];
			$this->MultiCell(0, 5, $task_description, $content_border);
		}
	}

}
?>
