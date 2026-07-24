-- =====================================================
-- SoulFM - Schema Update Script
-- Voor bestaande installaties die eerder schema.sql
-- hebben gedraaid. Voer dit uit via phpMyAdmin of CLI.
-- =====================================================

USE soulmc_soulfm;

-- 1. Nieuwe rollen toevoegen aan users tabel
ALTER TABLE users
MODIFY COLUMN role ENUM(
    'admin',
    'dj',
    'dj_hoofd',
    'administratie',
    'administratie_hoofd',
    'evenementen',
    'evenementen_hoofd',
    'redactie',
    'redactie_hoofd',
    'content',
    'content_hoofd',
    'marketing',
    'marketing_hoofd',
    'moderator',
    'listener'
) NOT NULL DEFAULT 'listener';

-- 2. Afdelingen tabel
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    head_role VARCHAR(50) NOT NULL,
    member_role VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO departments (slug, name, description, email, head_role, member_role) VALUES
('dj',            'DJ',            'Verantwoordelijk voor de uitzendingen en streamgegevens.',        'dj@soulfm.nl',             'dj_hoofd',            'dj'),
('administratie', 'Administratie', 'Beheert de planning en roosters van de zender.',                  'administratie@soulfm.nl',  'administratie_hoofd', 'administratie'),
('evenementen',   'Evenementen',   'Organiseert evenementen en publiceert hierover nieuws.',           'evenementen@soulfm.nl',    'evenementen_hoofd',   'evenementen'),
('redactie',      'Redactie',      'Schrijft en beheert nieuwsberichten en artikelen.',                'redactie@soulfm.nl',       'redactie_hoofd',      'redactie'),
('content',       'Content',       'Produceert content voor social media en de website.',              'content@soulfm.nl',        'content_hoofd',       'content'),
('marketing',     'Marketing',     'Verantwoordelijk voor marketing, campagnes en mailinglijsten.',    'marketing@soulfm.nl',      'marketing_hoofd',     'marketing');

-- 3. Afdeling e-mailadressen tabel
CREATE TABLE IF NOT EXISTS department_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_slug VARCHAR(50) NOT NULL,
    label VARCHAR(100) NOT NULL,
    email_address VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO department_emails (department_slug, label, email_address, description) VALUES
('dj',            'Algemeen DJ',        'dj@soulfm.nl',              'Algemeen mailbox voor de DJ-afdeling'),
('dj',            'Techniek',           'techniek@soulfm.nl',        'Technische vragen over stream en apparatuur'),
('administratie', 'Algemeen Admin',     'administratie@soulfm.nl',   'Algemene administratievragen'),
('administratie', 'Planning',           'planning@soulfm.nl',        'Vragen over roosters en planning'),
('evenementen',   'Algemeen Events',    'evenementen@soulfm.nl',     'Evenementenaanvragen en informatie'),
('evenementen',   'Sponsoring',         'sponsoring@soulfm.nl',      'Sponsorverzoeken voor evenementen'),
('redactie',      'Redactie Algemeen',  'redactie@soulfm.nl',        'Persberichten en redactionele vragen'),
('content',       'Content Team',       'content@soulfm.nl',         'Content en social media samenwerking'),
('marketing',     'Marketing Algemeen', 'marketing@soulfm.nl',       'Marketing en advertentievragen'),
('marketing',     'Nieuwsbrief',        'nieuwsbrief@soulfm.nl',     'Nieuwsbrief abonnementen en campagnes'),
('marketing',     'Partnerships',       'partnerships@soulfm.nl',    'Partnership en samenwerkingsverzoeken');

-- 4. Sollicitaties tabel
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    department VARCHAR(50) NOT NULL,
    motivation TEXT NOT NULL,
    experience TEXT DEFAULT NULL,
    portfolio_url VARCHAR(255) DEFAULT NULL,
    availability VARCHAR(100) DEFAULT NULL,
    status ENUM('new','in_review','accepted','rejected') NOT NULL DEFAULT 'new',
    notes TEXT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Bedrijfsmail credentials tabel
CREATE TABLE IF NOT EXISTS user_mail_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    mail_address VARCHAR(150) NOT NULL,
    mail_password_enc TEXT NOT NULL,
    imap_server VARCHAR(100) DEFAULT 'mail.soulfm.nl',
    smtp_server VARCHAR(100) DEFAULT 'mail.soulfm.nl',
    imap_port SMALLINT DEFAULT 993,
    smtp_port SMALLINT DEFAULT 587,
    extra_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Afdelingsmail login-credentials
CREATE TABLE IF NOT EXISTS department_mail_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_slug VARCHAR(50) NOT NULL UNIQUE,
    mail_address VARCHAR(150) NOT NULL,
    mail_password_enc TEXT NOT NULL,
    imap_server VARCHAR(100) DEFAULT 'mail.soulfm.nl',
    smtp_server VARCHAR(100) DEFAULT 'mail.soulfm.nl',
    imap_port SMALLINT DEFAULT 993,
    smtp_port SMALLINT DEFAULT 587,
    extra_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_slug) REFERENCES departments(slug) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. DJ live-radio credentials per gebruiker
CREATE TABLE IF NOT EXISTS dj_live_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    stream_type VARCHAR(50) NOT NULL DEFAULT 'Icecast',
    host VARCHAR(150) NOT NULL,
    mount_point VARCHAR(100) DEFAULT '',
    username VARCHAR(100) NOT NULL,
    password_enc TEXT NOT NULL,
    port SMALLINT NOT NULL DEFAULT 8000,
    extra_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Klaar! Alle nieuwe tabellen en rollen zijn toegevoegd.
