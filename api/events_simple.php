<?php
// Simple Events API for fallback
header("Content-Type: application/json");

function api_respond($success, $data = [], $error = null) {
    echo json_encode(["success" => $success, "data" => $data, "error" => $error]);
    exit();
}

$action = $_GET["action"] ?? "";

try {
    switch ($action) {
        case "get_events_by_status":
            // Return mock data for now
            $events = [
                [
                    "id" => 1,
                    "title" => "Sample Event 1",
                    "description" => "This is a sample event for demonstration",
                    "start" => "2024-12-20 10:00:00",
                    "end" => "2024-12-20 12:00:00",
                    "location" => "Main Hall",
                    "status" => "upcoming",
                    "created_at" => "2024-12-19 10:00:00"
                ],
                [
                    "id" => 2,
                    "title" => "Sample Event 2",
                    "description" => "Another sample event",
                    "start" => "2024-12-25 14:00:00",
                    "end" => "2024-12-25 16:00:00",
                    "location" => "Conference Room",
                    "status" => "upcoming",
                    "created_at" => "2024-12-19 10:00:00"
                ]
            ];
            
            $grouped = [
                'upcoming' => $events,
                'completed' => []
            ];
            
            api_respond(true, ["events" => $grouped]);
            break;
            
        default:
            api_respond(false, [], "Invalid action");
    }
} catch (Exception $e) {
    api_respond(false, [], "Error: " . $e->getMessage());
}
?>
