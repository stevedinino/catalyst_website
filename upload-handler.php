<?php
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}
 
// Validate required fields
$requiredFields = ['name', 'email', 'firm', 'phone', 'case'];
$errors = [];

foreach ($requiredFields as $field) {
  if (empty($_POST[$field])) {
    $errors[] = $field;
  }
}

if (!empty($errors)) {
  $errorQuery = implode(",", $errors);
  header("Location: error-page.html?type=missing&fields=" . urlencode($errorQuery));
  exit;
}

// Handle file upload (optional)
$uploadedFileName = "";
$fileLink = "";

if (isset($_FILES["file"]) && $_FILES["file"]["error"] === UPLOAD_ERR_OK) {
  $originalName = basename($_FILES["file"]["name"]);
  $fileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
  $fileSize = $_FILES["file"]["size"];

  if ($fileType !== "pdf") {
    header("Location: error-page.html?type=type");
    exit;
  }

  if ($fileSize > 200 * 1024 * 1024) {
    header("Location: error-page.html?type=toolarge");
    exit;
  }

  $targetFile = $uploadDir . $originalName;

  if (file_exists($targetFile)) {
    $timestampPrefix = date("Ymd_His") . "_";
    $originalName = $timestampPrefix . $originalName;
    $targetFile = $uploadDir . $originalName;
  }

  if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    header("Location: error-page.html?type=uploadfail");
    exit;
  }

  $uploadedFileName = $originalName;
  $fileLink = "https://catalystlegalnurse.com/uploads/" . rawurlencode($uploadedFileName);
}

// Prepare CSV row
$timestamp = date("Y-m-d H:i:s");
$csvRow = [
  $timestamp,
  $_POST["name"],
  $_POST["email"],
  $_POST["firm"],
  $_POST["phone"],
  $_POST["case"],
  $_POST["notes"] ?? "",
  $fileLink
];

$csvFilePath = $uploadDir . "submissions.csv";
$csvFileExists = file_exists($csvFilePath);
$csvFile = fopen($csvFilePath, "a");

if (!$csvFileExists) {
  fputcsv($csvFile, ["Timestamp", "Name", "Email", "Law Firm", "Phone", "Case Type", "Notes", "PDF Link"]);
}

fputcsv($csvFile, $csvRow);
fclose($csvFile);

// Send email notification
$to = "janet@catalystlegalnurse.com";
$subject = "New Case Submission Received";
$body = "A new case submission has been received:\n\n" .
        "Timestamp: $timestamp\n" .
        "Name: " . $_POST["name"] . "\n" .
        "Email: " . $_POST["email"] . "\n" .
        "Law Firm: " . $_POST["firm"] . "\n" .
        "Phone: " . $_POST["phone"] . "\n" .
        "Case Type: " . $_POST["case"] . "\n" .
        "Notes: " . ($_POST["notes"] ?? "None") . "\n" .
        "PDF File: " . ($fileLink ?: "None") . "\n";

$headers = "From: no-reply@catalystlegalnurse.com";

mail($to, $subject, $body, $headers);

// Redirect to confirmation
header("Location: index.html?success=1");
exit;
?>