<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Get all events
$query = "
    SELECT 
        ce.title,
        ce.description,
        ce.event_type,
        ce.venue,
        ce.start_date,
        ce.end_date,
        ce.start_time,
        ce.end_time,
        CONCAT(u.first_name, ' ', u.last_name) as created_by,
        ce.created_at
    FROM calendar_events ce
    LEFT JOIN users u ON ce.created_by = u.id
    WHERE ce.status = 'active'
    ORDER BY ce.start_date DESC
";

$result = $db->query($query);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="hr_calendar_events_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, ['Title', 'Description', 'Type', 'Venue', 'Start Date', 'End Date', 'Start Time', 'End Time', 'Created By', 'Date Created']);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['title'],
        $row['description'] ?? '',
        ucfirst($row['event_type']),
        $row['venue'] ?? '',
        date('Y-m-d', strtotime($row['start_date'])),
        $row['end_date'] ? date('Y-m-d', strtotime($row['end_date'])) : '',
        $row['start_time'] ? date('h:i A', strtotime($row['start_time'])) : '',
        $row['end_time'] ? date('h:i A', strtotime($row['end_time'])) : '',
        $row['created_by'] ?? 'System',
        date('Y-m-d H:i:s', strtotime($row['created_at']))
    ]);
}

fclose($output);
exit;