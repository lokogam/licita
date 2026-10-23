-- -----------------------------------------------------------------------------
-- Tenders module - database structure (MySQL 8 / MariaDB 10.6+)
--
-- Table / column names are in English. Equivalence with the specification:
--   tenders          -> ofertas
--   activities       -> actividades
--   tender_documents -> ofertas_documentos
-- See README.md for the field-by-field mapping.
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS tender_documents;
DROP TABLE IF EXISTS tenders;
DROP TABLE IF EXISTS tender_sequences;
DROP TABLE IF EXISTS activities;

SET FOREIGN_KEY_CHECKS = 1;

-- UNSPSC goods and services classifier (actividades).
CREATE TABLE activities (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    segment_code  INT UNSIGNED NOT NULL,
    segment       VARCHAR(200) NOT NULL,
    family_code   INT UNSIGNED NOT NULL,
    family        VARCHAR(200) NOT NULL,
    class_code    INT UNSIGNED NOT NULL,
    class_name    VARCHAR(200) NOT NULL,
    product_code  INT UNSIGNED NOT NULL,
    product       VARCHAR(200) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_activities_product_code (product_code),
    KEY idx_activities_product (product)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenders (ofertas).
CREATE TABLE tenders (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    consecutive   VARCHAR(20)   NOT NULL,
    subject       VARCHAR(150)  NOT NULL,
    description   VARCHAR(400)  NOT NULL,
    currency      VARCHAR(3)    NOT NULL,
    budget        DECIMAL(15,2) NOT NULL,
    activity_id   INT UNSIGNED  NOT NULL,
    start_date    DATE          NOT NULL,
    start_time    TIME          NOT NULL,
    closing_date  DATE          NOT NULL,
    closing_time  TIME          NOT NULL,
    status        VARCHAR(20)   NOT NULL,
    created_at    DATETIME      NOT NULL,
    updated_at    DATETIME      NOT NULL,
    -- Schedule as single instants, so status filters (scheduled/open/closed) use indexes.
    starts_at     DATETIME      AS (TIMESTAMP(start_date, start_time)) STORED,
    closes_at     DATETIME      AS (TIMESTAMP(closing_date, closing_time)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tenders_consecutive (consecutive),
    KEY idx_tenders_subject (subject),
    KEY idx_tenders_created_at (created_at),
    KEY idx_tenders_starts_at (starts_at),
    KEY idx_tenders_closes_at (closes_at),
    CONSTRAINT fk_tenders_activity FOREIGN KEY (activity_id) REFERENCES activities (id),
    CONSTRAINT chk_tenders_currency CHECK (currency IN ('COP', 'USD', 'EUR')),
    CONSTRAINT chk_tenders_budget CHECK (budget > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents attached to a tender (ofertas_documentos).
CREATE TABLE tender_documents (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tender_id      INT UNSIGNED NOT NULL,
    title          VARCHAR(100) NOT NULL,
    description    VARCHAR(200) NOT NULL,
    file_path      VARCHAR(255) NOT NULL,
    original_name  VARCHAR(255) NOT NULL,
    mime_type      VARCHAR(100) NOT NULL,
    size           INT UNSIGNED NOT NULL,
    created_at     DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_tender_documents_tender (tender_id),
    CONSTRAINT fk_tender_documents_tender FOREIGN KEY (tender_id) REFERENCES tenders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Last consecutive number issued per year (row-locked when generating O-0001-YY).
CREATE TABLE tender_sequences (
    year         SMALLINT UNSIGNED NOT NULL,
    last_number  INT UNSIGNED      NOT NULL,
    PRIMARY KEY (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
