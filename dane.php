<?php
header('Content-Type: application/json; charset=utf-8');

// Wyłączamy domyślne raportowanie błędów jako Fatal Error dla mysqli, żeby móc je obsłużyć ręcznie przez try-catch
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli("localhost", "root", "", "tygodniowyharmonogrampracy");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Błąd połączenia z bazą danych: " . $conn->connect_error]);
    exit;
}

$akcja = isset($_GET['akcja']) ? $_GET['akcja'] : '';

// 1. POBIERANIE WZORCÓW DO KALENDARZA
if ($akcja == 'lista') {
    $sql = "SELECT p.imie, p.nazwisko, h.godzroz, h.godzzak, h.data, pr.nazwa, pr.opis
            FROM harmonogram h
            JOIN pracownicy p ON h.idpracownik = p.idpracownik
            JOIN projekty pr ON h.idprojekt = pr.idprojekt";

    if (!empty($_GET['data'])) {
        $data = $conn->real_escape_string($_GET['data']);
        $sql .= " WHERE h.data = '$data'";
    }

    $result = $conn->query($sql);
    $dane = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $dane[] = $row;
        }
    }

    echo json_encode($dane);
    exit;
}

// 2. DODAWANIE WPISU
if ($akcja == 'dodaj' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie = isset($_POST['imie']) ? trim($_POST['imie']) : '';
    $nazwisko = isset($_POST['nazwisko']) ? trim($_POST['nazwisko']) : '';
    $data = isset($_POST['data']) ? trim($_POST['data']) : '';
    $godzroz = isset($_POST['godzroz']) ? trim($_POST['godzroz']) : '';
    $godzzak = isset($_POST['godzzak']) ? trim($_POST['godzzak']) : '';
    $nazwa = isset($_POST['nazwa']) ? trim($_POST['nazwa']) : '';
    $opis = isset($_POST['opis']) ? trim($_POST['opis']) : '';

    if (empty($imie) || empty($nazwisko) || empty($data) || empty($godzroz) || empty($godzzak) || empty($nazwa)) {
        echo json_encode(["status" => "error", "message" => "Wszystkie wymagane pola muszą być wypełnione!"]);
        exit;
    }

    $imie_sql = $conn->real_escape_string($imie);
    $nazwisko_sql = $conn->real_escape_string($nazwisko);
    $data_sql = $conn->real_escape_string($data);
    $godzroz_sql = $conn->real_escape_string($godzroz);
    $godzzak_sql = $conn->real_escape_string($godzzak);
    $nazwa_sql = $conn->real_escape_string($nazwa);
    $opis_sql = $conn->real_escape_string($opis);

    // --- PRACOWNIK ---
    $idPracownik = 0;
    $resPracownik = $conn->query("SELECT idpracownik FROM pracownicy WHERE LOWER(imie)=LOWER('$imie_sql') AND LOWER(nazwisko)=LOWER('$nazwisko_sql')");
    
    if ($resPracownik && $resPracownik->num_rows > 0) {
        $rowP = $resPracownik->fetch_assoc();
        $idPracownik = $rowP['idpracownik'];
    } else {
        $conn->query("INSERT INTO pracownicy (imie, nazwisko) VALUES ('$imie_sql', '$nazwisko_sql')");
        $idPracownik = $conn->insert_id;
        
        if (empty($idPracownik) || $idPracownik == 0) {
            $resPracownikAwaryjny = $conn->query("SELECT idpracownik FROM pracownicy WHERE LOWER(imie)=LOWER('$imie_sql') AND LOWER(nazwisko)=LOWER('$nazwisko_sql')");
            if ($resPracownikAwaryjny && $resPracownikAwaryjny->num_rows > 0) {
                $rowPAw = $resPracownikAwaryjny->fetch_assoc();
                $idPracownik = $rowPAw['idpracownik'];
            }
        }
    }

    // --- PROJEKT ---
    $idProjekt = 0;
    $resProjekt = $conn->query("SELECT idprojekt FROM projekty WHERE LOWER(nazwa)=LOWER('$nazwa_sql')");
    
    if ($resProjekt && $resProjekt->num_rows > 0) {
        $rowPr = $resProjekt->fetch_assoc();
        $idProjekt = $rowPr['idprojekt'];
    } else {
        $conn->query("INSERT INTO projekty (nazwa, opis) VALUES ('$nazwa_sql', '$opis_sql')");
        $idProjekt = $conn->insert_id;

        if (empty($idProjekt) || $idProjekt == 0) {
            $resProjektAwaryjny = $conn->query("SELECT idprojekt FROM projekty WHERE LOWER(nazwa)=LOWER('$nazwa_sql')");
            if ($resProjektAwaryjny && $resProjektAwaryjny->num_rows > 0) {
                $rowPrAw = $resProjektAwaryjny->fetch_assoc();
                $idProjekt = $rowPrAw['idprojekt'];
            }
        }
    }

    // --- PRÓBA ZAPISU DO HARMONOGRAMU Z WEWNĘTRZNĄ DIAGNOSTYKĄ ---
    $sqlInsert = "INSERT INTO harmonogram (idpracownik, idprojekt, godzroz, godzzak, data) 
                  VALUES ('$idPracownik', '$idProjekt', '$godzroz_sql', '$godzzak_sql', '$data_sql')";

    if ($conn->query($sqlInsert)) {
        echo json_encode(["status" => "success", "message" => "Pomyślnie dodano dyżur do harmonogramu!"]);
    } else {
        // ZAMIAST CRASHA - WYŚWIETLAMY CO SIĘ STAŁO:
        echo json_encode([
            "status" => "error", 
            "message" => "Baza odrzuciła zapis! Szczegóły diagnostyczne: Próba wstawienia idpracownik=[" . $idPracownik . "], idprojekt=[" . $idProjekt . "]. Wykryty błąd MySQL: " . $conn->error
        ]);
    }
    exit;
}

// 3. USUWANIE WPISU
if ($akcja == 'usun' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie = $conn->real_escape_string(trim($_POST['imie']));
    $nazwisko = $conn->real_escape_string(trim($_POST['nazwisko']));
    $data = $conn->real_escape_string(trim($_POST['data']));

    if (empty($imie) || empty($nazwisko) || empty($data)) {
        echo json_encode(["status" => "error", "message" => "Brak wymaganych danych do usunięcia wpisu."]);
        exit;
    }

    $sqlDelete = "DELETE h FROM harmonogram h
                  JOIN pracownicy p ON h.idpracownik = p.idpracownik
                  WHERE LOWER(p.imie) = LOWER('$imie') AND LOWER(p.nazwisko) = LOWER('$nazwisko') AND h.data = '$data'";

    if ($conn->query($sqlDelete)) {
        if ($conn->affected_rows > 0) {
            echo json_encode(["status" => "success", "message" => "Pomyślnie usunięto wpis(y)."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Nie znaleziono pasującego wpisu do usunięcia w tym dniu."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Błąd bazy danych przy usuwaniu: " . $conn->error]);
    }
    exit;
}
?>