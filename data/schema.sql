PRAGMA foreign_keys = ON;

-- 1) USERS
CREATE TABLE IF NOT EXISTS users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL CHECK(role IN ('recruiter','admin')),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2) JOBS
CREATE TABLE IF NOT EXISTS jobs (
  job_id INTEGER PRIMARY KEY AUTOINCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(150),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3) APPLICATIONS
CREATE TABLE IF NOT EXISTS applications (
  application_id INTEGER PRIMARY KEY AUTOINCREMENT,
  job_id INTEGER NOT NULL,
  applicant_name VARCHAR(150) NOT NULL,
  applicant_email VARCHAR(255) NOT NULL,
  motivation TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'EINGEGANGEN'
    CHECK(status IN ('EINGEGANGEN','IN_PRUEFUNG','INTERVIEW','ANGEBOT','ABGELEHNT')),
  consent INTEGER NOT NULL CHECK(consent IN (0,1)),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(job_id) REFERENCES jobs(job_id) ON DELETE CASCADE
);

-- 4) DOCUMENTS
CREATE TABLE IF NOT EXISTS documents (
  document_id INTEGER PRIMARY KEY AUTOINCREMENT,
  application_id INTEGER NOT NULL UNIQUE,
  file_path VARCHAR(500) NOT NULL,
  original_filename VARCHAR(255),
  mime_type VARCHAR(100),
  file_size INTEGER,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(application_id) REFERENCES applications(application_id) ON DELETE CASCADE
);

-- 5) NOTES
CREATE TABLE IF NOT EXISTS notes (
  note_id INTEGER PRIMARY KEY AUTOINCREMENT,
  application_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Indizes
CREATE INDEX IF NOT EXISTS idx_applications_job_id ON applications(job_id);
CREATE INDEX IF NOT EXISTS idx_notes_application_id ON notes(application_id);
