<?php

require_once 'fpdf/fpdf.php';
require_once 'BaseController.php';

class IssuePdf extends FPDF
{
	public $title;
	
	/* Abhilash 6.1.15 */
	function SetTitle($title, $isUTF8=false) {
		// Title of document
		if($isUTF8) {
			$title = $this->_UTF8toUTF16($title);
		}
			
		$this->title = $title;
	}
	function setCountOpen($count_open) {
		$this->count_open = 'Open'.'('.$count_open.')';
	}
	function setCountClosed($count_closed){
		$this->count_closed = 'Closed'.'('.$count_closed.')';
	}
	function setCountTotal($count_total) {
		$this->count_total = 'Total #'.$count_total;
	}
	function FancyTable($header, $data, $color){ 
		// Colors, line width and bold font
		if($color == 'orange') {
			$this->SetFillColor(255,204,153);
		}
		else {
			$this->SetFillColor(234,234,234);	
		}
		$this->SetDrawColor(230,230,230);
		$this->SetLineWidth(.3);
		$this->SetFont('Arial','',8);
		
		// Header 		
		$w = array(27,5,56,10,32,8,32);  /* Abhilash 26-10-13 */
		for($i=0;$i<count($header);$i++){ 
			$text_align = 'L';
			if(bcmod($i, '2') != 0){ // set text color for header label text
				$this->SetTextColor(128,0,0);
			}else{
				$this->SetTextColor(0,0,0);
			}
			$this->Cell($w[$i],7,$header[$i],0,0,$text_align,true);
		}	
		$this->Ln();
		// Color and font restoration
		$this->SetFillColor(224,235,255);
		$this->SetFont('');
		
		// Data
		$fill = false;
		
		// Closing line
		$this->Cell(array_sum($w),0,'','T');
	}

	// Page header
	function Header(){ 
		// Logo		
		$this->Image('./resources/images/logo.png',10,6);
		$this->SetFont('Arial','',9);
		// Move to the right
		$this->Cell(50);
		// Title
		$this->SetTextColor(0,0,0);
		if($this->title == "Issues (All)") { 
		$this->Cell(30,5,$this->title,0,0);
		$this->SetFillColor(255,204,153);
		$this->Cell(5,5,'',0,0,'C',true);
		$this->Cell(25,5,$this->count_open,0,0);
		$this->SetFillColor(234,234,234);
		$this->Cell(5,5,'',0,0,'C',true);
		$this->Cell(20,5,$this->count_closed,0,0);
		$this->Cell(20,5,$this->count_total,0,0);
		}
		if($this->title == "Issues (Open)") { 
		$this->Cell(30,5,$this->title,0,0);
		$this->SetFillColor(255,204,153);
		$this->Cell(5,5,'',0,0,'C',true);
		$this->Cell(20,5,$this->count_total,0,0);
		}
		if($this->title == "Issues (Closed)") {
			$this->Cell(30,5,$this->title,0,0);
			$this->SetFillColor(234,234,234);
			$this->Cell(5,5,'',0,0,'C',true);
			$this->Cell(20,5,$this->count_total,0,0);
		}
		$this->SetFont('Arial','',8);		
		$this->Cell(0,5,'Page '.$this->PageNo().' of {nb}',0,0,'R');		
		$this->Line(10, 16, 200, 16);
		// Line break
		$this->Ln(10);		
	} 	
	
	// Page footer
	function Footer(){
		// Position at 1.5 cm from bottom
		$this->SetY(-15);
		// Arial italic 8
		$this->SetFont('Arial','I',14);			
	}
	
	function content($content,$content_border){
		$this->SetFont('Arial','B',9);
		$align = 'L';
		if(isset($content['issue_title'])){
			$b = 'LRT';
			$this->MultiCell(0,5,$content['issue_title'],$b);
		}	
		if(isset($content['issue_description'])){
			$this->SetFont('Arial','',8);
			$issue_description = $content['issue_description'];
			$this->MultiCell(0,5,$issue_description,$content_border);
		}
	}
}
?>