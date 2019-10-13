<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 15/07/2019
	 * Time: 7:50 PM
	 */

	namespace Skyenet\PDF;

	use Skyenet\View\View;
	use TCPDF;

	class PDF extends TCPDF {
		public const PAGE_WIDTH_MILLIS = 210;
		public const PAGE_HEIGHT_MILLIS = 297;

		public const PAGE_MARGIN_MIN_TOP = 10;
		public const PAGE_MARGIN_MIN_LEFT = 10;
		public const PAGE_MARGIN_MIN_RIGHT = 10;
		public const PAGE_MARGIN_MIN_BOTTOM = 10;

		public $marginTop = 10;
		public $marginBottom = 10;
		public $marginLeft = 20;
		public $marginRight = 10;

		protected $isMeasurementObject = false;

		/* @var $headerView View */
		protected $headerView;

		/* @var $footerView View */
		protected $footerView;

		protected $headerBackgroundImage;
		protected $footerBackgroundImage;

		protected $headerVars;
		protected $footerVars;

		public function __construct() {
			parent::__construct();

			$this->SetTopMargin($this->marginTop);
			$this->SetLeftMargin($this->marginLeft);
			$this->SetRightMargin($this->marginRight);

			$this->SetFont('Courier', '', 10);
			$this->SetAutoPageBreak(true, $this->marginBottom);
		}

		public function setHeaderView(?string $backgroundImageContent, ?View $view = null, array $bindVariables = null): void {
			$this->headerView = $view;
			$this->headerVars = $bindVariables;
			$this->headerBackgroundImage = $backgroundImageContent;
		}

		public function setFooterView(?string $backgroundImageContent, ?View $view = null, array $bindVariables = null): void {
			$this->footerView = $view;
			$this->footerVars = $bindVariables;
			$this->footerBackgroundImage = $backgroundImageContent;
		}

		public function Header(): void {
			// if we are the measurement clone object, then skip headers/footers.. its just a waste of time
			if ($this->isMeasurementObject) {
				return;
			}

			// set the margins to our bare minimums, for printing our header view
			$this->SetTopMargin(self::PAGE_MARGIN_MIN_TOP);
			$this->SetLeftMargin(self::PAGE_MARGIN_MIN_LEFT);
			$this->SetRightMargin(self::PAGE_MARGIN_MIN_RIGHT);
			$this->SetAutoPageBreak(false);

			$headerContent = null;
			$pageNumber = $this->getAliasNumPage();
			$pageCount = $this->getAliasNbPages();

			// if we have a header image, draw this first
			if ($this->headerBackgroundImage) {
				// todo: config page whereby this image is uploaded and sizes are known/set to draw appropriately for the image at hand
				$headerHeight = 30;

				$this->Image($this->headerBackgroundImage, 0, 0, self::PAGE_WIDTH_MILLIS, $headerHeight);
				$this->marginTop = $headerHeight;
			}

			// if we have a header view, draw this over the image
			if ($this->headerView) {
				$headerContent = $this->headerView->buildOutput(array_merge(
					$this->headerVars ?? [],
					[
						'PageNumber' => $pageNumber,
						'PageCount' => $pageCount,
					]
				), true);
				$this->writeHTML($headerContent, false);

				$newY = $this->GetY();
				if ($this->marginTop < $newY) {
					$this->marginTop = $newY;
				}
			}

			// footer image? draw it now
			if ($this->footerBackgroundImage) {
				// todo: config page whereby this image is uploaded and sizes are known/set to draw appropriately for the image at hand
				$footerHeight = 30;

				$this->Image($this->footerBackgroundImage, 0, self::PAGE_HEIGHT_MILLIS - $footerHeight, self::PAGE_WIDTH_MILLIS, $footerHeight);
				$this->marginBottom = $footerHeight;
			}

			// and our footer view, if we have one
			if ($this->footerView) {
				$footerContent = $this->footerView->buildOutput(array_merge(
					$this->footerVars ?? [],
					[
						'PageNumber' => $pageNumber,
						'PageCount' => $pageCount,
					]
				), true);
				$footerContentHeight = $this->measureHtmlCellHeight($footerContent, false);

				if ($this->marginBottom < $footerContentHeight) {
					$this->marginBottom = $footerContentHeight;
				}

				$this->SetY(-$footerContentHeight);
				$this->writeHTML($footerContent, false);
				$this->SetY($this->marginTop);
				$this->resetLastH();
			}

			// restore our margins
			$this->SetLeftMargin($this->marginLeft);
			$this->SetRightMargin($this->marginRight);
			$this->SetTopMargin($this->marginTop);

			$this->SetAutoPageBreak(true, $this->marginBottom);
			$this->setPageMark();
		}

		public function Footer():void {
		}

		public function measureHtmlCellHeight(string $htmlString, bool $withTrailingNewLine = true): float {
			// TCPDF transactions use object duplication... so lets do the same here as it simplifies things immensely
			$measurePdf = clone $this;
			$measurePdf->isMeasurementObject = true;

			// Create a new page and start from the top
			$measurePdf->AddPage();
			$htmlTopPage = $measurePdf->getPage();

			// Insert our HTML content
			$measurePdf->writeHTML($htmlString, $withTrailingNewLine);

			// Fetch our new location in the document
			$htmlBottomY = $measurePdf->GetY();
			$htmlBottomPage = $measurePdf->getPage();

			$pageBreaks = $htmlBottomPage - $htmlTopPage;

			return ($pageBreaks * (static::PAGE_HEIGHT_MILLIS - $this->marginTop - $this->marginBottom)) + $htmlBottomY;
		}
	}