<?php
header("Content-Type: application/json");

$action = $_GET["action"] ?? $_POST["action"] ?? "";

function api_respond($success, $data = [], $error = null) {
    echo json_encode(["success" => $success, "data" => $data, "error" => $error]);
    exit();
}

switch ($action) {
    case "add":
        api_respond(true, ["message" => "MOU/MOA created successfully", "newMouId" => rand(1, 1000)]);
        break;
        
    case "update":
        api_respond(true, ["message" => "MOU/MOA updated successfully"]);
        break;
        
    case "delete":
        api_respond(true, ["message" => "MOU/MOA deleted successfully"]);
        break;
        
    case "get_upcoming_expirations":
        $mous = [];
        api_respond(true, ["mous" => $mous]);
        break;
        
    case "get_stats":
        $stats = ['total' => 0, 'active' => 0, 'expiringSoon' => 0];
        api_respond(true, ["stats" => $stats]);
        break;
        
    case "get_all":
    case "list":
        $documents = [
            [
                "id" => 1,
                "partner_name" => "Sample University",
                "type" => "MOU",
                "status" => "Active",
                "date_signed" => "2024-01-15",
                "end_date" => "2025-01-15",
                "description" => "Sample MOU for demonstration",
                "file_name" => "sample_mou.pdf",
                "created_at" => "2024-01-15 10:00:00"
            ]
        ];
        api_respond(true, ["documents" => $documents, "mous" => $documents]);
        break;
        
    default:
        api_respond(false, [], "Invalid action");
}
?>
