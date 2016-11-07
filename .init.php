<?php
/***********************************************************************************************
 * rodzeta.siteoptions - Users site options
 * Copyright 2016 Semenov Roman
 * MIT License
 ************************************************************************************************/

namespace Rodzeta\Siteoptions;

define(__NAMESPACE__ . "\APP", __DIR__ . "/");
define(__NAMESPACE__ . "\LIB", __DIR__  . "/lib/");
define(__NAMESPACE__ . "\FILE_OPTIONS", "/upload/.rodzeta.siteoptions.php");

require LIB . "encoding/php-array.php";

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

function CreateCache($siteOptions) {
	Loader::includeModule("iblock");

	$basePath = $_SERVER["DOCUMENT_ROOT"];
	$iblockId = Option::get("rodzeta.siteoptions", "iblock_id", 2);

	// create section RODZETA_SITE
	$res = \CIBlockSection::GetList(
		array("SORT" => "ASC"),
		array(
			"IBLOCK_ID" => $iblockId,
			"CODE" => "RODZETA_SITE",
		),
		true,
		array("*")
	);
	$sectionOptions = $res->GetNext();
	if (empty($sectionOptions["ID"])) {
		$iblockSection = new \CIBlockSection();
		$mainSectionId = $iblockSection->Add(array(
		  "IBLOCK_ID" => $iblockId,
		  "NAME" => "Пользовательские опции сайта",
		  "CODE" => "RODZETA_SITE",
		  "SORT" => 20000,
			"ACTIVE" => "Y",
	  ));
	  if (!empty($mainSectionId)) {
	  	Option::set("rodzeta.siteoptions", "section_id", $mainSectionId);
	  }
	} else {
		$mainSectionId = $sectionOptions["ID"];
	}

	$options = array();
	foreach ($siteOptions as $v) {
		$options["#" . $v["CODE"] . "#"] = array($v["MAIN"], $v["VALUE"], $v["NAME"]);
	}

	// init from infoblock section
	$res = \CIBlockElement::GetList(
		array("SORT" => "ASC"),
		array(
			"IBLOCK_ID" => $iblockId,
			"SECTION_ID" => $mainSectionId,
			"ACTIVE" => "Y"
		),
		false,
		false,
		array() // fields
	);
	while ($row = $res->GetNextElement()) {
		$item = $row->GetFields();
		foreach (array("NAME", "PREVIEW_TEXT", "DETAIL_TEXT") as $code) {
			$options["#" . $item["CODE"] . "_" . $code . "#"] = array(false, $item[$code], "");
		}
		foreach (array("PREVIEW_PICTURE", "DETAIL_PICTURE") as $code) {
			$img = \CFile::GetFileArray($item[$code]);
			$options["#" . $item["CODE"] . "_" . $code . "_SRC" . "#"] =
				array(false, $img["SRC"], "");
			$options["#" . $item["CODE"] . "_" . $code . "_DESCRIPTION" . "#"] =
				array(false, $img["DESCRIPTION"], "");
			$options["#" . $item["CODE"] . "_" . $code . "#"] =
				array(false, '<img src="' . $img["SRC"] . '" alt="'
						. htmlspecialchars($img["DESCRIPTION"]) . '">', "");
		}
	}

	\Encoding\PhpArray\Write($basePath . FILE_OPTIONS, $options);
}

function Options() {
	return include $_SERVER["DOCUMENT_ROOT"] . FILE_OPTIONS;
}

function AppendValues($data, $n, $v) {
	for ($i = 0; $i < $n; $i++) {
		$data[] = $v;
	}
	return $data;
}