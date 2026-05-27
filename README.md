http://localhost/tygodniowyharmonogrampracy/Praca.html
CREATE DATABASE tygodniowyharmonogrampracy
CHARACTER SET utf8mb4
COLLATE utf8mb4_polish_ci;

USE tygodniowyharmonogrampracy;

-- =====================================
-- TABELA PRACOWNICY
-- =====================================
CREATE TABLE pracownicy (
    idpracownik INT AUTO_INCREMENT PRIMARY KEY,
    imie VARCHAR(50) NOT NULL,
    nazwisko VARCHAR(50) NOT NULL
);

-- =====================================
-- TABELA PROJEKTY
-- =====================================
CREATE TABLE projekty (
    idprojekt INT AUTO_INCREMENT PRIMARY KEY,
    nazwa VARCHAR(100) NOT NULL,
    opis TEXT
);

-- =====================================
-- TABELA HARMONOGRAM
-- =====================================
CREATE TABLE harmonogram (
    idharmonogram INT AUTO_INCREMENT PRIMARY KEY,

    idpracownik INT NOT NULL,
    idprojekt INT NOT NULL,

    data DATE NOT NULL,

    godzroz TIME NOT NULL,
    godzzak TIME NOT NULL,

    FOREIGN KEY (idpracownik)
        REFERENCES pracownicy(idpracownik)
        ON DELETE CASCADE,

    FOREIGN KEY (idprojekt)
        REFERENCES projekty(idprojekt)
        ON DELETE CASCADE
);
----

ALTER TABLE pracownicy
ADD CONSTRAINT unique_pracownik UNIQUE(imie, nazwisko);

----

ALTER TABLE projekty
ADD CONSTRAINT unique_projekt UNIQUE(nazwa);

----

ALTER TABLE harmonogram
ADD CONSTRAINT unique_zmiana
UNIQUE(idpracownik, data, godzroz, godzzak);
