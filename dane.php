<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "tygodniowyharmonogrampracy");

if (isset($_GET['cos'])) {

    $sql = "SELECT p.imie, p.nazwisko, h.godzroz, h.godzzak, h.data, pr.nazwa
            FROM harmonogram h
            JOIN pracownicy p ON h.idpracownik = p.idpracownik
            JOIN projekty pr ON h.idprojekt = pr.idprojekt";

    if (!empty($_GET['data'])) {
        $data = $conn->real_escape_string($_GET['data']);
        $sql .= " WHERE h.data = '$data'";
    }

    $result = $conn->query($sql);


    $dane = [];

    while ($row = $result->fetch_assoc()) {
        $dane[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($dane);
    exit;
}
