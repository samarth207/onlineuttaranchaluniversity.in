<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$programs = [
    ["Code" => "ONMB201", "ShortName" => "Online MBA"],
    ["Code" => "ONMB202", "ShortName" => "Online MBA in Business Analytics"],
    ["Code" => "ONMC201", "ShortName" => "Online MCA"],
    ["Code" => "ONMC202", "ShortName" => "Online MCA in Cloud Computing"],
    ["Code" => "ONMS201", "ShortName" => "Online M.Sc. in Data Science"],
    ["Code" => "ONMS202", "ShortName" => "Online M.Sc. in Mathematics"],
    ["Code" => "ONMA201", "ShortName" => "Online MA in Journalism & Mass Communication"],
    ["Code" => "ONMA202", "ShortName" => "Online MA in Economics"],
    ["Code" => "ONMA203", "ShortName" => "Online MA in English"],
    ["Code" => "ONBB201", "ShortName" => "Online BBA"],
    ["Code" => "ONBB202", "ShortName" => "Online BBA in Business Analytics"],
    ["Code" => "ONBC201", "ShortName" => "Online BCA"],
    ["Code" => "ONBA201", "ShortName" => "Online BA in Journalism & Mass Communication"],
    ["Code" => "ONMX201", "ShortName" => "MBA-X (MBA for Working Professionals)"]
];

echo json_encode($programs);
