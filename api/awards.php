<?php
header("Content-Type: application/json");

$action = $_GET["action"] ?? "";

function api_respond($success, $data = [], $error = null) {
    echo json_encode(["success" => $success, "data" => $data, "error" => $error]);
    exit();
}

switch ($action) {
    case "get_awards":
        $awards = [
            ["id" => 1, "title" => "Academic Excellence", "category" => "academic", "award_date" => "2024-01-15"],
            ["id" => 2, "title" => "Research Innovation", "category" => "research", "award_date" => "2024-02-20"],
            ["id" => 3, "title" => "Leadership Award", "category" => "leadership", "award_date" => "2024-03-10"]
        ];
        api_respond(true, ["awards" => $awards]);
        break;
        
    case "get_stats":
        $stats = [
            'total' => 3,
            'academic' => 1,
            'research' => 1,
            'leadership' => 1
        ];
        api_respond(true, ["stats" => $stats]);
        break;
        
    case "get_awards_by_period":
        $data = ['red' => 1, 'blue' => 1, 'pink' => 1];
        api_respond(true, ["data" => $data]);
        break;
        
    case "get_monthly_trends":
        $thisYearData = [1200, 1800, 1500, 2200, 2800, 2500, 3200, 7200, 3800, 4200, 3500, 4000];
        $lastYearData = [1000, 1400, 1600, 1900, 2100, 1800, 2400, 2800, 2200, 2600, 2000, 2400];
        
        api_respond(true, [
            "data" => [
                "thisYear" => $thisYearData,
                "lastYear" => $lastYearData
            ]
        ]);
        break;
        
    default:
        api_respond(false, [], "Invalid action");
}
?>
