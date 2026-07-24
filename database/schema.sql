-- SoulFM Radio Website Database Schema
-- Run this file to set up the database

CREATE DATABASE IF NOT EXISTS soulmc_soulfm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE soulmc_soulfm;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM(
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
    ) NOT NULL DEFAULT 'listener',
    avatar VARCHAR(255) DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- Schedule table
CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    program_name VARCHAR(100) NOT NULL,
    dj_name VARCHAR(100) NOT NULL,
    dj_bio TEXT DEFAULT NULL,
    genre VARCHAR(50) DEFAULT 'Soul',
    cover_image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Song requests table
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    song_title VARCHAR(200) NOT NULL,
    artist_name VARCHAR(200) NOT NULL,
    requester_name VARCHAR(100) NOT NULL,
    message TEXT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    status ENUM('pending','played','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    played_at DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- News/blog table
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    author_id INT NOT NULL,
    published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'SoulFM'),
('tagline', 'Your Soul Music Station'),
('stream_url', 'https://stream.soulfm.nl/live'),
('primary_color', '#00b4d8'),
('logo_text', 'SoulFM'),
('facebook_url', 'https://facebook.com/soulfm'),
('twitter_url', 'https://twitter.com/soulfm'),
('instagram_url', 'https://instagram.com/soulfm'),
('contact_email', 'info@soulfm.nl'),
('contact_phone', '+31 20 123 4567'),
('contact_address', 'Radioplein 1, 1234 AB Amsterdam'),
('meta_description', 'SoulFM - Your 24/7 soul music radio station. Listen live, request songs, and stay updated with the latest music news.'),
('about_text', 'SoulFM is jouw 24/7 soul music radiozender. We spelen de beste soul, R&B, jazz en blues muziek voor jou.');

-- Insert default users (password for all: admin123)
-- IMPORTANT: Change these passwords after first login!
-- Hash generated with: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@soulfm.nl', '$2y$10$DummyHashRunSetupPhpFirst000000000000000000000000000X2', 'admin'),
('dj_marcus', 'marcus@soulfm.nl', '$2y$10$DummyHashRunSetupPhpFirst000000000000000000000000000X2', 'dj'),
('dj_sarah', 'sarah@soulfm.nl', '$2y$10$DummyHashRunSetupPhpFirst000000000000000000000000000X2', 'dj'),
('moderator1', 'mod@soulfm.nl', '$2y$10$DummyHashRunSetupPhpFirst000000000000000000000000000X2', 'moderator');
-- Run setup.php to replace these with real bcrypt hashes!

-- Schedule - Monday
INSERT INTO schedule (day_of_week, start_time, end_time, program_name, dj_name, dj_bio, genre) VALUES
('monday', '06:00:00', '09:00:00', 'Morning Soul', 'DJ Marcus', 'Marcus draait al 10 jaar de beste soul hits om jouw ochtend perfect te starten.', 'Soul'),
('monday', '09:00:00', '12:00:00', 'Soul Classics', 'DJ Sarah', 'Sarah neemt je mee door de tijdloze klassiekers van soul en R&B.', 'Classic Soul'),
('monday', '12:00:00', '15:00:00', 'Midday Groove', 'DJ Marcus', 'Perfecte middagsoul om door de werkdag te komen.', 'Soul/R&B'),
('monday', '15:00:00', '18:00:00', 'Afternoon Vibes', 'DJ Sarah', 'Smooth soul voor in de middag.', 'Smooth Soul'),
('monday', '18:00:00', '22:00:00', 'Evening Soul', 'DJ Marcus', 'De beste soul hits voor je avond.', 'Soul'),
('monday', '22:00:00', '06:00:00', 'Night Grooves', 'Autopilot', 'Soul muziek de hele nacht door.', 'Soul Mix');

-- Schedule - Tuesday
INSERT INTO schedule (day_of_week, start_time, end_time, program_name, dj_name, dj_bio, genre) VALUES
('tuesday', '06:00:00', '09:00:00', 'Morning Soul', 'DJ Sarah', 'Start jouw dinsdag met de beste soul.', 'Soul'),
('tuesday', '09:00:00', '12:00:00', 'R&B Mornings', 'DJ Marcus', 'De lekkerste R&B hits van de ochtend.', 'R&B'),
('tuesday', '12:00:00', '15:00:00', 'Soul Kitchen', 'DJ Sarah', 'Soul muziek die smaakt naar meer.', 'Soul'),
('tuesday', '15:00:00', '18:00:00', 'Afternoon Mix', 'DJ Marcus', 'Een mix van soul en R&B.', 'Soul/R&B'),
('tuesday', '18:00:00', '22:00:00', 'Evening Grooves', 'DJ Sarah', 'Groovy soul voor je avond.', 'Soul'),
('tuesday', '22:00:00', '06:00:00', 'Night Grooves', 'Autopilot', 'Soul muziek de hele nacht door.', 'Soul Mix');

-- Schedule - Wednesday
INSERT INTO schedule (day_of_week, start_time, end_time, program_name, dj_name, dj_bio, genre) VALUES
('wednesday', '06:00:00', '09:00:00', 'Soul Sunrise', 'DJ Marcus', 'De zon komt op met de beste soul.', 'Soul'),
('wednesday', '09:00:00', '12:00:00', 'Midweek Magic', 'DJ Sarah', 'Hump day met de beste muziek.', 'Soul/Jazz'),
('wednesday', '12:00:00', '15:00:00', 'Jazz & Soul', 'DJ Marcus', 'Jazz en soul in perfecte harmonie.', 'Jazz/Soul'),
('wednesday', '15:00:00', '18:00:00', 'Blues Hour', 'DJ Sarah', 'Duik in de blues met Sarah.', 'Blues'),
('wednesday', '18:00:00', '22:00:00', 'Soul Power', 'DJ Marcus', 'Energieke soul voor je woensdagavond.', 'Soul'),
('wednesday', '22:00:00', '06:00:00', 'Night Grooves', 'Autopilot', 'Soul muziek de hele nacht door.', 'Soul Mix');

-- Schedule - Thursday
INSERT INTO schedule (day_of_week, start_time, end_time, program_name, dj_name, dj_bio, genre) VALUES
('thursday', '06:00:00', '09:00:00', 'Morning Soul', 'DJ Sarah', 'Bijna weekend! Start met soul.', 'Soul'),
('thursday', '09:00:00', '12:00:00', 'Soul Classics', 'DJ Marcus', 'Tijdloze klassiekers voor de donderdag.', 'Classic Soul'),
('thursday', '12:00:00', '15:00:00', 'Midday R&B', 'DJ Sarah', 'R&B voor het midden van de dag.', 'R&B'),
('thursday', '15:00:00', '18:00:00', 'New Soul', 'DJ Marcus', 'De nieuwste soul releases.', 'Neo Soul'),
('thursday', '18:00:00', '22:00:00', 'Thursday Night Soul', 'DJ Sarah', 'Donderdagavond soul sessie.', 'Soul'),
('thursday', '22:00:00', '06:00:00', 'Night Grooves', 'Autopilot', 'Soul muziek de hele nacht door.', 'Soul Mix');

-- Schedule - Friday
INSERT INTO schedule (day_of_week, start_time, end_time, program_name, dj_name, dj_bio, genre) VALUES
('friday', '06:00:00', '09:00:00', 'Friday Feeling', 'DJ Marcus', 'Het weekend begint! Voel de soul.', 'Soul'),
('friday', '09:00:00', '12:00:00', 'TGIF Soul', 'DJ Sarah', 'Dankbaar voor de vrijdag met soul.', 'Soul/R&B'),
('friday', '12:00:00', '15:00:00', 'Weekend Countdown', 'DJ Marcus', 'Aftellen naar het weekend met toppers.', 'Soul'),
('friday', '15:00:00', '19:00:00', 'Friday Drive', 'DJ Sarah', 'De ideale rijmuziek naar huis.', 'Soul/R&B'),
('friday', '19:00:00', '23:00:00', 'Friday Night Fever', 'DJ Marcus', 'Vrijdagavond feest met de beste soul!', 'Soul/Funk'),
('friday', '23:00:00', '06:00:00', 'Late Night Soul', 'Autopilot', 'Nachtelijke soul vibes.', 'Soul Mix');

-- Schedule - Saturday
INSERT INTO schedule (day_of_week, start_time, end_time, program_name, dj_name, dj_bio, genre) VALUES
('saturday', '08:00:00', '11:00:00', 'Weekend Wake Up', 'DJ Sarah', 'Rustig opstaan met zachte soul.', 'Smooth Soul'),
('saturday', '11:00:00', '14:00:00', 'Saturday Soul', 'DJ Marcus', 'Zaterdag soul voor een geweldige dag.', 'Soul'),
('saturday', '14:00:00', '17:00:00', 'Afternoon Party', 'DJ Sarah', 'Feestelijk zaterdag middag.', 'Soul/Funk'),
('saturday', '17:00:00', '20:00:00', 'Soul Spectacular', 'DJ Marcus', 'Spectaculaire soul show.', 'Soul'),
('saturday', '20:00:00', '00:00:00', 'Saturday Night Soul', 'DJ Sarah', 'De beste zaterdag avond soul party.', 'Soul/R&B'),
('saturday', '00:00:00', '08:00:00', 'Night Grooves', 'Autopilot', 'Soul muziek de hele nacht door.', 'Soul Mix');

-- Schedule - Sunday
INSERT INTO schedule (day_of_week, start_time, end_time, program_name, dj_name, dj_bio, genre) VALUES
('sunday', '08:00:00', '11:00:00', 'Sunday Morning', 'DJ Marcus', 'Relaxte zondagochtend soul.', 'Gospel/Soul'),
('sunday', '11:00:00', '14:00:00', 'Gospel Hour', 'DJ Sarah', 'Inspirerende gospel en soul muziek.', 'Gospel'),
('sunday', '14:00:00', '17:00:00', 'Sunday Soul', 'DJ Marcus', 'Klassieke soul voor de zondagmiddag.', 'Classic Soul'),
('sunday', '17:00:00', '20:00:00', 'Mellow Sunday', 'DJ Sarah', 'Mellow vibes voor het einde van het weekend.', 'Smooth Soul'),
('sunday', '20:00:00', '23:00:00', 'Sunday Best', 'DJ Marcus', 'De beste nummers van de week.', 'Soul/R&B'),
('sunday', '23:00:00', '08:00:00', 'Night Grooves', 'Autopilot', 'Soul muziek de hele nacht door.', 'Soul Mix');

-- Sample news articles
INSERT INTO news (title, slug, content, excerpt, author_id, published, created_at) VALUES
('SoulFM lanceert vernieuwd platform!', 'soulfm-lanceert-vernieuwd-platform',
'<p>We zijn trots om het vernieuwde SoulFM platform te lanceren! Met een fris nieuw design en verbeterde functies is het nu nog makkelijker om te luisteren naar de beste soul muziek.</p><p>Het nieuwe platform biedt een ingebouwde audiospeler, een eenvoudig verzoekjes-formulier en een uitgebreid programmaschema.</p>',
'We zijn trots om het vernieuwde SoulFM platform te lanceren!', 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),

('Nieuw: Soul Classics elke zondag!', 'nieuw-soul-classics-elke-zondag',
'<p>Vanaf volgende week zijn er elke zondag Soul Classics op SoulFM! DJ Sarah neemt je mee door de tijdloze klassiekers van soul en R&B.</p><p>Verwacht tracks van artiesten als Aretha Franklin, Marvin Gaye en Otis Redding.</p>',
'Vanaf volgende week zijn er elke zondag Soul Classics op SoulFM!', 2, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),

('Verstuur je verzoekjes via de website!', 'verstuur-verzoekjes-via-website',
'<p>Goed nieuws voor alle SoulFM luisteraars! Je kunt nu eenvoudig je favoriete nummers aanvragen via ons nieuwe verzoekjesformulier op de website.</p>',
'Je kunt nu eenvoudig je favoriete nummers aanvragen via onze website!', 1, 1, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Sample requests
INSERT INTO requests (song_title, artist_name, requester_name, message, ip_address, status) VALUES
('I Will Always Love You', 'Whitney Houston', 'Jan de Vries', 'Voor mijn lieve vrouw op haar verjaardag!', '127.0.0.1', 'played'),
('A Change Is Gonna Come', 'Sam Cooke', 'Maria Janssen', 'Dit nummer raakt me altijd diep', '127.0.0.2', 'pending'),
('Respect', 'Aretha Franklin', 'Peter Bakker', 'Groetjes aan alle luisteraars!', '127.0.0.3', 'pending'),
('What''s Going On', 'Marvin Gaye', 'Lisa Smit', 'Tijdloos nummer!', '127.0.0.4', 'pending'),
('Stand By Me', 'Ben E. King', 'Tom Visser', 'Voor mijn beste vriend Mark', '127.0.0.5', 'played');

-- =====================================================
-- Afdelingen
-- =====================================================
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

INSERT INTO departments (slug, name, description, email, head_role, member_role) VALUES
('dj',            'DJ',            'Verantwoordelijk voor de uitzendingen en streamgegevens.',        'dj@soulfm.nl',             'dj_hoofd',             'dj'),
('administratie', 'Administratie', 'Beheert de planning en roosters van de zender.',                  'administratie@soulfm.nl',  'administratie_hoofd',  'administratie'),
('evenementen',   'Evenementen',   'Organiseert evenementen en publiceert hierover nieuws.',           'evenementen@soulfm.nl',    'evenementen_hoofd',    'evenementen'),
('redactie',      'Redactie',      'Schrijft en beheert nieuwsberichten en artikelen.',                'redactie@soulfm.nl',       'redactie_hoofd',       'redactie'),
('content',       'Content',       'Produceert content voor social media en de website.',              'content@soulfm.nl',        'content_hoofd',        'content'),
('marketing',     'Marketing',     'Verantwoordelijk voor marketing, campagnes en mailinglijsten.',    'marketing@soulfm.nl',      'marketing_hoofd',      'marketing');

-- =====================================================
-- Afdeling e-mailadressen
-- =====================================================
CREATE TABLE IF NOT EXISTS department_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_slug VARCHAR(50) NOT NULL,
    label VARCHAR(100) NOT NULL,
    email_address VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO department_emails (department_slug, label, email_address, description) VALUES
('dj',            'Algemeen DJ',         'dj@soulfm.nl',               'Algemeen mailbox voor de DJ-afdeling'),
('dj',            'Techniek',            'techniek@soulfm.nl',         'Technische vragen over stream en apparatuur'),
('administratie', 'Algemeen Admin',      'administratie@soulfm.nl',    'Algemene administratievragen'),
('administratie', 'Planning',            'planning@soulfm.nl',         'Vragen over roosters en planning'),
('evenementen',   'Algemeen Events',     'evenementen@soulfm.nl',      'Evenementenaanvragen en informatie'),
('evenementen',   'Sponsoring',          'sponsoring@soulfm.nl',       'Sponsorverzoeken voor evenementen'),
('redactie',      'Redactie Algemeen',   'redactie@soulfm.nl',         'Persberichten en redactionele vragen'),
('content',       'Content Team',        'content@soulfm.nl',          'Content en social media samenwerking'),
('marketing',     'Marketing Algemeen',  'marketing@soulfm.nl',        'Marketing en advertentievragen'),
('marketing',     'Nieuwsbrief',         'nieuwsbrief@soulfm.nl',      'Nieuwsbrief abonnementen en campagnes'),
('marketing',     'Partnerships',        'partnerships@soulfm.nl',     'Partnership en samenwerkingsverzoeken');

-- =====================================================
-- Sollicitaties
-- =====================================================
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

-- =====================================================
-- Bedrijfsmail credentials per gebruiker
-- =====================================================
-- Wachtwoorden worden AES-256 versleuteld opgeslagen.
-- De encryptiesleutel staat in config.php (MAIL_CRYPT_KEY).
CREATE TABLE IF NOT EXISTS user_mail_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    mail_address VARCHAR(150) NOT NULL COMMENT 'bijv. marcus@soulfm.nl',
    mail_password_enc TEXT NOT NULL COMMENT 'AES versleuteld wachtwoord',
    imap_server VARCHAR(100) DEFAULT 'mail.soulfm.nl',
    smtp_server VARCHAR(100) DEFAULT 'mail.soulfm.nl',
    imap_port SMALLINT DEFAULT 993,
    smtp_port SMALLINT DEFAULT 587,
    extra_notes TEXT DEFAULT NULL COMMENT 'Extra instructies voor de medewerker',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Afdelingsmail login-credentials
-- =====================================================
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

-- =====================================================
-- DJ live-radio credentials per gebruiker
-- =====================================================
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

-- =====================================================
-- Teamleden voor publieke teampagina
-- =====================================================
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    role_title VARCHAR(120) NOT NULL,
    bio TEXT DEFAULT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO team_members (name, role_title, bio, photo_url, display_order, is_active) VALUES
('DJ Marcus', 'DJ', 'Marcus draait al jaren de beste soul en R&B tijdens de ochtend- en avondshows.', NULL, 10, 1),
('DJ Sarah', 'DJ', 'Sarah brengt classic soul en smooth vibes met een energieke presentatie.', NULL, 20, 1),
('Redactie Team', 'Redactie', 'Ons redactieteam zorgt dagelijks voor nieuws, updates en achtergrondverhalen.', NULL, 30, 1);

-- =====================================================
-- Voorbeeld sollicitaties
-- =====================================================
INSERT INTO applications (first_name, last_name, email, phone, city, department, motivation, experience, availability, status, ip_address, created_at) VALUES
('Kevin', 'de Boer', 'kevin@example.com', '06-12345678', 'Amsterdam', 'dj',
 'Ik ben al jaren gepassioneerd bezig met soul en R&B muziek en draai al 5 jaar in clubs. SoulFM is de zender waar ik altijd naar luister.',
 '5 jaar ervaring als club-DJ, eigen radioshow op lokale zender.', 'Weekenden en avonden', 'new', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Lisa', 'van den Berg', 'lisa@example.com', '06-87654321', 'Rotterdam', 'redactie',
 'Als schrijver met een passie voor muziek zou ik graag bijdragen aan de nieuwssectie van SoulFM.',
 'Bachelor Journalistiek, 3 jaar freelance muziekjournalist.', 'Fulltime', 'in_review', '127.0.0.2', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Thomas', 'Smits', 'thomas@example.com', NULL, 'Utrecht', 'marketing',
 'Met mijn achtergrond in digitale marketing wil ik SoulFM helpen groeien op social media.',
 'HBO Marketing, 2 jaar bij een marketingbureau.', 'Parttime, 20 uur per week', 'new', '127.0.0.3', DATE_SUB(NOW(), INTERVAL 5 DAY));
