<?php
// Set upload directory
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

// Validate required fields
$requiredFields = ['name', 'email', 'firm', 'phone', 'case'];
foreach ($requiredFields as $field) {
  if (empty($_POST[$field])) {
    echo "Error: Missing required field '$field'.";
    exit;
  }
}

// Handle file upload if present
$uploadedFileName = "";
if (!empty($_FILES["file"]["name"])) {
  $originalName = basename($_FILES["file"]["name"]);
  $fileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

  if ($fileType !== "pdf") {
    echo "Error: Only PDF files are allowed.";
    exit;
  }

  $targetFile = $uploadDir . $originalName;

  // If file exists, prepend timestamp to avoid overwrite
  if (file_exists($targetFile)) {
    $timestampPrefix = date("Ymd_His") . "_";
    $originalName = $timestampPrefix . $originalName;
    $targetFile = $uploadDir . $originalName;
  }

  if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    echo "Error uploading file.";
    exit;
  }

  $uploadedFileName = $originalName;
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
  $uploadedFileName
];

// Write to CSV inside uploads folder
$csvFilePath = $uploadDir . "submissions.csv";
$csvFileExists = file_exists($csvFilePath);
$csvFile = fopen($csvFilePath, "a");

if (!$csvFileExists) {
  fputcsv($csvFile, ["Timestamp", "Name", "Email", "Law Firm", "Phone", "Case Type", "Notes", "PDF File"]);
}

fputcsv($csvFile, $csvRow);
fclose($csvFile);

// Send email notification to Janet
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
        "PDF File: " . ($uploadedFileName ?: "None") . "\n";

$headers = "From: no-reply@catalystlegalnurse.com";

mail($to, $subject, $body, $headers);


// Confirmation message
echo "Thank you, " . htmlspecialchars($_POST["name"]) . ". Your submission has been recorded.";

header("Location: index.html");
exit;

?>