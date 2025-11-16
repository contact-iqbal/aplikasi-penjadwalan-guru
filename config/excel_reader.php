<?php
/**
 * Simple Excel Reader
 * Fungsi helper untuk membaca file Excel (.xlsx, .xls) tanpa library eksternal
 */

function readExcelFile($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if ($extension === 'xlsx') {
        return readXLSX($filePath);
    } elseif ($extension === 'xls') {
        return readXLS($filePath);
    }

    throw new Exception("Format file tidak didukung. Gunakan .xlsx atau .xls");
}

function readXLSX($filePath) {
    // Baca file XLSX menggunakan ZipArchive
    $zip = new ZipArchive;
    if ($zip->open($filePath) !== TRUE) {
        throw new Exception("Tidak dapat membuka file Excel");
    }

    // Baca shared strings
    $sharedStrings = [];
    $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsXML) {
        $xml = simplexml_load_string($sharedStringsXML);
        foreach ($xml->si as $si) {
            $sharedStrings[] = (string)$si->t;
        }
    }

    // Baca worksheet pertama
    $worksheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if (!$worksheetXML) {
        throw new Exception("Tidak dapat membaca worksheet");
    }

    $xml = simplexml_load_string($worksheetXML);
    $rows = [];
    $rowIndex = 1;

    foreach ($xml->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $cellRef = (string)$cell['r'];
            $column = preg_replace('/[0-9]+/', '', $cellRef);

            $value = '';
            if (isset($cell->v)) {
                $value = (string)$cell->v;
                // Jika tipe 's' (shared string), ambil dari array sharedStrings
                if (isset($cell['t']) && (string)$cell['t'] === 's') {
                    $value = $sharedStrings[(int)$value] ?? '';
                }
            }

            $rowData[$column] = $value;
        }

        $rows[$rowIndex] = $rowData;
        $rowIndex++;
    }

    return $rows;
}

function readXLS($filePath) {
    // Untuk file .xls, kita akan menggunakan pendekatan sederhana
    // dengan membaca data biner secara manual
    // Ini adalah implementasi sederhana yang mungkin tidak sempurna untuk semua kasus

    $data = file_get_contents($filePath);

    if ($data === false) {
        throw new Exception("Tidak dapat membaca file");
    }

    // Coba deteksi apakah ini benar-benar file XLS
    if (substr($data, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
        throw new Exception("File bukan format XLS yang valid. Gunakan format .xlsx untuk hasil terbaik.");
    }

    // Untuk file XLS lama, sarankan user menggunakan XLSX
    throw new Exception("Format .xls tidak sepenuhnya didukung. Silakan convert file ke .xlsx terlebih dahulu.");
}
