<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

class SimpleExcelExporter
{
    public const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @param  array<int, array{name: string, rows: array<int, array<int, mixed>>, header_rows?: int}>  $sheets
     */
    public function store(array $sheets): string
    {
        if (empty($sheets)) {
            throw new RuntimeException('At least one sheet is required.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'salon_xlsx_');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create a temporary file for Excel export.');
        }

        $xlsxPath = $tempPath . '.xlsx';

        if (!rename($tempPath, $xlsxPath)) {
            @unlink($tempPath);
            throw new RuntimeException('Unable to prepare the Excel export file.');
        }

        $zip = new ZipArchive();

        if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($xlsxPath);
            throw new RuntimeException('Unable to open the Excel archive.');
        }

        $normalizedSheets = array_values(array_map(function (array $sheet, int $index) {
            return [
                'name' => $this->normalizeSheetName($sheet['name'] ?? ('Sheet ' . ($index + 1)), $index + 1),
                'rows' => $sheet['rows'] ?? [],
                'header_rows' => max(0, (int) ($sheet['header_rows'] ?? 1)),
            ];
        }, $sheets, array_keys($sheets)));

        $zip->addFromString('[Content_Types].xml', $this->buildContentTypesXml(count($normalizedSheets)));
        $zip->addFromString('_rels/.rels', $this->buildRootRelationshipsXml());
        $zip->addFromString('docProps/app.xml', $this->buildAppPropertiesXml($normalizedSheets));
        $zip->addFromString('docProps/core.xml', $this->buildCorePropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbookXml($normalizedSheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRelationshipsXml(count($normalizedSheets)));
        $zip->addFromString('xl/styles.xml', $this->buildStylesXml());

        foreach ($normalizedSheets as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet' . ($index + 1) . '.xml',
                $this->buildWorksheetXml($sheet['rows'], $sheet['header_rows'])
            );
        }

        $zip->close();

        return $xlsxPath;
    }

    private function normalizeSheetName(string $name, int $index): string
    {
        $name = trim(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $name) ?? '');

        if ($name === '') {
            $name = 'Sheet ' . $index;
        }

        return mb_substr($name, 0, 31);
    }

    private function buildContentTypesXml(int $sheetCount): string
    {
        $overrides = [
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>',
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>',
            '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>',
            '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>',
        ];

        for ($sheet = 1; $sheet <= $sheetCount; $sheet++) {
            $overrides[] = '<Override PartName="/xl/worksheets/sheet' . $sheet . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . implode('', $overrides)
            . '</Types>';
    }

    private function buildRootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    /**
     * @param  array<int, array{name: string}>  $sheets
     */
    private function buildAppPropertiesXml(array $sheets): string
    {
        $titles = array_map(
            fn(array $sheet) => '<vt:lpstr>' . $this->escapeXml($sheet['name']) . '</vt:lpstr>',
            $sheets
        );

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"'
            . ' xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Salon Reports</Application>'
            . '<HeadingPairs><vt:vector size="2" baseType="variant">'
            . '<vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant>'
            . '<vt:variant><vt:i4>' . count($sheets) . '</vt:i4></vt:variant>'
            . '</vt:vector></HeadingPairs>'
            . '<TitlesOfParts><vt:vector size="' . count($sheets) . '" baseType="lpstr">'
            . implode('', $titles)
            . '</vt:vector></TitlesOfParts>'
            . '</Properties>';
    }

    private function buildCorePropertiesXml(): string
    {
        $timestamp = now()->toAtomString();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
            . ' xmlns:dc="http://purl.org/dc/elements/1.1/"'
            . ' xmlns:dcterms="http://purl.org/dc/terms/"'
            . ' xmlns:dcmitype="http://purl.org/dc/dcmitype/"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Codex</dc:creator>'
            . '<cp:lastModifiedBy>Codex</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    /**
     * @param  array<int, array{name: string}>  $sheets
     */
    private function buildWorkbookXml(array $sheets): string
    {
        $sheetNodes = [];

        foreach ($sheets as $index => $sheet) {
            $sheetNodes[] = '<sheet name="'
                . $this->escapeXml($sheet['name'])
                . '" sheetId="'
                . ($index + 1)
                . '" r:id="rId'
                . ($index + 1)
                . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . implode('', $sheetNodes) . '</sheets>'
            . '</workbook>';
    }

    private function buildWorkbookRelationshipsXml(int $sheetCount): string
    {
        $relationships = [];

        for ($sheet = 1; $sheet <= $sheetCount; $sheet++) {
            $relationships[] = '<Relationship Id="rId'
                . $sheet
                . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'
                . $sheet
                . '.xml"/>';
        }

        $relationships[] = '<Relationship Id="rId' . ($sheetCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . implode('', $relationships)
            . '</Relationships>';
    }

    private function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Arial"/></font>'
            . '<font><b/><sz val="11"/><name val="Arial"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE7F4EA"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function buildWorksheetXml(array $rows, int $headerRows): string
    {
        $columnCount = 0;
        $columnWidths = [];

        foreach ($rows as $row) {
            $columnCount = max($columnCount, count($row));

            foreach ($row as $index => $value) {
                $length = $this->displayLength($value);
                $columnWidths[$index] = max($columnWidths[$index] ?? 10, min(max($length + 2, 10), 40));
            }
        }

        $colsXml = '';

        if ($columnCount > 0) {
            $columns = [];

            for ($index = 0; $index < $columnCount; $index++) {
                $width = $columnWidths[$index] ?? 12;
                $columns[] = '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
            }

            $colsXml = '<cols>' . implode('', $columns) . '</cols>';
        }

        $rowsXml = [];

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $cells = [];

            foreach ($row as $columnIndex => $value) {
                $styleId = $excelRow <= $headerRows ? 1 : 0;
                $cells[] = $this->buildCellXml(
                    $this->columnName($columnIndex + 1) . $excelRow,
                    $value,
                    $styleId
                );
            }

            $rowsXml[] = '<row r="' . $excelRow . '">' . implode('', $cells) . '</row>';
        }

        $autoFilter = $headerRows === 1 && $columnCount > 0 && count($rows) > 1
            ? '<autoFilter ref="A1:' . $this->columnName($columnCount) . '1"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" rightToLeft="1"/></sheetViews>'
            . $colsXml
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<sheetData>' . implode('', $rowsXml) . '</sheetData>'
            . $autoFilter
            . '</worksheet>';
    }

    private function buildCellXml(string $reference, mixed $value, int $styleId): string
    {
        if ($value === null || $value === '') {
            return '<c r="' . $reference . '" s="' . $styleId . '"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $reference . '" s="' . $styleId . '"><v>' . $value . '</v></c>';
        }

        if (is_bool($value)) {
            return '<c r="' . $reference . '" s="' . $styleId . '" t="b"><v>' . ($value ? '1' : '0') . '</v></c>';
        }

        return '<c r="' . $reference . '" s="' . $styleId . '" t="inlineStr"><is><t xml:space="preserve">'
            . $this->escapeXml((string) $value)
            . '</t></is></c>';
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod) . $name;
            $index = intdiv($index - 1, 26);
        }

        return $name;
    }

    private function displayLength(mixed $value): int
    {
        if ($value === null) {
            return 0;
        }

        if (is_int($value) || is_float($value)) {
            return strlen((string) $value);
        }

        return mb_strlen((string) $value);
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
