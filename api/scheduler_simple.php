<?php
// Simple Scheduler API for fallback
header("Content-Type: application/json");

function api_respond($success, $data = [], $error = null) {
    echo json_encode(["success" => $success, "data" => $data, "error" => $error]);
    exit();
}

$action = $_GET["action"] ?? "";

try {
    switch ($action) {
        case "get_events":
            // Return mock events data
            $events = [
                [
                    "id" => 1,
                    "title" => "Sample Meeting",
                    "start" => "2024-12-20 10:00:00",
                    "end" => "2024-12-20 11:00:00",
                    "description" => "Sample meeting for demonstration",
                    "location" => "Conference Room A",
                    "status" => "upcoming"
                ],
                [
                    "id" => 2,
                    "title" => "Team Review",
                    "start" => "2024-12-22 14:00:00",
                    "end" => "2024-12-22 15:30:00",
                    "description" => "Monthly team review meeting",
                    "location" => "Main Hall",
                    "status" => "upcoming"
                ]
            ];
            
            api_respond(true, $events);
            break;
            
        case "get_upcoming_events":
            // Return mock upcoming events
            $upcomingEvents = [
                [
                    "id" => 1,
                    "title" => "Sample Meeting",
                    "start" => "2024-12-20 10:00:00",
                    "end" => "2024-12-20 11:00:00",
                    "description" => "Sample meeting for demonstration",
                    "location" => "Conference Room A"
                ]
            ];
            
            api_respond(true, $upcomingEvents);
            break;
            
        case "get_trash":
            // Return empty trash data
            api_respond(true, ["meetings" => []]);
            break;
            
        default:
            api_respond(false, [], "Invalid action");
    }
} catch (Exception $e) {
    api_respond(false, [], "Error: " . $e->getMessage());
}
?>
