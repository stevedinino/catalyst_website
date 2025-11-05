<?php
$csvPath = "uploads/submissions.csv";

// Handle clear request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'])) {
  $file = fopen($csvPath, "w");
  fputcsv($file, ["Timestamp", "Name", "Email", "Law Firm", "Phone", "Case Type", "Notes", "PDF Link"]);
  fclose($file);
  exit;
}

// Load data
$rows = [];
$headers = [];
if (($handle = fopen($csvPath, "r")) !== false) {
  $headers = fgetcsv($handle);
  while (($data = fgetcsv($handle)) !== false) {
    $rows[] = $data;
  }
  fclose($handle);
}

// Output HTML table
echo '<table border="1" cellpadding="8" cellspacing="0">';
echo '<thead><tr>';
foreach ($headers as $header) {
  echo '<th>' . htmlspecialchars($header) . '</th>';
}
echo '</tr></thead><tbody>';
foreach ($rows as $row) {
  echo '<tr>';
  foreach ($row as $index => $cell) {
    if ($index === count($row) - 1 && filter_var($cell, FILTER_VALIDATE_URL)) {
      echo '<td><a href="' . htmlspecialchars($cell) . '" target="_blank">View PDF</a></td>';
    } else {
      echo '<td>' . htmlspecialchars($cell) . '</td>';
    }
  }
  echo '</tr>';
}
echo '</tbody></table>';
?>