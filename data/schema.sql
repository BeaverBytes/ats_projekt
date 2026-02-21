PRAGMA foreign_keys = ON;

-- 1) USERS
CREATE TABLE users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL CHECK(role IN ('recruiter','admin')),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2) JOBS
CREATE TABLE jobs (
  job_id INTEGER PRIMARY KEY AUTOINCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(150),
  is_active Integer NOT NULL DEFAULT 1 CHECK(is_active IN(0,1)),
  created_by_user_id INTEGER NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(created_by_user_id) REFERENCES users(user_id) ON DELETE RESTRICT
);

-- 3) APPLICATIONS
CREATE TABLE applications (
  application_id INTEGER PRIMARY KEY AUTOINCREMENT,
  job_id INTEGER NOT NULL,
  applicant_first_name VARCHAR(100) NOT NULL,
  applicant_last_name VARCHAR(100) NOT NULL,
  applicant_email VARCHAR(255) NOT NULL,
  applicant_phone VARCHAR(30),
  motivation TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'submitted'
    CHECK(status IN ('submitted','in_review','interview','offer','rejected')),
  consent INTEGER NOT NULL CHECK(consent IN (0,1)),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(job_id) REFERENCES jobs(job_id) ON DELETE CASCADE
);

-- 4) DOCUMENTS
CREATE TABLE documents (
  document_id INTEGER PRIMARY KEY AUTOINCREMENT,
  application_id INTEGER NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  size_bytes INTEGER NOT NULL CHECK(size_bytes >= 0),
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(application_id) REFERENCES applications(application_id) ON DELETE CASCADE
);

-- 5) NOTES
CREATE TABLE notes (
  note_id INTEGER PRIMARY KEY AUTOINCREMENT,
  application_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE RESTRICT
);

-- Indizes
CREATE INDEX idx_applications_job_id ON applications(job_id);
CREATE INDEX idx_applications_last_name ON applications(applicant_last_name);
CREATE INDEX idx_documents_application_id ON documents(application_id);
CREATE INDEX idx_jobs_active_created_at ON jobs(is_active, created_at);
CREATE INDEX idx_jobs_created_by_user_id ON jobs(created_by_user_id);
CREATE INDEX idx_notes_application_id ON notes(application_id);
CREATE INDEX idx_notes_user_id ON notes(user_id);

