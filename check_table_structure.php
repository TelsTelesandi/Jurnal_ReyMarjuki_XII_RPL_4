<?php
require_once 'config/database.php';

$conn = connectDB();

// Get table structure
$result = $conn->query("SHOW COLUMNS FROM laporan_masalah");
echo "<h2>Table: laporan_masalah</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Get current status values
$result = $conn->query("SELECT DISTINCT status_laporan FROM laporan_masalah");
echo "<h3>Current Status Values:</h3><ul>";
while($row = $result->fetch_assoc()) {
    echo "<li>" . $row['status_laporan'] . "</li>";
}
echo "</ul>";

$conn->close();
?>
